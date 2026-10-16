<?php

use App\Models\User;
use App\Models\UserPasskey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Testing\DatabaseTransactions;

class PasskeyApiTest extends TestCase
{
    use DatabaseTransactions;

    private const BASE = '/api/v3/auth';

    private function fakeCredential(): array
    {
        return [
            'id' => 'AAAA',
            'rawId' => 'AAAA',
            'type' => 'public-key',
            'response' => ['clientDataJSON' => 'AAAA', 'authenticatorData' => 'AAAA', 'signature' => 'AAAA'],
        ];
    }

    private function makePasskey(User $user, string $credentialId): UserPasskey
    {
        return UserPasskey::create([
            'user_id' => $user->id,
            'credential_id' => $credentialId,
            'public_key' => 'pem',
            'sign_count' => 0,
            'label' => 'Laptop',
        ]);
    }

    public function testProtectedEndpointsRequireAuth()
    {
        $this->post(self::BASE . '/passkey/register-options', []);
        $this->assertEquals(401, $this->response->status());
        $this->get(self::BASE . '/passkeys');
        $this->assertEquals(401, $this->response->status());
        $this->delete(self::BASE . '/passkeys/1');
        $this->assertEquals(401, $this->response->status());
    }

    public function testRegisterOptionsReturnsCreationOptions()
    {
        $user = User::factory()->create();
        $this->makePasskey($user, 'existing-cred');

        $this->actingAs($user)->post(self::BASE . '/passkey/register-options', []);

        $this->assertEquals(200, $this->response->status());
        $data = json_decode($this->response->getContent(), true)['data'];
        $this->assertEquals(env('WEBAUTHN_RP_ID'), $data['rp']['id']);
        $this->assertNotEmpty($data['challenge']);
        $this->assertEquals('existing-cred', $data['excludeCredentials'][0]['id']);
        $this->assertEquals('required', $data['authenticatorSelection']['residentKey']);
        $this->assertTrue($data['authenticatorSelection']['requireResidentKey']);
        $this->assertEquals('required', $data['authenticatorSelection']['userVerification']);
    }

    public function testRegisterVerifyWithoutChallengeFails()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(self::BASE . '/passkey/register-verify', ['credential' => $this->fakeCredential()]);
        $this->assertEquals(400, $this->response->status());
    }

    public function testRegisterVerifyValidatesBody()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(self::BASE . '/passkey/register-verify', []);
        $this->assertEquals(400, $this->response->status());
    }

    public function testLoginOptionsWithoutEmailHasEmptyAllowCredentials()
    {
        $this->post(self::BASE . '/passkey/login-options', []);

        $this->assertEquals(200, $this->response->status());
        $data = json_decode($this->response->getContent(), true)['data'];
        $this->assertNotEmpty($data['challenge_id']);
        $this->assertSame([], $data['options']['allowCredentials']);
        $this->assertNotNull(Cache::get('passkey:login:' . $data['challenge_id']));
    }

    public function testLoginOptionsIgnoresEmailAndNeverListsCredentials()
    {
        $user = User::factory()->create();
        $this->makePasskey($user, 'cred-1');

        $this->post(self::BASE . '/passkey/login-options', ['email' => $user->email]);

        $this->assertEquals(200, $this->response->status());
        $data = json_decode($this->response->getContent(), true)['data'];
        $this->assertSame([], $data['options']['allowCredentials']);
        $this->assertEquals('required', $data['options']['userVerification']);
    }

    public function testLoginVerifyWithUnknownChallengeFails()
    {
        $this->post(self::BASE . '/passkey/login-verify', ['challenge_id' => 'nope', 'credential' => $this->fakeCredential()]);
        $this->assertEquals(400, $this->response->status());
    }

    public function testLoginVerifyWithUnknownCredentialFails()
    {
        $this->post(self::BASE . '/passkey/login-options', []);
        $challengeId = json_decode($this->response->getContent(), true)['data']['challenge_id'];

        $this->post(self::BASE . '/passkey/login-verify', ['challenge_id' => $challengeId, 'credential' => $this->fakeCredential()]);

        $this->assertEquals(400, $this->response->status());
        // Challenge is single use.
        $this->assertNull(Cache::get('passkey:login:' . $challengeId));
    }

    public function testListAndDeleteOnlyAffectOwner()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $mine = $this->makePasskey($owner, 'mine');
        $theirs = $this->makePasskey($other, 'theirs');

        $this->actingAs($owner)->get(self::BASE . '/passkeys');
        $data = json_decode($this->response->getContent(), true)['data'];
        $this->assertCount(1, $data);
        $this->assertEquals($mine->id, $data[0]['id']);
        $this->assertArrayNotHasKey('public_key', $data[0]);

        $this->actingAs($owner)->delete(self::BASE . '/passkeys/' . $theirs->id);
        $this->assertEquals(404, $this->response->status());
        $this->assertNotNull(UserPasskey::find($theirs->id));

        $this->actingAs($owner)->delete(self::BASE . '/passkeys/' . $mine->id);
        $this->assertEquals(200, $this->response->status());
        $this->assertNull(UserPasskey::find($mine->id));
    }

    // ---- Real WebAuthn ceremonies: ES256 key, 'none' attestation, signed assertions ----

    private const UP = 0x01;
    private const UV = 0x04;
    private const AT = 0x40;

    private function b64u(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    private function cborHead(int $major, int $n): string
    {
        if ($n < 24) {
            return chr(($major << 5) | $n);
        }
        if ($n < 256) {
            return chr(($major << 5) | 24) . chr($n);
        }
        if ($n < 65536) {
            return chr(($major << 5) | 25) . pack('n', $n);
        }
        return chr(($major << 5) | 26) . pack('N', $n);
    }

    /** Tiny CBOR encoder: ints, text (string), bytes (['bin' => ..]), and maps (arrays). */
    private function cbor($v): string
    {
        if (is_int($v)) {
            return $v >= 0 ? $this->cborHead(0, $v) : $this->cborHead(1, -1 - $v);
        }
        if (is_string($v)) {
            return $this->cborHead(3, strlen($v)) . $v;
        }
        if (is_array($v) && array_keys($v) === ['bin']) {
            return $this->cborHead(2, strlen($v['bin'])) . $v['bin'];
        }
        $out = $this->cborHead(5, count($v));
        foreach ($v as $key => $val) {
            $out .= $this->cbor($key) . $this->cbor($val);
        }
        return $out;
    }

    private function newKey()
    {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $this->assertNotFalse($key, 'openssl could not generate an ES256 key');
        return $key;
    }

    private function rpIdHash(): string
    {
        return hash('sha256', env('WEBAUTHN_RP_ID'), true);
    }

    private function clientData(string $type, string $challenge, ?string $origin = null): string
    {
        return json_encode(['type' => $type, 'challenge' => $challenge, 'origin' => $origin ?? env('WEBAUTHN_ORIGIN')]);
    }

    private function verifiedUser(int $status = 1): User
    {
        $user = User::factory()->create();
        $user->status = $status;
        $user->save();
        return $user;
    }

    /** Runs register-options + register-verify with a self-built 'none' attestation. */
    private function register(User $user, $key, string $credId, array $o = []): void
    {
        $this->actingAs($user)->post(self::BASE . '/passkey/register-options', []);
        $challenge = json_decode($this->response->getContent(), true)['data']['challenge'];

        $d = openssl_pkey_get_details($key)['ec'];
        $cose = [1 => 2, 3 => -7, -1 => 1, -2 => ['bin' => str_pad($d['x'], 32, "\0", STR_PAD_LEFT)], -3 => ['bin' => str_pad($d['y'], 32, "\0", STR_PAD_LEFT)]];
        $authData = $this->rpIdHash()
            . chr($o['flags'] ?? (self::UP | self::UV | self::AT))
            . pack('N', $o['counter'] ?? 0)
            . str_repeat("\0", 16)
            . pack('n', strlen($credId)) . $credId
            . $this->cbor($cose);
        $attestation = $this->cbor(['fmt' => 'none', 'attStmt' => [], 'authData' => ['bin' => $authData]]);

        $body = ['credential' => [
            'id' => $this->b64u($credId),
            'rawId' => $this->b64u($credId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => $this->b64u($this->clientData('webauthn.create', $challenge, $o['origin'] ?? null)),
                'attestationObject' => $this->b64u($attestation),
            ],
        ]];
        if (array_key_exists('label', $o)) {
            $body['label'] = $o['label'];
        }
        if (array_key_exists('transports', $o)) {
            $body['credential']['response']['transports'] = $o['transports'];
        }
        $this->actingAs($user)->post(self::BASE . '/passkey/register-verify', $body);
    }

    /** Fetches fresh login options and returns [challenge_id, challenge]. */
    private function loginChallenge(): array
    {
        $this->post(self::BASE . '/passkey/login-options', []);
        $data = json_decode($this->response->getContent(), true)['data'];
        return [$data['challenge_id'], $data['options']['challenge']];
    }

    /** Builds a signed assertion for login-verify; $o overrides flags/counter/origin/handle/key. */
    private function loginBody(string $challengeId, string $challenge, string $credId, $key, User $user, array $o = []): array
    {
        $clientData = $this->clientData('webauthn.get', $challenge, $o['origin'] ?? null);
        $authData = $this->rpIdHash() . chr($o['flags'] ?? (self::UP | self::UV)) . pack('N', $o['counter'] ?? 1);
        openssl_sign($authData . hash('sha256', $clientData, true), $sig, $o['signWith'] ?? $key, OPENSSL_ALGO_SHA256);

        return [
            'challenge_id' => $challengeId,
            'credential' => [
                'id' => $this->b64u($credId),
                'rawId' => $this->b64u($credId),
                'type' => 'public-key',
                'response' => [
                    'clientDataJSON' => $this->b64u($clientData),
                    'authenticatorData' => $this->b64u($authData),
                    'signature' => $this->b64u($sig),
                    'userHandle' => $this->b64u((string)$user->id),
                ],
            ],
        ];
    }

    /** Registers a passkey for a fresh verified user and returns [user, key, credId, passkey]. */
    private function registeredUser(int $status = 1): array
    {
        $user = $this->verifiedUser($status);
        $key = $this->newKey();
        $credId = random_bytes(32);
        $this->register($user, $key, $credId);
        $this->assertEquals(200, $this->response->status(), $this->response->getContent());
        return [$user, $key, $credId, UserPasskey::findByCredentialId($this->b64u($credId))];
    }

    private function fakePassport(): void
    {
        Http::fake(['*/oauth/token' => Http::response([
            'token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'access', 'refresh_token' => 'refresh',
        ], 200)]);
    }

    private function loginVerify(string $credId, $key, User $user, array $o = []): void
    {
        [$cid, $challenge] = $this->loginChallenge();
        $this->post(self::BASE . '/passkey/login-verify', $this->loginBody($cid, $challenge, $credId, $key, $user, $o));
    }

    public function testRegistrationCeremonySucceedsAndStoresNullLabel()
    {
        [$user, , $credId, $passkey] = $this->registeredUser();

        $this->assertNotNull($passkey);
        $this->assertEquals($user->id, $passkey->user_id);
        $this->assertNull($passkey->label);
        $this->assertEquals(0, $passkey->sign_count);
        $data = json_decode($this->response->getContent(), true)['data'];
        $this->assertArrayHasKey('id', $data);
        $this->assertNull($data['label']);
    }

    public function testRegistrationKeepsAGivenLabel()
    {
        $user = $this->verifiedUser();
        $this->register($user, $this->newKey(), random_bytes(32), ['label' => 'Laptop']);
        $this->assertEquals(200, $this->response->status());
        $this->assertEquals('Laptop', json_decode($this->response->getContent(), true)['data']['label']);
    }

    public function testRegistrationRejectsMissingUserVerificationFlag()
    {
        $user = $this->verifiedUser();
        $this->register($user, $this->newKey(), random_bytes(32), ['flags' => self::UP | self::AT]);
        $this->assertEquals(400, $this->response->status());
        $this->assertEquals(0, UserPasskey::where('user_id', $user->id)->count());
    }

    public function testRegistrationRejectsWrongOrigin()
    {
        $user = $this->verifiedUser();
        $this->register($user, $this->newKey(), random_bytes(32), ['origin' => 'https://evil.example']);
        $this->assertEquals(400, $this->response->status());
    }

    public function testRegisteringTheSameCredentialTwiceReturns409()
    {
        [$user, $key, $credId] = $this->registeredUser();
        $this->register($user, $key, $credId);
        $this->assertEquals(409, $this->response->status());
        $this->assertEquals(1, UserPasskey::where('user_id', $user->id)->count());
    }

    public function testRegistrationValidatesRequiredResponseFields()
    {
        $user = $this->verifiedUser();
        $this->actingAs($user)->post(self::BASE . '/passkey/register-verify', ['credential' => [
            'id' => 'AAAA', 'response' => ['clientDataJSON' => 'AAAA'],
        ]]);
        $this->assertEquals(400, $this->response->status());
    }

    public function testLoginVerifyValidatesRequiredResponseFields()
    {
        $this->post(self::BASE . '/passkey/login-verify', ['challenge_id' => 'x', 'credential' => [
            'id' => 'AAAA', 'response' => ['clientDataJSON' => 'AAAA', 'authenticatorData' => 'AAAA'],
        ]]);
        $this->assertEquals(400, $this->response->status());
    }

    public function testLoginCeremonySucceedsAndMatchesLoginUserPayloadShape()
    {
        [$user, $key, $credId, $passkey] = $this->registeredUser();
        $this->fakePassport();

        $this->loginVerify($credId, $key, $user, ['counter' => 7]);

        $this->assertEquals(200, $this->response->status(), $this->response->getContent());
        $login = json_decode($this->response->getContent(), true)['data'];

        // The reference: the password login for the same account.
        $this->post('/api/v3/user/login', ['username' => $user->email, 'password' => 'password', 'client_id' => '2', 'client_secret' => 'x']);
        $this->assertEquals(200, $this->response->status(), $this->response->getContent());
        $reference = json_decode($this->response->getContent(), true)['data'];

        $this->assertEquals(array_keys($reference), array_keys($login));
        $this->assertEquals(array_keys($reference['auth']), array_keys($login['auth']));
        $this->assertEquals(array_keys($reference['user']), array_keys($login['user']));
        $this->assertEquals($user->email, $login['user']['email']);
        $this->assertEquals($user->id, $login['user']['id']);

        $passkey->refresh();
        $this->assertEquals(7, $passkey->sign_count);
        $this->assertNotNull($passkey->last_used_at);
    }

    public function testLoginPayloadShapeMatchesLoginUserInLocalEnv()
    {
        [$user, $key, $credId] = $this->registeredUser();
        $this->fakePassport();
        putenv('APP_ENV=local');
        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'local';
        try {
            $this->loginVerify($credId, $key, $user, ['counter' => 3]);
            $this->assertEquals(200, $this->response->status(), $this->response->getContent());
            $login = json_decode($this->response->getContent(), true)['data'];

            $this->post('/api/v3/user/login', ['username' => $user->email, 'password' => 'password', 'client_id' => '2', 'client_secret' => 'x']);
            $reference = json_decode($this->response->getContent(), true)['data'];
        } finally {
            putenv('APP_ENV=testing');
            $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
        }

        $this->assertEquals(array_keys($reference), array_keys($login));
        $this->assertEquals(array_keys($reference['auth']), array_keys($login['auth']));
        $this->assertEquals(array_keys($reference['user']), array_keys($login['user']));
        $this->assertEquals($user->email, $login['user']['email']);
    }

    public function testCounterRegressionIsRejectedAndLogged()
    {
        [$user, $key, $credId, $passkey] = $this->registeredUser();
        $this->fakePassport();
        $this->loginVerify($credId, $key, $user, ['counter' => 5]);
        $this->assertEquals(200, $this->response->status());

        Log::spy();
        $this->loginVerify($credId, $key, $user, ['counter' => 5]);
        $this->assertEquals(400, $this->response->status());
        $this->loginVerify($credId, $key, $user, ['counter' => 2]);
        $this->assertEquals(400, $this->response->status());
        Log::shouldHaveReceived('warning')->twice();
        $this->assertEquals(5, $passkey->fresh()->sign_count);
    }

    public function testLoginRejectsWrongOrigin()
    {
        [$user, $key, $credId, $passkey] = $this->registeredUser();
        $this->loginVerify($credId, $key, $user, ['origin' => 'https://evil.example']);
        $this->assertEquals(400, $this->response->status());
        $this->assertNull($passkey->fresh()->last_used_at);
    }

    public function testLoginRejectsMissingUserVerificationFlag()
    {
        [$user, $key, $credId, $passkey] = $this->registeredUser();
        $this->loginVerify($credId, $key, $user, ['flags' => self::UP]);
        $this->assertEquals(400, $this->response->status());
        $this->assertNull($passkey->fresh()->last_used_at);
    }

    public function testLoginRejectsSignatureFromAnotherKey()
    {
        [$user, $key, $credId] = $this->registeredUser();
        $this->loginVerify($credId, $key, $user, ['signWith' => $this->newKey()]);
        $this->assertEquals(400, $this->response->status());
    }

    public function testLoginRejectsReusedChallenge()
    {
        [$user, $key, $credId] = $this->registeredUser();
        $this->fakePassport();
        [$cid, $challenge] = $this->loginChallenge();
        $body = $this->loginBody($cid, $challenge, $credId, $key, $user, ['counter' => 4]);

        $this->post(self::BASE . '/passkey/login-verify', $body);
        $this->assertEquals(200, $this->response->status());

        $this->post(self::BASE . '/passkey/login-verify', $body);
        $this->assertEquals(400, $this->response->status());
    }

    public function testUnverifiedAccountGets402AndUsageIsNotRecorded()
    {
        [$user, $key, $credId, $passkey] = $this->registeredUser(0);
        $this->assertNull($passkey->last_used_at);

        $this->loginVerify($credId, $key, $user, ['counter' => 9]);

        $this->assertEquals(402, $this->response->status());
        $passkey->refresh();
        $this->assertNull($passkey->last_used_at);
        $this->assertEquals(0, $passkey->sign_count);
    }

    public function testUnknownCredentialGetsGeneric400()
    {
        [$user] = $this->registeredUser();
        $this->loginVerify(random_bytes(32), $this->newKey(), $user);
        $this->assertEquals(400, $this->response->status());
        $this->assertEquals(trans('message.passkey.verification_failed'), json_decode($this->response->getContent(), true)['message']);
    }

    public function testLoginOptionsWithEmailOfUserWithPasskeyStillHasEmptyAllowCredentials()
    {
        [$user] = $this->registeredUser();
        $this->post(self::BASE . '/passkey/login-options', ['email' => $user->email]);
        $this->assertSame([], json_decode($this->response->getContent(), true)['data']['options']['allowCredentials']);
    }

    public function testPublicPasskeyRoutesAreThrottled()
    {
        for ($i = 0; $i < 31; $i++) {
            $this->post(self::BASE . '/passkey/login-options', []);
        }
        $this->assertEquals(429, $this->response->status());
    }

    public function testDeletingAUserCascadesToTheirPasskeys()
    {
        [$user] = $this->registeredUser();
        DB::table('person')->where('id', $user->id)->delete();
        $this->assertEquals(0, UserPasskey::where('user_id', $user->id)->count());
    }

    public function testRegistrationStoresAllowedTransports()
    {
        $user = $this->verifiedUser();
        $this->register($user, $this->newKey(), random_bytes(32), ['transports' => ['internal', 'hybrid']]);
        $this->assertEquals(200, $this->response->status(), $this->response->getContent());
        $this->assertEquals('["internal","hybrid"]', UserPasskey::where('user_id', $user->id)->value('transports'));
    }

    public function testRegistrationRejectsUnknownTransport()
    {
        $user = $this->verifiedUser();
        $this->register($user, $this->newKey(), random_bytes(32), ['transports' => ['usb', 'carrier-pigeon']]);
        $this->assertEquals(400, $this->response->status());
        $this->assertEquals(0, UserPasskey::where('user_id', $user->id)->count());
    }

    public function testRegistrationRejectsTooManyTransports()
    {
        $user = $this->verifiedUser();
        $this->register($user, $this->newKey(), random_bytes(32), ['transports' => array_fill(0, 7, 'usb')]);
        $this->assertEquals(400, $this->response->status());
        $this->assertEquals(0, UserPasskey::where('user_id', $user->id)->count());
    }

    public function testUnexpectedErrorIsLoggedAndReturnedAsGeneric500()
    {
        [$user, $key, $credId] = $this->registeredUser();
        \App\Facades\Util::shouldReceive('httpPost')->andThrow(new \Exception('SQLSTATE[42S02]: internal detail'));
        Log::spy();

        $this->loginVerify($credId, $key, $user, ['counter' => 1]);

        $this->assertEquals(500, $this->response->status());
        $this->assertStringNotContainsString('SQLSTATE', $this->response->getContent());
        $this->assertEquals(trans('message.error.exception'), json_decode($this->response->getContent(), true)['message']);
        Log::shouldHaveReceived('error')->once();
    }

    public function testMissingWebAuthnConfigReturnsGeneric500()
    {
        $rpId = env('WEBAUTHN_RP_ID');
        putenv('WEBAUTHN_RP_ID');
        unset($_ENV['WEBAUTHN_RP_ID'], $_SERVER['WEBAUTHN_RP_ID']);
        try {
            $this->post(self::BASE . '/passkey/login-options', []);
            $this->assertEquals(500, $this->response->status(), $this->response->getContent());
            $this->assertEquals(trans('message.error.exception'), json_decode($this->response->getContent(), true)['message']);
        } finally {
            putenv("WEBAUTHN_RP_ID={$rpId}");
            $_ENV['WEBAUTHN_RP_ID'] = $_SERVER['WEBAUTHN_RP_ID'] = $rpId;
        }
    }

    public function testDeleteRouteOnlyMatchesNumericIds()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->delete(self::BASE . '/passkeys/abc');
        $this->assertEquals(404, $this->response->status());
    }
}

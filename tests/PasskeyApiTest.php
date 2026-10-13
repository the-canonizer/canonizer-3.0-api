<?php

use App\Models\User;
use App\Models\UserPasskey;
use Illuminate\Support\Facades\Cache;
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
        $this->assertEquals('preferred', $data['authenticatorSelection']['residentKey']);
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

    public function testLoginOptionsWithEmailListsUserCredentials()
    {
        $user = User::factory()->create();
        $this->makePasskey($user, 'cred-1');

        $this->post(self::BASE . '/passkey/login-options', ['email' => $user->email]);

        $data = json_decode($this->response->getContent(), true)['data'];
        $this->assertEquals('cred-1', $data['options']['allowCredentials'][0]['id']);
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

        $this->assertEquals(404, $this->response->status());
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
}

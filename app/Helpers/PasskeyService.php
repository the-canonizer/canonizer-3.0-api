<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\UserPasskey;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use lbuchs\WebAuthn\WebAuthn;

/**
 * WebAuthn ceremony logic for passkeys. Controllers stay thin; every
 * failure is raised as an Exception whose code is the HTTP status.
 */
class PasskeyService
{
    const CHALLENGE_TTL = 300; // seconds
    const TIMEOUT_MS = 60000;

    private function rpId(): string
    {
        return env('WEBAUTHN_RP_ID');
    }

    private function origin(): string
    {
        return rtrim(env('WEBAUTHN_ORIGIN'), '/');
    }

    private function webauthn(): WebAuthn
    {
        return new WebAuthn(env('WEBAUTHN_RP_NAME', 'Canonizer'), $this->rpId(), ['none', 'packed', 'fido-u2f', 'apple', 'android-key', 'android-safetynet', 'tpm'], true);
    }

    public static function b64uEncode(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    public static function b64uDecode($str): string
    {
        $bin = is_string($str) ? base64_decode(strtr($str, '-_', '+/'), true) : false;
        if ($bin === false) {
            throw new Exception(trans('message.passkey.verification_failed'), 400);
        }
        return $bin;
    }

    /** Stores a challenge and returns it as raw bytes' base64url form. */
    private function storeChallenge(string $key, array $payload): string
    {
        Cache::put($key, $payload, self::CHALLENGE_TTL);
        return $payload['challenge'];
    }

    /** Single use: the challenge is removed whether or not verification succeeds. */
    private function pullChallenge(string $key): array
    {
        $payload = Cache::pull($key);
        if (!is_array($payload) || empty($payload['challenge'])) {
            throw new Exception(trans('message.passkey.challenge_invalid'), 400);
        }
        return $payload;
    }

    private function credentialDescriptors($passkeys): array
    {
        return $passkeys->map(function ($p) {
            $d = ['type' => 'public-key', 'id' => $p->credential_id];
            $transports = json_decode($p->transports ?? '[]', true);
            if (!empty($transports)) {
                $d['transports'] = $transports;
            }
            return $d;
        })->values()->all();
    }

    public function registrationOptions(User $user): array
    {
        $challenge = self::b64uEncode(random_bytes(32));
        $this->storeChallenge('passkey:reg:' . $user->id, ['challenge' => $challenge]);

        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        return [
            'rp' => ['name' => env('WEBAUTHN_RP_NAME', 'Canonizer'), 'id' => $this->rpId()],
            'user' => [
                'id' => self::b64uEncode((string)$user->id),
                'name' => $user->email,
                'displayName' => $name !== '' ? $name : $user->email,
            ],
            'challenge' => $challenge,
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],
                ['type' => 'public-key', 'alg' => -257],
            ],
            'timeout' => self::TIMEOUT_MS,
            'attestation' => 'none',
            'excludeCredentials' => $this->credentialDescriptors(UserPasskey::where('user_id', $user->id)->get()),
            'authenticatorSelection' => ['residentKey' => 'preferred', 'userVerification' => 'preferred'],
        ];
    }

    public function register(User $user, array $credential, ?string $label): UserPasskey
    {
        $stored = $this->pullChallenge('passkey:reg:' . $user->id);
        $response = $credential['response'] ?? [];
        try {
            $clientDataJson = self::b64uDecode($response['clientDataJSON'] ?? null);
            $attestation = self::b64uDecode($response['attestationObject'] ?? null);
            $this->assertClientData($clientDataJson, 'webauthn.create', $stored['challenge']);
            $data = $this->webauthn()->processCreate($clientDataJson, $attestation, new ByteBuffer(self::b64uDecode($stored['challenge'])), false, true, false);
        } catch (Exception $e) {
            throw new Exception(trans('message.passkey.verification_failed'), 400);
        }

        $credentialId = self::b64uEncode($data->credentialId);
        if (UserPasskey::where('credential_id', $credentialId)->exists()) {
            throw new Exception(trans('message.passkey.already_registered'), 409);
        }

        return UserPasskey::create([
            'user_id' => $user->id,
            'credential_id' => $credentialId,
            'public_key' => $data->credentialPublicKey,
            'sign_count' => (int)$data->signatureCounter,
            'transports' => json_encode(array_values($response['transports'] ?? [])),
            'aaguid' => isset($data->AAGUID) ? bin2hex($data->AAGUID) : null,
            'label' => $label !== null && $label !== '' ? $label : 'Passkey',
        ]);
    }

    public function loginOptions(?string $email): array
    {
        $allow = [];
        if ($email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $allow = $this->credentialDescriptors(UserPasskey::where('user_id', $user->id)->get());
            }
            // Unknown email or no passkeys: same shape, so accounts cannot be enumerated.
        }
        $challengeId = (string)Str::uuid();
        $challenge = self::b64uEncode(random_bytes(32));
        $this->storeChallenge('passkey:login:' . $challengeId, ['challenge' => $challenge]);

        return [
            'challenge_id' => $challengeId,
            'options' => [
                'challenge' => $challenge,
                'timeout' => self::TIMEOUT_MS,
                'rpId' => $this->rpId(),
                'allowCredentials' => $allow,
                'userVerification' => 'preferred',
            ],
        ];
    }

    /** Verifies an assertion and returns the authenticated user. */
    public function login(string $challengeId, array $credential): User
    {
        $stored = $this->pullChallenge('passkey:login:' . $challengeId);
        $response = $credential['response'] ?? [];

        $passkey = UserPasskey::where('credential_id', $credential['id'] ?? $credential['rawId'] ?? '')->first();
        if (!$passkey) {
            throw new Exception(trans('message.passkey.credential_unknown'), 404);
        }

        try {
            $clientDataJson = self::b64uDecode($response['clientDataJSON'] ?? null);
            $authData = self::b64uDecode($response['authenticatorData'] ?? null);
            $signature = self::b64uDecode($response['signature'] ?? null);
            $this->assertClientData($clientDataJson, 'webauthn.get', $stored['challenge']);
            $wa = $this->webauthn();
            $wa->processGet($clientDataJson, $authData, $signature, $passkey->public_key, new ByteBuffer(self::b64uDecode($stored['challenge'])), null, false, true);
            $newCount = (int)$wa->getSignatureCounter();
        } catch (Exception $e) {
            throw new Exception(trans('message.passkey.verification_failed'), 400);
        }

        // Discoverable login: the authenticator's user handle must match the credential owner.
        if (!empty($response['userHandle']) && self::b64uDecode($response['userHandle']) !== (string)$passkey->user_id) {
            throw new Exception(trans('message.passkey.verification_failed'), 400);
        }

        // Counter must increase, unless the authenticator never uses one (always 0).
        if (($newCount > 0 || $passkey->sign_count > 0) && $newCount <= $passkey->sign_count) {
            throw new Exception(trans('message.passkey.verification_failed'), 400);
        }

        $passkey->sign_count = $newCount;
        $passkey->last_used_at = now();
        $passkey->save();

        $user = User::find($passkey->user_id);
        if (!$user) {
            throw new Exception(trans('message.passkey.credential_unknown'), 404);
        }
        return $user;
    }

    /** Checks type, challenge and origin from clientDataJSON against our expectations. */
    private function assertClientData(string $clientDataJson, string $type, string $challenge): void
    {
        $c = json_decode($clientDataJson, true);
        if (!is_array($c)
            || ($c['type'] ?? null) !== $type
            || !hash_equals($challenge, (string)($c['challenge'] ?? ''))
            || rtrim((string)($c['origin'] ?? ''), '/') !== $this->origin()) {
            throw new Exception('client data mismatch');
        }
    }
}

<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Helpers\ResponseInterface;
use App\Http\Resources\SuccessResource;
use App\Models\Nickname;
class Controller extends BaseController
{
    /**
     * Common response among all the classes.
     *
     * @var mixed
     */
    protected $resProvider;

    public function __construct(ResponseInterface $resProvider)
    {
        $this->resProvider = $resProvider;
    }

    /**
     * Passport password-grant client used when the server requests a user token itself
     * (bot login, passkey login). Single source so the callers can't drift apart.
     */
    protected function passwordGrantClient(): array
    {
        return [
            'client_id' => env('PASSPORT_PASSWORD_CLIENT_ID', '2'),
            'client_secret' => env('PASSPORT_PASSWORD_CLIENT_SECRET', 'x6UX6WOv482Ree7r4sqEdzksvoadWKp6Dmgexbs7'),
        ];
    }

    /**
     * Local dev only: bypasses Passport (PHP built-in server deadlocks) and returns the
     * same {auth, user, nick_names} login payload with an unsigned JWT the frontend can decode.
     */
    protected function localDevLoginResponse($user, $clientId)
    {
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = base64_encode(json_encode([
            'aud' => $clientId ?? '2',
            'sub' => $user->id,
            'exp' => time() + 31536000,
            'iat' => time(),
            'scopes' => ['*']
        ]));
        $signature = base64_encode('local_dev_signature');
        $jwt = "$header.$payload.$signature";

        $nickNames = Nickname::getAllNicknames($user->id);

        $data = (object)[
            'auth' => (object)[
                'token_type' => 'Bearer',
                'expires_in' => 31536000,
                'access_token' => $jwt,
            ],
            'user' => $user,
            'nick_names' => $nickNames,
        ];
        $res = (object)[
            'status_code' => 200,
            'message' => 'Success',
            'data' => $data,
            'error' => null
        ];
        return (new SuccessResource($res))->response()->setStatusCode(200);
    }
}

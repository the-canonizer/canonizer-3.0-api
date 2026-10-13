<?php

namespace App\Http\Controllers;

use App\Facades\Util;
use App\Helpers\PasskeyService;
use App\Helpers\ResponseInterface;
use App\Http\Request\Validate;
use App\Http\Request\ValidationMessages;
use App\Http\Request\ValidationRules;
use App\Http\Resources\Authentication\UserResource;
use App\Http\Resources\ErrorResource;
use App\Models\UserPasskey;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class PasskeyController extends Controller
{
    private ValidationRules $rules;

    private ValidationMessages $validationMessages;

    private PasskeyService $passkeys;

    public function __construct(ResponseInterface $resProvider, PasskeyService $passkeys)
    {
        $this->rules = new ValidationRules;
        $this->validationMessages = new ValidationMessages;
        $this->resProvider = $resProvider;
        $this->passkeys = $passkeys;
    }

    public function registerOptions(Request $request)
    {
        try {
            $options = $this->passkeys->registrationOptions($request->user());
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $options, null);
        } catch (Exception $e) {
            return $this->failure($e);
        }
    }

    public function registerVerify(Request $request, Validate $validate)
    {
        $errors = $validate->validate($request, $this->rules->getPasskeyRegisterRules(), $this->validationMessages->getPasskeyMessages());
        if ($errors) {
            return (new ErrorResource($errors))->response()->setStatusCode(400);
        }
        try {
            $passkey = $this->passkeys->register($request->user(), $request->input('credential'), $request->input('label'));
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $passkey->toPublicArray(), null);
        } catch (Exception $e) {
            return $this->failure($e);
        }
    }

    /**
     * Any `email` in the body is accepted for backwards compatibility but ignored:
     * login is discoverable-credential only, so the response never depends on it.
     */
    public function loginOptions()
    {
        try {
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $this->passkeys->loginOptions(), null);
        } catch (Exception $e) {
            return $this->failure($e);
        }
    }

    /**
     * Verifies the assertion, then issues a token exactly like the email/password
     * login (same Passport password grant, same {auth, user} payload).
     */
    public function loginVerify(Request $request, Validate $validate)
    {
        $errors = $validate->validate($request, $this->rules->getPasskeyLoginVerifyRules(), $this->validationMessages->getPasskeyMessages());
        if ($errors) {
            return (new ErrorResource($errors))->response()->setStatusCode(400);
        }
        try {
            // Unverified accounts are rejected (402) inside login(), before any usage is recorded.
            $user = $this->passkeys->login($request->input('challenge_id'), $request->input('credential'));
            $user->is_admin = ($user->type == 'admin') ? true : false;

            // Same local-dev token bypass and payload as UserController::loginUser.
            if (env('APP_ENV') === 'local') {
                return $this->localDevLoginResponse($user, $request->client_id);
            }

            $generateToken = Util::httpPost(URL::to('/') . '/oauth/token', ['grant_type' => 'password'] + $this->passwordGrantClient() + [
                'username' => $user->email,
                'password' => env('PASSPORT_MASTER_PASSWORD'),
                'scope' => '*',
            ]);
            if ($generateToken->status_code != 200) {
                return (new ErrorResource($generateToken))->response()->setStatusCode($generateToken->status_code);
            }
            $data = [
                'auth' => $generateToken->data,
                'user' => new UserResource($user),
            ];
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $data, null);
        } catch (Exception $e) {
            return $this->failure($e);
        }
    }

    public function index(Request $request)
    {
        $list = UserPasskey::where('user_id', $request->user()->id)->orderBy('id')->get()
            ->map(function ($p) {
                return $p->toPublicArray();
            })->values();
        return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $list, null);
    }

    public function destroy(Request $request, $id)
    {
        $passkey = UserPasskey::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$passkey) {
            return $this->resProvider->apiJsonResponse(404, trans('message.passkey.not_found'), null, null);
        }
        $passkey->delete();
        return $this->resProvider->apiJsonResponse(200, trans('message.passkey.deleted'), null, null);
    }

    /**
     * Service exceptions carry an HTTP status as their code and a client-safe message.
     * Anything else (DB errors, HTTP failures, misconfiguration) is logged and returned
     * as a generic 500 so internals never reach the client.
     */
    private function failure(Exception $e)
    {
        if (in_array($e->getCode(), [400, 402, 404, 409], true)) {
            return $this->resProvider->apiJsonResponse($e->getCode(), $e->getMessage(), null, null);
        }
        Log::error($e);
        return $this->resProvider->apiJsonResponse(500, trans('message.error.exception'), null, null);
    }
}

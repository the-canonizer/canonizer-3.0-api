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

    public function loginOptions(Request $request, Validate $validate)
    {
        $errors = $validate->validate($request, $this->rules->getPasskeyLoginOptionsRules(), $this->validationMessages->getPasskeyMessages());
        if ($errors) {
            return (new ErrorResource($errors))->response()->setStatusCode(400);
        }
        try {
            $data = $this->passkeys->loginOptions($request->input('email'));
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $data, null);
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
            $user = $this->passkeys->login($request->input('challenge_id'), $request->input('credential'));
            if ($user->status != 1) {
                return $this->resProvider->apiJsonResponse(402, trans('message.error.account_not_verified'), null, null);
            }
            $user->is_admin = ($user->type == 'admin') ? true : false;

            $generateToken = Util::httpPost(URL::to('/') . '/oauth/token', [
                'grant_type' => 'password',
                'client_id' => env('PASSPORT_PASSWORD_CLIENT_ID'),
                'client_secret' => env('PASSPORT_PASSWORD_CLIENT_SECRET'),
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

    /** Service exceptions carry an HTTP status as their code; anything else is a 400. */
    private function failure(Exception $e)
    {
        $code = in_array($e->getCode(), [400, 404, 409], true) ? $e->getCode() : 400;
        return $this->resProvider->apiJsonResponse($code, $e->getMessage(), null, null);
    }
}

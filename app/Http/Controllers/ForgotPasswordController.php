<?php

namespace App\Http\Controllers;

use Exception;
use Throwable;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Helpers\ResponseInterface;
use Illuminate\Support\Facades\Hash;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use Illuminate\Support\Facades\Event;
use App\Http\Resources\SuccessResource;
use App\Http\Request\ValidationMessages;
use App\Events\ForgotPasswordSendOtpEvent;
use App\Http\Resources\Authentication\UserResource;

class ForgotPasswordController extends Controller
{
    private ValidationRules $rules;

    private ValidationMessages $validationMessages;

    public function __construct(ResponseInterface $resProvider)
    {
        $this->rules = new ValidationRules;
        $this->validationMessages = new ValidationMessages;
        $this->resProvider = $resProvider;
    }

    /**
     * @OA\Post(path="/forgotpassword/sendOtp",
     *   tags={"forgotpassword"},
     *   summary="forgot password send Otp",
     *   description="This api used to forgot password send Otp",
     *   operationId="forgotPassword",
     * @OA\Parameter(
     *     name="email",
     *     required=true,
     *     in="body",
     *     description="User Email Id",
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                 @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="data",
     *                                         type="array"
     *                                    )
     *                                 )
     *                            ),
     *
     *   @OA\Response(response=400, description="Exception occurs",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                 @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="error",
     *                                         type="array"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="data",
     *                                         type="string"
     *                                    )
     *                                 )
     *                             )
     *
     * )
     */

    public function sendOtp(Request $request, Validate $validate)
    {

        $validationErrors = $validate->validate($request, $this->rules->getForgotPasswordSendOtpValidationRules(), $this->validationMessages->getForgotPasswordSendOtpValidationMessages());

        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {
            $user = User::where('email', '=', $request->email)->first();

            if ($user) {
                $authCode = mt_rand(100000, 999999);
                $user->otp = $authCode;
                $user->status = 0;
                $user->update();
                try {
                    Event::dispatch(new ForgotPasswordSendOtpEvent($user));
                } catch (Throwable $e) {
                    $status = 403;
                    $message = trans('message.error.otp_failed');
                    return $this->resProvider->apiJsonResponse($status, $message,null, $e->getMessage());
                }
                $status = 200;
                $message = trans('message.success.forgot_password');
            } else {
                $status = 400;
                $message = trans('message.error.email_invalid');
            }
            return $this->resProvider->apiJsonResponse($status, $message, '', '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, $e->getMessage(), '', '');
        }
    }

    /**
     * @OA\Post(path="/forgotpassword/verifyOtp",
     *   tags={"forgotpassword"},
     *   summary="forgot password verify Otp",
     *   description="This api used to forgot password verify Otp",
     *   operationId="forgotPassword",
     * @OA\Parameter(
     *     name="email",
     *     required=true,
     *     in="body",
     *     description="User Email Id",
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     * @OA\Parameter(
     *     name="otp",
     *     required=true,
     *     in="body",
     *     description="User Otp",
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                 @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="data",
     *                                         type="array"
     *                                    )
     *                                 )
     *                            ),
     *
     *   @OA\Response(response=400, description="Exception occurs",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                 @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="error",
     *                                         type="array"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="data",
     *                                         type="string"
     *                                    )
     *                                 )
     *                             )
     *
     * )
     */

    public function verifyOtp(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getForgotPasswordVerifyOtpValidationRules(), $this->validationMessages->getForgotPasswordVerifyOtpValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        try {

            $user = User::where('email', '=', $request->username)->first();

            if (empty($user) || $request->otp != $user->otp) {
                $status = 401;
                $message = trans('message.error.otp_not_match');
                return $this->resProvider->apiJsonResponse($status, $message,null, null);
            } else {
                $userRes = User::where('email', '=', $request->username)->update(['otp' => '', 'status' => 1]);

                $status = 200;
                $message = trans('message.success.success');
                return $this->resProvider->apiJsonResponse($status, $message,null, null);
            }
        } catch (Exception $e) {
                $status = 400;
                $message = trans('message.error.exception');
                return $this->resProvider->apiJsonResponse($status, $message,null, null);
        }
    }

    /**
     * @OA\Post(path="/forgotpassword/update",
     *   tags={"forgotpassword"},
     *   summary="forgot password update",
     *   description="This api used to forgot password update",
     *   operationId="forgotPasswordupdate",
     * @OA\Parameter(
     *     name="username",
     *     required=true,
     *     in="body",
     *     description="User Name",
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     * @OA\Parameter(
     *     name="new_password",
     *     required=true,
     *     in="body",
     *     description="User new password",
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     * @OA\Parameter(
     *     name="confirm_password",
     *     required=true,
     *     in="body",
     *     description="User confirm password",
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                 @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="data",
     *                                         type="array"
     *                                    )
     *                                 )
     *                            ),
     *
     *   @OA\Response(response=400, description="Exception occurs",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                 @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="error",
     *                                         type="array"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="data",
     *                                         type="string"
     *                                    )
     *                                 )
     *                             )
     *
     * )
     */

    public function updatePassword(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getForgotPasswordUpdateValidationRules(), $this->validationMessages->getForgotPasswordUpdateValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        $user = User::where('email', '=', $request->username)->first();

        if (empty($user)) {
            $status = 401;
            $message = trans('message.error.user_not_exist');
            return $this->resProvider->apiJsonResponse($status, $message,null, null);
        }

        try {
            $newPassword = Hash::make($request->get('new_password'));
            $user->password = $newPassword;
            $user->save();
            $status = 200;
            $message = trans('message.success.password_change');
            return $this->resProvider->apiJsonResponse($status, $message,null, null);
        } catch (Exception $e) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message,null, null);
        }
    }
}

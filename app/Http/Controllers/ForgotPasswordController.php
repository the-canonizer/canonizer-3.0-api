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
     * @OA\Post(
     *   path="/forgot-password/send-otp",
     *   tags={"Forgot Password"},
     *   summary="Send OTP for Forgot Password",
     *   description="Sends a one-time password (OTP) to the user's registered email for password reset.",
     *   operationId="forgotPassword",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="User's email address",
     *       @OA\JsonContent(
     *           required={"email"},
     *           @OA\Property(
     *               property="email",
     *               type="string",
     *               format="email",
     *               description="Registered email ID of the user"
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="OTP sent successfully",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="OTP sent successfully"),
     *           @OA\Property(property="error", type="string", nullable=true),
     *           @OA\Property(
     *               property="data",
     *               type="object",
     *               @OA\Property(property="otp", type="integer", example=123456)
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Invalid request or email not found",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Invalid email or request"),
     *           @OA\Property(property="error", type="string", example="User not found")
     *       )
     *   ),
     *   @OA\Response(
     *       response=403,
     *       description="Request forbidden due to an error",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=403),
     *           @OA\Property(property="message", type="string", example="Action not allowed"),
     *           @OA\Property(property="error", type="string", example="Too many requests")
     *       )
     *   )
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
                $user->update();
                try {
                    Event::dispatch(new ForgotPasswordSendOtpEvent($user));
                } catch (Throwable $e) {
                    $status = 403;
                    $message = trans('message.error.otp_failed');
                    return $this->resProvider->apiJsonResponse($status, $message, null, $e->getMessage());
                }
                $status = 200;
                $message = trans('message.success.forgot_password');
            } else {
                $status = 400;
                $message = trans('message.error.email_not_registered');
            }
            return $this->resProvider->apiJsonResponse($status, $message, '', '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, $e->getMessage(), '', '');
        }
    }

    /**
     * @OA\Post(
     *   path="/forgot-password/verify-otp",
     *   tags={"Forgot Password"},
     *   summary="Verify OTP for Forgot Password",
     *   description="This API verifies the OTP sent to the user's email for password reset.",
     *   operationId="forgotPasswordVerifyOtp",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="Request body with email and OTP",
     *       @OA\JsonContent(
     *           required={"email", "otp"},
     *           @OA\Property(
     *               property="email",
     *               type="string",
     *               format="email",
     *               description="Registered email ID"
     *           ),
     *           @OA\Property(
     *               property="otp",
     *               type="integer",
     *               description="One-time password received via email"
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="OTP verification successful",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="OTP verified successfully"),
     *           @OA\Property(property="error", type="string", nullable=true),
     *           @OA\Property(property="data", type="object")
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Invalid OTP or email",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Invalid OTP or email"),
     *           @OA\Property(property="error", type="string", example="OTP expired or incorrect")
     *       )
     *   ),
     *   @OA\Response(
     *       response=403,
     *       description="Too many failed attempts or blocked request",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=403),
     *           @OA\Property(property="message", type="string", example="Too many failed attempts"),
     *           @OA\Property(property="error", type="string", example="Account temporarily locked")
     *       )
     *   )
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
            if (strlen($request->otp) < 6) {
                $status = 403;
                $message = trans('message.error.otp_lenth_match');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            } elseif (strlen($request->otp) > 6) {
                $status = 403;
                $message = trans('message.error.otp_lenth_match');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }
            if (empty($user) || $request->otp != $user->otp) {
                $status = 403;
                $message = trans('message.error.otp_not_match');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            } else {
                $userRes = User::where('email', '=', $request->username)->update(['otp' => '']);

                $status = 200;
                $message = trans('message.success.otp_verified');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }
        } catch (Exception $e) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }

    /**
     * @OA\Post(
     *   path="/forgot-password/update",
     *   tags={"Forgot Password"},
     *   summary="Update Password",
     *   description="This API allows users to update their password after verifying their identity.",
     *   operationId="forgotPasswordUpdate",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="User credentials for password update",
     *       @OA\JsonContent(
     *           required={"username", "new_password", "confirm_password"},
     *           @OA\Property(
     *               property="username",
     *               type="string",
     *               description="User's registered username or email",
     *               example="user@example.com"
     *           ),
     *           @OA\Property(
     *               property="new_password",
     *               type="string",
     *               description="New password (must meet security criteria)",
     *               minLength=8,
     *               example="NewPass@123"
     *           ),
     *           @OA\Property(
     *               property="confirm_password",
     *               type="string",
     *               description="Confirm new password (must match new_password)",
     *               minLength=8,
     *               example="NewPass@123"
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Password updated successfully",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Password updated successfully"),
     *           @OA\Property(property="error", type="string", nullable=true),
     *           @OA\Property(property="data", type="object", nullable=true)
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Invalid request parameters",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Passwords do not match"),
     *           @OA\Property(property="error", type="string", example="Validation error")
     *       )
     *   ),
     *   @OA\Response(
     *       response=403,
     *       description="Unauthorized or expired token",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=403),
     *           @OA\Property(property="message", type="string", example="Unauthorized request"),
     *           @OA\Property(property="error", type="string", example="Invalid token or session expired")
     *       )
     *   )
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
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }

        try {
            $newPassword = Hash::make($request->get('new_password'));
            $user->password = $newPassword;
            $user->save();
            $status = 200;
            $message = trans('message.success.password_reset');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        } catch (Exception $e) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }
}

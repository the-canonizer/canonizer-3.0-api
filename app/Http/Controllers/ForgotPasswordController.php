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
                    $message = config('message.error.otp_failed');
                    return $this->resProvider->apiJsonResponse($status, $message,null, $e->getMessage());
                }
                $status = 200;
                $message = config('message.success.forgot_password');
            } else {
                $status = 400;
                $message = config('message.error.email_invalid');
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
                $res = (object)[
                    "status_code" => 401,
                    "message"     => "OTP does not match",
                    "error"       => null,
                    "data"        => null
                ];
                return (new ErrorResource($res))->response()->setStatusCode(401);
            } else {
                $userRes = User::where('email', '=', $request->username)->update(['otp' => '', 'status' => 1]);

                $response = (object)[
                    "status_code" => 200,
                    "message"     => "Success",
                    "error"       => null,
                    "data"        => null
                ];
                return (new SuccessResource($response))->response()->setStatusCode(200);
            }
        } catch (Exception $e) {
            $res = (object)[
                "status_code" => 400,
                "message"     => "Something went wrong",
                "error"       => null,
                "data"        => null
            ];
            return (new ErrorResource($res))->response()->setStatusCode(400);
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
            $res = (object)[
                "status_code" => 401,
                "message"     => "User Does Not Exist",
                "error"       => null,
                "data"        => null
            ];
            return (new ErrorResource($res))->response()->setStatusCode(401);
        }

        try {
            $newPassword = Hash::make($request->get('new_password'));
            $user->password = $newPassword;
            $user->save();
            $res = (object)[
                "status_code" => 200,
                "message"     => "Password changed successfully",
                "error"       => null,
                "data"        => null
            ];
            return (new SuccessResource($res))->response()->setStatusCode(200);
        } catch (Exception $e) {
            $res = (object)[
                "status_code" => 400,
                "message"     => "Something went wrong",
                "error"       => null,
                "data"        => $e->getMessage()
            ];
            return (new ErrorResource($res))->response()->setStatusCode(400);
        }
    }
}

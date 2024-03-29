<?php

namespace App\Http\Controllers;

use Exception;
use Throwable;
use App\Models\User;
use App\Facades\Util;
use App\Models\Country;
use App\Models\Support;
use App\Jobs\SendOtpJob;
use App\Models\Nickname;
use App\Jobs\WelcomeMail;
use App\Models\SocialUser;
use App\Events\SendOtpEvent;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Events\WelcomeMailEvent;
use App\Models\SocialEmailVerify;
use App\Models\TwitterOauthToken;
use App\Helpers\ResponseInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use Illuminate\Support\Facades\Event;
use Abraham\TwitterOAuth\TwitterOAuth;
use App\Http\Resources\SuccessResource;
use App\Http\Request\ValidationMessages;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Resources\Authentication\UserResource;

class UserController extends Controller
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
     * @OA\POST(path="/client_token",
     *   tags={"User"},
     *   summary="This api used to get password client id and client secrect",
     *   description="",
     *   operationId="clienttoken",
     *    @OA\RequestBody(
     *     required=true,
     *     description="Request Body Json Parameter",
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *               @OA\Property(
     *                  property="client_id",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="client_secret",
     *                  type="string"
     *              )
     *          )
     *     ),
     *   ),
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="object",
     *                                 @OA\Property(
     *                                         property="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="data",
     *                                         type="object"
     *                                    )
     *                                 )
     *                            ),
     *
     *    @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *    @OA\Response(
     *     response=403,
     *     description="Exception Throwable",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     *
     * )
     */

    public function clientToken(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getTokenValidationRules(), $this->validationMessages->getTokenValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        try {
            $postUrl = URL::to('/') . '/oauth/token';
            $isFromTestCases = $request->get('from_test_case', null);
            if ($isFromTestCases == '1') {
                $postUrl .= '?from_test_case=1';
            }
            $payload = [
                'grant_type' => 'client_credentials',
                'client_id' => $request->client_id,
                'client_secret' => $request->client_secret,
                'scope' => '*',
            ];
            $generateToken = Util::httpPost($postUrl, $payload);
            if ($generateToken->status_code == 200) {
                return (new SuccessResource($generateToken))->response()->setStatusCode(200);
            }
            return (new ErrorResource($generateToken))->response()->setStatusCode($generateToken->status_code);
        } catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }


    /**
     * @OA\POST(path="/register",
     *   tags={"User"},
     *   summary="Create user API",
     *   description="This is used to register the user.",
     *   operationId="createUser",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *    @OA\RequestBody(
     *     required=true,
     *     description="Request Body Json Parameter",
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *               @OA\Property(
     *                  property="client_id",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="client_secret",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="first_name",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="middle_name",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="last_name",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="phone_number",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="password",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="password_confirmation",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="country_code",
     *                  type="string"
     *              )
     *          )
     *     ),
     *   ),
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="object",
     *                                 @OA\Property(
     *                                         property="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="data",
     *                                         type="object"
     *                                    )
     *                                 )
     *                            ),
     *
     *    @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *    @OA\Response(
     *     response=403,
     *     description="Exception Throwable",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     *
     * )
     */

    public function createUser(Request $request, Validate $validate)
    {

        $validationErrors = $validate->validate($request, $this->rules->getRegistrationValidationRules(), $this->validationMessages->getRegistrationValidationMessages());

        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {
            $postUrl = env('RECAPTCHA_SITE_VERIFY_URL');
            $payload = [
                'secret' => env('RECAPTCHA_SECRET_KEY'),
                'response' => $request->captcha_token,
                'remoteip' => $request->ip()
            ];
            $validateRecaptcha = Util::httpPost($postUrl, $payload);
            if (($validateRecaptcha->status_code != 200 || !$validateRecaptcha->data['success'] || $validateRecaptcha->data['score'] < 0.5) && !app()->environment('testing')) {
                $status = 406;
                $message = "The reCAPTCHA verification failed, please try again.";
                if ($validateRecaptcha->status_code != 200) {
                    $message = "An error occurred during reCAPTCHA verification.";
                } elseif (!$validateRecaptcha->data['success']) {
                    $message = "The reCAPTCHA verification failed.";
                } elseif ($validateRecaptcha->data['score'] < 0.5) {
                    $message = "The reCAPTCHA verification score is too low.";
                }

                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }
            $authCode = mt_rand(100000, 999999);
            //$authCode = 454545;
            $input = [
                "first_name" => $request->first_name,
                "last_name" => $request->last_name,
                "middle_name" => $request->middle_name,
                "email" => $request->email,
                "phone_number" => $request->phone_number,
                "country_code" => $request->country_code,
                "password" => Hash::make($request->password),
                "otp" => $authCode
            ];

            $user = User::create($input);

            if ($user) {
                $nickname = $user->first_name . (empty($user->last_name) ? '' : '-') . $user->last_name;
                $this->createNickname($user->id, $nickname);
                try {
                    Event::dispatch(new SendOtpEvent($user));
                } catch (Throwable $e) {
                    $status = 403;
                    $message = trans('message.error.otp_failed');
                    return $this->resProvider->apiJsonResponse($status, $message, null, $e->getMessage());
                }
                $status = 200;
                $message = trans('message.success.reg_success');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            } else {
                $status = 400;
                $message = trans('message.error.reg_failed');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }
        } catch (Exception $e) {
            $status = 400;
            $message = $e->getMessage();
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }


    /**
     * @OA\POST(path="/user/login",
     *   tags={"User"},
     *   summary="Logs user into the system",
     *   description="",
     *   operationId="loginUser",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *    @OA\RequestBody(
     *     required=true,
     *     description="Request Body Json Parameter",
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *               @OA\Property(
     *                  property="username",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="password",
     *                  type="string"
     *              )
     *          )
     *     ),
     *   ),
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="object",
     *                                 @OA\Property(
     *                                         property="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="data",
     *                                         type="object",
     *                                           @OA\Property(
     *                                              property="auth",
     *                                              type="object",
     *                                              @OA\Property(
     *                                                  property="token_type",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="expires_in",
     *                                                  type="integer"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="access_token",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="refresh_token",
     *                                                  type="string"
     *                                              )
     *                                          ),
     *                                           @OA\Property(
     *                                              property="user",
     *                                              type="object",
     *                                              @OA\Property(
     *                                                  property="first_name",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="middle_name",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="last_name",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="email",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="phone_number",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="mobile_verified",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="birthday",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="default_algo",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="private_flags",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="join_time",
     *                                                  type="integer"
     *                                              ),
     *                                          )
     *                                    )
     *                                 )
     *                            ),
     *
     *    @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *    @OA\Response(
     *     response=403,
     *     description="Exception Throwable",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     *
     * )
     */
    public function loginUser(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getLoginValidationRules(), $this->validationMessages->getLoginValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {
            $username = $request->username;
            $password = $request->password;
            $user = User::where('email', '=', $username)->first();
            if (empty($user)) {
                $status = 400;
                $message = trans('message.error.email_not_registered');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }
            if (!Hash::check($password, $user->password)) {
                $status = 400;
                $message = trans('message.error.password_not_match');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }

            if ($user->status != 1) {
                $status = 402;
                $message = trans('message.error.account_not_verified');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }

            $postUrl = URL::to('/') . '/oauth/token';
            $isFromTestCases = $request->get('from_test_case', null);
            if ($isFromTestCases == '1') {
                $postUrl .= '?from_test_case=1';
            }
            $user->is_admin = ($user->type == 'admin') ? true : false;
            $payload = [
                'grant_type' => 'password',
                'client_id' => $request->client_id,
                'client_secret' => $request->client_secret,
                'username' => $username,
                'password' => $password,
                'scope' => '*',
            ];

            $generateToken = Util::httpPost($postUrl, $payload);
            return $this->getTokenResponse($generateToken, $user);
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, $e->getMessage(), null, null);
        }
    }

    /**
     * @OA\Get(path="/user/logout",
     *   tags={"User"},
     *   summary="Logout the user",
     *   description="",
     *   operationId="logoutUser",
     *   parameters={},
     *   @OA\Response(response="default", description="successful operation")
     * )
     */
    public function logoutUser(Request $request)
    {
        $loggedInUser = $request->user();

        try {
            $loggedInUser->token()->revoke();
            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        } catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }

    /**
     * @OA\Get(path="/user/{username}",
     *   tags={"User"},
     *   summary="Get user by user nick name",
     *   description="",
     *   operationId="getUserByName",
     *   @OA\Parameter(
     *     name="username",
     *     in="path",
     *     description="The name that needs to be fetched. Use user1 for testing. ",
     *     required=true,
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Response(response=200, description="successful operation", @OA\Schema(ref="#/components/schemas/User")),
     *   @OA\Response(response=400, description="Invalid username supplied"),
     *   @OA\Response(response=404, description="User not found")
     * )
     */
    public function getUserByNickName($username)
    {
    }

    /**
     * @OA\Put(path="/user/{username}",
     *   tags={"User"},
     *   summary="Updated user",
     *   description="This can only be done by the logged in user.",
     *   operationId="updateUser",
     *   @OA\Parameter(
     *     name="username",
     *     in="path",
     *     description="name that need to be updated",
     *     required=true,
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Response(response=400, description="Invalid user supplied"),
     *   @OA\Response(response=404, description="User not found"),
     *   @OA\RequestBody(
     *       required=true,
     *       description="Updated user object",
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(ref="#/components/schemas/User")
     *       )
     *   ),
     * )
     */
    public function updateUser()
    {
    }

    /**
     * @OA\Delete(path="/user/{username}",
     *   tags={"User"},
     *   summary="Delete user",
     *   description="This can only be done by the logged in user.",
     *   operationId="deleteUser",
     *   @OA\Parameter(
     *     name="username",
     *     in="path",
     *     description="The name that needs to be deleted",
     *     required=true,
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *   @OA\Response(
     *      response=401,
     *      description="Unauthenticated"
     *   ),
     *   @OA\Response(response=404, description="User not found")
     * )
     */
    public function deleteUser()
    {
    }

    protected function createNickname($userID, $nickname)
    {
        $nicknameCreated = false;
        if (empty($userID) || empty($nickname)) {

            return $nicknameCreated;
        }
        // Check whether user exists or not for the given id
        $user = User::getUserById($userID);


        if (empty($user)) {
            return $nicknameCreated;
        }

        // Check whether nickname exists for the given nickname
        $isExists = Nickname::isNicknameExists($nickname);

        if ($isExists === true) {
            $randNumber = mt_rand(000, 999);
            $nickname = $nickname . $randNumber;
        }

        try {

            // Create nickname
            $nicknameObj = new Nickname();
            $nicknameObj->user_id = $userID;
            $nicknameObj->nick_name = substr($nickname, 0, 50);
            $nicknameObj->private = 0;
            $nicknameObj->create_time = time();
            $nicknameObj->save();
            $nicknameCreated = true;
        } catch (Exception $ex) {
            $nicknameCreated = false;
        }
        return $nicknameCreated;
    }

    /**
     * @OA\POST(path="/verifyOtp",
     *   tags={"User"},
     *   summary="For verify Otp after login details",
     *   description="This api used to verify Otp after login details",
     *   operationId="verifyOtp",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *    @OA\RequestBody(
     *     required=true,
     *     description="Request Body Json Parameter",
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *               @OA\Property(
     *                  property="client_id",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="client_secret",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="username",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="otp",
     *                  type="string"
     *              )
     *          )
     *     ),
     *   ),
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="object",
     *                                 @OA\Property(
     *                                         property="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="data",
     *                                         type="object",
     *                                           @OA\Property(
     *                                              property="auth",
     *                                              type="object",
     *                                              @OA\Property(
     *                                                  property="token_type",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="expires_in",
     *                                                  type="integer"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="access_token",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="refresh_token",
     *                                                  type="string"
     *                                              )
     *                                          ),
     *                                           @OA\Property(
     *                                              property="user",
     *                                              type="object",
     *                                              @OA\Property(
     *                                                  property="first_name",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="middle_name",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="last_name",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="email",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="phone_number",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="mobile_verified",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="birthday",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="default_algo",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="private_flags",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="join_time",
     *                                                  type="integer"
     *                                              ),
     *                                          )
     *                                    )
     *                                 )
     *                            ),
     *
     *    @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *    @OA\Response(
     *     response=403,
     *     description="Exception Throwable",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     *
     * )
     */

    public function postVerifyOtp(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getVerifyOtpValidationRules(), $this->validationMessages->getVerifyOtpValidationMessages());
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
                $status = 400;
                $message = trans('message.error.otp_not_match');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }

            $postUrl = URL::to('/') . '/oauth/token';
            $payload = [
                'grant_type' => 'password',
                'client_id' => $request->client_id,
                'client_secret' => $request->client_secret,
                'username' => $request->username,
                'password' => env('PASSPORT_MASTER_PASSWORD'),
                'scope' => '*',
            ];
            $generateToken = Util::httpPost($postUrl, $payload);
            if ($generateToken->status_code == 200) {
                $userRes = User::where('email', '=', $request->username)->update(['otp' => '', 'status' => 1]);
                if ($request->is_login == 0) {
                    $link_index_page = config('global.APP_URL_FRONT_END') . '/topic/132-Help/1-Agreement';
                    Event::dispatch(new WelcomeMailEvent($user, $link_index_page));
                }
                $user->is_admin = ($user->type == 'admin') ? true : false;
                $data = [
                    "auth" => $generateToken->data,
                    "user" => new UserResource($user),
                ];
                $status = 200;
                $message = trans('message.success.success');
                return $this->resProvider->apiJsonResponse($status, $message, $data, null);
            }
            return (new ErrorResource($generateToken))->response()->setStatusCode($generateToken->status_code);
        } catch (Exception $e) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }

    /**
     * @OA\POST(path="/user/social/login",
     *   tags={"User"},
     *   summary="For get social token url",
     *   description="This api used to create social token url and we are using this url for generating code",
     *   operationId="usersociallogin",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *    @OA\RequestBody(
     *     required=true,
     *     description="Request Body Json Parameter",
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *               @OA\Property(
     *                  property="provider",
     *                  type="string"
     *              )
     *          )
     *     ),
     *   ),
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="object",
     *                                 @OA\Property(
     *                                         property="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="data",
     *                                         type="object"
     *                                    )
     *                                 )
     *                            ),
     *
     *    @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *    @OA\Response(
     *     response=403,
     *     description="Exception Throwable",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     *
     * )
     */

    public function socialLogin(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getSocialLoginValidationRules(), $this->validationMessages->getSocialLoginValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {
            $provider = $request->provider;

            if ($provider == 'twitter') {
                $connection = new TwitterOAuth(env('TWITTER_CLIENT_ID'), env('TWITTER_CLIENT_SECRET'));
                $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => env('TWITTER_CALLBACK_URL')));
                if ($connection->getLastHttpCode() != 200 || empty($request_token['oauth_token']) || empty($request_token['oauth_token_secret'])) {
                    $status = 400;
                    $message = trans('message.error.exception');
                    return $this->resProvider->apiJsonResponse($status, $message, null, null);
                }

                TwitterOauthToken::create([
                    "token"  => $request_token['oauth_token'],
                    "secret" => $request_token['oauth_token_secret']
                ]);

                $auth_url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));

                $data = [
                    "url" => $auth_url
                ];
                $status = 200;
                $message = trans('message.success.success');
                return $this->resProvider->apiJsonResponse($status, $message, $data, null);
            }

            $redirect = Socialite::with($provider)->stateless()->redirect()->getTargetUrl();
            if (empty($redirect)) {
                $status = 400;
                $message = trans('message.error.exception');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }
            $data = [
                "url" => $redirect
            ];
            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, $data, null);
        } catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $ex->getMessage());
        }
    }

    public function twitterCallback(Request $request)
    {
        $code = '';
        if ($request->has('oauth_token') && $request->has('oauth_verifier')) {
            $token = $request->input('oauth_token');
            $verifier = $request->input('oauth_verifier');
            $twitter = TwitterOauthToken::where('token', $token)->latest()->first();
            if (!empty($twitter)) {
                $connection = new TwitterOAuth(env('TWITTER_CLIENT_ID'), env('TWITTER_CLIENT_SECRET'), $twitter->token, $twitter->secret);
                $access_token = $connection->oauth("oauth/access_token", ["oauth_verifier" => $verifier]);
                if ($connection->getLastHttpCode() == 200 && !empty($access_token['oauth_token']) && !empty($access_token['oauth_token_secret'])) {
                    $code = $access_token['oauth_token'];
                    $twitter->access_token = $access_token['oauth_token'];
                    $twitter->access_secret = $access_token['oauth_token_secret'];
                    $twitter->save();
                }
            }
        }
        $frontend_redirect = env('TWITTER_URL') . '?code=' . $code;
        return redirect($frontend_redirect);
    }

    /**
     * @OA\POST(path="/user/social/callback",
     *   tags={"User"},
     *   summary="For get social user details",
     *   description="This api used to get social social users detauls and auth details",
     *   operationId="usersocialcallback",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="password"
     *         ) 
     *    ),
     *    @OA\RequestBody(
     *     required=true,
     *     description="Request Body Json Parameter",
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *               @OA\Property(
     *                  property="client_id",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="client_secret",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="provider",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="code",
     *                  type="string"
     *              )
     *          )
     *     ),
     *   ),
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="object",
     *                                 @OA\Property(
     *                                         property="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="data",
     *                                         type="object",
     *                                           @OA\Property(
     *                                              property="auth",
     *                                              type="object",
     *                                              @OA\Property(
     *                                                  property="token_type",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="expires_in",
     *                                                  type="integer"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="access_token",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="refresh_token",
     *                                                  type="string"
     *                                              )
     *                                          ),
     *                                           @OA\Property(
     *                                              property="user",
     *                                              type="object",
     *                                              @OA\Property(
     *                                                  property="first_name",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="middle_name",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="last_name",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="email",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="phone_number",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="mobile_verified",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="birthday",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="default_algo",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="private_flags",
     *                                                  type="string"
     *                                              ),
     *                                              @OA\Property(
     *                                                  property="join_time",
     *                                                  type="integer"
     *                                              ),
     *                                          )
     *                                    )
     *                                 )
     *                            ),
     *
     *    @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *    @OA\Response(
     *     response=403,
     *     description="Exception Throwable",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     *
     * )
     */

    public function socialCallback(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getSocialCallbackValidationRules(), $this->validationMessages->getSocialCallbackValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        try {

            $provider = $request->provider;
            $providerEmail = '';
            $providerId = 0;
            $providerUserName = '';
            if ($provider == 'twitter') {
                $code = $request->code;
                $twitter = TwitterOauthToken::where(['access_token' => $code])->latest()->first();
                if (empty($twitter)) {
                    $status = 400;
                    $message = trans('message.error.exception');
                    return $this->resProvider->apiJsonResponse($status, $message, null, null);
                }
                $connection = new TwitterOAuth(env('TWITTER_CLIENT_ID'), env('TWITTER_CLIENT_SECRET'), $twitter->access_token, $twitter->access_secret);
                $twitterUser = $connection->get('account/verify_credentials', ['include_email' => true]);

                if ($connection->getLastHttpCode() != 200 || empty($twitterUser->id)) {
                    $status = 400;
                    $message = trans('message.error.exception');
                    return $this->resProvider->apiJsonResponse($status, $message, null, null);
                }
                $providerEmail = $twitterUser->email;
                $providerId = $twitterUser->id;
                $providerUserName = $twitterUser->name ?? $twitterUser->screen_name;
            } else {
                $userSocial =   Socialite::driver($provider)->stateless()->user();
                if (empty($userSocial)) {
                    $status = 400;
                    $message = trans('message.error.exception');
                    return $this->resProvider->apiJsonResponse($status, $message, null, null);
                }
                $providerEmail = $userSocial->getEmail();
                $providerUserName = $userSocial->getName();
                $providerId = $userSocial->getId();
            }
            $social_user = SocialUser::where(['provider_id' => $providerId, 'provider' => $provider])->first();
            if (empty($social_user)) {
                if (empty($providerUserName)) {
                    $status = 423;
                    $message = trans('message.social.name_not_received');
                    $data = [
                        "code" => $request->code,
                        "provider" => $request->provider,
                        "email" => $providerEmail
                    ];
                    SocialEmailVerify::create([
                        'first_name'    => "",
                        'last_name'     => "",
                        'email'         => $providerEmail,
                        'provider_id' => $providerId,
                        'provider' => $request->provider,
                        'code' => $request->code,
                    ]);
                    return $this->resProvider->apiJsonResponse($status, $message, $data, null);
                }
                $splitName = Util::split_name($providerUserName);
                if (empty($providerEmail)) {
                    $status = 422;
                    $message = trans('message.social.email_not_received');
                    $data = [
                        "code" => $request->code,
                        "provider" => $request->provider,
                    ];

                    SocialEmailVerify::create([
                        'first_name'    => $splitName[0],
                        'last_name'     => $splitName[1],
                        'email'         => $providerEmail,
                        'provider_id' => $providerId,
                        'provider' => $request->provider,
                        'code' => $request->code,
                    ]);
                    return $this->resProvider->apiJsonResponse($status, $message, $data, null);
                }
                $user = User::where(['email' => $providerEmail])->first();
                if (empty($user)) {
                    $user = User::create([
                        'first_name'    => $splitName[0],
                        'last_name'     => $splitName[1],
                        'email'         => $providerEmail,
                        'status'        => 1,
                        'is_active'     => 1
                    ]);
                    $nickname = $user->first_name . (empty($user->last_name) ? '' : '-') . $user->last_name;
                    $this->createNickname($user->id, $nickname);
                }
                $this->createSocialUser($providerId, $providerEmail, $providerUserName, $provider, $user->id);
            } else {
                $user = User::find($social_user->user_id);
            }

            if ($user->is_active != 1) {
                $status = 402;
                $message = trans('message.error.in_active_message');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }
            $user->is_admin = ($user->type == 'admin') ? true : false;
            $postUrl = URL::to('/') . '/oauth/token';
            $payload = [
                'grant_type' => 'password',
                'client_id' => $request->client_id,
                'client_secret' => $request->client_secret,
                'username' => $user->email,
                'password' => env('PASSPORT_MASTER_PASSWORD'),
                'scope' => '*',
            ];
            $generateToken = Util::httpPost($postUrl, $payload);
            return $this->getTokenResponse($generateToken, $user);
        } catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }

    /**
     * @OA\Get(path="/country/list",
     *   tags={"User"},
     *   summary="For get country list",
     *   description="This api used to get country list",
     *   operationId="countrylist",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="object",
     *                                 @OA\Property(
     *                                         property="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="data",
     *                                         type="object"
     *                                    )
     *                                 )
     *                            ),
     *
     *    @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     *
     * )
     */

    public function countryList(Request $request)
    {

        try {

            $result = Country::where('status', 1)->get();

            if (empty($result)) {
                $status = 400;
                $message = trans('message.error.exception');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }

            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, $result, null);
        } catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }


    /**
     * @OA\POST(path="/user/reSendOtp",
     *   tags={"User"},
     *   summary="User Resend Otp",
     *   description="This api used to Resend Otp",
     *   operationId="userReSend",
     *  @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *    @OA\RequestBody(
     *     required=true,
     *     description="Request Body Json Parameter",
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *               @OA\Property(
     *                  property="email",
     *                  type="string"
     *              )
     *          )
     *     ),
     *   ),
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="object",
     *                                 @OA\Property(
     *                                         property="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="data",
     *                                         type="object"
     *                                    )
     *                                 )
     *                            ),
     *
     *    @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     *
     * )
     */

    public function reSendOtp(Request $request, Validate $validate)
    {

        $validationErrors = $validate->validate($request, $this->rules->getUserReSendOtpValidationRules(), $this->validationMessages->getUserReSendOtpValidationMessages());

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
                    Event::dispatch(new SendOtpEvent($user));
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
     * @OA\GET(path="/user/social/list",
     *   tags={"User"},
     *   summary="Get User Social Link Account List",
     *   description="This API is use for get user social link account list",
     *   operationId="socialList",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *     @OA\Response(
     *         response=200,
     *        description = "Success",
     *        @OA\JsonContent(
     *             type="object",
     *              @OA\Property(
     *                   property="status_code",
     *                   type="integer"
     *               ),
     *               @OA\Property(
     *                   property="message",
     *                   type="string"
     *               ),
     *              @OA\Property(
     *                   property="error",
     *                   type="string"
     *              ),
     *             @OA\Property(
     *                property="data",
     *                type="array",
     *                @OA\Items(
     *                    @OA\Property(
     *                          property="id",
     *                          type="integer"
     *                    ),
     *                    @OA\Property(
     *                          property="user_id",
     *                          type="integer"
     *                    ),
     *                    @OA\Property(
     *                          property="social_name",
     *                          type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="provider",
     *                           type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="provider_id",
     *                           type="string"
     *                     )
     *                ),
     *             ),
     *        ),
     *     ),
     *
     *
     *     @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *    @OA\Response(
     *     response=403,
     *     description="Exception Throwable",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     * )
     */


    public function socialList(Request $request)
    {
        try {

            $result = SocialUser::where('user_id', $request->user()->id)->get();

            if (empty($result)) {
                $status = 400;
                $message = trans('message.error.exception');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }

            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, $result, null);
        } catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $ex->getMessage());
        }
    }

    /**
     * @OA\Delete(path="/user/social/delete/{id}",
     *   tags={"User"},
     *   summary="Unlink Social User",
     *   description="This API is use for unlink soical account and delete social user",
     *   operationId="socialDelete",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *   @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Delete a record from this id",
     *         @OA\Schema(
     *              type="integer"
     *         ) 
     *    ),
     *     @OA\Response(
     *         response=200,
     *        description = "Success",
     *        @OA\JsonContent(
     *             type="object",
     *              @OA\Property(
     *                   property="status_code",
     *                   type="integer"
     *               ),
     *               @OA\Property(
     *                   property="message",
     *                   type="string"
     *               ),
     *              @OA\Property(
     *                   property="error",
     *                   type="string"
     *              ),
     *             @OA\Property(
     *                property="data",
     *                type="string",
     *             ),
     *        ),
     *     ),
     *
     *
     *     @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *    @OA\Response(
     *     response=403,
     *     description="Exception Throwable",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     * )
     */

    public function socialDelete(Request $request, $id)
    {
        $loggedInUser = $request->user();
        try {
            $social_user = SocialUser::where('id', $id)->where('user_id', $loggedInUser->id)->delete();
            $status = 200;
            $message = trans('message.social.unlink_social_user');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        } catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $ex->getMessage());
        }
    }

    /**
     * @OA\POST(path="/user/social/socialLink",
     *   tags={"User"},
     *   summary="For link social user",
     *   description="This api used to link social users",
     *   operationId="usersocialsociallink",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="password"
     *         ) 
     *    ),
     *    @OA\RequestBody(
     *     required=true,
     *     description="Request Body Json Parameter",
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *               @OA\Property(
     *                  property="client_id",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="client_secret",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="provider",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="code",
     *                  type="string"
     *              )
     *          )
     *     ),
     *   ),
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="object",
     *                                 @OA\Property(
     *                                         property="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="data",
     *                                         type="object"
     *                                    )
     *                                 )
     *                            ),
     *
     *    @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *    @OA\Response(
     *     response=403,
     *     description="Exception Throwable",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     *
     * )
     */

    public function socialLink(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getSocialCallbackValidationRules(), $this->validationMessages->getSocialCallbackValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        try {

            $provider = $request->provider;
            $providerEmail = '';
            $providerId = 0;
            $providerUserName = '';

            if ($provider == 'twitter') {
                $user = $request->user();
                $code = $request->code;
                $twitter = TwitterOauthToken::where(['access_token' => $code])->latest()->first();
                if (empty($twitter)) {
                    $status = 400;
                    $message = trans('message.error.exception');
                    return $this->resProvider->apiJsonResponse($status, $message, null, null);
                }
                $connection = new TwitterOAuth(env('TWITTER_CLIENT_ID'), env('TWITTER_CLIENT_SECRET'), $twitter->access_token, $twitter->access_secret);
                $twitterUser = $connection->get('account/verify_credentials', ['include_email' => true]);

                if ($connection->getLastHttpCode() != 200 || empty($twitterUser->id)) {
                    $status = 400;
                    $message = trans('message.error.exception');
                    return $this->resProvider->apiJsonResponse($status, $message, null, null);
                }
                $providerEmail = $twitterUser->email ?? $request->user()->email;
                $providerId = $twitterUser->id;
                $providerUserName = $twitterUser->name ?? $twitterUser->screen_name;
            } else {
                $userSocial =   Socialite::driver($provider)->stateless()->user();
                if (empty($userSocial)) {
                    $status = 400;
                    $message = trans('message.error.exception');
                    return $this->resProvider->apiJsonResponse($status, $message, null, null);
                }
                $providerEmail = $userSocial->getEmail() ?? $request->user()->email;
                $providerUserName = $userSocial->getName();
                $providerId = $userSocial->getId();
            }
            $social_user = SocialUser::where(['provider_id' => $providerId, 'provider' => $provider])->first();
            if (!empty($social_user)) {
                $status = 403;
                $message = trans('message.social.already_linked');
                $data = [
                    "already_link_user" => $social_user,
                    "current_user" => $request->user(),
                ];
                return $this->resProvider->apiJsonResponse($status, $message, $data, null);
            }

            $this->createSocialUser($providerId, $providerEmail, $providerUserName, $provider, $request->user()->id);
            $status = 200;
            $message = trans('message.social.successfully_linked');
            $data = null;
            return $this->resProvider->apiJsonResponse($status, $message, $data, null);
        } catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }

    protected function createSocialUser($providerId, $email, $name, $provider, $userId)
    {

        $userSocial =  SocialUser::create([
            'user_id'       => $userId,
            'social_email'  => $email,
            'provider_id'   => $providerId,
            'provider'      => $provider,
            'social_name'   => $name,
        ]);

        return $userSocial;
    }


    /**
     * @OA\POST(path="/user/deactivate",
     *   tags={"User"},
     *   summary="For deactivate user",
     *   description="This api used to deactivate users",
     *   operationId="deactivateuser",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="password"
     *         ) 
     *    ),
     *    @OA\RequestBody(
     *     required=true,
     *     description="Request Body Json Parameter",
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *               @OA\Property(
     *                  property="user_id",
     *                  type="string"
     *              )
     *          )
     *     ),
     *   ),
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="object",
     *                                 @OA\Property(
     *                                         property="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="data",
     *                                         type="object"
     *                                    )
     *                                 )
     *                            ),
     *
     *    @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *    @OA\Response(
     *     response=403,
     *     description="Exception Throwable",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     *
     * )
     */

    public function deactivateUser(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getDeactivateUserValidationRules(), $this->validationMessages->getDeactivateUserValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {
            $user_to_deactivate = $request->user_id;
            // deactivate user
            $user = User::where('id', '=', $user_to_deactivate)->first();
            $user->status = 0;
            $user->save();
            // // delete all user supports 
            // $encode = Util::canon_encode($user_to_deactivate);
            // //get nicknames
            // $nicknames = Nickname::where('owner_code', '=', $encode)->get();
            $userNickname = Nickname::personNicknameArray();

            $as_of_time = time() + 100;
            $supportedTopic = Support::whereIn('nick_name_id', $userNickname)
                ->whereRaw("(start < $as_of_time) and ((end = 0) or (end > $as_of_time))")
                ->groupBy('topic_num')->orderBy('start', 'DESC')->get();
            if (count($supportedTopic) > 0) {
                foreach ($supportedTopic as $k => $v) {
                    $allUserSupports = Support::where('topic_num', $v->topic_num)
                        ->whereIn('nick_name_id', $userNickname)
                        ->whereRaw("(start < $as_of_time) and ((end = 0) or (end > $as_of_time))")
                        ->orderBy('support_order', 'ASC')
                        ->get();
                    if (count($allUserSupports) > 0) {
                        foreach ($allUserSupports as $key => $support) {
                            $currentSupport = Support::where('support_id', $support->support_id);
                            $currentSupport->update(array('end' => time()));
                        }
                    }
                }
            }

            // removing linked social accounts 
            SocialUser::where('user_id', $user_to_deactivate)->delete();
            $status = 200;
            $message = trans('message.success.user_remove');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        } catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }

    protected function getTokenResponse($generateToken, $user)
    {
        if ($generateToken->status_code == 200) {
            $data = [
                "auth" => $generateToken->data,
                "user" => new UserResource($user),
            ];
            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, $data, null);
        }
        return (new ErrorResource($generateToken))->response()->setStatusCode($generateToken->status_code);
    }


    public function postVerifyEmail(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getPostVerifyEmailValidationRules(), $this->validationMessages->getPostVerifyEmailValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {
            $provider = $request->provider;
            $providerUserName = '';

            $socialEmailVerify = SocialEmailVerify::where('code', '=', $request->code)->where('provider', '=', $provider)->where('email_verified', '=', 0)->first();
            if (empty($socialEmailVerify) || $request->otp != $socialEmailVerify->otp) {
                $status = 400;
                $message = trans('message.error.otp_not_match');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }

            $social_user = SocialUser::where(['provider_id' => $socialEmailVerify->provider_id, 'provider' => $socialEmailVerify->provider])->first();
            $providerUserName = $socialEmailVerify->first_name . ' ' . $socialEmailVerify->last_name;
            if (empty($social_user)) {
                $user = User::where(['email' => $socialEmailVerify->email])->first();
                if (empty($user)) {
                    $user = User::create([
                        'first_name'    => $socialEmailVerify->first_name,
                        'last_name'     => $socialEmailVerify->last_name,
                        'email'         => $socialEmailVerify->email,
                        'status'        => 1,
                        'is_active'     => 1
                    ]);
                    $nickname = $user->first_name . (empty($user->last_name) ? '' : '-') . $user->last_name;
                    $this->createNickname($user->id, $nickname);
                }
                $this->createSocialUser($socialEmailVerify->provider_id, $socialEmailVerify->email, $providerUserName, $socialEmailVerify->provider, $user->id);
            } else {
                $user = User::find($social_user->user_id);
            }
            $user->is_admin = ($user->type == 'admin') ? true : false;

            $postUrl = URL::to('/') . '/oauth/token';
            $payload = [
                'grant_type' => 'password',
                'client_id' => $request->client_id,
                'client_secret' => $request->client_secret,
                'username' => $user->email,
                'password' => env('PASSPORT_MASTER_PASSWORD'),
                'scope' => '*',
            ];
            $generateToken = Util::httpPost($postUrl, $payload);
            SocialEmailVerify::where('code', '=', $request->code)->where('provider', '=', $provider)->update(['otp' => '', 'email_verified' => 1, 'email' => $socialEmailVerify->email]);
            return $this->getTokenResponse($generateToken, $user);
        } catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }


    public function reSendOtpVerifyEmail(Request $request, Validate $validate)
    {

        $validationErrors = $validate->validate($request, $this->rules->getReSendOtpVerifyEmailValidationRules(), $this->validationMessages->getReSendOtpVerifyEmailValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {
            $provider = $request->provider;
            $socialEmailVerify = SocialEmailVerify::where('code', '=', $request->code)->where('provider', '=', $provider)->first();
            $socialEmailVerify->email = $request->email;
            if ($request->type == 'nameVerify') {
                $socialEmailVerify->first_name = $request->first_name;
                $socialEmailVerify->last_name = $request->last_name;
            }
            $authCode = mt_rand(100000, 999999);
            if ($socialEmailVerify) {
                $socialEmailVerify->otp = $authCode;
                $socialEmailVerify->update();
                try {
                    Event::dispatch(new SendOtpEvent($socialEmailVerify));
                } catch (Throwable $e) {
                    $status = 403;
                    $message = trans('message.error.otp_failed');
                    return $this->resProvider->apiJsonResponse($status, $message, null, $e->getMessage());
                }
                $status = 200;
                $message = trans('message.success.forgot_password');
            }
            return $this->resProvider->apiJsonResponse($status, $message, '', '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, $e->getMessage(), '', '');
        }
    }

    /**
     * @OA\Post(path="/login-as-user",
     *   tags={"User"},
     *   summary="get user access token for login as user",
     *   description="This is used to get user access token for login as user from admin.",
     *   operationId="loginAsUser",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *   ),
     *   @OA\RequestBody(
     *       required=true,
     *       description="login as user",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               @OA\Property(
     *                   property="id",
     *                   description="User id is required",
     *                   required=true,
     *                   type="integer",
     *               )
     *           )
     *       )  
     *    ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message"),
     *   @OA\Response(response=401, description="Unauthenticated")
     *  )
     */
    public function loginAsUser(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getLoginAsUserValidationRules(), $this->validationMessages->getLoginAsUserValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {
            $userId = $request->id;
            $user = User::find($userId);
            if (empty($user)) {
                $status = 400;
                $message = trans('message.error.user_not_exist');
                return $this->resProvider->apiJsonResponse($status, $message, null, "");
            }
            $token = $user->createToken('loginAsUser', ['*'])->accessToken;
            $data = [
                'access_token' => $token,
                'user' => new UserResource($user)
            ];
            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, $data, null);
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, $e->getMessage(), '', '');
        }
    }
}

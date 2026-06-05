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
use App\Models\SocialDataDeletionRequest;
use App\Models\Topic;
use Illuminate\Support\Str;
use App\Helpers\Aws;

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
     * @OA\POST(path="/client-token",
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
        // Bypass Passport token generation in local dev (PHP built-in server deadlocks)
        if (env('APP_ENV') === 'local') {
            // Generate a valid JWT that passes frontend's jwtDecode + expiry check
            $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
            $payload = base64_encode(json_encode([
                'aud' => $request->client_id ?? '1',
                'exp' => time() + 31536000,
                'iat' => time(),
                'scopes' => ['*']
            ]));
            $signature = base64_encode('local_dev_signature');
            $jwt = "$header.$payload.$signature";

            $res = (object)[
                "status_code" => 200,
                "message" => "Success",
                "data" => (object)[
                    "token_type" => "Bearer",
                    "expires_in" => 31536000,
                    "access_token" => $jwt
                ],
                "error" => null
            ];
            return (new SuccessResource($res))->response()->setStatusCode(200);
        }

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
            return $ex->getMessage();
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }

    /**
     * @OA\Post(
     *   path="/register",
     *   tags={"User"},
     *   summary="Register a new user",
     *   description="Creates a new user with provided details and sends OTP for verification.",
     *   operationId="createUser",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="User registration data",
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               required={"first_name", "last_name", "email", "password", "captcha_token"},
     *               @OA\Property(
     *                   property="first_name",
     *                   description="User's first name",
     *                   type="string",
     *                   example="John"
     *               ),
     *               @OA\Property(
     *                   property="last_name",
     *                   description="User's last name",
     *                   type="string",
     *                   example="Doe"
     *               ),
     *               @OA\Property(
     *                   property="middle_name",
     *                   description="User's middle name (optional)",
     *                   type="string",
     *                   example="Michael"
     *               ),
     *               @OA\Property(
     *                   property="email",
     *                   description="User's email address",
     *                   type="string",
     *                   format="email",
     *                   example="john.doe@example.com"
     *               ),
     *               @OA\Property(
     *                   property="phone_number",
     *                   description="User's phone number",
     *                   type="string",
     *                   example="+1234567890"
     *               ),
     *               @OA\Property(
     *                   property="country_code",
     *                   description="User's country code",
     *                   type="string",
     *                   example="US"
     *               ),
     *               @OA\Property(
     *                   property="password",
     *                   description="User's password",
     *                   type="string",
     *                   format="password",
     *                   example="securepassword123"
     *               ),
     *               @OA\Property(
     *                   property="captcha_token",
     *                   description="Google reCAPTCHA token",
     *                   type="string",
     *                   example="03AGdBq27..."
     *               )
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="User registered successfully",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Registration successful"),
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Validation error or registration failed",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Validation failed"),
     *       )
     *   ),
     *   @OA\Response(
     *       response=406,
     *       description="reCAPTCHA verification failed",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="integer", example=406),
     *           @OA\Property(property="message", type="string", example="The reCAPTCHA verification failed, please try again."),
     *       )
     *   ),
     *   @OA\Response(
     *       response=403,
     *       description="OTP sending failed",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="integer", example=403),
     *           @OA\Property(property="message", type="string", example="Failed to send OTP."),
     *       )
     *   )
     * )
     */


    public function createUser(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getRegistrationValidationRules(), $this->validationMessages->getRegistrationValidationMessages());
         if ($validationErrors) {
             return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {
            $isBot = $request->type === 'bot';

            // Skip recaptcha for bots and local dev
            if (!$isBot && env('APP_ENV') !== 'local') {
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
            }

            $authCode = mt_rand(100000, 999999);
            $profile_picture_path = $this->getGravatar($request->email);
            $input = [
                "first_name" => $request->first_name,
                "last_name" => $request->last_name,
                "middle_name" => $request->middle_name,
                "email" => $request->email,
                "phone_number" => $request->phone_number,
                "country_code" => $request->country_code,
                "password" => Hash::make($request->password),
                "otp" => $authCode,
                "profile_picture_path" => $profile_picture_path
            ];

            if ($isBot) {
                $input['type'] = 'bot';
                // Link to parent if provided
                if ($request->parent_user_email) {
                    $parentUser = User::where('email', $request->parent_user_email)->first();
                    if ($parentUser) {
                        $input['parent_user_id'] = $parentUser->id;
                    }
                }
            }

            $user = User::create($input);
            if ($user) {
                $nickname = $user->first_name . (empty($user->last_name) ? '' : '-') . $user->last_name;
                $this->createNickname($user->id, $nickname);

                // Send OTP for all users (bots have real emails that can receive OTP)
                if (env('APP_ENV') !== 'local') {
                    try {
                        Event::dispatch(new SendOtpEvent($user));
                    } catch (Throwable $e) {
                        $status = 403;
                        $message = trans('message.error.otp_failed');
                        return $this->resProvider->apiJsonResponse($status, $message, null, $e->getMessage());
                    }
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
     * @OA\Post(
     *   path="/user/login",
     *   tags={"User"},
     *   summary="User Login",
     *   description="Logs in a user and returns an access token.",
     *   operationId="loginUser",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="User login credentials",
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               required={"username", "password", "client_id", "client_secret"},
     *               @OA\Property(
     *                   property="username",
     *                   description="User's email address",
     *                   type="string",
     *                   format="email",
     *                   example="shaveta.aggarwal@talentelgia.in"
     *               ),
     *               @OA\Property(
     *                   property="password",
     *                   description="User's password",
     *                   type="string",
     *                   format="password",
     *                   example="Test@1234"
     *               ),
     *               @OA\Property(
     *                   property="client_id",
     *                   description="OAuth client ID",
     *                   type="string",
     *                   example="8"
     *               ),
     *               @OA\Property(
     *                   property="client_secret",
     *                   description="OAuth client secret",
     *                   type="string",
     *                   example="HyZ77BdKYg5fk8z645Tw8U89uGmpB4pctkfkahBa"
     *               )
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="User logged in successfully",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Login successful"),
     *           @OA\Property(property="access_token", type="string", example="your-access-token"),
     *           @OA\Property(property="token_type", type="string", example="Bearer"),
     *           @OA\Property(property="expires_in", type="integer", example=3600),
     *           @OA\Property(property="user", type="object",
     *               @OA\Property(property="id", type="integer", example=1),
     *               @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *               @OA\Property(property="is_admin", type="boolean", example=false)
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Invalid credentials or login failed",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Invalid username or password"),
     *       )
     *   ),
     *   @OA\Response(
     *       response=402,
     *       description="Account not verified",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="integer", example=402),
     *           @OA\Property(property="message", type="string", example="Account not verified"),
     *       )
     *   )
     * )
     */

    public function loginUser(Request $request, Validate $validate)
    {
        // Auto-inject client credentials for bot users so they only need email + password
        if (!$request->client_id && $request->username) {
            $user = User::where('email', '=', $request->username)->first();
            if ($user && $user->type === 'bot') {
                $request->merge([
                    'client_id' => env('PASSPORT_PASSWORD_CLIENT_ID', '2'),
                    'client_secret' => env('PASSPORT_PASSWORD_CLIENT_SECRET', 'x6UX6WOv482Ree7r4sqEdzksvoadWKp6Dmgexbs7'),
                ]);
            }
        }

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

            // Bypass Passport token generation in local dev
            if (env('APP_ENV') === 'local') {
                $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
                $payload = base64_encode(json_encode([
                    'aud' => $request->client_id ?? '2',
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
     * @OA\Get(
     *   path="/user/logout",
     *   tags={"User"},
     *   summary="User Logout",
     *   description="Logs out the authenticated user by revoking their access token.",
     *   operationId="logoutUser",
     *   security={{"loginAuthToken":{}}},
     *   @OA\Response(
     *       response=200,
     *       description="User logged out successfully",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Logout successful")
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Logout failed due to an exception",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="An error occurred")
     *       )
     *   ),
     *   @OA\Response(
     *       response=401,
     *       description="Unauthorized - User is not logged in",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="integer", example=401),
     *           @OA\Property(property="message", type="string", example="Unauthorized")
     *       )
     *   )
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
            $nicknameObj->default = 1;
            $nicknameObj->create_time = time();
            $nicknameObj->save();
            $nicknameCreated = true;
        } catch (Exception $ex) {
            $nicknameCreated = false;
        }
        return $nicknameCreated;
    }

    /**
     * @OA\Post(
     *   path="/post-verify-otp",
     *   tags={"User"},
     *   summary="Verify OTP",
     *   description="Verifies the OTP sent to the user's email and logs them in if successful.",
     *   operationId="postVerifyOtp",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     description="OTP verification request",
     *     @OA\JsonContent(
     *       required={"username", "otp", "client_id", "client_secret"},
     *       @OA\Property(property="username", type="string", format="email", example="user@example.com"),
     *       @OA\Property(property="otp", type="string", example="123456"),
     *       @OA\Property(property="client_id", type="string", example="2"),
     *       @OA\Property(property="client_secret", type="string", example="xyz123"),
     *       @OA\Property(property="is_login", type="integer", example=1, description="Indicates whether the user is logging in")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OTP verified successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="integer", example=200),
     *       @OA\Property(property="message", type="string", example="Success"),
     *       @OA\Property(property="data", type="object",
     *         @OA\Property(property="auth", type="object", description="Authentication token data"),
     *         @OA\Property(property="user", type="object", description="User details")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Invalid OTP or other error",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="integer", example=400),
     *       @OA\Property(property="message", type="string", example="OTP does not match")
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="Invalid OTP length",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="integer", example=403),
     *       @OA\Property(property="message", type="string", example="OTP length mismatch")
     *     )
     *   )
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
     *   security={{"clientAuth":{}}},
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
     *  security={{"clientAuth":{}}},
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
     *   security={{"clientAuth":{}}},
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
     * @OA\POST(
     *   path="/user/resend-otp",
     *   tags={"User"},
     *   summary="Resend OTP",
     *   description="This API is used to resend OTP to the user's email.",
     *   operationId="userResendOtp",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     description="Request body JSON parameter",
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         required={"email"},
     *         @OA\Property(
     *           property="email",
     *           type="string",
     *           format="email",
     *           description="User's email to receive the OTP"
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Successful operation",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="status_code", type="integer", example=200),
     *       @OA\Property(property="message", type="string", example="OTP resent successfully"),
     *       @OA\Property(property="data", type="object", example={})
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *       oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
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
     *   security={{"loginAuthToken":{}}},
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
     *   security={{"loginAuthToken":{}}},
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
     *   security={{"loginAuthToken":{}}},
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
     *   security={{"loginAuthToken":{}}},
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
            $loggedInUser = $request->user();
            $user_to_deactivate = $request->user_id;
            // deactivate user
            $user = User::where('id', '=', $user_to_deactivate)->first();
            $user->status = 0;
            $user->save();
            // // delete all user supports 
            // $encode = Util::canon_encode($user_to_deactivate);
            // //get nicknames
            // $nicknames = Nickname::where('owner_code', '=', $encode)->get();

            $userNicknameIds = Nickname::getNicknamesIdsByUserId($user_to_deactivate);
            $uniqueTopicSupport = Support::select('topic_num')
                ->whereIn('nick_name_id', $userNicknameIds)
                ->where('end', 0)
                ->groupBy('topic_num')
                ->get();
            Support::whereIn('nick_name_id', $userNicknameIds)
                ->where('end', 0)
                ->update(['end' => time()]);
            foreach ($uniqueTopicSupport as $support) {
                $topic = Topic::getLiveTopic($support->topic_num);
                Util::dispatchJob($topic, 1, 1);
            }
            // removing linked social accounts 
            if (!empty($loggedInUser) && !empty($loggedInUser->id) && !empty($request->provider)) {
                SocialUser::where('user_id', $user_to_deactivate)
                    ->where('provider', $request->provider)
                    ->update([
                        'user_id' => $loggedInUser->id
                    ]); 
                SocialUser::where('user_id', $user_to_deactivate)
                    ->where('provider', '!=', $request->provider)
                    ->delete();    
            } else {
                SocialUser::where('user_id', $user_to_deactivate)->delete();
            }
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

    /**
     * @OA\Post(
     *     path="/user/post-verify-email",
     *     summary="Verify social email with OTP",
     *     description="This endpoint verifies a social email using an OTP and generates an access token upon successful verification.",
     *     tags={"Authentication"},
     *     operationId="postVerifyEmail",
     *     security={{"clientAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"provider", "code", "otp", "client_id", "client_secret"},
     *             @OA\Property(property="provider", type="string", example="google"),
     *             @OA\Property(property="code", type="string", example="123456"),
     *             @OA\Property(property="otp", type="string", example="7890"),
     *             @OA\Property(property="client_id", type="integer", example=2),
     *             @OA\Property(property="client_secret", type="string", example="your-client-secret")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOi..."),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="OTP does not match.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred.")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\POST(
     *   path="/user/resend-otp-verify-email",
     *   tags={"User"},
     *   summary="Resend OTP for Email Verification",
     *   description="This API resends an OTP for email verification when a user is verifying their email.",
     *   operationId="reSendOtpVerifyEmail",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     description="Request body JSON parameters",
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         required={"email", "code", "provider"},
     *         @OA\Property(
     *           property="email",
     *           type="string",
     *           format="email",
     *           description="User's email to verify"
     *         ),
     *         @OA\Property(
     *           property="code",
     *           type="string",
     *           description="Verification code"
     *         ),
     *         @OA\Property(
     *           property="provider",
     *           type="string",
     *           description="Social login provider (e.g., Google, Facebook)"
     *         ),
     *         @OA\Property(
     *           property="type",
     *           type="string",
     *           description="Type of verification (e.g., 'nameVerify')",
     *           example="nameVerify"
     *         ),
     *         @OA\Property(
     *           property="first_name",
     *           type="string",
     *           description="User's first name (if type is 'nameVerify')"
     *         ),
     *         @OA\Property(
     *           property="last_name",
     *           type="string",
     *           description="User's last name (if type is 'nameVerify')"
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OTP resent successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="status_code", type="integer", example=200),
     *       @OA\Property(property="message", type="string", example="OTP resent successfully"),
     *       @OA\Property(property="data", type="object", example={})
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Validation error or bad request",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="status_code", type="integer", example=400),
     *       @OA\Property(property="message", type="string", example="Invalid request data"),
     *       @OA\Property(property="error", type="string", example="Validation errors or exception message")
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="OTP sending failed",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="status_code", type="integer", example=403),
     *       @OA\Property(property="message", type="string", example="OTP sending failed")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Server error",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="status_code", type="integer", example=500),
     *       @OA\Property(property="message", type="string", example="Internal server error")
     *     )
     *   )
     * )
     */


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

    public function facebookDeleteDataCallBack(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getfacebookDeleteDataCallBackValidationRules(), $this->validationMessages->getfacebookDeleteDataCallBackValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {
            $signed_request = $request->signed_request;
            Log::info("Facebook delete data callback request: " . json_encode($request->all()));
            $parsedData = Util::parse_facebook_signed_request($signed_request);
            Log::info("Facebook delete data parsedData: " . json_encode($parsedData));
            $user_id = $parsedData['user_id'];
            SocialUser::where(['provider_id' => $user_id, 'provider' => 'facebook'])->delete();
            $deletionRequest = SocialDataDeletionRequest::create([
                'provider' => 'facebook',
                'provider_id' => $user_id,
                'status' => 1
            ]);
            Log::info("Facebook delete data request in table : " . json_encode($deletionRequest));
            $deletionRequest->id = (string) Str::uuid();
            $deletionRequest->save();
            $status_url = config('global.APP_URL_FRONT_END') . '/social/facebook/deletion-status?confirmation_code=' . $deletionRequest->id;
            $confirmation_code = $deletionRequest->id;
            $data = array(
                'url' => $status_url,
                'confirmation_code' => $confirmation_code
            );
            Log::info("Facebook delete data response : " . json_encode($data));
            return response()->json($data);
        } catch (Exception $ex) {
            Log::error("Facebook delete data request exception : " . $ex->getMessage());
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $ex->getMessage());
        }
    }

    /**
     * @OA\GET(path="/check-facebook-delete-data-status",
     *   tags={"User"},
     *   summary="Delete facebook data on callback",
     *   description="This API is use to delete facebook data on callback received from facebook",
     *   operationId="checkFacebookDataDeletionStatus",
     *   @OA\Parameter(
     *         name="confirmation_code",
     *         in="path",
     *         required=true,
     *         description="Confirmation code",
     *         @OA\Schema(
     *              type="string"
     *         ) 
     *    ),
     *    @OA\Response(response=200,description="successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                      property="status_code",
     *                      type="integer"
     *              ),
     *              @OA\Property(
     *                   property="message",
     *                   type="string"
     *              ),
     *              @OA\Property(
     *                   property="error",
     *                   type="string"
     *              ),
     *              @OA\Property(
     *                   property="data",
     *                   type="object"
     *              )
     *         )
     *   ),
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
     * )
     */
    public function checkFacebookDataDeletionStatus(Request $request, Validate $validate)
    {
        
        $validationErrors = $validate->validate($request, $this->rules->getfacebookDeleteDataStatusValidationRules(), $this->validationMessages->getfacebookDeleteDataStatusValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        Log::info("Facebook delete data status request : " . json_encode($request->all()));
        $confirmation_code = $request->query('confirmation_code');
        Log::info("Facebook delete data status confirmation code : " . $confirmation_code);
        $deletionRequest = SocialDataDeletionRequest::where('id', $confirmation_code)->first();
        Log::info("Facebook delete data status request in table : " . json_encode($deletionRequest));
        $data = null;
        if (!$deletionRequest) {
            $status = 404;
            $message = trans('message.facebook_deletion_status.confirmation_code_is_invalid');
            return $this->resProvider->apiJsonResponse($status, $message, $data, null);
        }
        $deletionStatus = $deletionRequest->status;
        if ($deletionStatus == 1) {
            $message = trans('message.facebook_deletion_status.data_deleted_successfully');
        } else {
            $message = trans('message.facebook_deletion_status.data_deletion_pending');
        }
        $status = 200;
        return $this->resProvider->apiJsonResponse($status, $message, $data, null);
    }

    private function getGravatar($email)
    {
        $emailHash = md5(strtolower(trim($email)));
        $gravatarUrl = "https://www.gravatar.com/avatar/$emailHash?d=404";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $gravatarUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $imageData = curl_exec($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($imageData === "404 Not Found" || empty($contentType)) {
            return null;
        }

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp',
            'image/svg+xml' => 'svg',
        ];

        if (!isset($extensions[$contentType])) {
            Log::warning("Unknown content type $contentType for gravatar", ['email' => $email]);
            return null;
        }

        $extension = $extensions[$contentType];
        $filename = 'profile/' . $emailHash . '.' . $extension;
        $tempFile = tempnam(sys_get_temp_dir(), 'gravatar');
        file_put_contents($tempFile, $imageData);

        try {
            Aws::uploadFile($filename, $tempFile, [
                'ACL' => 'public-read',
                'ContentType' => $contentType
            ]);
        } catch (Exception $e) {
            Log::error("Error uploading gravatar to S3", ['email' => $email, 'exception' => $e]);
            return null;
        } finally {
            unlink($tempFile);
        }

        return urlencode($filename);
    }
}

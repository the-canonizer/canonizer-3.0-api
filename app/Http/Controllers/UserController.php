<?php

namespace App\Http\Controllers;

use Exception;
use Throwable;
use App\Models\User;
use App\Facades\Util;
use App\Models\Country;
use App\Jobs\SendOtpJob;
use App\Models\Nickname;
use App\Jobs\WelcomeMail;
use App\Models\SocialUser;
use App\Events\SendOtpEvent;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Events\WelcomeMailEvent;
use App\Helpers\ResponseInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use Illuminate\Support\Facades\Event;
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

    public function clientToken(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getTokenValidationRules(), $this->validationMessages->getTokenValidationMessages());
        if( $validationErrors ){
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        try {
            $postUrl = URL::to('/') . '/oauth/token';
            $payload = [
                'grant_type' => 'client_credentials',
                'client_id' => $request->client_id,
                'client_secret' => $request->client_secret,
                'scope' => '*',
            ];
            $generateToken = Util::httpPost($postUrl, $payload);
            if( $generateToken->status_code == 200 ){
                return (new SuccessResource($generateToken))->response()->setStatusCode(200);
            }
            return (new ErrorResource($generateToken))->response()->setStatusCode($generateToken->status_code);

        } catch( Exception $ex ) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message,null, null);
        }
    }

    /**
     * @OA\Post(path="/register",
     *   tags={"user"},
     *   summary="Create user",
     *   description="This is used to register the user.",
     *   operationId="createUser",
     *   @OA\RequestBody(
     *       description="Created user object",
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *              @OA\Property(
     *                  property="first_name",
     *                  description="First Name of the User",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="middle_name",
     *                  type="string",
     *                  description="Middle Name of the User"
     *              ),
     *              @OA\Property(
     *                  property="last_name",
     *                  type="string",
     *                  description="Last Name of the User"
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  description="Email Id of the User"
     *              ),
     *              @OA\Property(
     *                  property="phone_number",
     *                  type="string",
     *                  description="Phone Number of the User"
     *              ),
     *              @OA\Property(
     *                  property="password",
     *                  type="string",
     *                  description="Password of the User"
     *              ),
     *              @OA\Property(
     *                  property="confirm_password",
     *                  type="string",
     *                  description="Confirm password string"
     *              )
     *          )
     *       )
     *   ),
     *
     *   @OA\Response(
     *      response=200,
     *      description="success",
     *      @OA\Schema(ref="#/components/schemas/User")
     *   ),
     *   @OA\Response(
     *      response=400,
     *      description="Something went wrong",
     *      @OA\Schema(ref="#/components/schemas/ExceptionRes")
     *   ),
     *   @OA\Response(
     *      response=401,
     *      description="Unauthorised request"
     *   )
     *
     *
     * )
     */
    public function createUser(Request $request, Validate $validate)
    {

        $validationErrors = $validate->validate($request, $this->rules->getRegistrationValidationRules(), $this->validationMessages->getRegistrationValidationMessages());

        if( $validationErrors ){
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {

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

            if($user){
                 $nickname = $user->first_name."-".$user->last_name;
                 $this->createNickname($user->id, $nickname);
                try {
                    Event::dispatch(new SendOtpEvent($user));
                } catch (Throwable $e) {
                    $status = 403;
                    $message = trans('message.error.otp_failed');
                    return $this->resProvider->apiJsonResponse($status, $message,null, $e->getMessage());
                }
                $status = 200;
                $message = trans('message.success.reg_success');
                return $this->resProvider->apiJsonResponse($status, $message,null, null);
            }else{
                $status = 400;
                $message = trans('message.error.reg_failed');
                return $this->resProvider->apiJsonResponse($status, $message,null, null);
            }

        } catch (Exception $e) {
            $status = 400;
            $message = $e->getMessage();
            return $this->resProvider->apiJsonResponse($status, $message,null, null);
        }
    }


    /**
     * @OA\Get(path="/user/login",
     *   tags={"user"},
     *   summary="Logs user into the system",
     *   description="",
     *   operationId="loginUser",
     *   @OA\Parameter(
     *     name="username",
     *     required=true,
     *     in="query",
     *     description="The user name for login",
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="password",
     *     in="query",
     *     @OA\Schema(
     *         type="string",
     *     ),
     *     description="The password for login in clear text",
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="successful operation",
     *     @OA\Schema(type="string"),
     *     @OA\Header(
     *       header="X-Rate-Limit",
     *       @OA\Schema(
     *           type="integer",
     *           format="int32"
     *       ),
     *       description="calls per hour allowed by the user"
     *     ),
     *     @OA\Header(
     *       header="X-Expires-After",
     *       @OA\Schema(
     *          type="string",
     *          format="date-time",
     *       ),
     *       description="date in UTC when token expires"
     *     )
     *   ),
     *   @OA\Response(response=400, description="Invalid username/password")
     * )
     */
    public function loginUser(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getLoginValidationRules(), $this->validationMessages->getLoginValidationMessages());
        if( $validationErrors ){
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {
            $username = $request->username;
            $password = $request->password;
            $user = User::where('email', '=', $username)->first();

            if(empty($user)){
                $status = 401;
                $message = trans('message.error.email_not_registered');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }
            if(!Hash::check($password, $user->password)){
                $status = 401;
                $message = trans('message.error.password_not_match');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }

            if($user->status != 1){
                $status = 401;
                $message = trans('message.error.account_not_verified');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }

            $postUrl = URL::to('/') . '/oauth/token';
            $payload = [
                'grant_type' => 'password',
                'client_id' => $request->client_id,
                'client_secret' => $request->client_secret,
                'username' => $username,
                'password' => $password,
                'scope' => '*',
            ];
            $generateToken = Util::httpPost($postUrl, $payload);
            if($generateToken->status_code == 200){
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
            return $this->resProvider->apiJsonResponse(400, $e->getMessage(), null , null);
        }
    }

    /**
     * @OA\Get(path="/user/logout",
     *   tags={"user"},
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
            return $this->resProvider->apiJsonResponse($status, $message,null, null);
        }
    }

    /**
     * @OA\Get(path="/user/{username}",
     *   tags={"user"},
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
     *   tags={"user"},
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
     *   tags={"user"},
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

    protected function createNickname($userID, $nickname) {
        $nicknameCreated = false;
        if(empty($userID) || empty($nickname)) {

            return $nicknameCreated;
        }
        // Check whether user exists or not for the given id
        $user = User::getUserById($userID);


        if(empty($user)) {
            return $nicknameCreated;
        }

        // Check whether nickname exists for the given nickname
        $isExists = Nickname::isNicknameExists($nickname);

        if($isExists === true) {
            $randNumber = mt_rand(000, 999);
            $nickname = $nickname.$randNumber;
        }

        try {

            // Create nickname
            $nicknameObj = new Nickname();
            $nicknameObj->owner_code = Util::canon_encode($userID);
            $nicknameObj->nick_name = $nickname;
            $nicknameObj->private = 0;
            $nicknameObj->create_time = time();
            $nicknameObj->save();
            $nicknameCreated = true;

        } catch(Exception $ex) {
            $nicknameCreated = false;
        }
        return $nicknameCreated;
    }

    /**
     * @OA\Delete(path="/verifyOtp",
     *   tags={"postVerifyOtp"},
     *   summary="post Verify Otp",
     *   description="This use to verify user Otp.",
     *   operationId="verifyOtp",
     *   @OA\Parameter(
     *     name="username",
     *     in="path",
     *     description="The name that needs to be deleted",
     *     required=true,
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Response(response=400, description="Invalid username supplied"),
     *   @OA\Response(response=404, description="User not found")
     * )
     */

    public function postVerifyOtp(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getVerifyOtpValidationRules(), $this->validationMessages->getVerifyOtpValidationMessages());
        if( $validationErrors ){
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        try {

            $user = User::where('email', '=', $request->username)->first();

            if(empty($user) || $request->otp != $user->otp){
                $status = 401;
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
            if($generateToken->status_code == 200){
                $userRes = User::where('email', '=', $request->username)->update(['otp' => '','status' => 1]);

                Event::dispatch(new WelcomeMailEvent($user));

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

    public function socialLogin(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getSocialLoginValidationRules(), $this->validationMessages->getSocialLoginValidationMessages());
        if( $validationErrors ){
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        try {
            $provider = $request->provider;
            $redirect = Socialite::with($provider)->stateless()->redirect()->getTargetUrl();
            if(empty($redirect)) {
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
        }catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }

    public function socialCallback(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getSocialCallbackValidationRules(), $this->validationMessages->getSocialCallbackValidationMessages());
        if( $validationErrors ){
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        try {
            $provider = $request->provider;
            $userSocial =   Socialite::driver($provider)->stateless()->user();
            if(empty($userSocial)){
                $status = 400;
                $message = trans('message.error.exception');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }
            $user_email = $userSocial->getEmail();
            $social_name = $userSocial->getName();
            $user = User::where(['email' => $user_email])->first();
            if(empty($user)){
                $splitName = Util::split_name($social_name);
                $user = User::create([
                    'first_name'    => $splitName[0],
                    'last_name'     => $splitName[1],
                    'email'         => $user_email
                ]);
                SocialUser::create([
                    'user_id'       => $user->id,
                    'social_email'  => $user_email,
                    'provider_id'   => $userSocial->getId(),
                    'provider'      => $provider,
                    'social_name'   => $social_name,
                ]);
                $nickname = $user->first_name.'-'.$user->last_name;
                $this->createNickname($user->id, $nickname);
            }
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
            if($generateToken->status_code == 200){
                $data = [
                    "auth" => $generateToken->data,
                    "user" => new UserResource($user),
                ];
                $status = 200;
                $message = trans('message.success.success');
                return $this->resProvider->apiJsonResponse($status, $message, $data, null);
            }
            return (new ErrorResource($generateToken))->response()->setStatusCode($generateToken->status_code);

        }catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }


    public function countryList(Request $request)
    {

        try {

            $result = Country::where('status', 1)->get();

            if(empty($result)){
                $status = 400;
                $message = trans('message.error.exception');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }

            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, $result, null);

        }catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }


        /**
     * @OA\Post(path="/user/reSendOtp",
     *   tags={"user"},
     *   summary="User Resend Otp",
     *   description="This api used to Resend Otp",
     *   operationId="userReSend",
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
                $user->status = 0;
                $user->update();
                try {
                    Event::dispatch(new SendOtpEvent($user));
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
}

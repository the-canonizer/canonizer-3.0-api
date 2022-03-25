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
                $status = 402;
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
            return $this->resProvider->apiJsonResponse($status, $message,null, null);
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
            $social_user = SocialUser::where(['social_email' => $user_email, 'provider' => $provider])->first();
            if($request->user()){
                if (isset($social_user) && isset($social_user->user_id)) {
                    $status = 403;
                    $message = trans('message.social.already_linked');
                    $data = null;
				}else{
                    $socialUser = SocialUser::create([
						'user_id'       => $request->user()->id,
						'social_email'  => $user_email,
						'provider_id'   => $userSocial->getId(),
						'provider'      => $provider,
						'social_name'   => $social_name,
					]);
                    $status = 200;
                    $message = trans('message.social.successfully_linked');
                    $data = [
                        "auth" => null,
                        "user" => null,
                        "type" => "social_link"
                    ];
                }
                return $this->resProvider->apiJsonResponse($status, $message, $data, null);
            }else{
                $user = User::where(['email' => $user_email])->first();
                if(empty($user)){
                    $splitName = Util::split_name($social_name);
                    $user = User::create([
                        'first_name'    => $splitName[0],
                        'last_name'     => $splitName[1],
                        'email'         => $user_email
                    ]);
                    $nickname = $user->first_name.'-'.$user->last_name;
                    $this->createNickname($user->id, $nickname);
                }
                if (!isset($social_user) && !isset($social_user->user_id)) {
                    SocialUser::create([
                        'user_id'       => $user->id,
                        'social_email'  => $user_email,
                        'provider_id'   => $userSocial->getId(),
                        'provider'      => $provider,
                        'social_name'   => $social_name,
                    ]);
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
            }
           
            return (new ErrorResource($generateToken))->response()->setStatusCode($generateToken->status_code);

        }catch (Exception $ex) {
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

    public function SocialDelete(Request $request, $id)
    {
        $loggedInUser = $request->user();
        try {
            $social_user = SocialUser::where('id', $id)->where('user_id',$loggedInUser->id)->delete();
            $status = 200;
            $message = trans('message.social.unlink_social_user');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $ex->getMessage());
        }
    }

}

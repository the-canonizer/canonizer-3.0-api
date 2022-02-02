<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Facades\Util;
use App\Models\Country;
use App\Jobs\SendOtpJob;
use App\Models\Nickname;
use App\Jobs\WelcomeMail;
use App\Models\SocialUser;
use App\Events\SendOtpEvent;
use App\Events\WelcomeMailEvent;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Http\Request\ValidationMessages;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Resources\Authentication\UserResource;
use Illuminate\Support\Facades\Event;

class UserController extends Controller
{
    private ValidationRules $rules;

    private ValidationMessages $validationMessages;

    public function __construct()
    {
        $this->rules = new ValidationRules;
        $this->validationMessages = new ValidationMessages;
        $this->authTokenUrl = '/oauth/token';
    }

     /**
    * @OA\Post(path="/client_token",
    *   tags={"user"},
    *   summary="For get client auth details",
    *   description="This api used to get password client id and client secrect.",
    *   operationId="clienttoken",
    *   @OA\RequestBody(
    *       required=true,
    *       description="get client auth details",
    *       @OA\MediaType(
    *           mediaType="application/json",
    *           @OA\Schema(
    *                 @OA\Property(
    *                     property="client_id",
    *                     description="The Auth Client Id",
    *                     required=true,
    *                     type="string",
    *                 ),
    *                 @OA\Property(
    *                     property="client_secret",
    *                     description="The Auth Client secret",
    *                     required=true,
    *                     type="string",
    *                 )
    *       )
    *   ),
    *   @OA\Response(response=200,description="success",
    *                             @OA\JsonContent(
    *                                 type="array",
    *                                    @OA\Items(
    *                                         name="data",
    *                                         type="array"
    *                                    ),
    *                                    @OA\Items(
    *                                         name="status_code",
    *                                         type="integer"
    *                                    ),
    *                                    @OA\Items(
    *                                         name="message",
    *                                         type="boolean"
    *                                    ),
    *                                    @OA\Items(
    *                                         name="error",
    *                                         type="array"
    *                                    )
    *                                 )
    *                            )
    *   @OA\Response(response=400, description="Something went wrong",
    *                             @OA\JsonContent(
    *                                 type="array",
    *                                 @OA\Items(
    *                                         name="data",
    *                                         type="array"
    *                                    ),
    *                                    @OA\Items(
    *                                         name="status_code",
    *                                         type="integer"
    *                                    ),
    *                                    @OA\Items(
    *                                         name="message",
    *                                         type="boolean"
    *                                    ),
    *                                    @OA\Items(
    *                                         name="error",
    *                                         type="array"
    *                                    )
    *                                 )
    *                             )
    *                  )
    * )
    */
    public function clientToken(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getTokenValidationRules(), $this->validationMessages->getTokenValidationMessages());
        if( $validationErrors ){
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        try {
            $postUrl = URL::to('/') . $this->authTokenUrl;
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
            $res = (object)[
                "status_code" => 400,
                "message"     => "Something went wrong",
                "error"       => null,
                "data"        => $ex->getMessage()
            ];
            return (new ErrorResource($res))->response()->setStatusCode(400);
        }
    }

    /**
    * @OA\Post(path="/register",
    *   tags={"user"},
    *   summary="Create user",
    *   description="This is used to register the user.",
    *   operationId="createUser",
    *   @OA\RequestBody(
    *       required=true,
    *       description="user register api",
    *       @OA\MediaType(
    *           mediaType="application/json",
    *           @OA\Schema(
    *                 @OA\Property(
    *                     property="client_id",
    *                     description="The Auth Client Id",
    *                     required=true,
    *                     type="string",
    *                 ),
    *                 @OA\Property(
    *                     property="client_secret",
    *                     description="The Auth Client secret",
    *                     required=true,
    *                     type="string",
    *                 )
    *       )
    *   ),
    *   @OA\RequestBody(
    *       required=true,
    *       description="register the user",
    *       @OA\MediaType(
    *           mediaType="application/json",
    *           @OA\Schema(
    *                 @OA\Property(
    *                     property="first_name",
    *                     description="User First Name.",
    *                     required=true,
    *                     type="string",
    *                 ),
    *                 @OA\Property(
    *                     property="last_name",
    *                     description="User last Name.",
    *                     required=true,
    *                     type="string",
    *                 ),
    *                 @OA\Property(
    *                     property="middle_name",
    *                     description="User middle Name.",
    *                     required=true,
    *                     type="string",
    *                 ),
    *                 @OA\Property(
    *                     property="email",
    *                     description="User email.",
    *                     required=true,
    *                     type="string",
    *                 ),
    *                 @OA\Property(
    *                     property="phone_number",
    *                     description="User phone number.",
    *                     required=true,
    *                     type="string",
    *                 ),
    *                 @OA\Property(
    *                     property="country_code",
    *                     description="User country code.",
    *                     required=true,
    *                     type="string",
    *                 ),
    *                 @OA\Property(
    *                     property="password",
    *                     description="User password.",
    *                     required=true,
    *                     type="string",
    *                 ),
    *                 @OA\Property(
    *                     property="password_confirmation",
    *                     description="User confirm password",
    *                     required=true,
    *                     type="string",
    *                 ),
    *       )
    *   ),
    *   @OA\Response(response=200,description="success",
    *                             @OA\JsonContent(
    *                                 type="array",
    *                                    @OA\Items(
    *                                         name="data",
    *                                         type="array"
    *                                    ),
    *                                    @OA\Items(
    *                                         name="status_code",
    *                                         type="integer"
    *                                    ),
    *                                    @OA\Items(
    *                                         name="message",
    *                                         type="boolean"
    *                                    ),
    *                                    @OA\Items(
    *                                         name="error",
    *                                         type="array"
    *                                    )
    *                                 )
    *                            )
    *   @OA\Response(response=400, description="Something went wrong",
    *                             @OA\JsonContent(
    *                                 type="array",
    *                                 @OA\Items(
    *                                         name="data",
    *                                         type="array"
    *                                    ),
    *                                    @OA\Items(
    *                                         name="status_code",
    *                                         type="integer"
    *                                    ),
    *                                    @OA\Items(
    *                                         name="message",
    *                                         type="boolean"
    *                                    ),
    *                                    @OA\Items(
    *                                         name="error",
    *                                         type="array"
    *                                    )
    *                                 )
    *                             )
    *                  )
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

                Event::dispatch(new SendOtpEvent($user));

                    $response = (object)[
                        "status_code" => 200,
                        "message"     => "Otp sent successfully on your registered Email Id",
                        "error"       => null,
                        "data"        => null
                    ];
                    return (new SuccessResource($response))->response()->setStatusCode(200);

            }else{
                $res = (object)[
                    "status_code" => 400,
                    "message"     => "Your Registration failed Please try again!",
                    "error"       => null,
                    "data"        => null
                ];
                return (new ErrorResource($res))->response()->setStatusCode(400);
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

            if(empty($user) && !Hash::check($password, $user->password)){
                $res = (object)[
                    "status_code" => 401,
                    "message"     => "Email or password does not match",
                    "error"       => null,
                    "data"        => null
                ];
                return (new ErrorResource($res))->response()->setStatusCode(401);
            }

            $postUrl = URL::to('/') . $this->authTokenUrl;
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
                $response = (object)[
                    "status_code" => 200,
                    "message"     => "Success",
                    "error"       => null,
                    "data"        => $data
                ];
                return (new SuccessResource($response))->response()->setStatusCode(200);
            }
            return (new ErrorResource($generateToken))->response()->setStatusCode($generateToken->status_code);
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
            $res = (object)[
                "status_code" => 200,
                "message"     => "Success",
                "error"       => null,
                "data"        => null
            ];
            return (new SuccessResource($res))->response()->setStatusCode(200);
        } catch (Exception $ex) {
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
     *   @OA\Response(response=400, description="Invalid username supplied"),
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
     * @OA\Post(path="/verifyOtp",
     *   tags={"user"},
     *   summary="For verify Otp after login details",
     *   description="This api used to verify Otp after login details.",
     *   operationId="verifyOtp",
     *   @OA\SecurityScheme(
     *      type="http",
     *      description="Authentication Bearer Token",
     *      name="Authentication Bearer Token",
     *      in="header",
     *      scheme="bearer",
     *      bearerFormat="passport",
     *      securityScheme="apiAuth",
     *   )
     *   @OA\RequestBody(
     *       required=true,
     *       description="verify Otp after login details",
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *                 @OA\Property(
     *                     property="client_id",
     *                     description="The Auth Client Id",
     *                     required=true,
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="client_secret",
     *                     description="The Auth Client secret",
     *                     required=true,
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="otp",
     *                     description="one time password for validation",
     *                     required=true,
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="username",
     *                     description="email for validate user",
     *                     required=true,
     *                     type="string",
     *                 ),
     *       )
     *   ),
     *   @OA\Response(response=200,description="success",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                    @OA\Items(
     *                                         name="data",
     *                                         type="array"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="message",
     *                                         type="boolean"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="error",
     *                                         type="array"
     *                                    )
     *                                 )
     *                            )
     *   @OA\Response(response=400, description="Something went wrong",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                 @OA\Items(
     *                                         name="data",
     *                                         type="array"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="message",
     *                                         type="boolean"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="error",
     *                                         type="array"
     *                                    )
     *                                 )
     *                             )
     *                  )
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
                $res = (object)[
                    "status_code" => 401,
                    "message"     => "OTP does not match",
                    "error"       => null,
                    "data"        => null
                ];
                return (new ErrorResource($res))->response()->setStatusCode(401);
            }

            $postUrl = URL::to('/') . $this->authTokenUrl;
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

                $response = (object)[
                    "status_code" => 200,
                    "message"     => "Success",
                    "error"       => null,
                    "data"        => $data
                ];
                return (new SuccessResource($response))->response()->setStatusCode(200);
            }
            return (new ErrorResource($generateToken))->response()->setStatusCode($generateToken->status_code);
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
     * @OA\Post(path="/user/social/login",
     *   tags={"user"},
     *   summary="For get social token url",
     *   description="This api used to create social token url and we are using this url for generating code.",
     *   operationId="usersociallogin",
     *   @OA\SecurityScheme(
     *      type="http",
     *      description="Authentication Bearer Token",
     *      name="Authentication Bearer Token",
     *      in="header",
     *      scheme="bearer",
     *      bearerFormat="passport",
     *      securityScheme="apiAuth",
     *   )
     *   @OA\RequestBody(
     *       required=true,
     *       description="user social logine",
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *                 @OA\Property(
     *                     property="provider",
     *                     description="The provider of social",
     *                     required=true,
     *                     type="string",
     *                 )
     *       )
     *   ),
     *   @OA\Response(response=200,description="success",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                    @OA\Items(
     *                                         name="data",
     *                                         type="array"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="message",
     *                                         type="boolean"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="error",
     *                                         type="array"
     *                                    )
     *                                 )
     *                            )
     *   @OA\Response(response=400, description="Something went wrong",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                 @OA\Items(
     *                                         name="data",
     *                                         type="array"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="message",
     *                                         type="boolean"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="error",
     *                                         type="array"
     *                                    )
     *                                 )
     *                             )
     *                  )
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
                $res = (object)[
                    "status_code" => 400,
                    "message"     => "Something went wrong",
                    "error"       => null,
                    "data"        => null
                ];
                return (new ErrorResource($res))->response()->setStatusCode(400);
            }
            $data = [
                "url" => $redirect
            ];
            $response = (object)[
                "status_code" => 200,
                "message"     => "Success",
                "error"       => null,
                "data"        => $data
            ];
            return (new SuccessResource($response))->response()->setStatusCode(200);
        }catch (Exception $ex) {
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
     * @OA\Post(path="/user/social/callback",
     *   tags={"user"},
     *   summary="For get social user details",
     *   description="This api used to get social social users detauls and auth details.",
     *   operationId="usersocialcallback",
     *   @OA\SecurityScheme(
     *      type="http",
     *      description="Authentication Bearer Token",
     *      name="Authentication Bearer Token",
     *      in="header",
     *      scheme="bearer",
     *      bearerFormat="passport",
     *      securityScheme="apiAuth",
     *   )
     *   @OA\RequestBody(
     *       required=true,
     *       description="user social callback",
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *                 @OA\Property(
     *                     property="client_id",
     *                     description="The Auth Client Id",
     *                     required=true,
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="client_secret",
     *                     description="The Auth Client secret",
     *                     required=true,
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="provider",
     *                     description="The provider of social",
     *                     required=true,
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="code",
     *                     description="The provider of code",
     *                     required=true,
     *                     type="string",
     *                 ),
     *       )
     *   ),
     *   @OA\Response(response=200,description="success",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                    @OA\Items(
     *                                         name="data",
     *                                         type="array"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="message",
     *                                         type="boolean"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="error",
     *                                         type="array"
     *                                    )
     *                                 )
     *                            )
     *   @OA\Response(response=400, description="Something went wrong",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                 @OA\Items(
     *                                         name="data",
     *                                         type="array"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="message",
     *                                         type="boolean"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="error",
     *                                         type="array"
     *                                    )
     *                                 )
     *                             )
     *                  )
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
                $res = (object)[
                    "status_code" => 400,
                    "message"     => "Something went wrong",
                    "error"       => null,
                    "data"        => null
                ];
                return (new ErrorResource($res))->response()->setStatusCode(400);
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
            $postUrl = URL::to('/') . $this->authTokenUrl;
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
                $response = (object)[
                    "status_code" => 200,
                    "message"     => "Success",
                    "error"       => null,
                    "data"        => $data
                ];
                return (new SuccessResource($response))->response()->setStatusCode(200);
            }
            return (new ErrorResource($generateToken))->response()->setStatusCode($generateToken->status_code);

        }catch (Exception $ex) {
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
     * @OA\Post(path="/country/list",
     *   tags={"user"},
     *   summary="For get country list",
     *   description="This api used to get country list ",
     *   operationId="usersocialcallback",
     *   @OA\SecurityScheme(
     *      type="http",
     *      description="Authentication Bearer Token",
     *      name="Authentication Bearer Token",
     *      in="header",
     *      scheme="bearer",
     *      bearerFormat="passport",
     *      securityScheme="apiAuth",
     *   )
     *   @OA\Response(response=200,description="success",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                    @OA\Items(
     *                                         name="data",
     *                                         type="array"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="message",
     *                                         type="boolean"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="error",
     *                                         type="array"
     *                                    )
     *                                 )
     *                            )
     *   @OA\Response(response=400, description="Something went wrong",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                 @OA\Items(
     *                                         name="data",
     *                                         type="array"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="message",
     *                                         type="boolean"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="error",
     *                                         type="array"
     *                                    )
     *                                 )
     *                  )
     * )
     */

    public function countryList(Request $request)
    {

        try {

            $result = Country::where('status', 1)->get();

            if(empty($result)){
                $res = (object)[
                    "status_code" => 400,
                    "message"     => "Something went wrong",
                    "error"       => null,
                    "data"        => null
                ];
                return (new ErrorResource($res))->response()->setStatusCode(400);
            }

            $response = (object)[
                "status_code" => 200,
                "message"     => "Success",
                "error"       => null,
                "data"        => $result
            ];
            return (new SuccessResource($response))->response()->setStatusCode(200);

        }catch (Exception $ex) {
            $res = (object)[
                "status_code" => 400,
                "message"     => "Something went wrong",
                "error"       => null,
                "data"        => null
            ];
            return (new ErrorResource($res))->response()->setStatusCode(400);
        }
    }
}

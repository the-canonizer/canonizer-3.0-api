<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Facades\Util;
use App\Models\Nickname;
use App\Models\SocialUser;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Http\Request\ValidationMessages;
use App\Http\Resources\Authentication\UserResource;

class UserController extends Controller
{
    private ValidationRules $rules;

    private ValidationMessages $validationMessages;

    public function __construct()
    {
        $this->rules = new ValidationRules;
        $this->validationMessages = new ValidationMessages;
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
     *       description="Created user object",
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(ref="#/components/schemas/User")
     *       )
     *   ),
     *   @OA\Response(response="default", description="successful operation")
     * )
     */
    public function createUser(Request $request, Validate $validate)
    {

        $validationErrors = $validate->validate($request, $this->rules->getRegistrationValidationRules(), $this->validationMessages->getRegistrationValidationMessages());
       
        if( $validationErrors ){
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {

             //$authCode = mt_rand(100000, 999999);
            $authCode = 454545;
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
                $res = (object)[
                    "status_code" => 401,
                    "message"     => "OTP does not match",
                    "error"       => null,
                    "data"        => null
                ];
                return (new ErrorResource($res))->response()->setStatusCode(401);
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

    public function social(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getSocialValidationRules(), $this->validationMessages->getVerifyOtpValidationMessages());
        if( $validationErrors ){
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        try {

                $user = User::where('email', '=', $request->email)->first();

                if(!empty($user)){
                    $userRes = User::where('email', '=', $request->email)->update(['otp' => '','status' => 1]);
                }else{
                    $input = [
                        "first_name" => $request->first_name,
                        "last_name" => $request->last_name,
                        "email" => $request->email,
                        "otp" => '',
                        "status" => 1
                    ];
                    $userRes = User::create($input);

                    $socialUser = SocialUser::create([
                        'user_id'       => $userRes->id,
                        'social_email'  => $request->email,
                        'provider_id'   => $request->provider_id,
                        'provider'      => $request->provider,
                        'social_name'   => $request->name,
                    ]);
                    $nickname = $userRes->first_name."-".$userRes->last_name;
                    $this->createNickname($userRes->id, $nickname);

                }

                if($userRes){
                    $postUrl = URL::to('/') . '/oauth/token';
                    $payload = [
                        'grant_type' => 'password',
                        'client_id' => $request->client_id,
                        'client_secret' => $request->client_secret,
                        'username' => $request->email,
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
                }else{
                    $res = (object)[
                        "status_code" => 400,
                        "message"     => "Something went wrong",
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
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\Authentication\UserResource;
use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    public function clientToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required',
            'client_secret' => 'required',
        ]);

        if ($validator->fails()) {
            $res = (object)[
                "status_code" => 400,
                "message"     => "The given data was invalid.",
                "error"       => $validator->errors(),
                "data"        => null
            ];
            return (new ErrorResource($res))->response()->setStatusCode(400);
        }
        
        try {
            $response = Http::asForm()->post(URL::to('/') . '/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $request->client_id,
                'client_secret' => $request->client_secret,
                'scope' => '*',
            ]);
    
            $status = $response->status();
            
            if ($status === 200) {
                $res = (object)[
                    "status_code" => 200,
                    "message"     => "Success",
                    "error"       => null,
                    "data"        => $response->json()
                ];
                return (new SuccessResource($res))->response()->setStatusCode(200);
            } elseif ($status === 401) {
                $res = (object)[
                    "status_code" => 401,
                    "message"     => "Unauthenticated",
                    "error"       => null,
                    "data"        => null
                ];
                return (new ErrorResource($res))->response()->setStatusCode(401);
            } else {
                $res = (object)[
                    "status_code" => 400,
                    "message"     => "Something went wrong",
                    "error"       => null,
                    "data"        => $response->json()
                ];
                return (new ErrorResource($res))->response()->setStatusCode(400);
            }
        }catch(Exception $ex){
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
    public function createUser()
    {
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
    public function loginUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
        ]);

        if ($validator->fails()) {
            $res = (object)[
                "status_code" => 400,
                "message"     => "The given data was invalid.",
                "error"       => $validator->errors(),
                "data"        => null
            ];
            return (new ErrorResource($res))->response()->setStatusCode(400);
        }

        try {

            $username = $request->username;

            $user = User::where(function ($query) use ($username) {
                $query->where('email', '=', $username);
            })->first();

            if ($user) {
                if (Hash::check($request->password, $user->password)) {

                    $response = Http::asForm()->post(URL::to('/') . '/oauth/token', [
                        'grant_type' => 'password',
                        'client_id' => $request->client_id,
                        'client_secret' => $request->client_secret,
                        'username' => $request->username,
                        'password' => $request->password,
                        'scope' => '*',
                    ]);

                    $status = $response->status();
                    if ($status === 200) {
                        $data = [
                            "auth" => $response->json(),
                            "user" => new UserResource($user),
                        ];
                        $res = (object)[
                            "status_code" => 200,
                            "message"     => "Success",
                            "error"       => null,
                            "data"        => $data
                        ];
                        return (new SuccessResource($res))->response()->setStatusCode(200);
                    } elseif ($status === 401) {
                        $res = (object)[
                            "status_code" => 401,
                            "message"     => "Unauthenticated",
                            "error"       => null,
                            "data"        => null
                        ];
                        return (new ErrorResource($res))->response()->setStatusCode(401);
                    } else {
                        $res = (object)[
                            "status_code" => 400,
                            "message"     => "Something went wrong",
                            "error"       => null,
                            "data"        => null
                        ];
                        return (new ErrorResource($res))->response()->setStatusCode(400);
                    }
                }else{
                    $res = (object)[
                        "status_code" => 401,
                        "message"     => "Email or password does not match",
                        "error"       => null,
                        "data"        => null
                    ];
                    return (new ErrorResource($res))->response()->setStatusCode(401);
                }
            } else {
                $res = (object)[
                    "status_code" => 401,
                    "message"     => "Email or password does not match",
                    "error"       => null,
                    "data"        => null
                ];
                return (new ErrorResource($res))->response()->setStatusCode(401);
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
}

<?php

namespace App\Http\Controllers;
use Exception;
use App\Models\User;
use App\Facades\Util;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Http\Request\ValidationMessages;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;

/**
 * @OA\Info(title="Account Setting API", version="1.0.0")
 */
class ProfileController extends Controller
{
    private ValidationRules $rules;

    private ValidationMessages $validationMessages;

    public function __construct()
    {
        $this->rules = new ValidationRules;
        $this->validationMessages = new ValidationMessages;
    }

    /**
     * @OA\Post(path="/changepassword",
     *   tags={"changepassword"},
     *   summary="Update Password",
     *   description="This is used to update the user password.",
     *   operationId="changePassword",
     *   @OA\Parameter(
     *     name="current_password",
     *     required=true,
     *     in="query",
     *     description="The current password of logged in user",
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *    @OA\Parameter(
     *     name="new_password",
     *     required=true,
     *     in="query",
     *     description="The new password of logged in user",
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *    @OA\Parameter(
     *     name="confirm_password",
     *     required=true,
     *     in="query",
     *     description="The confirm password same as new password of logged in user",
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
    *   @OA\Response(status_code=200, message="Password updated successfully"),
    *   @OA\Response(
    *         response=400,
    *         message="Error",
    *         @OA\JsonContent(
    *             oneOf={
    *                 @OA\Schema(ref="#/components/schemas/Error"),
    *                 @OA\Schema(type="string")
    *             },  
    *         )
    *     )
    * )
    */
    public function changePassword(Request $request, Validate $validate)
    {
        $user = $request->user();
        $validationErrors = $validate->validate($request, $this->rules->getChangePasswordValidationRules(), $this->validationMessages->getChangePasswordValidationMessages());
        if( $validationErrors ){
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }        

        if (!Hash::check($request->get('current_password'), $user->password)) {
            $res = (object)[
                "status_code" => 400,
                "message"     => "Incorrect Current Password",
                "error"       => null,
                "data"        => null
            ];
            return (new ErrorResource($res))->response()->setStatusCode(400);
        }
        try{
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
        }catch(Exception $e){
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

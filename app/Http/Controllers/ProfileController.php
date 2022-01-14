<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Validator;

/**
 * @OA\Info(title="Account Setting API", version="1.0.0")
 */
class ProfileController extends Controller
{
    public function __construct()
    {
        //Auth middleware
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
    *             @OA\Examples(example="result", value={"success": true}, summary="An result object."),
    *             @OA\Examples(example="bool", value=false, summary="A boolean value."),
    *         )
    *     )
    * )
    */
    public function changePassword(Request $request)
    {
        $user = User::where('email', '=', 'reenanalwa@gmail.com')->first();
        $message = [
            'new_password.regex' => 'Password must be atleast 8 characters, including atleast one digit, one lower case letter and one special character(@,# !,$..)',
            'current_password.required' => 'The current password field is required.'
        ];        
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => ['required', 'regex:/^(?=.*?[a-z])(?=.*?[0-9])(?=.*?[^\w\s]).{8,}$/', 'different:current_password'],
            'confirm_password' => 'required|same:new_password'
        ], $message);
        if ($validator->fails()) {
            return response()->json(['status_code'=>400,'message'=>'Error','error'=>['errors'=>$validator->errors(),'message'=>'Invalid Data'],'data'=>null],400);
        }

        if (!Hash::check($request->get('current_password'), $user->password)) {
            return response()->json(['status_code'=>400,'message'=>'Incorrect Current Password','error'=>['errors'=>null,'message'=>'Incorrect Current Password']],400);
        }
        $newPassword = Hash::make($request->get('new_password'));
        $user->password = $newPassword;
        $user->save();
        return response()->json(['status_code'=>200,'message'=>'Password updated successfully'],200);
    }
}

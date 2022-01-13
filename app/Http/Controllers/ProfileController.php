<?php

namespace App\Http\Controllers;

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
     *   @OA\Response(response="default", description="successful operation")
     * )
     */
    public function changePassword()
    { die('vfdgd');
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
            return response()->json($validator,200);
        }

          /*  if (!Hash::check($request->get('current_password'), $user->password)) {
                session()->flash('error', 'Incorrect Current Password.');
                return redirect()->back();
            }

            $newPassword = Hash::make($request->get('new_password'));
            $user->password = $newPassword;
            $user->save();

            Auth::logout();
            session(['url.intended' => '/home']);
            return redirect()->route('login');*/
    }
}

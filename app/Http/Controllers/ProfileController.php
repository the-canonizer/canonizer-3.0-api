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
use App\Models\MobileCarrier;

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

    public function mobileCarrier(Request $request){
        try{
            $carrier = MobileCarrier::all();
            $res = (object)[
                "status_code" => 200,
                "message"     => "Password changed successfully",
                "error"       => null,
                "data"        => $carrier
            ];
            return (new SuccessResource($res))->response()->setStatusCode(200);

        }catch(Exception $e){
            $res = (object)[
                "status_code" => 400,
                "message"     => "Something went wrong",
                "error"       => null,
                "data"        => null
            ];
            return (new ErrorResource($res))->response()->setStatusCode(400);
        }
    }

    public function updateProfile(Request $request){ 
      
       $user = $request->user();
       echo "<pre>"; print_r($user); exit;

       $user->first_name = $input['first_name'];
       if ($input['first_name_bit'] != '0')
           $private_flags[] = $input['first_name_bit'];
       $user->last_name = $input['last_name'];
       if ($input['last_name_bit'] != '0')
           $private_flags[] = $input['last_name_bit'];
       $user->middle_name = $input['middle_name'];
       if ($input['middle_name_bit'] != '0')
           $private_flags[] = $input['middle_name_bit'];
       $user->gender = isset($input['gender']) ? $input['gender'] : '';
       //if($input['gender_bit']!='0') $private_flags[]=$input['gender_bit'];
       $user->birthday = date('Y-m-d', strtotime($input['birthday']));
       if ($input['birthday_bit'] != '0')
           $private_flags[] = $input['birthday_bit'];
       
       if ($input['email_bit'] != '0')
           $private_flags[] = $input['email_bit'];
       
       $user->language = $input['language'];
       $user->address_1 = $input['address_1'];
       if ($input['address_1_bit'] != '0')
           $private_flags[] = $input['address_1_bit'];
       $user->address_2 = $input['address_2'];
       if ($input['address_2_bit'] != '0')
           $private_flags[] = $input['address_2_bit'];
       $user->city = $input['city'];
       if ($input['city_bit'] != '0')
           $private_flags[] = $input['city_bit'];
       $user->state = $input['state'];
       if ($input['state_bit'] != '0')
           $private_flags[] = $input['state_bit'];
       $user->country = $input['country'];
       if ($input['country_bit'] != '0')
           $private_flags[] = $input['country_bit'];
       $user->postal_code = $input['postal_code'];
       if ($input['postal_code_bit'] != '0')
           $private_flags[] = $input['postal_code_bit'];
       if ($input['email_bit'] != '0')
           $private_flags[] = $input['email_bit'];

       $flags = implode(",", $private_flags);
       $user->default_algo = $request->input('default_algo');
       $user->private_flags = $flags;
       $user->update();



    }
}

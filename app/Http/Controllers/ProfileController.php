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

    public function updateProfile(Request $request, Validate $validate){ 
      
       $user = $request->user();
       $input = $request->all();
       $validationErrors = $validate->validate($request, $this->rules->getUpdateProfileValidatonRules(),[]);
       if( $validationErrors ){
           return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
       }

       try{           
            $private_flags = [];
            if(isset($input['first_name']))
                $user->first_name =  $input['first_name']; 
            if(isset($input['middle_name']))
                $user->middle_name = $input['middle_name'] ;
            if(isset($input['last_name']))
                $user->last_name =  $input['last_name'];
            if(isset($input['address_1']))
                $user->address_1 = $input['address_1'];
            if(isset($input['address_2']))
                $user->address_2 = $input['address_2']; 
            if(isset($input['city']))
                $user->city = $input['city'];
            if(isset($input['state']))
                $user->state =  $input['state'];
            if(isset($input['country']))
                $user->country = $input['country'];
            if(isset($input['postal_code']))
                $user->postal_code = $input['postal_code']; 
            if(isset($input['gender']))
                $user->gender = $input['gender'];
            if(isset($input['birthday']))
                $user->birthday = date('Y-m-d', strtotime($input['birthday']));
            if(isset($input['language']))
                $user->language = $input['language']; 
            if(isset($input['phone_number']))
                $user->phone_number = $input['phone_number'];
            if(isset($input['mobile_carrier']))
                $user->phone_number = $input['mobile_carrier'];
            if(isset($input['default_algo']))
                $user->default_algo = $input['default_algo'];
            
            if(isset($input['first_name_bit']) && ($input['first_name_bit'] != '0'))
            $private_flags[] = $input['first_name_bit'];
            if(isset($input['last_name_bit']) && ($input['last_name_bit'] != '0'))
            $private_flags[] = $input['last_name_bit'];
            if(isset($input['middle_name_bit']) && ($input['middle_name_bit'] != '0'))
            $private_flags[] = $input['middle_name_bit'] ;
            if(isset($input['birthday_bit']) && ($input['birthday_bit'] != '0'))
            $private_flags[] = $input['birthday_bit'];
            if(isset($input['email_bit']) && ($input['email_bit'] != '0'))
            $private_flags[] = $input['email_bit'];
            if(isset($input['address_1_bit']) && ($input['address_1_bit'] != '0'))
            $private_flags[] = $input['address_1_bit'];
            if(isset($input['address_2_bit']) && ($input['address_2_bit'] != '0'))
            $private_flags[] = $input['address_2_bit'];
            if(isset ($input['city_bit']) &&  ($input['city_bit'] != '0'))
            $private_flags[] = $input['city_bit'];
            if(isset($input['state_bit']) && ($input['state_bit'] != '0'))
            $private_flags[] =  $input['state_bit'];
            if(isset($input['country_bit']) && ($input['country_bit'] != '0'))
            $private_flags[] =  $input['country_bit'];
            if(isset($input['postal_code_bit']) && ($input['postal_code_bit'] != '0'))
            $private_flags[] =  $input['postal_code_bit'];
            if(!empty($private_flags))
            $user->private_flags = implode(",", $private_flags);
            $user->update_time = time();
            $user->update();            
            
            $response = (object)[
                "status_code" => 200,
                "message"     => "Profile updated successfully.",
                "error"       => null,
                "data"        => $user
            ];
            return (new SuccessResource($response))->response()->setStatusCode(200);
      
      
        }catch(Exception $e){
            $res = (object)[
                "status_code" => 400,
                "message"     => "Something went wrong",
                "error"       => $e->getMessage(),
                "data"        => null
            ];
            return (new ErrorResource($res))->response()->setStatusCode(400);
       }


    }
}

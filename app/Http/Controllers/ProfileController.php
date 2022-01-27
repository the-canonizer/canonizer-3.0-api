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

    /**
     * @OA\Get(path="/mobilecarrier",
     *   tags={"profile"},
     *   summary="",
     *   description="Get list of mobile carrier",
     *   operationId="loginUser",
     *   @OA\Response(response=200, description="Sucsess")
     *   @OA\Response(response=400, description="Something went wrog")
     * )
     */

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



    /**
     * @OA\Post(path="/updateprofile",
     *   tags={"profile"},
     *   summary="Update Profile",
     *   description="This is used to update the user profile.",
     *   operationId="updateprofile",
     *   @OA\Parameter(
     *     name="first_name",
     *     required=true,
     *     in="query",
     *     description="The first name is required",
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *    @OA\Parameter(
     *     name="last_name",
     *     required=true,
     *     in="query",
     *     description="The last name is required",
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),    
     *   @OA\Response(status_code=200, message="Profile Updated Successfully"),
     *   @OA\Response(status_code=400, message="The given data was invalid")
     *   @OA\Response(status_code=400, message="Somethig went wrong")
     * )
    */
    public function updateProfile(Request $request, Validate $validate){ 
      
       $user = $request->user();
       $input = $request->all();
       $validationErrors = $validate->validate($request, $this->rules->getUpdateProfileValidatonRules(),$this->validationMessages->getUpdateProfileValidationMessages());
       if( $validationErrors ){
           return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
       }

       try{    
            $fields = ['first_name','last_name','middle_name','address_1','address_2','city','state','country','postal_code','phone_number','mobile_carrier','gender','birthday','default_algo'] ;
            foreach($fields as $f){
               if(isset($input[$f])){
                   if($f == 'birthday')
                    $user->$f = date('Y-m-d', strtotime($input[$f]));
                   else
                    $user->$f = $input[$f];
               }
            }
            
            $flagFields = [
                'first_name_bit'    =>  'first_name',
                'last_name_bit' =>  'last_name',
                'middle_name_bit'   => 'middle_name',
                'birthday_bit' =>'birthday',
                'email_bit' => 'email',
                'address_1_bit' => 'address_1',
                'address_2_bit' => 'address_2',
                'city_bit' => 'city',
                'state_bit' => 'state',
                'country_bit' => 'country',
                'postal_code_bit' => 'postal_code'
            ];
            $private_flags = [];
            foreach($flagFields as $pf => $field){
                if(isset($input[$pf]) && !$input[$pf]){
                    $private_flags[] = $field;
                }
            } 
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

     /**
     * @OA\Get(path="/user/profile",
     *   tags={"profile"},
     *   summary="Get looged in user profile",
     *   description="",
     *   operationId="userProfile",   
     *   @OA\Response(response=200, description="success", @OA\Schema(ref="#/components/schemas/User")),
     *   @OA\Response(response=404, description="Something went wrong")
     * )
     */
    public function getProfile(Request $request){
        $user = $request->user();
        try{
            $res = (object)[
                "status_code" => 200,
                "message"     => "Success",
                "error"       => null,
                "data"        => $user
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

<?php

namespace App\Http\Controllers;
use App\Helpers\ResponseInterface;
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
use Illuminate\Support\Facades\Mail;
use App\Events\SendOtpEvent;
use Illuminate\Support\Facades\Event;
use App\Models\Languages;

/**
 * @OA\Info(title="Account Setting API", version="1.0.0")
 */
class ProfileController extends Controller
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
     * @OA\Post(
     *     path="/changepassword",
     *     tags={"changepassword"},
     *     summary="Update Password",
     *     description="This is used to update the user password.",
     *     operationId="changePassword",
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
    *   @OA\Response(
     *     response=200,
     *     description="Password updated successfully"
     * ),
    *   @OA\Response(
    *         response=400,
    *         description="Error"
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
            return $this->resProvider->apiJsonResponse(400, 'Incorrect Username or Password', '', '');
        }
        try{
            $newPassword = Hash::make($request->get('new_password'));
            $user->password = $newPassword;
            $user->save();
            return $this->resProvider->apiJsonResponse(200, 'Password changed successfully', '', '');
        }catch(Exception $e){
            return $this->resProvider->apiJsonResponse(400, 'Something went wrong', $e->getMessage(), '');
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
                "message"     => "success",
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
            if($user->update($input)){
                $response = (object)[
                    "status_code" => 200,
                    "message"     => "Profile updated successfully.",
                    "error"       => null,
                    "data"        => $request->user()
                ];
                return (new SuccessResource($response))->response()->setStatusCode(200);
            }else{
                    $response = (object)[
                        "status_code" => 400,
                        "message"     => "Failed to update profile, please try again.",
                        "error"       => null,
                        "data"        => null
                    ];
                    return (new ErrorResource($response))->response()->setStatusCode(400);
            }
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

    /**
     * @OA\Post(path="/sendotp",
     *   tags={"otp"},
     *   summary="Send Otp to verify number",
     *   description="Send Otp to verify number",
     *   operationId="sendOtp",
     *   @OA\Parameter(
     *     name="phone_number",
     *     required=true,
     *     in="query",
     *     description="10 digit valid phone number is required",
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *    @OA\Parameter(
     *     name="mobile_carrier",
     *     required=true,
     *     in="query",
     *     description="Mobile carrier is required",
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Response(status_code=200, message="Otp has been sent on your phone number."),
     *   @OA\Response(status_code=400, message="The given data was invalid")
     *   @OA\Response(status_code=400, message="Somethig went wrong")
     * )
    */
    public function sendOtp(Request $request, Validate $validate){
        $user = $request->user();
        $input = $request->all();
        $validationErrors = $validate->validate($request, $this->rules->getVerifyPhoneValidatonRules(),[]);
        if( $validationErrors ){
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try{
            $otp = mt_rand(100000, 999999);
            $result['otp'] = $otp;
            $result['subject'] = "Canonizer - Phone number verification code";
            $receiver = $input['phone_number'] . "@" . $input['mobile_carrier'];
            $user->phone_number = $input['phone_number'];
            $user->mobile_carrier = $input['mobile_carrier'];
            $user->otp = $otp;
            $user->update();
            Event::dispatch(new SendOtpEvent($user,true));
            $res = (object)[
                "status_code" => 200,
                "message"     => "Otp has been sent on your phone number.",
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

      /**
     * @OA\Post(path="/verifyotp",
     *   tags={"otp"},
     *   summary="Verify Otp sent on phone number",
     *   description="Verify Otp sent on phone number",
     *   operationId="verifyOtp",
     *   @OA\Parameter(
     *     name="otp",
     *     required=true,
     *     in="query",
     *     description="6 digit otp is required",
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Response(status_code=200, message="Phone number has been verified successfully."),
     *   @OA\Response(status_code=400, message="The given data was invalid")
     *   @OA\Response(status_code=400, message="Invalid One Time Verification Code.")
     * )
    */
    public function verifyOtp(Request $request, Validate $validate){
        $user = $request->user();
        $input = $request->all();
        $validationErrors = $validate->validate($request, $this->rules->getVerifyOtpValidatonRules(),[]);
        if( $validationErrors ){
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        try{
            if($user->otp == trim($input['otp'])){
                $user->mobile_verified = 1;
                $user->otp = "";
                $user->update();
                $res = (object)[
                    "status_code" => 200,
                    "message"     => "Phone number has been verified successfully.",
                    "error"       => null,
                    "data"        => $user
                ];
                return (new SuccessResource($res))->response()->setStatusCode(200);
            }else{
                $user->mobile_verified = 0;
                $user->update();
                $res = (object)[
                    "status_code" => 400,
                    "message"     => "Invalid One Time Verification Code.",
                    "error"       => null,
                    "data"        => null
                ];
                return (new SuccessResource($res))->response()->setStatusCode(200);
            }

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
     * @OA\Get(path="/get_languages",
     *   tags={"languages"},
     *   summary="",
     *   description="Get list of Languages",
     *   operationId="languages",
     *   @OA\Response(response=200, description="Sucsess")
     *   @OA\Response(response=400, description="Something went wrog")
     * )
     */
    public function getLanguages()
    {

        try {
            $languages = Languages::all();
            $res = (object) [
                "status_code" => 200,
                "message" => "Success",
                "error" => null,
                "data" => $languages,
            ];
            return (new SuccessResource($res))->response()->setStatusCode(200);
        } catch (\Throwable $e) {
            $res = (object) [
                "status_code" => 400,
                "message" => "Something went wrong",
                "error" => $e->getMessage(),
                "data" => null,
            ];
            return (new ErrorResource($res))->response()->setStatusCode(400);
        }

    }
}

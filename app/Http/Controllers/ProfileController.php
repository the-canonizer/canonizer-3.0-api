<?php

namespace App\Http\Controllers;
use App\Helpers\ResponseInterface;
use Exception;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Http\Request\ValidationMessages;
use Illuminate\Support\Facades\Hash;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Models\MobileCarrier;
use App\Events\SendOtpEvent;
use App\Helpers\Aws;
use Illuminate\Support\Facades\Event;
use App\Models\Languages;
use App\Models\User;
use App\Models\Nickname;
use App\Helpers\TopicSupport;

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
     *     path="/change-password",
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
        $status = $message = $data = '';

        $user = $request->user();
        $validationErrors = $validate->validate($request, $this->rules->getChangePasswordValidationRules(), $this->validationMessages->getChangePasswordValidationMessages());
        if( $validationErrors ){
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        if (!Hash::check($request->get('current_password'), $user->password)) {
            return $this->resProvider->apiJsonResponse(400, 'Incorrect Current Password', '', '');
        }
        try{
            $newPassword = Hash::make($request->get('new_password'));
            $user->password = $newPassword;
            $user->save();
            $status = 200;
            $message = trans('message.success.password_change');
        }catch(Exception $e){
            $status = 200;
            $message = trans('message.error.exception');
            $data = $e->getMessage();
        }
        return $this->resProvider->apiJsonResponse($status, $message, $data, '');
    }

    /**
     * @OA\Get(path="/mobile-carrier",
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
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $carrier, '');
        }catch(Exception $e){
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), $e->getMessage(), '');
        }
    }



    /**
     * @OA\Post(path="/update-profile",
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
     *   @OA\Response(response=200, description="Profile Updated Successfully"),
     *   @OA\Response(response=400, description="The given data was invalid")
     *   @OA\Response(response=400, description="Somethig went wrong")
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
            return ( $user->update($input) )
                 ? $this->resProvider->apiJsonResponse(200, trans('message.success.update_profile'), $request->user(), '')
                 : $this->resProvider->apiJsonResponse(400, trans('message.error.update_profile'), '', '');
        }catch(Exception $e){
           return $this->resProvider->apiJsonResponse(200, trans('message.error.exception'), $e->getMessage(), '');
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
        $user->profile_picture = !empty($user->profile_picture_path) ? $user->profile_picture_path : null;
        unset($user->profile_picture_path);

        try{
            $res = (object)[
                "status_code" => 200,
                "message"     => trans('message.success.success'),
                "error"       => null,
                "data"        => $user
            ];
            return (new SuccessResource($res))->response()->setStatusCode(200);
        }catch(Exception $e){
            $res = (object)[
                "status_code" => 400,
                "message"     => trans('message.error.exception'),
                "error"       => null,
                "data"        => $e->getMessage()
            ];
            return (new ErrorResource($res))->response()->setStatusCode(400);
        }
    }

    /**
     * @OA\Post(path="/send-otp",
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
     *   @OA\Response(response=200, description="Otp has been sent on your phone number."),
     *   @OA\Response(response=400, description="The given data was invalid")
     *   @OA\Response(response=400, description="Somethig went wrong")
     * )
    */
    public function sendOtp(Request $request, Validate $validate){
        $user = $request->user();
        $input = $request->all();
        $validationErrors = $validate->validate($request, $this->rules->getVerifyPhoneValidatonRules(),$this->validationMessages->getVerifyPhoneValidationMessages());
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
                "message"     => trans('message.success.phone_number_otp'),
                "error"       => null,
                "data"        => $user
            ];
            return (new SuccessResource($res))->response()->setStatusCode(200);
        }catch(Exception $e){
            $res = (object)[
                "status_code" => 400,
                "message"     => trans('message.error.exception'),
                "error"       => null,
                "data"        => $e->getMessage()
            ];
            return (new ErrorResource($res))->response()->setStatusCode(400);
        }

    }

      /**
     * @OA\Post(path="/verify-otp",
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
        $validationErrors = $validate->validate($request, $this->rules->getVerifyOtpValidatonRules(),$this->validationMessages->getVerifyOtpValidatonMessages());
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
                    "message"     => trans('message.success.verify_otp'),
                    "error"       => null,
                    "data"        => $user
                ];
                return (new SuccessResource($res))->response()->setStatusCode(200);
            }else{
                $user->mobile_verified = 0;
                $user->update();
                $res = (object)[
                    "status_code" => 400,
                    "message"     => trans('message.error.verify_otp'),
                    "error"       => null,
                    "data"        => null
                ];
                return (new SuccessResource($res))->response()->setStatusCode(400);
            }

        }catch(Exception $e){
            $res = (object)[
                "status_code" => 400,
                "message"     => trans('message.error.exception'),
                "error"       => null,
                "data"        => $e->getMessage()
            ];
            return (new ErrorResource($res))->response()->setStatusCode(400);
        }

    }


    /**
     * @OA\Get(path="/get-languages",
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
                "message" => trans('message.success.success'),
                "error" => null,
                "data" => $languages,
            ];
            return (new SuccessResource($res))->response()->setStatusCode(200);
        } catch (\Throwable $e) {
            $res = (object) [
                "status_code" => 400,
                "message" => trans('message.error.exception'),
                "error" => $e->getMessage(),
                "data" => null,
            ];
            return (new ErrorResource($res))->response()->setStatusCode(400);
        }

    }

    /**
     * 
     */
    public function getUserProfile(Request $request, $id)
    {        
        $user = User::getUserById($id);

        try{
            if(!empty($user)){
                $userArray = $user->toArray();
                $privateFlags = explode(",",$user->private_flags);
                foreach($privateFlags as $private)
                {
                    unset($userArray[$private]);
                }

                $publicNickNames = Nickname::getAllNicknames($id, 0);
                $userArray['nick_names'] = $publicNickNames;

                $status = 200;
                $message = trans('message.success.success');
                $data = $userArray;
                $error = null;


            }else{
                $status = 404;
                $message = trans('message.error.user_not_exist');
                $data = null;
                $error = trans('message.error.user_not_exist');
            }

            return $this->resProvider->apiJsonResponse($status, $message, $data, $error);

        }catch(Exception $e){
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), null, null);
        }
    }

     /**
     * @OA\Post(path="/user/all-supported-camps",
     */
    public function getUserSupportedCamps(Request $request, $id)
    {
       
        try{

            $user = User::getUserById($id);

            if(isset($user) && !empty($user))
            {
                $supportedCamps = TopicSupport::getAllSupportedCampsByUserId($id);

                $status = 200;
                $message =  trans('message.success.success');
                $data = $supportedCamps;
                $error = null;

            }else{
                $status = 404;
                $message = trans('message.error.user_not_exist');
                $data = null;
                $error = trans('message.error.user_not_exist'); 
            }
            return $this->resProvider->apiJsonResponse($status, $message, $data, $error);

        }catch(Exception $e){
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), null, $e->getMessage());
        }
    }


     /**
     * @OA\Post(path="/user/supports/{id}",
     */
    public function getUserSupports(Request $request, $id, Validate $validate)
    {
        $nickName = Nickname::find($id);
        $data = [];
        try{
            if(isset($nickName) && !empty($nickName))
            {
                $validationErrors = $validate->validate($request, $this->rules->getUserSupportsValidationRules(), $this->validationMessages->getUserSupportsMessages());
                if( $validationErrors ){
                    return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
                }
                $all = $request->all();
                $namespace = (isset($all['namespace']) && !empty($all['namespace'])) ? $all['namespace'] : 1;
                
                $user = Nickname::getUserByNickName($id);
                $userArray = $user->toArray();
                $privateFlags = explode(",",$user->private_flags);
                foreach($privateFlags as $private)
                {
                    unset($userArray[$private]);
                }               
                
                $userArray['profile_picture'] = $nickName->private ? null : (empty($userArray['profile_picture_path']) ? null : $userArray['profile_picture_path']);
                unset($userArray['profile_picture_path']);

                $supportResponse = $nickName->getNicknameSupportedCampList($namespace, ['nofilter' => true]);
                $support = TopicSupport::groupCampsForNickId($supportResponse, $nickName, $namespace);

                $data['profile'] = $userArray;
                $data['support_list'] = $support;
                $status = 200;
                $message = trans('message.success.success');
                $error = null;

            }else{
                $status = 404;
                $message = trans('message.error.user_not_exist');
                $data = null;
                $error = trans('message.error.user_not_exist'); 
            }   
            return $this->resProvider->apiJsonResponse($status, $message, $data, $error);

        }catch(Exception $e){
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), null, $e->getMessage());
        }
    }

    /**
     * @OA\Post(path="/update-profile-picture",
     *   tags={"profile-picture"},
     *   summary="Upload and update user profile picture",
     *   description="Upload and update user profile picture",
     *   operationId="updateProfilePicture",
     *   @OA\Parameter(
     *     name="profile_picture",
     *     required=true,
     *     @OA\Schema(
     *         type="image/binary"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="is_update",
     *     required=false,
     *     @OA\Schema(
     *         type="boolean"
     *     )
     *   ),
     *   @OA\Response(status_code=200, message="Profile updated successfully."),
     *   @OA\Response(status_code=400, message="The given data was invalid")
     *   @OA\Response(status_code=500, message="File upload failed.")
     * )
    */
    public function updateProfilePicture(Request $request, Validate $validate)
    {
        $user = $request->user();
        $input = $request->all();

        $validationErrors = $validate->validate($request, $this->rules->getUpdateProfilePictureValidatonRules(), $this->validationMessages->getUpdateProfilePictureValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {
            if (isset($input['profile_picture'])) {
                // For case of update the profile picture request
                if($request->has('is_update') && $request->get('is_update')) {
                    $user->profile_picture_path = urldecode($user->getOriginal('profile_picture_path'));
                    Aws::DeleteFile($user->profile_picture_path);
                }
                
                $six_digit_random_number = random_int(100000, 999999);
                $filename = $user->id . '_' . time() . '_' . $six_digit_random_number  . '.' . $input['profile_picture']->getClientOriginalExtension();

                $result = Aws::UploadFile('profile/' . $filename, $input['profile_picture']);
                $user->profile_picture_path = urlencode('profile/' . $filename);
            }
            if ($user->save()) {
                return $this->resProvider->apiJsonResponse(200, trans('message.success.update_profile'), ['profile_picture' => $user->profile_picture_path], '');
            }

            return $this->resProvider->apiJsonResponse(200, trans('message.error.update_profile'), '', '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }


    /**
     * @OA\Delete(path="/update-profile-picture",
     *   tags={"profile-picture"},
     *   summary="Delete user profile picture",
     *   description="Delete user profile picture",
     *   operationId="deleteProfilePicture",
     *   @OA\Response(status_code=200, message="Profile updated successfully."),
     *   @OA\Response(status_code=400, message="Exception message"),
     *   @OA\Response(status_code=404, message="File not found"),
     * )
    */
    public function deleteProfilePicture(Request $request, Validate $validate)
    {
        $user = $request->user();

        try {
            if (!is_null($user->profile_picture_path)) {
                $user->profile_picture_path = urldecode($user->getOriginal('profile_picture_path'));
                $result = Aws::DeleteFile($user->profile_picture_path);
                if ($result['@metadata']['statusCode'] === 204) {
                    $user->profile_picture_path = null;
                }

                return ($user->save()) ? $this->resProvider->apiJsonResponse(200, trans('message.success.update_profile'), ['profile_picture' => $user->profile_picture_path], '') : $this->resProvider->apiJsonResponse(400, trans('message.error.update_profile'), '', '');
            } else {
                return $this->resProvider->apiJsonResponse(404, trans('message.error.file_does_not_exists'), '', '');
            }
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}

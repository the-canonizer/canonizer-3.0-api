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
use App\Events\EmailChangeEvent;
use App\Models\UserEmail;
use App\Models\Tag;
use App\Models\UserTag;
use DB;
use Illuminate\Support\Str;

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
     *     tags={"Profile"},
     *     summary="Update Password",
     *     description="This API updates the user password.",
     *     operationId="changePassword",
     *     security={{"clientAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 required={"current_password", "new_password", "confirm_password"},
     *                 @OA\Property(
     *                     property="current_password",
     *                     type="string",
     *                     description="The current password of the logged-in user"
     *                 ),
     *                 @OA\Property(
     *                     property="new_password",
     *                     type="string",
     *                     description="The new password for the logged-in user"
     *                 ),
     *                 @OA\Property(
     *                     property="confirm_password",
     *                     type="string",
     *                     description="Must match the new password"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password updated successfully"
     *     ),
     *     @OA\Response(
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
     * @OA\Get(
     *     path="/mobile-carrier",
     *     tags={"Profile"},
     *     summary="Retrieve a list of mobile carriers",
     *     description="Fetches the list of available mobile carriers for the user.",
     *     operationId="getMobileCarriers",
     *     security={{"clientAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Something went wrong"
     *     )
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
     * @OA\Post(
     *     path="/update-profile",
     *     tags={"Profile"},
     *     summary="Update Profile",
     *     description="This endpoint updates the user's profile information.",
     *     operationId="updateProfile",
     *     security={{"clientAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"first_name", "last_name", "email", "phone_number"},
     *                 @OA\Property(property="first_name", type="string", description="User's first name", example="John"),
     *                 @OA\Property(property="last_name", type="string", description="User's last name", example="Doe"),
     *                 @OA\Property(property="email", type="string", format="email", description="User's email address", example="john.doe@example.com"),
     *                 @OA\Property(property="birthday", type="string", format="date", description="User's date of birth (YYYY-MM-DD)", example="1990-05-20"),
     *                 @OA\Property(property="gender", type="integer", description="User's gender (0: Male, 1: Female)", example=0),
     *                 @OA\Property(property="phone_number", type="string", description="User's phone number", example="9876543210"),
     *                 @OA\Property(property="mobile_carrier", type="integer", description="User's mobile carrier ID", example=2),
     *                 @OA\Property(property="address_1", type="string", description="User's primary address", example="123 Main St"),
     *                 @OA\Property(property="address_2", type="string", nullable=true, description="Additional address details", example="Apt 4B"),
     *                 @OA\Property(property="city", type="string", nullable=true, description="User's city", example="New York"),
     *                 @OA\Property(property="state", type="string", nullable=true, description="User's state", example="NY"),
     *                 @OA\Property(property="country", type="string", nullable=true, description="User's country", example="USA"),
     *                 @OA\Property(property="postal_code", type="string", nullable=true, description="User's postal code", example="10001"),
     *                 @OA\Property(property="first_name_bit", type="integer", example=1),
     *                 @OA\Property(property="last_name_bit", type="integer", example=1),
     *                 @OA\Property(property="email_bit", type="integer", example=1),
     *                 @OA\Property(property="address_1_bit", type="integer", example=1),
     *                 @OA\Property(property="address_2_bit", type="integer", example=1),
     *                 @OA\Property(property="postal_code_bit", type="integer", example=1),
     *                 @OA\Property(property="state_bit", type="integer", example=1),
     *                 @OA\Property(property="country_bit", type="integer", example=1),
     *                 @OA\Property(property="birthday_bit", type="integer", example=1),
     *                 @OA\Property(property="city_bit", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile Updated Successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The given data was invalid or something went wrong.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Authentication required.")
     *         )
     *     )
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
            DB::beginTransaction();
            $user->update($input) ;
            $userTags = $request->user_tags;

            if(isset($input['user_tags'])){
                // Step 1: Update or create new tags
                if (isset($userTags) && $userTags) {
                    foreach ($userTags as $tagId) {
                        UserTag::updateOrCreate(
                            ['user_id' => $user->id, 'tag_id' => $tagId],
                        );
                    }
                }
                UserTag::where('user_id', $user->id)
                    ->whereNotIn('tag_id', $userTags) // Find tags that are not in the new selection
                    ->delete();
            }

            $userModel = User::with('tags')->find($user->id);
            DB::commit();
            return  $this->resProvider->apiJsonResponse(200, trans('message.success.update_profile'), $userModel, '');
        }catch(Exception $e){
            DB::rollBack();
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), $e->getMessage(), '');
        }
    }

    /**
     * @OA\Get(
     *     path="/user/profile",
     *     tags={"Profile"},
     *     summary="Get logged-in user profile",
     *     description="Fetches the profile details of the authenticated user.",
     *     operationId="getUserProfile",
     *     security={{"clientAuth": {}}}, 
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User profile not found.")
     *         )
     *     )
     * )
     */

    public function getProfile(Request $request){
        $user = $request->user();
        $user->profile_picture = !empty($user->profile_picture_path) ? $user->profile_picture_path : null;
        // Load the tags relationship
        $user->load('tags');

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
     * @OA\Post(
     *     path="/send-otp",
     *     tags={"Profile"},
     *     summary="Send OTP to verify phone number",
     *     description="Sends a One-Time Password (OTP) to a valid phone number for verification.",
     *     operationId="sendOtp",
     *     security={{"clientAuth":{}}},
     *     @OA\Parameter(
     *         name="phone_number",
     *         in="query",
     *         required=true,
     *         description="A 10-digit valid phone number is required.",
     *         @OA\Schema(type="string", example="9876543210")
     *     ),
     *     @OA\Parameter(
     *         name="mobile_carrier",
     *         in="query",
     *         required=true,
     *         description="Mobile carrier is required.",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP has been sent to your phone number.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="OTP has been sent successfully."),
     *             @OA\Property(property="otp", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The given data was invalid or something went wrong.")
     *         )
     *     )
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
     * @OA\Post(
     *   path="/verify-otp",
     *   tags={"Profile"},
     *   summary="Verify OTP sent to the phone number",
     *   description="Verify OTP sent to the phone number",
     *   operationId="verifyOtp",
     *   security={{"clientAuth":{}}},
     *   @OA\Parameter(
     *     name="otp",
     *     in="query",
     *     required=true,
     *     description="6-digit OTP is required",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Phone number has been verified successfully.",
     *     @OA\JsonContent(
     *         @OA\Property(property="status", type="string", example="success"),
     *         @OA\Property(property="message", type="string", example="Phone number has been verified successfully.")
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Invalid OTP or invalid request data",
     *     @OA\JsonContent(
     *         oneOf={
     *             @OA\Schema(
     *                 @OA\Property(property="status", type="string", example="error"),
     *                 @OA\Property(property="message", type="string", example="The given data was invalid.")
     *             ),
     *             @OA\Schema(
     *                 @OA\Property(property="status", type="string", example="error"),
     *                 @OA\Property(property="message", type="string", example="Invalid One Time Verification Code.")
     *             )
     *         }
     *     )
     *   )
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
     * @OA\Get(
     *     path="/get-languages",
     *     tags={"Languages"},
     *     summary="Retrieve available languages",
     *     description="Fetches a list of supported languages.",
     *     operationId="getLanguages",
     *     security={{"clientAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="English")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Something went wrong",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Something went wrong")
     *         )
     *     )
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
     * @OA\Get(
     *   path="/user/profile/{id}",
     *   tags={"Profile"},
     *   summary="Get user profile",
     *   description="Fetches the profile details of a user by their ID. Some fields may be hidden based on privacy settings.",
     *   operationId="getUserProfileByID",
     *   security={{"clientAuth":{}}},
     *   @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       description="User ID",
     *       @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="User profile retrieved successfully",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="id", type="integer", description="User ID"),
     *           @OA\Property(property="first_name", type="string", description="First name"),
     *           @OA\Property(property="last_name", type="string", description="Last name"),
     *           @OA\Property(property="email", type="string", format="email", description="User email"),
     *           @OA\Property(property="nick_names", type="array", @OA\Items(type="string"), description="User's public nicknames")
     *       )
     *   ),
     *   @OA\Response(response=404, description="User not found"),
     *   @OA\Response(response=400, description="Exception occurred"),
     *   @OA\Response(response=401, description="Unauthorized")
     * )
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
     * @OA\Get(
     *     path="/user/all-supported-camps/{id}",
     *     tags={"Profile"},
     *     summary="Get all supported camps by a user",
     *     description="Fetches all camps supported by a specific user based on their ID.",
     *     operationId="getUserSupportedCamps",
     *     security={{"clientAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the user",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user_id", type="integer", description="User ID"),
     *             @OA\Property(
     *                 property="camps",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="camp_id", type="integer", description="Camp ID"),
     *                     @OA\Property(property="camp_name", type="string", description="Name of the camp")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=400, description="Exception occurred"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
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
     * @OA\Get(
     *     path="/user/supports/{id}",
     *     tags={"Profile"},
     *     summary="Get user supports",
     *     description="Fetches the supported camps and profile details of a user by their nickname ID.",
     *     operationId="getUserSupports",
     *     security={{"clientAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The nickname ID of the user",
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="namespace",
     *         in="query",
     *         required=false,
     *         description="Namespace filter (optional, default is 1)",
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with user support details"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation errors or exception occurred"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
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
                $privateFlags = $user->private_flags ? explode(",",$user->private_flags) : [];
                foreach($privateFlags as $private)
                {
                    // unset($userArray[$private]);
                    $userArray[$private] = Str::mask($userArray[$private], '•', strlen($userArray[$private]) > 2 && ($private === 'first_name' || $private === 'last_name') ? 1 : 0, strlen($userArray[$private]));
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
     * @OA\Post(
     *     path="/update-profile-picture",
     *     tags={"Profile"},
     *     summary="Upload and update user profile picture",
     *     description="This endpoint allows users to upload and update their profile picture.",
     *     operationId="updateProfilePicture",
     *     security={{"clientAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="profile_picture",
     *                     description="User profile picture",
     *                     type="string",
     *                     format="binary"
     *                 ),
     *                 @OA\Property(
     *                     property="is_update",
     *                     description="Flag to indicate if updating an existing profile picture",
     *                     type="boolean"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="The given data was invalid",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The given data was invalid")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="File upload failed.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="File upload failed.")
     *         )
     *     )
     * )
     */

    public function updateProfilePicture(Request $request, Validate $validate)
    {
        try {
            $user = $request->user();
            $input = $request->all();
    
            $validationErrors = $validate->validate($request, $this->rules->getUpdateProfilePictureValidatonRules(), $this->validationMessages->getUpdateProfilePictureValidationMessages());
            if ($validationErrors) {
                return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
            }
            
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
     * @OA\Delete(
     *   path="/update-profile-picture",
     *   tags={"Profile Picture"},
     *   summary="Delete user profile picture",
     *   description="Deletes the user's profile picture.",
     *   operationId="deleteProfilePicture",
     *   security={{"clientAuth":{}}},
     *   @OA\Response(
     *       response=200,
     *       description="Profile updated successfully."
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Exception message"
     *   ),
     *   @OA\Response(
     *       response=404,
     *       description="File not found"
     *   )
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

    /**
     * @OA\Get(
     *   path="/change-email-request",
     *   tags={"Profile"},
     *   summary="Request OTP for email change",
     *   description="Generates an OTP and sends it to the user's registered email for verification.",
     *   operationId="changeEmailRequest",
     *   security={{"clientAuth":{}}},
     *   @OA\Response(response=200, description="OTP sent successfully"),
     *   @OA\Response(response=400, description="Failed to generate OTP"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */


    public function changeEmailRequest(Request $request)
    {
        $user = $request->user();
        $otp = mt_rand(100000, 999999);
        $result['otp'] = $otp;
        $result['subject'] = "Canonizer - Change Email with  One-Time Passcode (OTP)";
        
        $user->otp = $otp;
        $user->update();
        Event::dispatch(new EmailChangeEvent($user,true));
        $res = (object)[
            "status_code" => 200,
            "message"     => trans('message.email.change_request_with_otp',['email'=>$user->email]),
            "error"       => null,
            "data"        => $user
        ];
        return (new SuccessResource($res))->response()->setStatusCode(200);
    }

    /**
     * @OA\Post(
     *   path="/emailchange-verify-otp",
     *   tags={"Profile"},
     *   summary="Verify OTP for email change",
     *   description="Validates the OTP entered by the user for email change.",
     *   operationId="emailChangeOtpVerification",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               @OA\Property(property="otp", type="string", description="One-Time Passcode sent to email")
     *           )
     *       )
     *   ),
     *   @OA\Response(response=200, description="OTP verified successfully"),
     *   @OA\Response(response=400, description="Invalid OTP"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */


    public function emailChangeOtpVerification(Request $request)
    {
        $user = $request->user();
        $all = $request->all();
        if($user->otp == $all['otp']){
            $user->otp = '';
            $user->update();
            return $this->resProvider->apiJsonResponse(200, trans('message.email.change_request_verfied'), '', '');
        }else{
            return $this->resProvider->apiJsonResponse(400, trans('message.email.change_request_failed'), '', '');
        }
    }

    /**
     * @OA\Post(
     *   path="/update-email-request",
     *   tags={"Profile"},
     *   summary="Update user email",
     *   description="Updates the user's email after OTP verification.",
     *   operationId="updateEmailRequest",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               @OA\Property(property="email", type="string", format="email", description="New email address")
     *           )
     *       )
     *   ),
     *   @OA\Response(response=200, description="Email update request successful"),
     *   @OA\Response(response=400, description="Validation errors or email already in use"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */

    public function updateEmailRequest(Request $request, Validate $validate)
    {
        $user = $request->user();
        $input = $request->all();
        $email = $input['email'];

        $validationErrors = $validate->validate($request, $this->rules->getUpdateEmailRules(), $this->validationMessages->getEmailUpdateValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        
        $otp = mt_rand(100000, 999999);
        $user->otp = $otp;
        $user->update();
        //verify email by sending OTP to new Email
        Event::dispatch(new EmailChangeEvent($user,false, $email));
        return $this->resProvider->apiJsonResponse(200, trans('message.email.verify_new_email', ['email'=>$email]), '', '');
    }

    /**
     * @OA\Post(
     *   path="/update-email",
     *   tags={"Profile"},
     *   summary="Verify and update user email",
     *   description="Verifies OTP and updates the user's email. Can also set the new email as primary.",
     *   operationId="verifyAndUpdateEmail",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               @OA\Property(property="email", type="string", format="email", description="New email address"),
     *               @OA\Property(property="otp", type="string", description="One-Time Passcode for verification"),
     *               @OA\Property(property="set_primary", type="boolean", description="Set this email as primary (optional)")
     *           )
     *       )
     *   ),
     *   @OA\Response(response=200, description="Email updated successfully"),
     *   @OA\Response(response=400, description="Invalid OTP or validation error"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */

    public function verifyAndUpdateEmail(Request $request,  Validate $validate)
    {
        $user = $request->user();
        $all = $request->all();
        $email = $all['email'];
        $setPrimary = ($all['set_primary']) ? $all['set_primary'] : 0;


        $validationErrors = $validate->validate($request, $this->rules->getVerfiyAndUpdateEmailRules(), $this->validationMessages->getVerfiyAndUpdateEmaiMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        if($user->otp == $all['otp']){
            $user->otp = '';
            $user->email = $email;
            $user->update();

            if($setPrimary){
                
                UserEmail::where('user_id',$user->id)->update(['is_primary' => 0]);
                UserEmail::where('email','=', $email)->update(['is_primary' => 1]);

                return $this->resProvider->apiJsonResponse(200, trans('message.email.newemail_added_verified'), $user, '');
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.email.updated_email'), $user, '');
        }else{
            return $this->resProvider->apiJsonResponse(400, trans('message.email.change_request_failed'), '', '');
        }
    }

    /**
     * @OA\Post(
     *   path="/add-email",
     *   tags={"Profile"},
     *   summary="Add a new email",
     *   description="Adds a new email to the user’s account. If marked as primary, an OTP will be sent for verification.",
     *   operationId="addEmail",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               @OA\Property(property="email", type="string", format="email", description="New email address"),
     *               @OA\Property(property="is_primary", type="boolean", description="Set as primary email (optional)")
     *           )
     *       )
     *   ),
     *   @OA\Response(response=200, description="Email added successfully"),
     *   @OA\Response(response=400, description="Validation errors"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */

    public function addEmail(Request $request, Validate $validate)
    {
        $user = $request->user();
        $all = $request->all();
        $email = $all['email'];
        $isPrimary = isset($all['is_primary']) && $all['is_primary'] ? $all['is_primary'] : 0;


        $validationErrors = $validate->validate($request, $this->rules->getAddEmailRules(), $this->validationMessages->getAddEmailMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $userEmail = new UserEmail;
        $userEmail->user_id = $user->id;
        $userEmail->email = $email;
        //$userEmail->is_primary = isset($all['is_primary']) && $all['is_primary'] ? $all['is_primary'] : 0;
        $userEmail->save();
        if($isPrimary){
            $otp = mt_rand(100000, 999999);
            $user->otp = $otp;
            $user->update();
            $data['email'] = $userEmail->email;
            $data['otp'] = $otp;
            //verify email by sending OTP to new Email
            Event::dispatch(new EmailChangeEvent($user,false, $email));
            return $this->resProvider->apiJsonResponse(200, trans('message.email.newemail_added_verify'), $data, '');
        }else{
            return $this->resProvider->apiJsonResponse(200, trans('message.email.newemail_added'), $userEmail, '');
        }
        return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $user, '');
    }

    /**
     * @OA\Get(
     *   path="/users-email",
     *   tags={"Profile"},
     *   summary="Get all emails linked to the user",
     *   description="Retrieves all email addresses associated with the logged-in user.",
     *   operationId="getAllEmail",
     *   security={{"clientAuth":{}}},
     *   @OA\Response(response=200, description="List of emails retrieved successfully"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */

    public function getAllEmail(Request $request)
    {
        $user = $request->user();
        $emailList = UserEmail::getAll($user->id);
        return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $emailList, '');
    }

    /**
     * @OA\Post(
     *   path="/gravatar",
     *   tags={"Profile"},
     *   summary="Retrieve Gravatar image for an email",
     *   description="Fetches the Gravatar profile image associated with the given email address.",
     *   operationId="getGravatar",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               @OA\Property(property="email", type="string", format="email", description="Email address to retrieve Gravatar")
     *           )
     *       )
     *   ),
     *   @OA\Response(response=200, description="Gravatar image retrieved successfully"),
     *   @OA\Response(response=404, description="Gravatar not found"),
     *   @OA\Response(response=400, description="Validation errors"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */

    public function getGravatar(Request $request, Validate $validate)
    {
        try {
            $input = $request->all();
            $validationErrors = $validate->validate($request, $this->rules->getGravatarValidatonRules(), 
            $this->validationMessages->getGravatarValidationMessages());
            if ($validationErrors) {
                return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
            }
            $emailHash = md5($input['email']);
            $gravatarUrl = "https://www.gravatar.com/avatar/$emailHash?d=404";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $gravatarUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
            $imageData = curl_exec($ch);
            if ($imageData === "404 Not Found") {
                return $this->resProvider->apiJsonResponse(200, trans('message.error.record_not_found'), null, '');
            }
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            $responseData = [
                'image_data' => base64_encode($imageData), // or adjust based on your data
                'content_type' => $contentType,
            ];
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $responseData, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
    
}

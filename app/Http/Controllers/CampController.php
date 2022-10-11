<?php

namespace App\Http\Controllers;

use stdClass;
use Exception;
use Throwable;
use App\Models\Camp;
use App\Facades\Util;
use App\Models\Topic;
use App\Models\Support;
use App\Library\General;
use App\Models\Nickname;
use App\Helpers\CampForum;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Jobs\ActivityLoggerJob;
use App\Models\CampSubscription;
use App\Helpers\ResourceInterface;
use App\Helpers\ResponseInterface;
use Illuminate\Support\Facades\DB;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use Illuminate\Support\Facades\Event;
use App\Http\Request\ValidationMessages;
use App\Events\ThankToSubmitterMailEvent;
use App\Jobs\ObjectionToSubmitterMailJob;
use Illuminate\Support\Facades\Gate;
use App\Facades\GetPushNotificationToSupporter;

class CampController extends Controller
{

    public function __construct(ResponseInterface $respProvider, ResourceInterface $resProvider, ValidationRules $rules, ValidationMessages $validationMessages)
    {
        $this->rules = $rules;
        $this->validationMessages = $validationMessages;
        $this->resourceProvider  = $resProvider;
        $this->resProvider = $respProvider;
    }


    /**
     * @OA\POST(path="/camp/save",
     *   tags={"Camp"},
     *   summary="save camp",
     *   description="This API is use for save camp",
     *   operationId="campSave",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *    @OA\RequestBody(
     *     required=true,
     *     description="Request Body Json Parameter",
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *               @OA\Property(
     *                  property="camp_name",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="parent_camp_num",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="topic_num",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="nick_name",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="note",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="key_words",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="camp_about_url",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="camp_about_nick_id",
     *                  type="string"
     *              )
     *          )
     *     ),
     *   ),
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="object",
     *                                 @OA\Property(
     *                                         property="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="data",
     *                                         type="object",
     *                                           @OA\Property(
     *                                              property="camp_num",
     *                                              type="integer"
     *                                          )
     *                                    )
     *                                 )
     *                            ),
     *
     *    @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *    @OA\Response(
     *     response=403,
     *     description="Exception Throwable",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     *
     * )
     */

    public function store(Request $request, Validate $validate)
    {

        $validationErrors = $validate->validate($request, $this->rules->getCampStoreValidationRules(), $this->validationMessages->getCampStoreValidationMessages());

        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        if (! Gate::allows('nickname-check', $request->nick_name)) {
            return $this->resProvider->apiJsonResponse(403, trans('message.error.invalid_data'), '', '');
        }
                
        try {

            $liveCamps = Camp::getAllLiveCampsInTopic($request->topic_num);
            $nonLiveCamps = Camp::getAllNonLiveCampsInTopic($request->topic_num);
            $camp_existsLive = 0;
            $camp_existsNL = 0;

            if(!empty($liveCamps)){
                foreach($liveCamps as $value){
                    if(strtolower(trim($value->camp_name)) == strtolower(trim($request->camp_name))){
                            $camp_existsLive = 1;
                    }
                }
            }

            if(!empty($nonLiveCamps)){
                foreach($nonLiveCamps as $value){
                    if(strtolower(trim($value->camp_name)) == strtolower(trim($request->camp_name))){
                             $camp_existsNL = 1;
                    }
                }
            }

            if($camp_existsLive || $camp_existsNL){
                $result = Camp::where('topic_num', $request->topic_num)->where('camp_name', $request->camp_name)->first();
                if (!empty($result)) {
                    $topic_name = Topic::select('topic_name')->where('topic_num', $request->topic_num)->first();
                    $status = 400;
                    $result->if_exist = true;
                    $result->topic_name = $topic_name->topic_name;
                    $error['camp_name'][] = trans('message.validation_camp_store.camp_name_unique');
                    $message = trans('message.error.invalid_data');
                    return $this->resProvider->apiJsonResponse($status, $message, $result, $error);
                }
            }

            $parentCamp = Camp::getParentFromParent($request->parent_camp_num,$request->topic_num);
            $is_disabled = false;
            $is_one_level = false;
            $allowUnderCamp = [];
            foreach($parentCamp as $val){
                if($val->is_disabled === 1){
                    $is_disabled = true; 
                }
                if($val->is_one_level === 1){
                    $is_one_level = true; 
                    $allowUnderCamp[] = $val->camp_num;
                }
            }

            if($is_disabled == true){
                $message = trans('message.validation_camp_store.camp_creation_not_allowed');
                $status = 400;
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }
            if($is_one_level == true){
                if (!in_array($request->parent_camp_num, $allowUnderCamp)){
                    $message = trans('message.validation_camp_store.camp_only_one_level_allowed');
                    $status = 400;
                    return $this->resProvider->apiJsonResponse($status, $message, null, null);
               }
            }

            $current_time = time();

            ## check if mind_expert topic and camp abt nick name id is null then assign nick name as about nickname ##
            if ($request->topic_num == config('global.mind_expert_topic_num') && !isset($request->camp_about_nick_id)) {
                $request->camp_about_nick_id = $request->nick_name ?? "";
            } else {
                $request->camp_about_nick_id = $request->camp_about_nick_id ?? "";
            }

            $nextCampNum = Camp::where('topic_num', $request->topic_num)->max('camp_num');
            $nextCampNum++;
            $input = [
                "camp_name" =>  Util::remove_emoji($request->camp_name),
                "camp_num" => $nextCampNum,
                "parent_camp_num" => $request->parent_camp_num,
                "topic_num" => $request->topic_num,
                "submit_time" => strtotime(date('Y-m-d H:i:s')),
                "submitter_nick_id" => $request->nick_name,
                "go_live_time" =>  $current_time,
                "language" => 'English',
                "note" => $request->note ?? "",
                "key_words" =>  Util::remove_emoji($request->key_words ?? ""),
                "camp_about_url" => Util::remove_emoji($request->camp_about_url  ?? ""),
                "title" => $request->title ?? "",
                "camp_about_nick_id" =>  $request->camp_about_nick_id,
                "grace_period" => 0,
                "is_disabled" =>  !empty($request->is_disabled) ? $request->is_disabled : 0,
                "is_one_level" =>  !empty($request->is_one_level) ? $request->is_one_level : 0,
            ];

            $camp = Camp::create($input);

            if ($camp) {
                $topic = Topic::getLiveTopic($camp->topic_num, $request->asof);
                Util::dispatchJob($topic, $camp->camp_num, 1);
                $camp_id = $camp->camp_num ?? 1;
                $filter['topicNum'] = $request->topic_num;
                $filter['asOf'] = $request->asof;
                $filter['campNum'] = $camp_id;
                $livecamp = Camp::getLiveCamp($filter);
                $link = Util::getTopicCampUrl($topic->topic_num, $camp_id, $topic, $livecamp, time());
                try {
                    $dataEmail = (object) [
                        "type" => "camp",
                        "link" =>  $link,
                        "historylink" => Util::topicHistoryLink($topic->topic_num, $camp->camp_num, $topic->topic_name, $camp->camp_name, 'camp'),
                        "object" =>  $topic->topic_name . " / " . $camp->camp_name,
                    ];
                    Event::dispatch(new ThankToSubmitterMailEvent($request->user(), $dataEmail));
                    $activitLogData = [
                        'log_type' =>  "topic/camps",
                        'activity' => 'Camp created',
                        'url' => $link,
                        'model' => $camp,
                        'topic_num' => $filter['topicNum'],
                        'camp_num' =>   $filter['campNum'],
                        'user' => $request->user(),
                        'nick_name' => Nickname::getNickName($request->nick_name)->nick_name,
                        'description' =>  $request->camp_name
                    ];
                    dispatch(new ActivityLoggerJob($activitLogData))->onQueue(env('QUEUE_SERVICE_NAME'));
                    GetPushNotificationToSupporter::pushNotificationToSupporter($request->user(), $request->topic_num, $camp->camp_num, config('global.notification_type.Camp'));
                } catch (Throwable $e) {
                    $data = null;
                    $status = 403;
                    $message = $e->getMessage();
                    return $this->resProvider->apiJsonResponse($status, $message, null, null);
                }
                $data = [
                    "camp_num" =>  $camp_id,
                ];
                $status = 200;
                $message = trans('message.success.camp_created');
            } else {
                $data = null;
                $status = 400;
                $message = trans('message.error.camp_failed');
            }
            return $this->resProvider->apiJsonResponse($status, $message, $data, null);
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, $e->getMessage(), null, null);
        }
    }

    /**
     * @OA\Post(path="/get-camp-record",
     *   tags={"Camp"},
     *   summary="get camp record",
     *   description="Used to get camp record.",
     *   operationId="getCampRecord",
     *   @OA\RequestBody(
     *       required=true,
     *       description="Get camp records",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               @OA\Property(
     *                   property="topic_num",
     *                   description="topic number is required",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="camp_num",
     *                   description="Camp number is required",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="as_of",
     *                   description="As of filter type",
     *                   required=false,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="as_of_date",
     *                   description="As of filter date",
     *                   required=false,
     *                   type="string",
     *               )
     *          )
     *      )
     *   ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */

    public function getCampRecord(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getCampRecordValidationRules(), $this->validationMessages->getCampRecordValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $filter['topicNum'] = $request->topic_num;
        $filter['asOf'] = $request->as_of;
        $filter['asOfDate'] = $request->as_of_date;
        $filter['campNum'] = $request->camp_num;
        $parentCampName = null;
        $camp = [];
        try {
            $livecamp = Camp::getLiveCamp($filter);
            if ($livecamp) {
                $livecamp->nick_name = $livecamp->nickname->nick_name ?? trans('message.general.nickname_association_absence');
                $parentCamp = Camp::campNameWithAncestors($livecamp, $filter);
                if ($request->user()) {
                    $campSubscriptionData = Camp::getCampSubscription($filter, $request->user()->id);
                    $livecamp->flag = $campSubscriptionData['flag'];
                    $livecamp->subscriptionId = $campSubscriptionData['camp_subscription_data'][0]['subscription_id'] ?? null;
                    $livecamp->subscriptionCampName = $campSubscriptionData['camp_subscription_data'][0]['camp_name'] ?? null;
                }
                if ($livecamp->parent_camp_num != null && $livecamp->parent_camp_num > 0) {
                    $parentCampName = CampForum::getCampName($filter['topicNum'], $livecamp->parent_camp_num);
                }
                $livecamp->parent_camp_name = $parentCampName;
                $camp[] = $livecamp;
                $indexs = ['topic_num', 'camp_num', 'camp_name', 'key_words', 'camp_about_url', 'nick_name', 'flag', 'subscriptionId', 'subscriptionCampName', 'parent_camp_name','is_disabled','is_one_level'];
                $camp = $this->resourceProvider->jsonResponse($indexs, $camp);
                $camp = $camp[0];
                $camp['parentCamps'] = $parentCamp;
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $camp, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\POST(path="/camp/allParent",
     *   tags={"Camp"},
     *   summary="Get All Parent",
     *   description="This API is use for get all parent",
     *   operationId="allParent",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *    @OA\RequestBody(
     *     required=true,
     *     description="Request Body Json Parameter",
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *               @OA\Property(
     *                  property="topic_num",
     *                  type="string"
     *              )
     *          )
     *     ),
     *   ),
     *     @OA\Response(
     *         response=200,
     *        description = "Success",
     *        @OA\JsonContent(
     *             type="object",
     *              @OA\Property(
     *                   property="status_code",
     *                   type="integer"
     *               ),
     *               @OA\Property(
     *                   property="message",
     *                   type="string"
     *               ),
     *              @OA\Property(
     *                   property="error",
     *                   type="string"
     *              ),
     *             @OA\Property(
     *                property="data",
     *                type="array",
     *                @OA\Items(
     *                    @OA\Property(
     *                          property="id",
     *                          type="integer"
     *                    ),
     *                    @OA\Property(
     *                          property="topic_num",
     *                          type="integer"
     *                    ),
     *                    @OA\Property(
     *                          property="parent_camp_num",
     *                          type="integer"
     *                     ),
     *                     @OA\Property(
     *                           property="key_words",
     *                           type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="language",
     *                           type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="camp_num",
     *                           type="integer"
     *                     ),
     *                     @OA\Property(
     *                           property="note",
     *                           type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="submit_time",
     *                           type="integer"
     *                     ),
     *                     @OA\Property(
     *                           property="submitter_nick_id",
     *                           type="integer"
     *                     ),
     *                     @OA\Property(
     *                           property="go_live_time",
     *                           type="integer"
     *                     ),
     *                     @OA\Property(
     *                           property="objector_nick_id",
     *                           type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="object_time",
     *                           type="integer"
     *                     ),
     *                     @OA\Property(
     *                           property="object_reason",
     *                           type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="proposed",
     *                           type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="replacement",
     *                           type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="title",
     *                           type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="camp_name",
     *                           type="string" 
     *                     ),
     *                     @OA\Property(
     *                           property="camp_about_url",
     *                           type="string"
     *                     ),
     *                     @OA\Property(
     *                           property="camp_about_nick_id",
     *                           type="integer"
     *                     ),
     *                     @OA\Property(
     *                           property="grace_period",
     *                           type="integer"
     *                     )
     *                ),
     *             ),
     *        ),
     *     ),
     *
     *
     *     @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *    @OA\Response(
     *     response=403,
     *     description="Exception Throwable",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     * )
     */

    public function getAllParentCamp(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getAllParentCampValidationRules(), $this->validationMessages->getAllParentCampValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        try {
            $result = Camp::getAllParentCamp($request->topic_num, $request->filter, $request->asOfDate);
            $result = Camp::filterParentCampForForm($result,$request->topic_num,$request->parent_camp_num);
            if($request->camp_num){
                $camp = Camp::getLiveCamp(['topicNum' => $request->topic_num, 'campNum' => $request->camp_num, 'asOf' => 'default']);
                $childCamps = array_unique(Camp::getAllChildCamps($camp));
                foreach($result as $key => $val){
                    if(in_array($val->camp_num, $childCamps)){
                        unset($result[$key]);
                    }
                }
                $result = array_unique($result);
            }
            if (empty($result)) {
                $status = 200;
                $message = trans('message.error.record_not_found');
                return $this->resProvider->apiJsonResponse($status, $message, $result, null);
            }
            $data = $result;
            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, $data, null);
        } catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }

    /**
     * @OA\Get(
     *     path="/camp/allAboutNickName",
     *     summary="API For Get all About Nick Name",
     *     tags={"Camp"},
     *      @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *     @OA\Response(
     *         response=200,
     *        description = "Success",
     *        @OA\JsonContent(
     *             type="object",
     *              @OA\Property(
     *                   property="status_code",
     *                   type="integer"
     *               ),
     *               @OA\Property(
     *                   property="message",
     *                   type="string"
     *               ),
     *              @OA\Property(
     *                   property="error",
     *                   type="string"
     *              ),
     *             @OA\Property(
     *                property="data",
     *                type="array",
     *                @OA\Items(
     *                      @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=""
     *                      ),
     *                      @OA\Property(
     *                         property="owner_code",
     *                         type="string",
     *                         example=""
     *                      ),
     *                      @OA\Property(
     *                         property="nick_name",
     *                         type="string",
     *                         example=""
     *                      ),
     *                      @OA\Property(
     *                         property="create_time",
     *                         type="string",
     *                         example=""
     *                      ),
     *                      @OA\Property(
     *                         property="private",
     *                         type="integer",
     *                         example=""
     *                      ),
     *                ),
     *             ),
     *        ),
     *     ),
     *
     *
     *     @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *    @OA\Response(
     *     response=403,
     *     description="Exception Throwable",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     * )
     */

    public function getAllAboutNickName(Request $request, Validate $validate)
    {
        try {
            $allNicknames = DB::table('nick_name')
                ->select(
                    DB::raw("id, owner_code, TRIM(nick_name) nick_name, create_time , private")
                )->orderBy('nick_name', 'ASC')->get();
            if (empty($allNicknames)) {
                $status = 400;
                $message = trans('message.error.exception');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }

            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, $allNicknames, null);
        } catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }

    /**
     * @OA\POST(path="/camp/getTopicNickNameUsed",
     *   tags={"Camp"},
     *   summary="Get Topic Nick Name Used",
     *   description="This API is use for get Topic Nick Name Used",
     *   operationId="getTopicNickNameUsed",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *    @OA\RequestBody(
     *     required=true,
     *     description="Request Body Json Parameter",
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *               @OA\Property(
     *                  property="topic_num",
     *                  type="string"
     *              )
     *          )
     *     ),
     *   ),
     *     @OA\Response(
     *         response=200,
     *        description = "Success",
     *        @OA\JsonContent(
     *             type="object",
     *              @OA\Property(
     *                   property="status_code",
     *                   type="integer"
     *               ),
     *               @OA\Property(
     *                   property="message",
     *                   type="string"
     *               ),
     *              @OA\Property(
     *                   property="error",
     *                   type="string"
     *              ),
     *             @OA\Property(
     *                property="data",
     *                type="array",
     *                @OA\Items(
     *                    @OA\Property(
     *                          property="id",
     *                          type="integer"
     *                    ),
     *                     @OA\Property(
     *                           property="nick_name",
     *                           type="string"
     *                     )
     *                ),
     *             ),
     *        ),
     *     ),
     *
     *
     *     @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *    @OA\Response(
     *     response=403,
     *     description="Exception Throwable",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     * )
     */

    public function getTopicNickNameUsed(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getAllParentCampValidationRules(), $this->validationMessages->getAllParentCampValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        try {
            $allNicknames = Nickname::topicNicknameUsed($request->topic_num);
            if (empty($allNicknames)) {
                $status = 400;
                $message = trans('message.error.exception');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
            }
            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, $allNicknames, null);
        } catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }

    /**
     * @OA\POST(path="/camp/subscription",
     *   tags={"Camp"},
     *   summary="Subscribe or unsubscribe to a camp or all topic camps",
     *   description="This API is used to subscribe or unsubscribe to a specific camp or all topic camps.",
     *   operationId="campSubscription",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *   ),
     *   @OA\RequestBody(
     *       required=true,
     *       description="Subscribe or unsubscribe to a camp or topic",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               @OA\Property(
     *                   property="topic_num",
     *                   description="Topic number is required",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="camp_num",
     *                   description="Camp number is required",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="checked",
     *                   description="Subscribe or unsubscribe",
     *                   required=true,
     *                   type="boolean",
     *               ),
     *               @OA\Property(
     *                   property="subscription_id",
     *                   description="Previous subscription id",
     *                   required=false,
     *                   type="integer",
     *               ),
     *           )
     *       )
     *   ), 
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function campSubscription(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getAllCampSubscriptionValidationRules(), $this->validationMessages->getAllCampSubscriptionValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $filter['campNum'] = $request->camp_num;
        $filter['topicNum'] = $request->topic_num;
        $filter['checked'] = $request->checked;
        $filter['subscriptionId'] = $request->subscription_id ?? "";
        $response = new stdClass();
        try {
            $campSubscriptionData = CampSubscription::where('user_id', '=', $request->user()->id)->where('camp_num', '=', $filter['campNum'])->where('topic_num', '=', $filter['topicNum'])->where('subscription_start', '<=', strtotime(date('Y-m-d H:i:s')))->where('subscription_end', '=', null)->orWhere('subscription_end', '>=', strtotime(date('Y-m-d H:i:s')))->first();
            if ($filter['checked'] && empty($campSubscriptionData)) {
                $campSubscription = new CampSubscription;
                $campSubscription->user_id = $request->user()->id;
                $campSubscription->topic_num = $filter['topicNum'];
                $campSubscription->camp_num = $filter['campNum'];
                $campSubscription->subscription_start = strtotime(date('Y-m-d H:i:s'));
                $msg = trans('message.success.subscribed');
            } elseif ($filter['checked'] && $campSubscriptionData) {
                return $this->resProvider->apiJsonResponse(200, trans('message.validation_subscription_camp.already_subscribed'), [], '');
            } else {
                $campSubscription = CampSubscription::where('user_id', '=', $request->user()->id)->where('id', '=', $filter['subscriptionId'])->where('subscription_end', '=', null)->first();
                if (empty($campSubscription)) {
                    return $this->resProvider->apiJsonResponse(200, trans('message.validation_subscription_camp.already_unsubscribed'), [], '');
                }
                $campSubscription->subscription_end = strtotime(date('Y-m-d H:i:s'));
                $msg = trans('message.success.unsubscribed');
            }
            $campSubscription->save();
            $filter['subscriptionId'] = ($filter['subscriptionId'] && !$filter['checked']) ? "" : $campSubscription->id;
            $campSubscriptionData = Camp::getCampSubscription($filter, $request->user()->id);
            $response->flag = $campSubscriptionData['flag'];
            $response->subscriptionId = $campSubscriptionData['camp_subscription_data'][0]['subscription_id'] ?? $filter['subscriptionId'];
            $response->subscriptionCampName = $campSubscriptionData['camp_subscription_data'][0]['camp_name'] ??  null;
            $response->msg = $msg;
            $indexes = ['msg', 'subscriptionId', 'flag', 'subscriptionId', 'subscriptionCampName'];
            $data[0] = $response;

            /* Update the subscription for Mongo Tree -- CAN-1162 */
            $topic = Topic::where('topic_num', $filter['topicNum'])->orderBy('id', 'DESC')->first();
            if(!empty($topic)) {
                Util::dispatchJob($topic, $filter['campNum'], 1);
            }
            
            $data = $this->resourceProvider->jsonResponse($indexes, $data);
            $data = $data[0];
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $data, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }


    /**
     * @OA\GET(path="/camp/subscription/list/",
     *   tags={"Camp"},
     *   summary="list posubscriptionst",
     *   description="This is use for get subscription list",
     *   operationId="subscriptionList",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *   @OA\Parameter(
     *         name="page",
     *         in="url",
     *         required=false,
     *         description="Add page field in query parameters",
     *         @OA\Schema(
     *              type="Query Parameters"
     *         ) 
     *    ),
     *   @OA\Parameter(
     *         name="per_page",
     *         in="url",
     *         required=false,
     *         description="Add per_page field in query parameters",
     *         @OA\Schema(
     *              type="Query Parameters"
     *         ) 
     *    ),
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="object",
     *                                 @OA\Property(
     *                                         property="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="data",
     *                                         type="object",
     *                                          @OA\Property(
     *                                              property="items",
     *                                              type="object",
     *                                                  @OA\Property(
     *                                                      property="topic_num",
     *                                                      type="integer"
     *                                                  ),
     *                                                 @OA\Property(
     *                                                      property="title",
     *                                                      type="string"
     *                                                 ),
     *                                                 @OA\Property(
     *                                                      property="title_link",
     *                                                      type="string"
     *                                                 ),
     *                                                 @OA\Property(
     *                                                      property="is_remove_subscription",
     *                                                      type="string"
     *                                                 ),
     *                                                 @OA\Property(
     *                                                      property="subscription_id",
     *                                                      type="string"
     *                                                 ),
     *                                                 @OA\Property(
     *                                                      property="camps",
     *                                                      type="object"
     *                                                 )
     *                                          ),
     *                                          @OA\Property(
     *                                              property="current_page",
     *                                              type="integer"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="per_page",
     *                                              type="integer"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="last_page",
     *                                              type="integer"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="total_rows",
     *                                              type=""
     *                                          ),
     *                                          @OA\Property(
     *                                              property="from",
     *                                              type="integer"
     *                                          ),
     *                                          @OA\Property(
     *                                              property="to",
     *                                              type="integer"
     *                                          )
     *                                    )
     *                                 )
     *                            ),
     *
     *    @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     *
     * )
     */

    public function campSubscriptionList(Request $request)
    {
        try {
            $user = $request->user();
            $userId = $user->id;
            $result = CampSubscription::where('user_id', $userId)->where('subscription_end', NULL)->orderBy('camp_subscription.id', 'desc')->get();
            $campSubscriptionList = [];
            foreach ($result as $subscription) {
                $topic = Topic::getLiveTopic($subscription->topic_num, 'default');
                $camp = ($subscription->camp_num != 0) ? Camp::getLiveCamp(['topicNum' => $subscription->topic_num, 'campNum' => $subscription->camp_num, 'asOf' => 'default']) : null;
                //echo "<pre>"; print_r($camp->camp_name);die;
                $tempCamp = [
                    'camp_num' => $subscription->camp_num,
                    'camp_name' => $camp->camp_name ?? '',
                    'camp_link' => Camp::campLink($subscription->topic_num, $subscription->camp_num, $topic->topic_name ?? '', $camp->camp_name ?? ''),
                    'subscription_start' => $subscription->subscription_start,
                    'subscription_id' => $subscription->id,
                ];
                if (isset($campSubscriptionList[$subscription->topic_num])) {
                    if ($subscription->camp_num != 0) {
                        $campSubscriptionList[$subscription->topic_num]['camps'][] = $tempCamp;
                    } else {
                        $campSubscriptionList[$subscription->topic_num]['is_remove_subscription'] = true;
                        $campSubscriptionList[$subscription->topic_num]['subscription_id'] = $subscription->id;
                    }
                } else {
                    $campSubscriptionList[$subscription->topic_num] = array(
                        'topic_num' => $subscription->topic_num,
                        'title' => $topic->topic_name ?? '',
                        'title_link' => Topic::topicLink($subscription->topic_num, 1, $topic->topic_name ?? ''),
                        'is_remove_subscription' => ($subscription->camp_num == 0),
                        'subscription_id' => ($subscription->camp_num == 0) ? $subscription->id : 0,
                        'camps' => ($subscription->camp_num == 0) ? [] : [$tempCamp],
                    );
                }
            }
            $per_page = !empty($request->per_page) ? $request->per_page : config('global.per_page');
            $currentPage = $request->page;
            $paginate = Util::paginate(array_values($campSubscriptionList), $per_page, $currentPage);
            $collection = Util::getPaginatorResponse($paginate);
            $status = 200;
            $message = trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, $collection, null);
        } catch (Throwable $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, $ex->getMessage());
        }
    }

    /**
     * @OA\Post(path="/get-camp-breadcrumb",
     *   tags={"Camp"},
     *   summary="get camp bread crumb",
     *   description="Used to get camp bread crumb.",
     *   operationId="getCampBreadCrumb",
     *   @OA\RequestBody(
     *       required=true,
     *       description="Get camp bread crumb",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               @OA\Property(
     *                   property="topic_num",
     *                   description="topic number is required",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="camp_num",
     *                   description="Camp number is required",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="as_of",
     *                   description="As of filter type",
     *                   required=false,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="as_of_date",
     *                   description="As of filter date",
     *                   required=false,
     *                   type="string",
     *               )
     *          )
     *      )
     *   ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */
    public function getCampBreadCrumb(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getCampBreadCrumbValidationRules(), $this->validationMessages->getCampBreadCrumbValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $filter['topicNum'] = $request->topic_num;
        $filter['asOf'] = $request->as_of;
        $filter['asOfDate'] = $request->as_of_date;
        $filter['campNum'] = $request->camp_num;
        $data = new stdClass();
        $data->flag = 0;
        $data->subscription_id = null;
        $data->subscribed_camp_name = null;
        try {
            $livecamp = Camp::getLiveCamp($filter);
            $data->bread_crumb = Camp::campNameWithAncestors($livecamp, $filter);
            $topic = Topic::select('topic_name')->where('topic_num', $request->topic_num)->first();
            if ($request->user()) {
                $campSubscriptionData = Camp::getCampSubscription($filter, $request->user()->id);
                $data->flag = $campSubscriptionData['flag'];
                $data->subscription_id = $campSubscriptionData['camp_subscription_data'][0]['subscription_id'] ??  null;
                $data->subscribed_camp_name = $campSubscriptionData['camp_subscription_data'][0]['camp_name'] ?? null;
            }
            $data->topic_name = $topic->topic_name ?? '';
            $indexs = ['bread_crumb', 'flag', 'subscription_id', 'subscribed_camp_name', 'topic_name'];
            $response[] = $data;
            $response = $this->resourceProvider->jsonResponse($indexs, $response);
            $response = $response[0];
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $response, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    public function getCampHistory(Request $request, Validate $validate)
    {
        $filter['topicNum'] = $request->topic_num;
        $filter['campNum'] = $request->camp_num;
        $filter['type'] = $request->type;
        $filter['per_page'] = $request->per_page;
        $filter['userId'] = $request->user()->id ?? null;
        $filter['currentTime'] = time();
        $response = new stdClass();
        $response->statement = [];
        $response->ifIAmImplicitSupporter = null;
        $response->ifIamSupporter = null;
        $response->ifSupportDelayed = null;
        $response->ifIAmExplicitSupporter = null;
        try {
            $response->topic = Camp::getAgreementTopic($filter);
            $liveCamp = Camp::getLiveCamp($filter);
            $campHistoryQuery = Camp::where('topic_num', $filter['topicNum'])->where('camp_num', '=', $filter['campNum'])->latest('submit_time');
            $submitTime = $liveCamp->submit_time;
            if ($request->user()) {
                $nickNames = Nickname::personNicknameArray();
                $response->ifIamSupporter = Support::ifIamSupporter($filter['topicNum'], $filter['campNum'], $nickNames, $submitTime);
                $response->ifIAmImplicitSupporter = Support::ifIamImplicitSupporter($filter, $nickNames, $submitTime);
                $response->ifSupportDelayed = Support::ifIamSupporter($filter['topicNum'], $filter['campNum'], $nickNames, $submitTime, true);
                $response->ifIAmExplicitSupporter = Support::ifIamExplicitSupporter($filter, $nickNames);
                $response = Camp::campHistory($campHistoryQuery, $filter, $response, $liveCamp);
            } else {
                $response = Camp::campHistory($campHistoryQuery, $filter, $response, $liveCamp);
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $response, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Get(path="/edit-camp",
     *   tags={"Camp"},
     *   summary="Get camp record",
     *   description="Get camp details for editing",
     *   operationId="editCampRecord",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *  @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Get camp details for editing",
     *         @OA\Schema(
     *              type="integer"
     *         ) 
     *    ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */
    public function editCampRecord($id)
    {
        try {
            $camp = Camp::where('id', $id)->first();
            if ($camp) {
                $filter['topicNum'] = $camp->topic_num;
                $filter['campNum'] = $camp->camp_num;
                $filter['asOf'] = 'default';
                $topic = Camp::getAgreementTopic($filter);
                $data = new stdClass();
                $data->topic = $topic;
                $data->nick_name = Nickname::topicNicknameUsed($camp->topic_num);
                $data->camp = $camp;
                $data->parent_camp = Camp::campNameWithAncestors($camp, $filter);
                $response[0] = $data;
                $indexes = ['camp', 'nick_name', 'parent_camp', 'topic'];
                $camp = $this->resourceProvider->jsonResponse($indexes, $response);
                $camp = $camp[0];
                return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $camp, '');
            } else {
                return $this->resProvider->apiJsonResponse(400, trans('message.error.record_not_found'), '', '');
            }
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(path="/manage-camp",
     *   tags={"Camp"},
     *   summary="Edit/update/object camp",
     *   description="This API is used to edit, update and object a camp.",
     *   operationId="edit/update/object-CampHistory",
     *   @OA\RequestBody(
     *       required=true,
     *       description="Manage camp",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *              @OA\Property(
     *                  property="topic_num",
     *                  description="Topic number is required",
     *                  required=true,
     *                  type="integer",
     *              ),
     *              @OA\Property(
     *                  property="camp_num",
     *                  description="Camp number is required",
     *                  required=true,
     *                  type="integer",
     *              ),
     *               @OA\Property(
     *                   property="nick_name",
     *                   description="Nick name of the user",
     *                   required=true,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="parent_camp_num",
     *                   description="Parent camp num",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="old_parent_camp_num",
     *                   description="Old parent camp num",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="camp_name",
     *                   description="Camp name",
     *                   required=true,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="keywords",
     *                   description="Keywords",
     *                   required=false,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="camp_about_url",
     *                   description="Camp about url",
     *                   required=false,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="note",
     *                   description="Note for camp",
     *                   required=false,
     *                   type="string",
     *               ),
     *              @OA\Property(
     *                   property="submitter",
     *                   description="Nick name id of user who previously added camp",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="event_type",
     *                   description="Possible values objection, edit, update",
     *                   required=true,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="objection_reason",
     *                   description="Objection reason in case user is objecting to a camp change",
     *                   required=false,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="camp_id",
     *                   description="Id of camp",
     *                   required=false,
     *                   type="integer",
     *               )
     *         )
     *      )
     *   ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */
    public function manageCamp(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getManageCampValidationRules(), $this->validationMessages->getManageCampValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        if (! Gate::allows('nickname-check', $request->nick_name)) {
            return $this->resProvider->apiJsonResponse(403, trans('message.error.invalid_data'), '', '');
        }

        $all = $request->all();
        $all['parent_camp_num'] = $all['parent_camp_num'] ?? null;
        $all['old_parent_camp_num'] = $all['old_parent_camp_num'] ?? null;
        $nickNameIds = Nickname::getNicknamesIdsByUserId($request->user()->id);
        if (!in_array($request->nick_name, $nickNameIds)) {
            return $this->resProvider->apiJsonResponse(400, trans('message.general.nickname_association_absence'), '', '');
        }
        if (strtolower(trim($all['camp_name'])) == 'agreement' && $all['camp_num'] != 1) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.camp_alreday_exist'), '', '');
        }
        try {
            if (Camp::IfTopicCampNameAlreadyExists($all)) {
                return $this->resProvider->apiJsonResponse(400, trans('message.error.camp_alreday_exist'), '', '');
            }
            $nickNames = Nickname::personNicknameArray();
            $ifIamSingleSupporter = Support::ifIamSingleSupporter($all['topic_num'], $all['camp_num'], $nickNames);
         
            if ($all['event_type'] == "update") {
                $camp = $this->updateCamp($all);
                if (!$ifIamSingleSupporter) {
                    $camp->go_live_time = strtotime(date('Y-m-d H:i:s', strtotime('+1 days')));
                } else {
                    $camp->grace_period = 0;
                }
            }
            if ($all['event_type'] == "objection") {
                $camp = $this->objectCamp($all);
            } elseif ($all['event_type'] == "edit") {
                $camp = $this->editCamp($all);
            }
            $camp->save();
            $topic = $camp->topic;
            $filter['topicNum'] = $all['topic_num'];
            $filter['campNum'] = $all['camp_num'];
            $liveCamp = Camp::getLiveCamp($filter);
            $link = Util::getTopicCampUrl($topic->topic_num, $camp->num, $topic, $liveCamp);
          
            if ($all['event_type'] == "objection") {
                Util::dispatchJob($topic, $camp->camp_num, 1);
                $this->objectCampNotification($camp, $all, $link, $liveCamp, $request);
            } else if ($all['event_type'] == "update") {
               if($ifIamSingleSupporter){
                    Util::checkParentCampChanged($all, false, $liveCamp);
                }                
                $this->updateCampNotification($camp, $liveCamp, $link, $request);
                Util::dispatchJob($topic, $camp->camp_num, 1);
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $camp, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    private function editCamp($all)
    {
        $camp = Camp::where('id', $all['camp_id'])->first();
        $camp->topic_num = $all['topic_num'];
        $camp->parent_camp_num = $all['parent_camp_num'];
        $camp->camp_name = Util::remove_emoji($all['camp_name']);
        $camp->note = $all['note'] ?? null;
        $camp->key_words = Util::remove_emoji($all['key_words'] ?? "");
        $camp->submitter_nick_id = $all['nick_name'];
        $camp->camp_about_url = Util::remove_emoji($all['camp_about_url'] ?? "");
        $camp->camp_about_nick_id = $all['camp_about_nick_id'] ?? "";
        $camp->is_disabled =  !empty($all['is_disabled']) ? $all['is_disabled'] : 0;
        $camp->is_one_level =  !empty($all['is_one_level']) ? $all['is_one_level'] : 0;
        return $camp;
    }

    private function updateCamp($all)
    {
        $camp_name = Util::remove_emoji($all['camp_name']);
        $camp = new Camp();
        $camp->topic_num = $all['topic_num'];
        $camp->parent_camp_num = $all['parent_camp_num'];
        $camp->old_parent_camp_num = isset($all['old_parent_camp_num']) ? $all['old_parent_camp_num'] : null;
        $camp->camp_name = isset($camp_name) ? trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ",  $camp_name)))  : "";
        $camp->submit_time = strtotime(date('Y-m-d H:i:s'));
        $camp->go_live_time =  time();
        $camp->language = 'English';
        $camp->note = $all['note'] ?? "";
        $camp->key_words = Util::remove_emoji($all['key_words'] ?? "");
        $camp->submitter_nick_id = $all['nick_name'];
        $camp->camp_about_url = Util::remove_emoji($all['camp_about_url'] ?? "");
        $camp->camp_about_nick_id = $all['camp_about_nick_id'] ?? "";
        $camp->is_disabled =  !empty($all['is_disabled']) ? $all['is_disabled'] : 0;
        $camp->is_one_level =  !empty($all['is_one_level']) ? $all['is_one_level'] : 0;
        $camp->camp_num = $all['camp_num'];
        if ($all['topic_num'] == '81' && !isset($all['camp_about_nick_id'])) {
            $camp->camp_about_nick_id = $all['nick_name'];
        }
        $camp->grace_period = 1;
        return $camp;
    }

    private function objectCamp($all)
    {
        $camp = Camp::where('id', $all['camp_id'])->first();
        $camp->objector_nick_id = $all['nick_name'];
        $camp->object_reason = $all['objection_reason'];
        $camp->is_disabled =  !empty($all['is_disabled']) ? $all['is_disabled'] : 0;
        $camp->is_one_level =  !empty($all['is_one_level']) ? $all['is_one_level'] : 0;
        $camp->object_time = time();
        return $camp;
    }

    private function updateCampNotification($camp, $liveCamp, $link, $request)
    {
        $link = 'camp/history/' . $camp->topic_num . '/' . $camp->camp_num;
        $data['type'] = "camp";
        $data['object'] = $liveCamp->topic->topic_name . " / " . $camp->camp_name;
        $data['link'] = $link;
        $data['support_camp'] = $liveCamp->camp_name;
        $data['is_live'] = ($camp->go_live_time <= time()) ? 1 : 0;
        $data['note'] = $camp->note;
        $data['camp_num'] = $camp->camp_num;
        $nickName = Nickname::getNickName($camp->submitter_nick_id);
        $data['topic_num'] = $camp->topic_num;
        $data['nick_name'] = $nickName->nick_name;
        $data['subject'] = "Proposed change to " . $liveCamp->topic->topic_name . ' / ' . $liveCamp->camp_name . " submitted";
        $data['namespace_id'] = (isset($liveCamp->topic->namespace_id) && $liveCamp->topic->namespace_id)  ?  $liveCamp->topic->namespace_id : 1;
        $data['nick_name_id'] = $nickName->id;
        $subscribers = Camp::getCampSubscribers($camp->topic_num, $camp->camp_num);
        $activityLogData = [
            'log_type' =>  "topic/camps",
            'activity' => 'Camp updated',
            'url' => $link,
            'model' => $camp,
            'topic_num' => $camp->topic_num,
            'camp_num' =>  $camp->camp_num,
            'user' => $request->user(),
            'nick_name' => $nickName->nick_name,
            'description' => $camp->camp_name
        ];
        dispatch(new ActivityLoggerJob($activityLogData))->onQueue(env('QUEUE_SERVICE_NAME'));
        Util::mailSubscribersAndSupporters([], $subscribers, $link, $data);
    }

    private function objectCampNotification($camp, $all, $link, $liveCamp, $request)
    {
        $user = Nickname::getUserByNickName($all['submitter']);
        $link = 'camp/history/' . $camp->topic_num . '/' . $camp->camp_num;
        $nickName = Nickname::getNickName($all['nick_name']);
        $data['nick_name'] = $nickName->nick_name;
        $data['forum_link'] = 'forum/' . $camp->topic_num . '-' . $camp->camp_name . '/' . $camp->camp_num . '/threads';
        $data['subject'] = $data['nick_name'] . " has objected to your proposed change.";
        $data['namespace_id'] = (isset($liveCamp->topic->namespace_id) && $liveCamp->topic->namespace_id)  ?  $liveCamp->topic->namespace_id : 1;
        $data['nick_name_id'] = $nickName->id;
        $data['topic_link'] = $link;
        $data['type'] = "Camp";
        $data['object_type'] = "";
        $data['object'] = $liveCamp->topic->topic_name . "/" . $liveCamp->camp_name;
        $data['help_link'] = config('global.APP_URL_FRONT_END') . '/' .  General::getDealingWithDisagreementUrl();
        $activityLogData = [
            'log_type' =>  "topic/camps",
            'activity' => 'Change to camp objected',
            'url' => $link,
            'model' => $camp,
            'topic_num' => $camp->topic_num,
            'camp_num' =>  $camp->camp_num,
            'user' => $request->user(),
            'nick_name' => $nickName->nick_name,
            'description' => $camp->camp_name
        ];
        try {
            dispatch(new ActivityLoggerJob($activityLogData))->onQueue(env('QUEUE_SERVICE_NAME'));
            dispatch(new ObjectionToSubmitterMailJob($user, $link, $data))->onQueue(env('QUEUE_SERVICE_NAME'));
        } catch (\Swift_TransportException $e) {
            throw new \Swift_TransportException($e);
        }
    }
}

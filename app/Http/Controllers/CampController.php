<?php

namespace App\Http\Controllers;

use stdClass;
use Exception;
use Throwable;
use App\Models\Camp;
use App\Facades\Util;
use App\Models\Topic;
use App\Models\Nickname;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Models\CampSubscription;
use App\Helpers\ResourceInterface;
use App\Helpers\ResponseInterface;
use Illuminate\Support\Facades\DB;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use Illuminate\Support\Facades\Event;
use App\Http\Request\ValidationMessages;
use App\Events\ThankToSubmitterMailEvent;
use App\Jobs\ActivityLoggerJob;
use App\Helpers\CampForum;
use App\Models\Support;

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
        try {

            $result = Camp::where('topic_num', $request->topic_num)->where('camp_name', $request->camp_name)->first();
            if (!empty($result)) {
                $status = 400;
                $error['camp_name'][] = trans('message.validation_camp_store.camp_name_unique');
                $message = trans('message.error.invalid_data');
                return $this->resProvider->apiJsonResponse($status, $message, null, $error);
            }

            $current_time = time();

            ## check if mind_expert topic and camp abt nick name id is null then assign nick name as about nickname ##
            if ($request->topic_num == config('global.mind_expert_topic_num') && !isset($request->camp_about_nick_id)) {
                $request->camp_about_nick_id = $request->nick_name ?? "";
            } else {
                $request->camp_about_nick_id = $request->camp_about_nick_id ?? "";
            }

            $nextCampNum =  Camp::where('topic_num', $request->topic_num)
                ->latest('submit_time')->first();
            $nextCampNum->camp_num++;
            $input = [
                "camp_name" => $request->camp_name,
                "camp_num" => $nextCampNum->camp_num,
                "parent_camp_num" => $request->parent_camp_num,
                "topic_num" => $request->topic_num,
                "submit_time" => strtotime(date('Y-m-d H:i:s')),
                "submitter_nick_id" => $request->nick_name,
                "go_live_time" =>  $current_time,
                "language" => 'English',
                "note" => $request->note ?? "",
                "key_words" => $request->key_words ?? "",
                "camp_about_url" => $request->camp_about_url ?? "",
                "title" => $request->title ?? "",
                "camp_about_nick_id" =>  $request->camp_about_nick_id,
                "grace_period" => 0
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
                $indexs = ['topic_num', 'camp_num', 'camp_name', 'key_words', 'camp_about_url', 'nick_name', 'flag', 'subscriptionId', 'subscriptionCampName', 'parent_camp_name'];
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
            if (empty($result)) {
                $status = 400;
                $message = trans('message.error.exception');
                return $this->resProvider->apiJsonResponse($status, $message, null, null);
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
            if ($request->user()) {
                $campSubscriptionData = Camp::getCampSubscription($filter, $request->user()->id);
                $data->flag = $campSubscriptionData['flag'];
                $data->subscription_id = $campSubscriptionData['camp_subscription_data'][0]['subscription_id'] ??  null;
                $data->subscribed_camp_name = $campSubscriptionData['camp_subscription_data'][0]['camp_name'] ?? null;
            }
            $indexs = ['bread_crumb', 'flag', 'subscription_id', 'subscribed_camp_name'];
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
        $response->if_i_am_supporter = null;
        $response->if_i_am_implicit_supporter = null;
        $response->if_support_delayed = NULL;
        try {
            $liveCamp = Camp::getLiveCamp($filter);
            $campHistoryQuery = Camp::where('topic_num', $filter['topicNum'])->where('camp_num', '=', $filter['campNum'])->latest('submit_time');
            $submitTime = $liveCamp->submit_time;
            if ($request->user()) {
                $nickNames = Nickname::personNicknameArray();
                $response->if_i_am_supporter = Support::ifIamSupporter($filter['topicNum'], $filter['campNum'], $nickNames, $submitTime);
                $response->if_i_am_implicit_supporter = Support::ifIamImplicitSupporter($filter, $nickNames, $submitTime);
                $response->if_support_delayed = Support::ifIamSupporter($filter['topicNum'], $filter['campNum'], $nickNames, $submitTime, true);
                $response = Camp::campHistory($campHistoryQuery, $filter, $response, $liveCamp);
            }else{
                $response = Camp::campHistory($campHistoryQuery, $filter, $response, $liveCamp);
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $response, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}

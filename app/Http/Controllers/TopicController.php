<?php

namespace App\Http\Controllers;

use stdClass;
use Exception;
use Throwable;
use Carbon\Carbon;
use App\Models\Camp;
use App\Facades\Util;
use App\Models\Topic;
use App\Models\Support;
use App\Library\General;
use App\Models\Nickname;
use App\Models\Statement;
use App\Models\Namespaces;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Models\ChangeAgreeLog;
use App\Jobs\ActivityLoggerJob;
use App\Models\CampSubscription;
use App\Helpers\ResourceInterface;
use App\Helpers\ResponseInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Events\NotifySupportersEvent;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use Illuminate\Support\Facades\Event;
use App\Http\Request\ValidationMessages;
use App\Events\ThankToSubmitterMailEvent;
use App\Jobs\ObjectionToSubmitterMailJob;
use App\Facades\GetPushNotificationToSupporter;
use App\Helpers\Helpers;

class TopicController extends Controller
{

    public function __construct(ResponseInterface $respProvider, ResourceInterface $resProvider, ValidationRules $rules, ValidationMessages $validationMessages)
    {
        $this->rules = $rules;
        $this->validationMessages = $validationMessages;
        $this->resourceProvider  = $resProvider;
        $this->resProvider = $respProvider;
    }

    /**
     * @OA\POST(path="/topic/save",
     *   tags={"Topic"},
     *   summary="save topic",
     *   description="This is use for save topic",
     *   operationId="topicSave",
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
     *                  property="topic_name",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="namespace",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="nick_name",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="note",
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
     *                                             @OA\Property(
     *                                              property="topic_num",
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

    public function store(Request $request, Validate $validate)
    {

        $validationErrors = $validate->validate($request, $this->rules->getTopicStoreValidationRules(), $this->validationMessages->getTopicStoreValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }


        if (!Gate::allows('nickname-check', $request->nick_name)) {
            return $this->resProvider->apiJsonResponse(403, trans('message.error.invalid_data'), '', '');
        }
        $result = Topic::where('topic_name', Util::remove_emoji($request->topic_name))->first();
        $liveTopicData = Topic::select('topic.*')
            ->join('camp', 'camp.topic_num', '=', 'topic.topic_num')
            ->where('camp.camp_name', '=', 'Agreement')
            ->where('topic_name', Util::remove_emoji($request->topic_name))
            ->where('topic.objector_nick_id', "=", null)
            ->whereRaw('topic.go_live_time in (select max(go_live_time) from topic where objector_nick_id is null and go_live_time < "' . time() . '" group by topic_num)')
            ->where('topic.go_live_time', '<=', time())
            ->latest('submit_time')
            ->first();

        $nonLiveTopicData = Topic::select('topic.*')
            ->join('camp', 'camp.topic_num', '=', 'topic.topic_num')
            ->where('camp.camp_name', '=', 'Agreement')
            ->where('topic_name', Util::remove_emoji($request->topic_name))
            ->where('topic.objector_nick_id', "=", null)
            ->where('topic.go_live_time', ">", time())
            ->first();

        $result = Topic::where('topic_name', Util::remove_emoji($request->topic_name))->first();
        
        if (isset($liveTopicData) && $liveTopicData != null) {
            if ($liveTopicData && isset($liveTopicData['topic_name'])) {
                $status = 400;
                $result->if_exist = true;
                $error['topic_name'][] = trans('message.validation_topic_store.topic_name_unique');
                $message = trans('message.error.invalid_data');
                return $this->resProvider->apiJsonResponse($status, $message, $result, $error);
            }
        }

        if (isset($nonLiveTopicData) && $nonLiveTopicData != null) {
            if ($nonLiveTopicData && isset($nonLiveTopicData['topic_name'])) {
                $status = 400;
                $result->if_exist = true;
                $error['topic_name'][] = trans('message.validation_topic_store.topic_name_unique');
                $message = trans('message.error.invalid_data');
                return $this->resProvider->apiJsonResponse($status, $message, $result, $error);
            }
        }

        try {
            $current_time = time();
            $input = [
                "topic_name" => Util::remove_emoji($request->topic_name),
                "namespace_id" => $request->namespace,
                "submit_time" => $current_time,
                "submitter_nick_id" => $request->nick_name,
                "go_live_time" =>  $current_time,
                "language" => 'English',
                "note" => $request->note ?? "",
                "grace_period" => 0,
                "is_disabled" =>  !empty($request->is_disabled) ? $request->is_disabled : 0,
                "is_one_level" =>  !empty($request->is_one_level) ? $request->is_one_level : 0,
            ];
            DB::beginTransaction();
            $topic = Topic::create($input);
            $nickName = Nickname::getNickName($request->nick_name)->nick_name;
            if ($topic) {

                Util::dispatchJob($topic, 1, 1);
                
                $timelineMessage = $nickName . " created a new topic and also added their support on Camp ". $topic->topic_name;
                
                $timeline_url =Util::getTimelineUrlgetTimelineUrl($topic_num= $topic->topic_num, $topic_name =$topic->topic_name, $camp_num=1, $camp_name="Agreement", $topicTitle=$topic->topic_name, $type="create_topic", $rootUrl=null, $namespaceId=$topic->namespace_id, $topicCreatedByNickId=$topic->submitter_nick_id);
                       
                Util::dispatchTimelineJob($topic_num = $topic->topic_num, $campNum = 1, $updateAll =1, $message =$timelineMessage, $type="create_topic", $id=1, $old_parent_id=null, $new_parent_id=null,$delay=null,$asOfDefaultDate=time(),$timeline_url);
                
                $topicInput = [
                    "topic_num" => $topic->topic_num,
                    "nick_name_id" => $request->nick_name,
                    "delegate_nick_name_id" => 0,
                    "start" =>  $current_time,
                    "camp_num" => 1,
                    "support_order" => 1,
                    "reason" => trans('message.general.default_support_added_reason'),
                    "citation_link" => null,
                    "reason_summary" => null,
                    "is_system_generated" => 1,
                ];
                ## If topic is created then add default support to that topic ##
                $support = Support::create($topicInput);

                if (isset($request->namespace) && $request->namespace == 'other') {

                    ## Create new namespace request ##
                    $othernamespace = trim($request->create_namespace, '/');
                    $namespace = new Namespaces();
                    $namespace->parent_id = 0;
                    $namespace->name = '/' . $othernamespace . '/';
                    $namespace->save();

                    ## update namespace id ##
                    $topic->namespace_id = $namespace->id;
                    $topic->update();
                }
                DB::commit();
                try {
                    $topicLive = Topic::getLiveTopic($topic->topic_num, $request->asof);
                    $filter['topicNum'] = $request->topic_num;
                    $filter['asOf'] = $request->asof;
                    $filter['campNum'] = 1;
                    $camp = Camp::getLiveCamp($filter);
                    $link = Util::getTopicCampUrlWithoutTime($topic->topic_num, 1, $topicLive, $camp, time());
                    $historylink = Util::topicHistoryLink($topic->topic_num, 1, $topic->topic_name, 'Aggreement', 'topic');
                    $dataEmail = (object) [
                        "type" => "topic",
                        "link" => $link,
                        "historylink" => $historylink,
                        "object" => $topic->topic_name,
                        'namespace_id' => $topic->namespace_id
                    ];
                    Event::dispatch(new ThankToSubmitterMailEvent($request->user(), $dataEmail));
                    $nickName = Nickname::getNickName($request->nick_name)->nick_name;
                    $activitLogData = [
                        'log_type' =>  "topic/camps",
                        'activity' => trans('message.activity_log_message.topic_create', ['nick_name' =>  $nickName]),
                        'url' => $link,
                        'model' => $topic,
                        'topic_num' => $topic->topic_num,
                        'camp_num' =>  1,
                        'user' => $request->user(),
                        'nick_name' => $nickName,
                        'description' => $request->topic_name
                    ];
                    dispatch(new ActivityLoggerJob($activitLogData))->onQueue(env('ACTIVITY_LOG_QUEUE'));
                } catch (Throwable $e) {
                    $data = null;
                    $status = 403;
                    $message = $e->getMessage();
                    return $this->resProvider->apiJsonResponse($status, $message, $data, null);
                }
                $data = $topic;
                $status = 200;
                $message = trans('message.success.topic_created');
            } else {
                $data = null;
                $status = 400;
                $message = trans('message.error.topic_failed');
            }
            return $this->resProvider->apiJsonResponse($status, $message, $data, null);
        } catch (Throwable $e) {
            DB::rollback();
            $status = 400;
            $message = $e->getMessage();
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }

    /**
     * @OA\Post(path="/get-topic-record",
     *   tags={"Topic"},
     *   summary="get topic record",
     *   description="Used to get topic record.",
     *   operationId="getTopicRecord",
     *   @OA\RequestBody(
     *       required=true,
     *       description="Get topic records",
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
     *         )
     *      )
     *   ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */

    public function getTopicRecord(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getTopicRecordValidationRules(), $this->validationMessages->getTopicRecordValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $filter['topicNum'] = $request->topic_num;
        $filter['asOf'] = $request->as_of;
        $filter['asOfDate'] = $request->as_of_date;
        $filter['campNum'] = $request->camp_num;
        try {
            $topic = Topic::getLiveTopic($filter['topicNum'], $filter['asOf'], $filter['asOfDate']);
            if (!$topic) {
            $topic = Topic::getLiveTopic($filter['topicNum'], 'default', $filter['asOfDate']);
            }
            $namespace = Namespaces::find($topic->namespace_id);
            $namespaceLabel = '';
            if (!empty($namespace)) {
                $namespaceLabel = Namespaces::getNamespaceLabel($namespace, $namespace->name);
            }
            $topic->namespace_name = $namespaceLabel;
            $topic->submitter_nick_name=NickName::getNickName($topic->submitter_nick_id)->nick_name;
            $topic->topicSubscriptionId = "";
            $topic->camp_num =  $topic->camp_num ?? 1;
            if ($request->user()) {
                $topicSubscriptionData = CampSubscription::where('user_id', '=', $request->user()->id)->where('camp_num', '=', 0)->where('topic_num', '=', $filter['topicNum'])->where('subscription_start', '<=', strtotime(date('Y-m-d H:i:s')))->where('subscription_end', '=', null)->orWhere('subscription_end', '>=', strtotime(date('Y-m-d H:i:s')))->first();
                $topic->topicSubscriptionId = isset($topicSubscriptionData->id) ? $topicSubscriptionData->id : "";
            }
            $topicRecord[] = $topic;
            $indexs = ['topic_num', 'camp_num', 'topic_name', 'namespace_name', 'topicSubscriptionId', 'namespace_id', 'note', 'submitter_nick_name', 'go_live_time', 'camp_about_nick_id', 'submitter_nick_id', 'submit_time'];
            $topicRecord = $this->resourceProvider->jsonResponse($indexs, $topicRecord);
            $topicRecord = $topicRecord[0];
            
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $topicRecord, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(path="/commit/change",
     *   tags={"Topic"},
     *   summary="Commit a change",
     *   description="Used to commit a change for camp, topic and statement.",
     *   operationId="commitChange",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *   @OA\RequestBody(
     *       required=true,
     *       description="Commit change",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               @OA\Property(
     *                   property="id",
     *                   description="Record id is required",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="type",
     *                   description="Type (topic, camp, statement)",
     *                   required=true,
     *                   type="string",
     *               ),
     *         )
     *      )
     *   ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */
    public function commitAndNotifyChange(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getCommitChangeValidationRules(), $this->validationMessages->getCommitChangeValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $all = $request->all();
        $type = $all['type'];
        $id = $all['id'];
        $message = "";
        $nickNames = Nickname::personNicknameArray();
        try {
            if ($type == 'statement') {
                $model = Statement::where('id', '=', $id)->whereIn('submitter_nick_id', $nickNames)->first();
            } else if ($type == 'camp') {
                $model = Camp::where('id', '=', $id)->first();
            } else if ($type == 'topic') {
                $model = Topic::where('id', '=', $id)->first();
            }
            if (!$model) {
                return $this->resProvider->apiJsonResponse(400, trans('message.error.record_not_found'), '', '');
            }

            $filter['topicNum'] = $model->topic_num;
            $filter['campNum'] = $model->camp_num ?? 1;

            $ifIamSingleSupporter = Support::ifIamSingleSupporter($filter['topicNum'], $filter['campNum'], $nickNames);

            $model->submit_time = time();
            $model->go_live_time = strtotime(date('Y-m-d H:i:s', strtotime('+1 days')));

            if($ifIamSingleSupporter) {
                $model->go_live_time = time();
            }

            $model->grace_period = 0;
            if ($type == 'camp') {
                $preliveCamp = Camp::getLiveCamp($filter);
            }
            $model->update();
            $liveCamp = Camp::getLiveCamp($filter);
            $liveTopic = Topic::getLiveTopic($model->topic_num, 'default');
            if ($type == 'topic') {
                // $directSupporter = Support::getAllDirectSupporters($liveTopic->topic_num);
                // $subscribers = Camp::getCampSubscribers($liveTopic->topic_num, 1);
                $data['namespace_id'] = (isset($liveTopic->namespace_id) && $liveTopic->namespace_id)  ?  $liveTopic->namespace_id : 1;
                
                // $data['object'] = $liveTopic->topic_name;
                $data['object'] = Helpers::renderParentCampLinks($liveTopic->topic_num, 1, $liveTopic->topic_name, true, '>>');
            } else {
                // $directSupporter =  Support::getAllDirectSupporters($model->topic_num, $model->camp_num);
                // $subscribers = Camp::getCampSubscribers($model->topic_num, $model->camp_num);
                // $data['object'] = $liveCamp->topic->topic_name . ' >> ' . $liveCamp->camp_name;

                $data['object'] = Helpers::renderParentCampLinks($liveCamp->topic->topic_num, $liveCamp->camp_num, $liveCamp->topic->topic_name, true, '>>');

                $data['namespace_id'] = (isset($liveCamp->topic->namespace_id) && $liveCamp->topic->namespace_id)  ?  $liveCamp->topic->namespace_id : 1;
                $data['camp_num'] = $model->camp_num;
            }
            $nickName = Nickname::getNickName($model->submitter_nick_id);
            $data['go_live_time'] = $model->go_live_time;
            $data['note'] = $model->note;
            $data['topic_num'] = $model->topic_num;
            $data['nick_name'] = $nickName->nick_name;
            $data['nick_name_id'] = $nickName->id;
            if ($type == 'statement') {
                $link = config('global.APP_URL_FRONT_END') . '/statement/history/' . $model->topic_num . '/' . $model->camp_num;
                $data['support_camp'] = $liveCamp->camp_name;
                $data['type'] = 'statement : for camp ';
                $data['typeobject'] = 'statement';
                $data['forum_link'] = 'forum/' . $model->topic_num . '-statement/' . $model->camp_num . '/threads';
                $data['subject'] = "Proposed change to statement for camp " . $liveCamp->topic->topic_name . " >> " . $liveCamp->camp_name . " submitted";
                $message = trans('message.success.statement_commit');

                $notification_type = config('global.notification_type.statementCommit');
                // GetPushNotificationToSupporter::pushNotificationToSupporter($request->user(), $model->topic_num, $model->camp_num, "statement-commit", null, $nickName->nick_name);
            } else if ($type == 'camp') {
                $link = config('global.APP_URL_FRONT_END') . '/camp/history/' . $liveCamp->topic_num . '/' . $liveCamp->camp_num;
                $data['support_camp'] = $model->camp_name;
                $data['type'] = 'camp : ';
                $data['typeobject'] = 'camp';
                $data['forum_link'] = 'forum/' . $liveCamp->topic_num . '-' . $liveCamp->camp_name . '/' . $liveCamp->camp_num . '/threads';
                $data['subject'] = "Proposed change to " . $liveCamp->topic->topic_name . ' >> ' . $liveCamp->camp_name . " submitted";
                $topic = $model->topic;
                $message = trans('message.success.camp_commit');

                if ($ifIamSingleSupporter) {
                    $all['topic_num'] = $liveCamp->topic_num;
                    Util::checkParentCampChanged($all, false, $liveCamp);
                    $beforeUpdateCamp = Util::getCampByChangeId($filter['campNum']);
                    $before_parent_camp_num = $beforeUpdateCamp->parent_camp_num;
                    if ($before_parent_camp_num == $all['parent_camp_num']) {
                        Util::parentCampChangedBasedOnCampChangeId($filter['campNum']);
                    }
                    // $this->updateCampNotification($model, $liveCamp, $link, $request);

                    /** Archive and restoration of archive camp #574 */
                    Util::updateArchivedCampAndSupport($model, $model->is_archive);
                    // $prevArchiveStatus = $preliveCamp->is_archive;
                    // $updatedArchiveStatus = $all['is_archive'] ?? 0;
                    // if ($prevArchiveStatus != $updatedArchiveStatus) {
                    //     Util::updateArchivedCampAndSupport($model, $updatedArchiveStatus);
                    // }
                }

                if (isset($topic)) {
                    Util::dispatchJob($topic, $model->camp_num, 1);
                }

                //timeline start
                // $nickName = Nickname::getNickName($model->submitter_nick_id)->nick_name;
                if ($all['parent_camp_num'] != $all['old_parent_camp_num']) {
                    $timelineMessage = $nickName->nick_name . " changed the parent of camp   " . $model->camp_name;

                    $timeline_url =Util::getTimelineUrlgetTimelineUrl($topic_num= $topic->topic_num, $topic_name =$topic->topic_name, $camp_num=$model->camp_num, $camp_name=$model->camp_name, $topicTitle=$topic->topic_name, $type="parent_change", $rootUrl=null, $namespaceId=$topic->namespace_id, $topicCreatedByNickId=$topic->submitter_nick_id);
               
                    Util::dispatchTimelineJob($topic= $topic->topic_num, $model->camp_num, 1, $timelineMessage, "parent_change", $model->id, $all['old_parent_camp_num'], $all['parent_camp_num'],$delay=null,$asOfDefaultDate=time(),$timeline_url);
                }
                //end of timeline

                //timeline start
                if ($model->camp_num != null) {
                    $old_camp = Camp::where('id', $model->camp_num)->first();
                    if (Util::remove_emoji(strtolower(trim($old_camp['camp_name']))) != Util::remove_emoji(strtolower(trim($model->camp_name)))) {
                        $timelineMessage = $nickName->nick_name . " changed camp name from " . $old_camp['camp_name'] . " to " . $model->camp_name;
                        
                        $timeline_url =Util::getTimelineUrlgetTimelineUrl($topic_num= $topic->topic_num, $topic_name =$topic->topic_name, $camp_num=$model->camp_num, $camp_name=$model->camp_name, $topicTitle=$topic->topic_name, $type="update_camp", $rootUrl=null, $namespaceId=$topic->namespace_id, $topicCreatedByNickId=$topic->submitter_nick_id);
               
                        Util::dispatchTimelineJob($topic= $topic->topic_num, $model->camp_num, 1, $timelineMessage, "update_camp", $model->id, null, null, $delay=null,$asOfDefaultDate=time(),$timeline_url);
                    }
                }
                //end of timeline

                $currentTime = time();
                $delayCommitTimeInSeconds = (1 * 60 * 60) + 10; // 1 hour commit time + 10 seconds for delay job
                $delayLiveTimeInSeconds = (24 * 60 * 60) + 10; // 24 hour commit time + 10 seconds for delay job
                if (($currentTime < $model->go_live_time && $currentTime >= $model->submit_time) && $model->grace_period && $model->objector_nick_id == null) {
                    Util::dispatchJob($topic, $model->camp_num, 1, $delayCommitTimeInSeconds);
                    Util::dispatchJob($topic, $model->camp_num, 1, $delayLiveTimeInSeconds, $model->id);
                } else {
                    if ($currentTime < $model->go_live_time && $model->objector_nick_id == null) {
                        Util::dispatchJob($topic, $model->camp_num, 1, $delayLiveTimeInSeconds, $model->id);
                    }
                }

                $notification_type = config('global.notification_type.campCommit');
                // GetPushNotificationToSupporter::pushNotificationToSupporter($request->user(), $liveCamp->topic_num, $liveCamp->camp_num, 'camp-commit', null, $nickName->nick_name);
            } else if ($type == 'topic') {
                $model->camp_num = 1;
                $link = config('global.APP_URL_FRONT_END') . '/topic/history/' . $liveTopic->topic_num;
                $data['support_camp'] = $model->topic_name;
                $data['type'] = 'topic : ';
                $data['typeobject'] = 'topic';
                $data['camp_num'] = 1;
                $data['forum_link'] = 'forum/' . $liveTopic->topic_num . '-' . $liveTopic->topic_name . '/1/threads';
                $data['subject'] = "Proposed change to topic " . $liveTopic->topic_name . " submitted";
                $message = trans('message.success.topic_commit');
                if (isset($liveTopic)) {
                    Util::dispatchJob($liveTopic, 1, 1);
                }
                
                $notification_type = config('global.notification_type.topicCommit');
                // GetPushNotificationToSupporter::pushNotificationToSupporter($request->user(), $liveTopic->topic_num, 1, 'topic-commit', null, $nickName->nick_name);
            }

            $notificationData = [
                "email" => [],
                "push_notification" => []
            ];
            $notificationData['email'] = $data;
    
            $liveThread =  null;
            $threadId =  null;
            $getMessageData = GetPushNotificationToSupporter::getMessageData(Auth::user(), $liveTopic, $liveCamp, $liveThread, $threadId, $notification_type, $nickName->nick_name, null);
            if (!empty($getMessageData)) {
                $notificationData['push_notification'] = [
                    "topic_num" => $liveTopic->topic_num,
                    "camp_num" => $liveCamp->camp_num,
                    "notification_type" => $getMessageData->notification_type,
                    "title" => $getMessageData->title,
                    "message_body" => $getMessageData->message_body,
                    "link" => $getMessageData->link,
                    "thread_id" => !empty($threadId) ? $threadId : null,
                ];
            }
            
            Event::dispatch(new NotifySupportersEvent($liveCamp, $notificationData, $notification_type, $link, config('global.notify.both')));
            $activityLogData = [
                'log_type' =>  "topic/camps",
                'activity' => trans('message.activity_log_message.commit_change', ['nick_name' =>  $nickName->nick_name, 'type' => $type]),
                'url' => $link,
                'model' => $model,
                'topic_num' => $model->topic_num,
                'camp_num' =>  $model->camp_num,
                'user' => $request->user(),
                'nick_name' => $nickName->nick_name,
                'description' => $model->value
            ];

            switch ($type) {
                case 'topic':
                    $activityLogData['topic_name'] = $liveTopic->topic_name;
                    $activityLogData['camp_name'] = null;
                    break;
                case 'camp':
                case 'statement':
                    $activityLogData['topic_name'] = $liveCamp->topic->topic_name;
                    $activityLogData['camp_name'] = $liveCamp->camp_name;
                    break;
                
                default:
                    break;
            }

            dispatch(new ActivityLoggerJob($activityLogData))->onQueue(env('ACTIVITY_LOG_QUEUE'));
            // Util::mailSubscribersAndSupporters($directSupporter, $subscribers, $link, $data);
            return $this->resProvider->apiJsonResponse(200, $message, '', '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(path="/agree-to-change",
     *   tags={"Topic"},
     *   summary="Agree to change",
     *   description="Used to agree on a change for camp, topic and statement.",
     *   operationId="agreeToChange",
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
     *       required=true,https://canonizer3.canonizer.comstatement/history/88/1
     *       description="Agree to change",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               @OA\Property(
     *                   property="record_id",
     *                   description="Record id is required",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="change_for",
     *                   description="Type (topic, camp, statement)",
     *                   required=true,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="camp_num",
     *                   description="Camp number",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="topic_num",
     *                   description="Topic number",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="nick_name_id",
     *                   description="Nick name id",
     *                   required=true,
     *                   type="integer",
     *               ),
     *         )
     *      )
     *   ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */
    public function agreeToChange(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getAgreeToChangeValidationRules(), $this->validationMessages->getAgreeToChangeValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $data = $request->all();
        $message = "";
        $changeId = $data['record_id'];
        $responseData = [
            'is_submitted' => 1
        ];

        try {        

            $where = [
                'id' => $changeId,
                ['objector_nick_id', '!=', null],
            ];
            switch ($data['change_for']) {
                case 'statement':
                    $model = Statement::where($where)->first();
                    break;
                case 'camp':
                    $model = Camp::where($where)->first();
                    break;
                case 'topic':
                    $model = Topic::where($where)->first();
                    break;

                default:
                    $model = null;
                    break;
            }
            if (!is_null($model)) {
                $responseData['is_submitted'] = 0;
                $message = trans('message.error.disagree_objected_history_changed', ['history' => $data['change_for']]);
                return $this->resProvider->apiJsonResponse(200, $message, $responseData, '');
            } 

            if($data['user_agreed'] == 0) {
                $changeAgreeLog = (new ChangeAgreeLog())->where([
                    'change_id' => $changeId,
                    'camp_num' => $data['camp_num'],
                    'topic_num' => $data['topic_num'],
                    'nick_name_id' => $data['nick_name_id'],
                    'change_for' => $data['change_for'],
                ])->delete();
                if ($changeAgreeLog) {
                    $message = trans('message.success.topic_not_agree');
                }
                else {
                    $responseData['is_submitted'] = 0;
                    $message = trans('message.error.disagree_history_changed', ['history' => $data['change_for']]);
                }
                return $this->resProvider->apiJsonResponse(200, $message, $responseData, '');
            }
            $log = new ChangeAgreeLog();
            $log->change_id = $changeId;
            $log->camp_num = $data['camp_num'];
            $log->topic_num = $data['topic_num'];
            $log->nick_name_id = $data['nick_name_id'];
            $log->change_for = $data['change_for'];
            $log->save();
            
            /*
            *   https://github.com/the-canonizer/Canonizer-Beta--Issue-Tracking/issues/232 
            *   Now support at the time of submition will be count as total supporter. 
            *   Also check if submitter is not a direct supporter, then it will be count as direct supporter   
            */
            $agreed_supporters = ChangeAgreeLog::where('topic_num', '=', $data['topic_num'])
                ->where('camp_num', '=', $data['camp_num'])
                ->where('change_id', '=', $changeId)
                ->where('change_for', '=', $data['change_for'])
                ->get()->pluck('nick_name_id')->toArray();

            $agreeCount = count($agreed_supporters);
            
            if ($data['change_for'] == 'statement') {
                $statement = Statement::where('id', $changeId)->first();
                if ($statement) {
                    $submitterNickId = $statement->submitter_nick_id;
                    // $supporters = Support::getAllSupporters($data['topic_num'], $data['camp_num'], $submitterNickId);
                    // $supporters = Support::countSupporterByTimestamp((int)$data['topic_num'], (int)$data['camp_num'], $submitterNickId, $statement->submit_time, ['topicNum' => $data['topic_num'], 'campNum' => $data['camp_num']]);
                    [$totalSupporters, $totalSupportersCount] = Support::getTotalSupporterByTimestamp((int)$data['topic_num'], (int)$data['camp_num'], $submitterNickId, $statement->submit_time, ['topicNum' => $data['topic_num'], 'campNum' => $data['camp_num']]);
                    if($submitterNickId > 0 && !in_array($submitterNickId, $agreed_supporters)) 
                    {   
                        $agreeCount++;
                    }
                    if ($agreeCount == $totalSupportersCount) {
                        $statement->go_live_time = strtotime(date('Y-m-d H:i:s'));
                        $statement->update();
                        self::updateStatementsInReview($statement);
                        ChangeAgreeLog::where('topic_num', '=', $data['topic_num'])->where('camp_num', '=', $data['camp_num'])->where('change_id', '=', $changeId)->where('change_for', '=', $data['change_for'])->delete();
                    }
                    $message = trans('message.success.statement_agree');
                } else {
                    return $this->resProvider->apiJsonResponse(400, trans('message.error.record_not_found'), '', '');
                }
            } else if ($data['change_for'] == 'camp') {
                $camp = Camp::where('id', $changeId)->first();
                if ($camp) {
                    DB::beginTransaction();

                    $filter['topicNum'] = $data['topic_num'];
                    $filter['campNum'] = $data['camp_num'];
                    $preLiveCamp = Camp::getLiveCamp($filter);
                    $data['parent_camp_num'] = $camp->parent_camp_num;
                    $data['old_parent_camp_num'] = $camp->old_parent_camp_num;
                    // Util::checkParentCampChanged($data, true, $liveCamp);
                    $submitterNickId = $camp->submitter_nick_id;
                    
                    /*
                    *   https://github.com/the-canonizer/Canonizer-Beta--Issue-Tracking/issues/232 
                    *   Now support at the time of submition will be count as total supporter. 
                    *   Also check if submitter is not a direct supporter, then it will be count as direct supporter   
                    */
                    // $supporters = Support::getAllSupporters($data['topic_num'], $data['camp_num'], $submitterNickId);
                    // $supporters = Support::countSupporterByTimestamp((int)$data['topic_num'], (int)$data['camp_num'], $submitterNickId, $camp->submit_time);
                    [$totalSupporters, $totalSupportersCount] = Support::getTotalSupporterByTimestamp((int)$data['topic_num'], (int)$data['camp_num'], $submitterNickId, $camp->submit_time, ['topicNum' => $data['topic_num'], 'campNum' => $data['camp_num']]);
                    if($submitterNickId > 0 && !in_array($submitterNickId, $agreed_supporters)) 
                    {   
                        $agreeCount++;
                    }

                    if ($agreeCount == $totalSupportersCount) {
                        $camp->go_live_time = strtotime(date('Y-m-d H:i:s'));
                        $camp->update();
                        self::updateCampsInReview($camp);
                        $liveCamp = Camp::getLiveCamp($filter);
                        Util::checkParentCampChanged($data, true, $liveCamp);
                        ChangeAgreeLog::where('topic_num', '=', $data['topic_num'])->where('camp_num', '=', $data['camp_num'])->where('change_id', '=', $changeId)->where('change_for', '=', $data['change_for'])->delete();
                        $topic = $camp->topic;
                        if (isset($topic)) {
                            Util::dispatchJob($topic, $camp->camp_num, 1);
                        }

                       /** Archive and restoration of archive camp #574 */
                        if($liveCamp->is_archive != $preLiveCamp->is_archive)
                        {
                            util::updateArchivedCampAndSupport($camp, $liveCamp->is_archive);
                        }
                    }
                    DB::commit();
                    $message = trans('message.success.camp_agree');
                } else {
                    DB::rollback();
                    return $this->resProvider->apiJsonResponse(400, trans('message.error.record_not_found'), '', '');
                }
            } else if ($data['change_for'] == 'topic') {
                $topic = Topic::where('id', $changeId)->first();
                if ($topic) {
                    $submitterNickId = $topic->submitter_nick_id;
                    /*
                    *   https://github.com/the-canonizer/Canonizer-Beta--Issue-Tracking/issues/232 
                    *   Now support at the time of submition will be count as total supporter. 
                    *   Also check if submitter is not a direct supporter, then it will be count as direct supporter   
                    */
                    // $supporters = Support::getAllSupporters($data['topic_num'], $data['camp_num'], $submitterNickId);
                    // $supporters = Support::countSupporterByTimestamp((int)$data['topic_num'], (int)$data['camp_num'], $submitterNickId, $topic->submit_time);
                    [$totalSupporters, $totalSupportersCount] = Support::getTotalSupporterByTimestamp((int)$data['topic_num'], (int)$data['camp_num'], $submitterNickId, $topic->submit_time, ['topicNum' => $data['topic_num'], 'campNum' => $data['camp_num']]);
                    
                    if($submitterNickId > 0 && !in_array($submitterNickId, $agreed_supporters)) 
                    {   
                        $agreeCount++;
                    }
                    
                    if ($agreeCount == $totalSupportersCount) {
                        $topic->go_live_time = strtotime(date('Y-m-d H:i:s'));
                        $topic->update();
                        self::updateTopicsInReview($topic);
                        ChangeAgreeLog::where('topic_num', '=', $data['topic_num'])->where('camp_num', '=', $data['camp_num'])->where('change_id', '=', $changeId)->where('change_for', '=', $data['change_for'])->delete();
                        if (isset($topic)) {
                            Util::dispatchJob($topic, $data['camp_num'], 1);
                        }
                    }
                    $message = trans('message.success.topic_agree');
                }
            } else {
                return $this->resProvider->apiJsonResponse(400, trans('message.error.record_not_found'), '', '');
            }
            return $this->resProvider->apiJsonResponse(200, $message, $responseData, '');
            
        } catch (Exception $e) {
          
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    private function updateTopicsInReview($topic)
    {
        $inReviewTopicChanges = Topic::where([
            ['topic_num', '=', $topic->topic_num],
            ['submit_time', '<', $topic->submit_time],
            ['go_live_time', '>', Carbon::now()->timestamp]
        ])->whereNull('objector_nick_id')->get();
        if (count($inReviewTopicChanges)) {
            foreach ($inReviewTopicChanges as $key=>$topic) {
                Topic::where('id', $topic->id)->update(['go_live_time' => strtotime(date('Y-m-d H:i:s')) - ($key+1)]);
            }
        }
    }

    private function updateStatementsInReview($statement)
    {
        $inReviewStatementChanges = Statement::where([
            ['topic_num', '=', $statement->topic_num],
            ['camp_num', '=', $statement->camp_num],
            ['submit_time', '<', $statement->submit_time],
            ['go_live_time', '>', Carbon::now()->timestamp]
        ])->whereNull('objector_nick_id')->get();
        if (count($inReviewStatementChanges)) {
            foreach ($inReviewStatementChanges as $key=>$statement) {
                Statement::where('id', $statement->id)->update(['go_live_time' => strtotime(date('Y-m-d H:i:s')) - ($key+1)]);
            }
        }
    }

    private function updateCampsInReview($camp)
    {
        $inReviewCampChanges = Camp::where([
            ['topic_num', '=', $camp->topic_num],
            ['camp_num', '=', $camp->camp_num],
            ['submit_time', '<', $camp->submit_time],
            ['go_live_time', '>', Carbon::now()->timestamp]
        ])->whereNull('objector_nick_id')->get();
        if (count($inReviewCampChanges)) {
            foreach ($inReviewCampChanges as $key=>$Camp) {
                Camp::where('id', $Camp->id)->update(['go_live_time' => strtotime(date('Y-m-d H:i:s')) - ($key+1)]);
            }
        }
    }


    /**
     * @OA\Post(path="/manage-topic",
     *   tags={"Topic"},
     *   summary="edit, update and object topic record",
     *   description="This API is used to edit, update and object topic record.",
     *   operationId="edit, update, object-TopicHistory",
     *   @OA\RequestBody(
     *       required=true,
     *       description="Get topic record history",
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
     *                  property="topic_id",
     *                  description="Topic id is required",
     *                  required=true,
     *                  type="integer",
     *              ),
     *               @OA\Property(
     *                   property="nick_name",
     *                   description="Nick name of the user",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="note",
     *                   description="Note for topic",
     *                   required=false,
     *                   type="string",
     *               ),
     *              @OA\Property(
     *                   property="submitter",                                      
     *                   description="Nick name id of user who previously added statement",
     *                   required=true,
     *                   type="integer",
     *               ),
     *              @OA\Property(
     *                   property="namespace_id",
     *                   description="TOpic namespace id",
     *                   required=true,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="event_type",
     *                   description="Possible values objection, edit, update",
     *                   required=true,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="objection_reason",
     *                   description="Objection reason in case user is objecting to a statement",
     *                   required=false,
     *                   type="string",
     *               )
     *         )
     *      )
     *   ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */
    public function manageTopic(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getManageTopicValidationRules(), $this->validationMessages->getManageTopicValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        if (!Gate::allows('nickname-check', $request->nick_name)) {
            return $this->resProvider->apiJsonResponse(403, trans('message.error.invalid_data'), '', '');
        }

        $all = $request->all();
        $current_time = time();
        try {
            $nickNameIds = Nickname::getNicknamesIdsByUserId($request->user()->id);
            $nickNames = Nickname::personNicknameArray();
            if (!in_array($request->nick_name, $nickNameIds)) {
                return $this->resProvider->apiJsonResponse(400, trans('message.general.nickname_association_absence'), '', '');
            }
            if (Topic::ifTopicNameAlreadyTaken($all)) {
                return $this->resProvider->apiJsonResponse(400, trans('message.error.topic_name_alreday_exist'), '', '');
            }
            DB::beginTransaction();
            if ($all['event_type'] == "objection") {
                // $checkUserDirectSupportExists = Support::checkIfSupportExists($all['topic_num'], $nickNames);
                $topic = Statement::where('id', $all['topic_id'])->first();
                $filters = [
                    'topicNum' => $all['topic_num'],
                    'campNum' => $all['camp_num'],
                ];
                $checkIfIAmExplicitSupporter = Support::ifIamExplicitSupporterBySubmitTime($filters, $nickNames , $topic->submit_time, 'topic', false, 'ifIamExplicitSupporter');

                if($checkIfIAmExplicitSupporter){
                    $message = trans('message.support.not_authorized_for_objection_topic');
                    return $this->resProvider->apiJsonResponse(400, $message, '', '');
                }
                $topic = Topic::where('id', $all['topic_id'])->first();
                $topic->objector_nick_id = $all['nick_name'];
                $topic->object_reason = $all['objection_reason'];
                $topic->object_time = $current_time;
                $topic->is_disabled =  !empty($request->is_disabled) ? $request->is_disabled : 0;
                $topic->is_one_level =  !empty($request->is_one_level) ? $request->is_one_level : 0;
                $message = trans('message.success.topic_object');
            }

            if ($all['event_type'] == "edit") {
                $topic = Topic::where('id', $all['topic_id'])->first();
                $topic->topic_name = Util::remove_emoji($all['topic_name']  ?? "");
                $topic->namespace_id = isset($all['namespace_id']) ? $all['namespace_id'] : "";
                $topic->submitter_nick_id = isset($all['nick_name']) ? $all['nick_name'] : "";
                $topic->note = isset($all['note']) ? $all['note'] : "";
                $topic->is_disabled =  !empty($request->is_disabled) ? $request->is_disabled : 0;
                $topic->is_one_level =  !empty($request->is_one_level) ? $request->is_one_level : 0;
                $message = trans('message.success.topic_update');
            }

            if ($all['event_type'] == "update") {
                $topic = new Topic();
                $topic->topic_num = $all['topic_num'];
                $topic->topic_name = Util::remove_emoji($all['topic_name']);
                $topic->namespace_id = $all['namespace_id'];
                $topic->submit_time = $current_time;
                $topic->submitter_nick_id = $all['nick_name'];
                // $topic->go_live_time = $current_time;
                $topic->go_live_time = Carbon::parse($current_time)->addDay()->timestamp;
                $topic->language = 'English';
                $topic->note = isset($all['note']) ? $all['note'] : "";
                // $topic->grace_period = 0;
                $topic->grace_period = 1;

                $topic->is_disabled =  !empty($request->is_disabled) ? $request->is_disabled : 0;
                $topic->is_one_level =  !empty($request->is_one_level) ? $request->is_one_level : 0;
                $message = trans('message.success.topic_update');
            }

            $ifIamSingleSupporter = Support::ifIamSingleSupporter($all['topic_num'], 0, $nickNames);

            if (!$ifIamSingleSupporter) {
                $topic->go_live_time = strtotime(date('Y-m-d H:i:s', strtotime('+1 days')));
                $topic->go_live_time;
                $topic->grace_period = 1;
            }

            if ($all['event_type'] == "objection") {
                $topic->grace_period = 0;
            }
            
            $topic->save();
            DB::commit();

            if ($all['event_type'] == "objection") {
                $this->objectedTopicNotification($all, $topic, $request);
            } else if ($all['event_type'] == "update") {
                
                Util::dispatchJob($topic, 1, 1);
                //timeline start
                if($all['topic_id']!=null){
                    $old_topic = Topic::where('id', $all['topic_id'])->first();
                    if(Util::remove_emoji(strtolower(trim($old_topic['topic_name']))) != Util::remove_emoji(strtolower(trim($all['topic_name'])))){

                        $nickName = Nickname::getNickName($topic->submitter_nick_id)->nick_name;

                        $timelineMessage = $nickName . " changed topic name from ". $old_topic['topic_name']. " to ".$topic->topic_name;

                        $timeline_url =Util::getTimelineUrlgetTimelineUrl($topic_num= $topic->topic_num, $topic_name =$topic->topic_name, $camp_num=1, $camp_name="Agreement", $topicTitle=$topic->topic_name, $type="update_topic", $rootUrl=null, $namespaceId=$topic->namespace_id, $topicCreatedByNickId=$topic->submitter_nick_id);
                        
                        Util::dispatchTimelineJob($topic_num = $topic->topic_num, $campNum = 1, $updateAll =1, $message =$timelineMessage, $type="update_topic", $id = 1, $old_parent_id=null, $new_parent_id=null, $delay=null, $asOfDefaultDate=time(),$timeline_url);   
                    }
                }
                //end of timeline
                $currentTime = time();
                $delayCommitTimeInSeconds = (1*60*60) + 10; // 1 hour commit time + 10 seconds for delay job
                $delayLiveTimeInSeconds = (24*60*60) + 10; // 24 hour commit time + 10 seconds for delay job
                if (($currentTime < $topic->go_live_time && $currentTime >= $topic->submit_time) && $topic->grace_period && $topic->objector_nick_id == null) {
                    Util::dispatchJob($topic, 1, 1, $delayCommitTimeInSeconds);
                    Util::dispatchJob($topic, 1, 1, $delayLiveTimeInSeconds);
                } else {
                    if($current_time < $topic->go_live_time && $topic->objector_nick_id == null) {
                        Util::dispatchJob($topic, 1, 1, $delayLiveTimeInSeconds);
                    }
                }
            }

            return $this->resProvider->apiJsonResponse(200, $message, '', '');
        } catch (Exception $e) {
            DB::rollback();
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage().''.$e->getLine());
        }
    }

    private function objectedTopicNotification($all, $topic, $request)
    {
        if (isset($topic)) {
            Util::dispatchJob($topic, 1, 1);
        }
        $user = Nickname::getUserByNickName($all['submitter']);
        $liveTopic = Topic::getLiveTopic($topic->topic_num, 'default');
        $link = 'topic/history/' . $topic->topic_num . '-' .  $liveTopic->topic_name;
        $nickName = Nickname::getNickName($all['nick_name']);
        $data['topic_link'] = Util::getTopicCampUrlWithoutTime($topic->topic_num, 1, $liveTopic, 1);
        $data['history_link'] = config('global.APP_URL_FRONT_END') .'/'. $link;
        $data['type'] = "Topic";
        $data['namespace_id'] = $topic->namespace_id;
        $data['object'] = $liveTopic->topic_name;
        $data['object_type'] = "";
        $data['nick_name'] = $nickName->nick_name;
        $data['forum_link'] = 'forum/' . $topic->topic_num . '-' . $liveTopic->topic_name . '/1/threads';
        $data['subject'] = $data['nick_name'] . " has objected to your proposed change.";
        $data['namespace_id'] = (isset($topic->namespace_id) && $topic->namespace_id)  ?  $topic->namespace_id : 1;
        $data['nick_name_id'] = $nickName->id;
        $data['help_link'] = config('global.APP_URL_FRONT_END') . '/' .General::getDealingWithDisagreementUrl();
        $activityLogData = [
            'log_type' =>  "topic/camps",
            'activity' => trans('message.activity_log_message.topic_object', ['nick_name' =>  $nickName->nick_name]),
            'url' => $link,
            'model' => $topic,
            'topic_num' => $topic->topic_num,
            'camp_num' =>  1,
            'user' => $request->user(),
            'nick_name' => $nickName->nick_name,
            'description' => $liveTopic->topic_name
        ];
        try {
            dispatch(new ActivityLoggerJob($activityLogData))->onQueue(env('ACTIVITY_LOG_QUEUE'));
            dispatch(new ObjectionToSubmitterMailJob($user, $link, $data))->onQueue(env('NOTIFY_SUPPORTER_QUEUE'));
            GetPushNotificationToSupporter::pushNotificationOnObject($topic->topic_num, 1, $all['submitter'],$all['nick_name'],config('global.notification_type.objectTopic'));
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(path="/get-topic-history",
     *   tags={"Topic"},
     *   summary="get topic history",
     *   description="This API is used to get topic history.",
     *   operationId="getTopicHistory",
     *   @OA\RequestBody(
     *       required=true,
     *       description="Get topic history",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *              @OA\Property(
     *                  property="topic_num",
     *                  description="Topic number is required",
     *                  required=true,
     *                  type="integer",
     *              ),
     *               @OA\Property(
     *                   property="per_page",
     *                   description="Records per page",
     *                   required=true,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="event_type",
     *                   description="Possible values are objected, live, in_review, old, all",
     *                   required=true,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="page",
     *                   description="Page number",
     *                   required=true,
     *                   type="string",
     *               )
     *         )
     *      )
     *   ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */

    public function getTopicHistory(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getTopicHistoryValidationRules(), $this->validationMessages->getTopicHistoryValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $filter['topicNum'] = $request->topic_num;
        $filter['campNum'] = 1;
        $filter['per_page'] = $request->per_page;
        $filter['page'] = $request->page;
        $filter['currentTime'] = time();
        $filter['type'] = $request->type;
        $response = new stdClass();
        $details = new stdClass();
        try {
            $topicHistoryQuery = Topic::where('topic_num', $filter['topicNum'])->latest('submit_time');
            $topics = Topic::getTopicHistory($filter, $request, $topicHistoryQuery);
            $response = $topics;
            $details->ifIamSupporter = null;
            $details->ifSupportDelayed = null;
            $details->ifIAmExplicitSupporter = null;
            $details->topic = Camp::getAgreementTopic($filter);
            $details->parentTopic = (sizeof($topics->items) > 1) ?  $topics->items[0]->topic_name : null;
            $submit_time = $topicHistoryQuery->first() ? $topicHistoryQuery->first()->submit_time : null;
            if ($request->user()) {
                $nickNames = Nickname::personNicknameArray();
                $details->ifIamSupporter = Support::ifIamSupporter($filter['topicNum'], 1, $nickNames, $submit_time);
                $details->ifSupportDelayed = Support::ifIamSupporter($filter['topicNum'], 1, $nickNames, $submit_time, $delayed = true);
                $details->ifIAmExplicitSupporter = Support::ifIamExplicitSupporter($filter, $nickNames, "topic");
            }
            $response->details = $details;
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $response, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(path="/edit-topic",
     *   tags={"Topic"},
     *   summary="Get topic record",
     *   description="Get topic details for editing",
     *   operationId="editTopicRecord",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *   @OA\RequestBody(
     *       required=true,
     *       description="Edit topic",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *              @OA\Property(
     *                  property="record_id",
     *                  description="Record id is required",
     *                  required=true,
     *                  type="integer",
     *               ),
     *               @OA\Property(
     *                   property="event_type",
     *                   description="Possible values are edit, objected, live, in_review, old, all",
     *                   required=true,
     *                   type="string",
     *               ),
     *         )
     *      )
     *   ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */
    public function editTopicRecord(Request $request, Validate $validate)
    {
        try {
            $validationErrors = $validate->validate($request, $this->rules->getEditCaseValidationRules(), $this->validationMessages->getEditCaseValidationMessages());
            if ($validationErrors) {
                return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
            }
            $topic = Topic::where('id', $request->record_id)->first();
            if ($topic) {
                // if topic is agreed and live by another supporter, then it is not objectionable.
                if ($request->event_type == 'objection' && $topic->go_live_time <= time()) {
                    $response = collect($this->resProvider->apiJsonResponse(400, trans('message.error.objection_history_changed', ['history' => 'topic']), '', '')->original)->toArray();
                    $response['is_live'] = true;
                    return $response;
                }

                $nickName = Nickname::topicNicknameUsed($topic->topic_num);
                $data = new stdClass();
                $data->topic = $topic;
                $data->nick_name = $nickName;
                $response[0] = $data;
                $indexes = ['topic', 'nick_name'];
                $response = $this->resourceProvider->jsonResponse($indexes, $response);
                $response = $response[0];
                return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $response, '');
            } else {
                return $this->resProvider->apiJsonResponse(404, trans('message.error.record_not_found'), '', '');
            }
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

/**
     * @OA\Post(path="/discard/change",
     *   tags={"Topic"},
     *   summary="discard a change",
     *   description="Used to discard a change for camp, topic and statement.",
     *   operationId="discardChange",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *   @OA\RequestBody(
     *       required=true,
     *       description="Discard change",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               @OA\Property(
     *                   property="id",
     *                   description="Record id is required",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="type",
     *                   description="Type (topic, camp, statement)",
     *                   required=true,
     *                   type="string",
     *               ),
     *         )
     *      )
     *   ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */
    public function discardChange(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getDiscardChangeValidationRules(), $this->validationMessages->getDiscardChangeValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $inputs = $request->post();
        $type = $inputs['type'];
        $id = $inputs['id'];
        $message = "";
        $nickNames = Nickname::personNicknameArray();
        try {
            if ($type == 'statement') {
                $model = Statement::where('id', '=', $id)->whereIn('submitter_nick_id', $nickNames)->first();
            } else if ($type == 'camp') {
                $model = Camp::where('id', '=', $id)->first();
            } else if ($type == 'topic') {
                $model = Topic::where('id', '=', $id)->first();
            }
            if (!$model) {
                return $this->resProvider->apiJsonResponse(400, trans('message.error.record_not_found'), '', '');
            }

            if ($model->grace_period == 1) {
                $model->delete();
            } else {
                throw new Exception('The Change is already submitted. You cannot discard it.');
            }

            return $this->resProvider->apiJsonResponse(200, $message, '', '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    private function updateCampNotification($camp, $liveCamp, $link, $request)
    {
        $link = config('global.APP_URL_FRONT_END') .'/camp/history/' . $camp->topic_num . '/' . $camp->camp_num;
        $data['type'] = "camp";
        $data['object'] = $liveCamp->topic->topic_name . " >> " . $camp->camp_name;
        $data['link'] = $link;
        $data['support_camp'] = $liveCamp->camp_name;
        $data['is_live'] = ($camp->go_live_time <= time()) ? 1 : 0;
        $data['note'] = $camp->note;
        $data['camp_num'] = $camp->camp_num;
        $nickName = Nickname::getNickName($camp->submitter_nick_id);
        $data['topic_num'] = $camp->topic_num;
        $data['nick_name'] = $nickName->nick_name;
        $data['subject'] = "Proposed change to " . $liveCamp->topic->topic_name . ' >> ' . $liveCamp->camp_name . " submitted";
        $data['namespace_id'] = (isset($liveCamp->topic->namespace_id) && $liveCamp->topic->namespace_id)  ?  $liveCamp->topic->namespace_id : 1;
        $data['nick_name_id'] = $nickName->id;
        $notificationData = [
            "email" => [],
            "push_notification" => []
        ];
        $notificationData['email'] = $data;
        Event::dispatch(new NotifySupportersEvent($liveCamp, $notificationData, config('global.notification_type.manageCamp'), $link, config('global.notify.email')));

        // $subscribers = Camp::getCampSubscribers($camp->topic_num, $camp->camp_num);
        // $activityLogData = [
        //     'log_type' =>  "topic/camps",
        //     'activity' => trans('message.activity_log_message.camp_update', ['nick_name' => $nickName->nick_name]),
        //     'url' => $link,
        //     'model' => $camp,
        //     'topic_num' => $camp->topic_num,
        //     'camp_num' =>  $camp->camp_num,
        //     'user' => $request->user(),
        //     'nick_name' => $nickName->nick_name,
        //     'description' => $camp->camp_name
        // ];
        // dispatch(new ActivityLoggerJob($activityLogData))->onQueue(env('ACTIVITY_LOG_QUEUE'));
        // Util::mailSubscribersAndSupporters([], $subscribers, $link, $data);
    }
}

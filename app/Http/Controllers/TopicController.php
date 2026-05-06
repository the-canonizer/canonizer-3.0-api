<?php

namespace App\Http\Controllers;

use stdClass;
use Exception;
use Throwable;
use Carbon\Carbon;
use App\Models\Tag;
use App\Facades\Aws;
use App\Models\Camp;
use App\Facades\Util;
use App\Models\Topic;
use App\Models\Support;
use App\Helpers\Helpers;
use App\Library\General;
use App\Models\Nickname;
use App\Models\TopicTag;
use App\Models\Statement;
use App\Models\Namespaces;
use App\Models\FeatureTopic;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Models\ChangeAgreeLog;
use App\Jobs\ActivityLoggerJob;
use App\Models\CampSubscription;
use App\Facades\Aws as FacadesAws;
use App\Helpers\ResourceInterface;
use App\Helpers\ResponseInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Events\NotifySupportersEvent;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Query\Builder;
use App\Http\Request\ValidationMessages;
use App\Events\ThankToSubmitterMailEvent;
use App\Jobs\ObjectionToSubmitterMailJob;
use App\Facades\GetPushNotificationToSupporter;
use App\Models\HotTopic;
use App\Events\{CampLeaderAssignedEvent, CampLeaderRemovedEvent};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Services\CampService;

class TopicController extends Controller
{

    protected $rules;
    protected $validationMessages;
    protected $campService;

    public function __construct(ResponseInterface $respProvider, ResourceInterface $resProvider, ValidationRules $rules, ValidationMessages $validationMessages, CampService $campService)
    {
        $this->rules = $rules;
        $this->validationMessages = $validationMessages;
        $this->resourceProvider  = $resProvider;
        $this->resProvider = $respProvider;
        $this->campService = $campService;
    }

    /**
     * @OA\Post(
     *   path="/topic/save",
     *   tags={"Topic"},
     *   summary="Save topic",
     *   description="This endpoint is used to save a new topic.",
     *   operationId="topicSave",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     description="Request body JSON parameters",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(
     *             property="topic_name",
     *             type="string",
     *             example="hello sandbox topic",
     *             description="The name of the topic"
     *         ),
     *         @OA\Property(
     *             property="namespace",
     *             type="integer",
     *             example=1,
     *             description="Namespace ID"
     *         ),
     *         @OA\Property(
     *             property="nick_name",
     *             type="integer",
     *             example=709,
     *             description="Nick name ID of the submitter"
     *         ),
     *         @OA\Property(
     *             property="tags",
     *             type="array",
     *             @OA\Items(type="integer", example=6),
     *             description="Array of tag IDs"
     *         ),
     *         @OA\Property(
     *             property="is_rank_hidden",
     *             type="boolean",
     *             example=true,
     *             description="Boolean flag to indicate if the rank is hidden"
     *         ),
     *         @OA\Property(
     *             property="note",
     *             type="string",
     *             example="This is an optional note.",
     *             description="Additional information about the topic"
     *         )
     *     )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Successful operation",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Success"),
     *           @OA\Property(property="error", type="string", nullable=true),
     *           @OA\Property(
     *               property="data",
     *               type="object",
     *               @OA\Property(property="topic_num", type="integer", example=123)
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Something went wrong",
     *       @OA\JsonContent(
     *           oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *       )
     *   )
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
                $error['existed_topic_reference']["topic_name"] = $liveTopicData->topic_name ?? "";
                $error['existed_topic_reference']["topic_num"] = $liveTopicData->topic_num ?? "";
                $message = trans('message.error.invalid_data');
                return $this->resProvider->apiJsonResponse($status, $message, $result, $error);
            }
        }

        if (isset($nonLiveTopicData) && $nonLiveTopicData != null) {
            if ($nonLiveTopicData && isset($nonLiveTopicData['topic_name'])) {
                $status = 400;
                $result->if_exist = true;
                $error['topic_name'][] = trans('message.validation_topic_store.topic_name_under_review');
                $error['existed_topic_reference']["topic_name"] = $nonLiveTopicData->topic_name ?? "";
                $error['existed_topic_reference']["topic_num"] = $nonLiveTopicData->topic_num ?? "";
                $error['existed_topic_reference']["under_review"] = 1;
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
                "is_rank_hidden" =>  !empty($request->is_rank_hidden) ? $request->is_rank_hidden : 0
            ];

            DB::beginTransaction();
            $topic = Topic::create($input);

            $nickName = Nickname::getNickName($request->nick_name)->nick_name;
            if ($topic) {
                // Check if the array exists for tags ...
                if ($request->has('tags') && is_array($request->tags)) {
                    $topic->tags()->syncWithPivotValues($request->tags, ['topic_num' => $topic->topic_num]);
                }

                Util::dispatchJob($topic, 1, 1);
                // Eventline - topic create event saved
                $timelineMessage = $nickName . " created a new topic " . $topic->topic_name;

                $timeline_url = Util::getTimelineUrlgetTimelineUrl($topic->topic_num, $topic->topic_name, 1, "Agreement", $topic->topic_name, "create_topic", null, $topic->namespace_id, $topic->submitter_nick_id);

                Util::dispatchTimelineJob($topic->topic_num, 1, 1, $timelineMessage, "create_topic", 1, null, null, null, time(), $timeline_url);

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
                // Eventline - default support event saved
                $timelineMessage = $nickName . " added their support on Camp Agreement";

                $timeline_url = Util::getTimelineUrlgetTimelineUrl($topic->topic_num, $topic->topic_name, 1, "Agreement", $topic->topic_name, "direct_support_added", null, $topic->namespace_id, $topic->submitter_nick_id);

                Util::dispatchTimelineJob($topic->topic_num, 1, 1, $timelineMessage, "direct_support_added", 1, null, null, null, time() + 1, $timeline_url);


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
                    $historylink = Util::topicHistoryLink($topic->topic_num, $topic->topic_name, 'topic', 1, 'Aggreement');
                    $dataEmail = (object) [
                        "type" => "topic",
                        "link" => $link,
                        "historylink" => $historylink,
                        "object" => $topic->topic_name,
                        'namespace_id' => $topic->namespace_id,
                        'note' => $topic->note
                    ];
                    Event::dispatch(new ThankToSubmitterMailEvent($request->user(), $dataEmail));
                    $nickName = Nickname::getNickName($request->nick_name)->nick_name;
                    $activitLogData = [
                        'log_type' =>  "topic/camps",
                        'activity' => trans('message.activity_log_message.topic_create', ['nick_name' =>  $nickName, 'camp_name' => Camp::AGREEMENT_CAMP]),
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
     * @OA\Post(
     *   path="/get-topic-record",
     *   tags={"Topic"},
     *   summary="Retrieve a topic record",
     *   description="Fetches a topic record based on filters such as topic number, camp number, and timestamp.",
     *   operationId="getTopicRecord",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(
     *               property="topic_num",
     *               type="integer",
     *               example=116,
     *               description="The topic number"
     *           ),
     *           @OA\Property(
     *               property="as_of",
     *               type="string",
     *               example="default",
     *               description="As of filter (e.g., 'default', timestamp)"
     *           ),
     *           @OA\Property(
     *               property="as_of_date",
     *               type="integer",
     *               example=1709078400,
     *               description="As of date in timestamp format"
     *           ),
     *           @OA\Property(
     *               property="camp_num",
     *               type="integer",
     *               example=1,
     *               description="The camp number associated with the topic"
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Successful retrieval of the topic record",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Success"),
     *           @OA\Property(
     *               property="data",
     *               type="object",
     *               @OA\Property(property="topic_num", type="integer", example=116),
     *               @OA\Property(property="camp_num", type="integer", example=1),
     *               @OA\Property(property="topic_name", type="string", example="Climate Change"),
     *               @OA\Property(property="namespace_name", type="string", example="Science"),
     *               @OA\Property(property="topicSubscriptionId", type="string", example=""),
     *               @OA\Property(property="namespace_id", type="integer", example=2),
     *               @OA\Property(property="note", type="string", example="A detailed discussion on climate change."),
     *               @OA\Property(property="submitter_nick_name", type="string", example="JohnDoe"),
     *               @OA\Property(property="go_live_time", type="integer", example=1709078400),
     *               @OA\Property(property="camp_about_nick_id", type="integer", example=12),
     *               @OA\Property(property="submitter_nick_id", type="integer", example=45),
     *               @OA\Property(property="submit_time", type="string", format="date-time", example="2025-03-04T12:34:56Z"),
     *               @OA\Property(property="tags", type="array", @OA\Items(type="string", example="environment")),
     *               @OA\Property(property="in_review_changes", type="integer", example=2)
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad Request - Validation errors or exception occurred",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Invalid parameters")
     *       )
     *   ),
     *   @OA\Response(
     *       response=404,
     *       description="Topic not found",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=404),
     *           @OA\Property(property="message", type="string", example="Topic record not found")
     *       )
     *   )
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
            if (!$topic)
                return $this->resProvider->apiJsonResponse(404, '', null, trans('message.error.topic_record_not_found'));

            $namespace = Namespaces::find($topic->namespace_id);
            $namespaceLabel = '';
            if (!empty($namespace)) {
                $namespaceLabel = Namespaces::getNamespaceLabel($namespace, $namespace->name);
            }
            $topic->namespace_name = $namespaceLabel;
            $topic->submitter_nick_name = NickName::getNickName($topic->submitter_nick_id)->nick_name;
            $topic->topicSubscriptionId = "";
            $topic->camp_num =  $topic->camp_num ?? 1;
            $topic->load('tags');
            $topic->tags->makeHidden(['pivot']);
            // $topic->agreement_camp_record = app(CampController::class)->getCampRecord($request->merge(['camp_num' => 1]), $validate)->getData()->data;
            $topic->agreement_camp_record = $this->campService->getCampRecordData($request->topic_num, 1, ['topicNum' => $request->topic_num, 'campNum' => 1, 'asOf' => $request->as_of, 'asOfDate' => $request->as_of_date], $request->user());
            // $topic->tags = $topic->tags_array;
            if ($request->user()) {
                $topicSubscriptionData = CampSubscription::where('user_id', '=', $request->user()->id)->where('camp_num', '=', 0)->where('topic_num', '=', $filter['topicNum'])->where('subscription_start', '<=', strtotime(date('Y-m-d H:i:s')))->where('subscription_end', '=', null)->orWhere('subscription_end', '>=', strtotime(date('Y-m-d H:i:s')))->first();
                $topic->topicSubscriptionId = isset($topicSubscriptionData->id) ? $topicSubscriptionData->id : "";
            }
            $topicRecord[] = $topic;
            $indexs = ['topic_num', 'camp_num', 'topic_name', 'namespace_name', 'topicSubscriptionId', 'namespace_id', 'note', 'submitter_nick_name', 'go_live_time', 'camp_about_nick_id', 'submitter_nick_id', 'submit_time', 'tags', 'agreement_camp_record'];
            $topicRecord = $this->resourceProvider->jsonResponse($indexs, $topicRecord);
            $topicRecord = $topicRecord[0];

            if ($topic && $filter['asOf'] === 'default') {
                $inReviewChangesCount = Helpers::getChangesCount((new Topic()), $request->topic_num, $request->camp_num);
                $topicRecord = array_merge($topicRecord, ['in_review_changes' => $inReviewChangesCount]);
            }

            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $topicRecord, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(path="/commit/change",
     *   tags={"Topic"},
     *   summary="Commit a change",
     *   description="Used to commit a change for camp, topic, and statement.",
     *   operationId="commitChange",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="Commit change request body",
     *       @OA\JsonContent(
     *           required={"id", "type"},
     *           @OA\Property(
     *               property="id",
     *               type="integer",
     *               example=8227,
     *               description="Record ID (required)"
     *           ),
     *           @OA\Property(
     *               property="type",
     *               type="string",
     *               example="topic",
     *               description="Type of change (topic, camp, statement) (required)"
     *           ),
     *           @OA\Property(
     *               property="old_parent_camp_num",
     *               type="integer",
     *               nullable=true,
     *               example=null,
     *               description="Old parent camp number (optional)"
     *           ),
     *           @OA\Property(
     *               property="parent_camp_num",
     *               type="integer",
     *               nullable=true,
     *               example=null,
     *               description="New parent camp number (optional)"
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Change committed successfully")
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad Request - Validation errors or exception occurred",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Invalid parameters")
     *       )
     *   )
     * )
     */


    public function commitAndNotifyChange(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getCommitChangeValidationRules(), $this->validationMessages->getCommitChangeValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        $iscalledfromService = $request->called_from_service ?? false;

        if ($iscalledfromService && $request->header('Authorization') != 'Bearer: ' . env('API_TOKEN')) {
            return $this->resProvider->apiJsonResponse(401, 'Unauthorized', '', '');
        }

        $all = $request->all();
        $type = $all['type'];
        $id = $all['id'];
        $message = "";
        $event_type = NULL;
        $nickNames = Nickname::personNicknameArray();
        $archiveCampSupportNicknames = [];
        $changeGoneLive = false;
        try {
            if ($type == 'statement') {
                $model = Statement::where('id', '=', $id)
                    ->when(!$iscalledfromService, function ($query) use ($nickNames) {
                        return $query->whereIn('submitter_nick_id', $nickNames);
                    })
                    ->first();
            } else if ($type == 'camp') {
                $model = Camp::where('id', '=', $id)->first();
            } else if ($type == 'topic') {
                $model = Topic::where('id', '=', $id)->first();
            }
            if (!$model) {
                return $this->resProvider->apiJsonResponse(400, trans('message.error.record_not_found'), '', '');
            }

            if ($iscalledfromService) {
                $nickNames = Nickname::personNicknameArray($model->submitter_nick_id);
            }

            $filter['topicNum'] = $model->topic_num;
            $filter['campNum'] = $model->camp_num ?? 1;
            $filter['asOf'] = $all['asOf'] ?? "default";
            $filter['asOfDate'] = $model->go_live_time;
            $archiveReviewPeriod = false;
            $preLiveStatment = Statement::getLiveStatement($filter);
            $preliveCamp = Camp::getLiveCamp($filter);
            $preliveTopic = Topic::getLiveTopic($model->topic_num, 'default');
            $prevArchiveStatus = $preliveCamp->is_archive;
            $pre_LiveId = null;

            // Check if nominated camp leader is not a direct supporter, object the change if nominated as camp leader
            $directSupports = collect(Support::getAllDirectSupporters($model->topic_num, $model->camp_num))->pluck('nick_name_id')->values()->all();
            if ($type == 'camp' && !is_null($model->camp_leader_nick_id) && $model->grace_period === 1 && !in_array($model->camp_leader_nick_id, $directSupports) && $preliveCamp->camp_leader_nick_id !== $model->camp_leader_nick_id) {
                $model->objector_nick_id = $model->camp_leader_nick_id;
                $model->object_reason = trans('message.camp_leader.error.system_generated.nominated_user_removes_support');
                $model->object_time = time();
                $model->grace_period = 0;
                $model->save();

                $responseData = [
                    "archive_camp_support_nicknames" => $archiveCampSupportNicknames,
                    "change_gone_live" => $changeGoneLive ?? false
                ];
                return $this->resProvider->apiJsonResponse(200, $message, $responseData, '');
            }

            if ($type == 'camp') {

                $updatedArchiveStatus = $model->is_archive;
                if ($prevArchiveStatus != $updatedArchiveStatus && $updatedArchiveStatus === 0) {  //need to check if archive = 0 or 1
                    $model->archive_action_time = time();
                    // get supporters list
                    $archiveCampSupportNicknames = Support::getSupportersNickNameOfArchivedCamps($model->topic_num, [$model->camp_num], $updatedArchiveStatus);
                    $explicitArchiveSupporters = Support::ifIamArchiveExplicitSupporters($filter, $updatedArchiveStatus, 'supporters');
                    foreach ($archiveCampSupportNicknames as $key => $sp) {
                        if (in_array($sp->nick_name_id, $nickNames) || $sp->delegate_nick_name_id != 0) {
                            unset($archiveCampSupportNicknames[$key]);
                        }
                    }

                    foreach ($explicitArchiveSupporters as $key => $xsp) {
                        if (in_array($xsp->nick_name_id, $nickNames) || $xsp->delegate_nick_name_id != 0) {
                            unset($explicitArchiveSupporters[$key]);
                        }
                    }

                    // echo count($explicitArchiveSupporters);
                    if (count($archiveCampSupportNicknames) > 0 || count($explicitArchiveSupporters) > 0) {
                        $archiveReviewPeriod = true;
                    }
                }
            }

            $ifIamSingleSupporter = Support::ifIamSingleSupporter($filter['topicNum'], $nickNames, $filter['campNum']);

            $model->submit_time = time();
            // $model->go_live_time = strtotime(date('Y-m-d H:i:s', strtotime('+1 days')));
            $model->go_live_time = Carbon::now()->addSeconds((int)env('LIVE_TIME_DELAY_IN_SECONDS') - 10)->timestamp;
            if ($ifIamSingleSupporter && !$archiveReviewPeriod) {
                $model->go_live_time = time();
                $changeGoneLive = true;
            }

            if ($type == 'camp') {
                $totalSupporters = Support::getTotalSupporterByTimestamp('camp', (int)$filter['topicNum'], (int)$filter['campNum'], $model->submitter_nick_id, $model->submit_time, $filter + ['change_id' => $model->id], false)[0];
                if (count($totalSupporters) === 1 && in_array($model->submitter_nick_id, collect($totalSupporters)->pluck('id')->all()) && !$archiveReviewPeriod) {
                    $model->go_live_time = time();
                    $changeGoneLive = true;
                    // Log of system assigned/remove camp leader
                    if (!is_null($model->camp_leader_nick_id)) {
                        Camp::dispatchCampLeaderActivityLogJob($preliveTopic, $model, $model->camp_leader_nick_id, request()->user(), 'assigned');
                    }

                    if (!is_null($preliveCamp->camp_leader_nick_id)) {
                        Camp::dispatchCampLeaderActivityLogJob($preliveTopic, $model, $preliveCamp->camp_leader_nick_id, request()->user(), 'removed');
                    }
                }
            }

            $model->grace_period = 0;

            $model->update();

            //Updating rest of the changes which are "in review"
            if ($ifIamSingleSupporter) {
                switch ($type) {
                    case 'statement':
                        Helpers::updateStatementsInReview($model);
                        break;
                    case 'camp':
                        if (!$archiveReviewPeriod) {
                            Helpers::updateCampsInReview($model);
                        }
                        break;
                    case 'topic':
                        if (!$archiveReviewPeriod) {
                            Helpers::updateTopicsInReview($model);
                        }
                        break;

                    default:
                        break;
                }
            }

            $liveCamp = Camp::getLiveCamp($filter);
            $liveTopic = Topic::getLiveTopic($model->topic_num, 'default');
            if ($type == 'topic') {
                // $directSupporter = Support::getAllDirectSupporters($liveTopic->topic_num);
                // $subscribers = Camp::getCampSubscribers($liveTopic->topic_num, 1);
                $data['namespace_id'] = (isset($liveTopic->namespace_id) && $liveTopic->namespace_id)  ?  $liveTopic->namespace_id : 1;

                // $data['object'] = $liveTopic->topic_name;
                $data['object'] = Helpers::renderParentCampLinks($liveTopic->topic_num, 1, $liveTopic->topic_name, true, 'topic');
            } else {
                // $directSupporter =  Support::getAllDirectSupporters($model->topic_num, $model->camp_num);
                // $subscribers = Camp::getCampSubscribers($model->topic_num, $model->camp_num);
                // $data['object'] = $liveCamp->topic->topic_name . ' >> ' . $liveCamp->camp_name;

                $data['object'] = Helpers::renderParentCampLinks($liveCamp->topic->topic_num, $liveCamp->camp_num, $liveCamp->topic->topic_name, true, 'camp');

                $data['namespace_id'] = (isset($liveCamp->topic->namespace_id) && $liveCamp->topic->namespace_id)  ?  $liveCamp->topic->namespace_id : 1;
                $data['camp_num'] = $model->camp_num;
            }
            $nickName = Nickname::getNickName($model->submitter_nick_id);
            $data['go_live_time'] = $model->go_live_time;
            $data['note'] = $model->note;
            $data['topic_num'] = $model->topic_num;
            $data['nick_name'] = $nickName->nick_name;
            $data['nick_name_id'] = $nickName->id;
            $changeData = [];
            if ($type == 'statement') {
                $link = config('global.APP_URL_FRONT_END') . '/statement/history/' . $model->topic_num . '/' . $model->camp_num;
                $data['support_camp'] = $liveCamp->camp_name;
                $data['type'] = 'statement : for camp ';
                $data['typeobject'] = 'statement';
                $data['forum_link'] = 'forum/' . $model->topic_num . '-statement/' . $model->camp_num . '/threads';
                $data['subject'] = "Proposed change to statement for camp " . $liveCamp->topic->topic_name . " >> " . $liveCamp->camp_name . " submitted";
                $message = trans('message.success.statement_commit');

                $notification_type = config('global.notification_type.statementCommit');
                $event_type = "statement";
                $pre_LiveId = $model->id;
                // GetPushNotificationToSupporter::pushNotificationToSupporter($request->user(), $model->topic_num, $model->camp_num, "statement-commit", null, $nickName->nick_name);

                if (!$ifIamSingleSupporter) {

                    if (($preLiveStatment->parsed_value ?? "-") !== ($model->parsed_value ?? "-")) {
                        $changeData[] =  [
                            'field' => 'statement',
                            'live' => '<p>' . trim(strip_tags($preLiveStatment->parsed_value ?? "-")) . '</p>',
                            'change-in-review' => '<p>' . trim(strip_tags($model->parsed_value ?? "-")) . '</p>',
                        ];
                    }

                    if (($preLiveStatment->note ?? "-") !== ($model->note ?? "-")) {
                        $changeData[] =  [
                            'field' => 'summary',
                            'live' => trim($preLiveStatment->note ?? "-"),
                            'change-in-review' => trim($model->note ?? "-"),
                        ];
                    }

                    if (count($changeData) > 0) {
                        $changeData = [
                            'type' => 'camp',
                            'data' => $changeData,
                        ];
                    }
                }
            } else if ($type == 'camp') {
                $link = config('global.APP_URL_FRONT_END') . '/camp/history/' . $liveCamp->topic_num . '/' . $liveCamp->camp_num;
                $data['support_camp'] = $model->camp_name;
                $data['type'] = 'camp : ';
                $data['typeobject'] = 'camp';
                $data['forum_link'] = 'forum/' . $liveCamp->topic_num . '-' . $liveCamp->camp_name . '/' . $liveCamp->camp_num . '/threads';
                $data['subject'] = "Proposed change to " . $liveCamp->topic->topic_name . ' >> ' . $liveCamp->camp_name . " submitted";
                $topic = $model->topic;
                $message = trans('message.success.camp_commit');

                if ($all['parent_camp_num'] != $all['old_parent_camp_num']) {
                    $event_type = "parent_change";
                }

                if (Util::remove_emoji(strtolower(trim($preliveCamp->camp_name))) != Util::remove_emoji(strtolower(trim($model->camp_name)))) {
                    $event_type = "update_camp";
                }

                $pre_LiveId = $preliveCamp->id;

                if ($ifIamSingleSupporter) {

                    /** Archive and restoration of archive camp #574 */
                    if (!$archiveReviewPeriod) {
                        Util::updateArchivedCampAndSupport($model, $model->is_archive, $prevArchiveStatus);
                    }
                    $all['topic_num'] = $liveCamp->topic_num;
                    Util::checkParentCampChanged($all, false, $liveCamp);
                    $beforeUpdateCamp = Util::getCampByChangeId($filter['campNum']);
                    $before_parent_camp_num = $beforeUpdateCamp->parent_camp_num;
                    if ($before_parent_camp_num == $all['parent_camp_num']) {
                        Util::parentCampChangedBasedOnCampChangeId($filter['campNum']);
                    }
                    // $this->updateCampNotification($model, $liveCamp, $link, $request);


                    // $prevArchiveStatus = $preliveCamp->is_archive;
                    // $updatedArchiveStatus = $all['is_archive'] ?? 0;
                    // if ($prevArchiveStatus != $updatedArchiveStatus) {
                    //     Util::updateArchivedCampAndSupport($model, $updatedArchiveStatus);
                    // }

                    //timeline start
                    // $nickName = Nickname::getNickName($model->submitter_nick_id)->nick_name;
                    if ($event_type == "parent_change") {
                        $timelineMessage = $nickName->nick_name . " changed the parent of camp   " . $model->camp_name;

                        $timeline_url = Util::getTimelineUrlgetTimelineUrl($topic->topic_num, $topic->topic_name, $model->camp_num, $model->camp_name, $topic->topic_name, "parent_change", null, $topic->namespace_id, $topic->submitter_nick_id);

                        Util::dispatchTimelineJob($topic->topic_num, $model->camp_num, 1, $timelineMessage, "parent_change", $model->id, $all['old_parent_camp_num'], $all['parent_camp_num'], null, time(), $timeline_url);
                    }
                    //end of timeline
                    //timeline start
                    //$old_camp = Camp::where('id', $model->camp_num)->first();
                    if ($event_type == "update_camp") {
                        $timelineMessage = $nickName->nick_name . " changed camp name from " . $preliveCamp->camp_name . " to " . $model->camp_name;

                        $timeline_url = Util::getTimelineUrlgetTimelineUrl($topic->topic_num, $topic->topic_name, $model->camp_num, $model->camp_name, $topic->topic_name, "update_camp", null, $topic->namespace_id, $topic->submitter_nick_id);

                        Util::dispatchTimelineJob($topic->topic_num, $model->camp_num, 1, $timelineMessage, "update_camp", $model->id, null, null, null, time(), $timeline_url);
                    }
                    //end of timeline
                } else {
                    $changeData = [];

                    if ($preliveCamp->camp_name !== $model->camp_name) {
                        $changeData[] =  [
                            'field' => 'camp_name',
                            'live' => trim($preliveCamp->camp_name),
                            'change-in-review' => trim($model->camp_name),
                        ];
                    }

                    if ($preliveCamp->parent_camp_num !== $model->parent_camp_num) {
                        $changeData[] =  [
                            'field' => 'parent_camp',
                            'live' => trim(isset($preliveCamp->parent_camp_num) && $preliveCamp->parent_camp_num != 0 ? Camp::getParentCamp($preliveCamp->topic_num, $preliveCamp->parent_camp_num, 'default')->camp_name : null),
                            'change-in-review' => trim(isset($model->parent_camp_num) && $model->parent_camp_num != 0 ? Camp::getParentCamp($model->topic_num, $model->parent_camp_num, 'default')->camp_name : null),
                        ];
                    }

                    if ($preliveCamp->is_archive !== $model->is_archive) {
                        $changeData[] =  [
                            'field' => 'camp_archive',
                            'live' => $preliveCamp->is_archive ? "Yes" : "No",
                            'change-in-review' => $model->is_archive ? "Yes" : "No",
                        ];
                    }

                    if ($preliveCamp->is_one_level !== $model->is_one_level) {
                        $changeData[] =  [
                            'field' => 'single_level_sub_camps_only',
                            'live' => $preliveCamp->is_one_level ? "Yes" : "No",
                            'change-in-review' => $model->is_one_level ? "Yes" : "No",
                        ];
                    }

                    if ($preliveCamp->is_disabled !== $model->is_disabled) {
                        $changeData[] =  [
                            'field' => 'disable_additional_sub_camps',
                            'live' => $preliveCamp->is_disabled ? "Yes" : "No",
                            'change-in-review' => $model->is_disabled ? "Yes" : "No",
                        ];
                    }

                    if ($preliveCamp->key_words !== $model->key_words) {
                        $changeData[] =  [
                            'field' => 'keywords',
                            'live' => strlen($preliveCamp->key_words) > 0 ? $preliveCamp->key_words : '-',
                            'change-in-review' => strlen($model->key_words) > 0 ? $model->key_words : '-',
                        ];
                    }

                    if ($preliveCamp->note !== $model->note) {
                        $changeData[] =  [
                            'field' => 'summary',
                            'live' => strlen($preliveCamp->note) > 0 ? $preliveCamp->note : '-',
                            'change-in-review' => strlen($model->note) > 0 ? $model->note : '-',
                        ];
                    }

                    if (($preliveCamp->camp_about_url !== $model->camp_about_url)  && !(empty($preliveCamp->camp_about_url) && empty($model->camp_about_url))) {
                        $changeData[] =  [
                            'field' => 'camp_about_url',
                            'live' => strlen($preliveCamp->camp_about_url) > 0 ? '<a href="' . $preliveCamp->camp_about_url . '" target="_blank">' . $preliveCamp->camp_about_url . '</a>' : '-',
                            'change-in-review' => strlen($model->camp_about_url) > 0 ? '<a href="' . $model->camp_about_url . '" target="_blank">' . $model->camp_about_url . '</a>' : '-',
                        ];
                    }

                    if (($preliveCamp->camp_about_nick_id !== $model->camp_about_nick_id) && !(empty($preliveCamp->camp_about_nick_id) && empty($model->camp_about_nick_id))) {
                        $liveNickname = NickName::getNickName($preliveCamp->camp_about_nick_id);
                        $currentNickname = NickName::getNickName($model->camp_about_nick_id);
                        $liveUrl = Util::linkForEmail(config('global.APP_URL_FRONT_END') . '/user/supports/' . $preliveCamp->camp_about_nick_id . '?topicnum=&campnum=&canon=' . $data['namespace_id']);
                        $currentUrl = Util::linkForEmail(config('global.APP_URL_FRONT_END') . '/user/supports/' . $model->camp_about_nick_id . '?topicnum=&campnum=&canon=' . $data['namespace_id']);
                        $live = '-';
                        $current = '-';
                        if (!empty($liveNickname) && !empty($liveNickname->nick_name)) {
                            $live = '<a href="' . $liveUrl . '" target="_blank">' . $liveNickname->nick_name . '</a>';
                        }

                        if (!empty($currentNickname) && !empty($currentNickname->nick_name)) {
                            $current = '<a href="' . $currentUrl . '" target="_blank">' . $currentNickname->nick_name . '</a>';
                        }

                        $changeData[] =  [
                            'field' => 'camp_about_nick_name',
                            'live' => $live,
                            'change-in-review' => $current,
                        ];
                    }

                    if ($preliveCamp->camp_leader_nick_id !== $model->camp_leader_nick_id) {
                        $changeData[] =  [
                            'field' => 'camp_leader_nick_name',
                            'live' => NickName::getNickName($preliveCamp->camp_leader_nick_id)->nick_name ?? '-',
                            'change-in-review' => NickName::getNickName($model->camp_leader_nick_id)->nick_name ?? '-',
                        ];
                    }

                    if (count($changeData) > 0) {
                        $changeData = [
                            'type' => 'camp',
                            'data' => $changeData,
                        ];
                    }
                }

                if (isset($topic)) {
                    Util::dispatchJob($topic, $model->camp_num, 1);
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

                if (Util::remove_emoji(strtolower(trim($preliveTopic->topic_name))) != Util::remove_emoji(strtolower(trim($model->topic_name)))) {
                    $event_type = "update_topic";
                }

                $pre_LiveId = $preliveTopic->id;

                if (isset($liveTopic)) {
                    Util::dispatchJob($liveTopic, 1, 1);
                }

                $notification_type = config('global.notification_type.topicCommit');
                // GetPushNotificationToSupporter::pushNotificationToSupporter($request->user(), $liveTopic->topic_num, 1, 'topic-commit', null, $nickName->nick_name);
                if ($ifIamSingleSupporter) {
                    if ($id != null) {
                        //timeline start
                        if ($event_type == "update_topic") {

                            $timelineMessage = $nickName->nick_name . " changed topic name from " . $preliveTopic->topic_name . " to " . $liveTopic->topic_name;

                            $timeline_url = Util::getTimelineUrlgetTimelineUrl($liveTopic->topic_num, $liveTopic->topic_name, 1, "Agreement", $liveTopic->topic_name, "update_topic", null, $liveTopic->namespace_id, $liveTopic->submitter_nick_id);

                            Util::dispatchTimelineJob($liveTopic->topic_num, 1, 1, $message = $timelineMessage, "update_topic", 1, null, null, null, time(), $timeline_url);
                        }
                        //end of timeline
                    }
                } else {
                    $changeData = [];

                    if ($preliveTopic->topic_name !== $model->topic_name) {
                        $changeData[] =  [
                            'field' => 'topic_name',
                            'live' => trim($preliveTopic->topic_name),
                            'change-in-review' => trim($model->topic_name),
                        ];
                    }

                    if ($preliveTopic->namespace_id !== $model->namespace_id) {

                        $namespaceData = [
                            'field' => 'topic_canon',
                            'live' => trim($preliveTopic->namespace_id),
                            'change-in-review' => trim($model->namespace_id),
                        ];

                        $namespace = Namespaces::find($namespaceData['live']);
                        if (!empty($namespace)) {
                            $namespaceData['live'] = str_replace(">", " > ", trim(Namespaces::stripAndChangeSlashes(Namespaces::getNamespaceLabel($namespace, $namespace->name))));
                        }

                        $namespace = Namespaces::find($namespaceData['change-in-review']);
                        if (!empty($namespace)) {
                            $namespaceData['change-in-review'] = str_replace(">", " > ", trim(Namespaces::stripAndChangeSlashes(Namespaces::getNamespaceLabel($namespace, $namespace->name))));
                        }

                        $changeData[] = $namespaceData;
                    }

                    if ($preliveCamp->note !== $model->note) {
                        $changeData[] =  [
                            'field' => 'summary',
                            'live' => strlen($preliveCamp->note) > 0 ? $preliveCamp->note : '-',
                            'change-in-review' => strlen($model->note) > 0 ? $model->note : '-',
                        ];
                    }

                    if (count($changeData) > 0) {
                        $changeData = [
                            'type' => 'topic',
                            'data' => $changeData,
                        ];
                    }
                }
            }

            $currentTime = time();
            $delayLiveTimeInSeconds = env('LIVE_TIME_DELAY_IN_SECONDS');
            if ($currentTime < $model->go_live_time && $model->objector_nick_id == null) {
                $additionalInfo = [
                    'model_id' => $model->id,
                    'model_type' => $type,
                    'job_type' => 'live-time-job',
                    'event_type' => $event_type,
                    'pre_LiveId' => $pre_LiveId
                ];

                Util::dispatchJob($liveTopic, $model->camp_num, 1, $delayLiveTimeInSeconds, $additionalInfo);
            }

            if (count($changeData)) {
                $data['change_data'] = $changeData;
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
            // Email In review case
            if ($currentTime < $model->go_live_time && $model->objector_nick_id == null) {
                Event::dispatch(new NotifySupportersEvent($liveCamp, $notificationData, $notification_type, $link, config('global.notify.both')));
            }

            if ($changeGoneLive && $type == 'camp')
            {
                if (!is_null($model->camp_leader_nick_id) && is_null($preliveCamp->camp_leader_nick_id)) {
                    Event::dispatch(new CampLeaderAssignedEvent($model->topic_num, $model->camp_num, $model->camp_leader_nick_id, true));
                }
                if (is_null($model->camp_leader_nick_id) && !is_null($preliveCamp->camp_leader_nick_id)) {
                    Event::dispatch(new CampLeaderRemovedEvent($preliveCamp->topic_num, $preliveCamp->camp_num, $preliveCamp->camp_leader_nick_id, true));
                }

                if (!is_null($preliveCamp->camp_leader_nick_id) && !is_null($model->camp_leader_nick_id) && $preliveCamp->camp_leader_nick_id !== $model->camp_leader_nick_id) {
                    Event::dispatch(new CampLeaderAssignedEvent($model->topic_num, $model->camp_num, $model->camp_leader_nick_id, true));
                    Event::dispatch(new CampLeaderRemovedEvent($preliveCamp->topic_num, $preliveCamp->camp_num, $preliveCamp->camp_leader_nick_id, true));
                }
            }

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
            $responseData = [
                "archive_camp_support_nicknames" => $archiveCampSupportNicknames,
                "change_gone_live" => $changeGoneLive ?? false
            ];
            return $this->resProvider->apiJsonResponse(200, $message, $responseData, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *   path="/agree-to-change",
     *   tags={"Topic"},
     *   summary="Agree to change",
     *   description="Used to agree on a change for camp, topic, and statement.",
     *   operationId="agreeToChange",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="Agree to change request body",
     *       @OA\JsonContent(
     *           required={"record_id", "change_for", "camp_num", "topic_num", "nick_name_id", "user_agreed"},
     *           @OA\Property(
     *               property="record_id",
     *               type="integer",
     *               example=88,
     *               description="Record ID (required)"
     *           ),
     *           @OA\Property(
     *               property="change_for",
     *               type="string",
     *               example="topic",
     *               description="Type of change (topic, camp, statement) (required)"
     *           ),
     *           @OA\Property(
     *               property="camp_num",
     *               type="integer",
     *               example=1,
     *               description="Camp number (required)"
     *           ),
     *           @OA\Property(
     *               property="topic_num",
     *               type="integer",
     *               example=101,
     *               description="Topic number (required)"
     *           ),
     *           @OA\Property(
     *               property="nick_name_id",
     *               type="integer",
     *               example=709,
     *               description="Nick name ID (required)"
     *           ),
     *          @OA\Property(
     *               property="user_agreed",
     *               type="integer",
     *               example=0,
     *               description="User Agreed (required)"
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Agreement recorded successfully")
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad Request - Validation errors or exception occurred",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Invalid parameters")
     *       )
     *   )
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
            'is_submitted' => 1,
            'change_gone_live' => false
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

            if ($data['user_agreed'] == 0) {
                $changeAgreeLog = (new ChangeAgreeLog())->where([
                    'change_id' => $changeId,
                    'camp_num' => $data['camp_num'],
                    'topic_num' => $data['topic_num'],
                    'nick_name_id' => $data['nick_name_id'],
                    'change_for' => $data['change_for'],
                ])->delete();
                if ($changeAgreeLog) {
                    $message = trans('message.success.topic_not_agree');
                } else {
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
                    [$totalSupporters, $totalSupportersCount] = Support::getTotalSupporterByTimestamp('statement', (int)$data['topic_num'], (int)$data['camp_num'], $submitterNickId, $statement->submit_time, ['topicNum' => $data['topic_num'], 'campNum' => $data['camp_num']]);
                    if ($submitterNickId > 0 && !in_array($submitterNickId, $agreed_supporters)) {
                        $agreeCount++;
                    }
                    if ($agreeCount == $totalSupportersCount) {
                        $statement->go_live_time = strtotime(date('Y-m-d H:i:s'));
                        $statement->update();
                        Helpers::updateStatementsInReview($statement);
                        ChangeAgreeLog::where('topic_num', '=', $data['topic_num'])->where('camp_num', '=', $data['camp_num'])->where('change_id', '=', $changeId)->where('change_for', '=', $data['change_for'])->delete();
                        /** Update check to send status of change */
                        $responseData["change_gone_live"] = true;
                    }
                    $message = trans('message.success.statement_agree');
                } else {
                    return $this->resProvider->apiJsonResponse(400, trans('message.error.record_not_found'), '', '');
                }
            } else if ($data['change_for'] == 'camp') {
                $camp = Camp::where('id', $changeId)->first();
                $filter['topicNum'] = $data['topic_num'];
                $filter['campNum'] = $data['camp_num'];
                $preLiveCamp = Camp::getLiveCamp($filter);
                if ($camp) {
                    DB::beginTransaction();
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
                    [$totalSupporters, $totalSupportersCount] = Support::getTotalSupporterByTimestamp('camp', (int)$data['topic_num'], (int)$data['camp_num'], $submitterNickId, $camp->submit_time, ['topicNum' => $data['topic_num'], 'campNum' => $data['camp_num'], 'change_id' => $changeId], false);
                    if ($submitterNickId > 0 && !in_array($submitterNickId, $agreed_supporters)) {
                        $agreeCount++;
                    }

                    /**Un-archive and restoration of archive camp and support #574 */
                    if ($camp->is_archive != $preLiveCamp->is_archive && $camp->is_archive === 0) {
                        $revokableSupporter = Support::getSupportersNickNameOfArchivedCamps($data['topic_num'], [$data['camp_num']], $camp->is_archive);
                        $explicitArchiveSupporters = Support::ifIamArchiveExplicitSupporters($filter, $camp->is_archive, 'supporters');

                        foreach ($revokableSupporter as $k => $rs) {
                            if (array_search($rs->nick_name_id, array_column($totalSupporters, 'id')) !== false) {
                                unset($revokableSupporter[$k]);
                            }
                        }
                        foreach ($explicitArchiveSupporters as $k => $sp) {
                            if (array_search($sp->nick_name_id, array_column($totalSupporters, 'id')) !== false) {
                                unset($explicitArchiveSupporters[$k]);
                            }
                        }
                        $explicitArchiveSupporters = array_unique($explicitArchiveSupporters->pluck(['nick_name_id'])->toArray());
                        $totalSupportersCount = $totalSupportersCount + count($revokableSupporter) + count($explicitArchiveSupporters);
                    }



                    if ($agreeCount == $totalSupportersCount) {
                        $camp->go_live_time = strtotime(date('Y-m-d H:i:s'));
                        if ($camp->is_archive != $preLiveCamp->is_archive) {
                            $camp->archive_action_time = time();
                        }
                        $preliveCamp = Camp::getLiveCamp(['topicNum' => $camp->topic_num, 'asOf' => "", 'campNum' => $camp->camp_num]);

                        $topic = Topic::getLiveTopic($camp->topic_num, "default");

                        // Log of system assigned/remove camp leader
                        if (!is_null($camp->camp_leader_nick_id)) {
                            Camp::dispatchCampLeaderActivityLogJob($topic, $camp, $camp->camp_leader_nick_id, request()->user(), 'assigned');
                            Event::dispatch(new CampLeaderAssignedEvent($camp->topic_num, $camp->camp_num, $camp->camp_leader_nick_id, true));
                        }

                        if (!is_null($preliveCamp->camp_leader_nick_id)) {
                            Camp::dispatchCampLeaderActivityLogJob($topic, $preliveCamp, $preliveCamp->camp_leader_nick_id, request()->user(), 'removed');
                            Event::dispatch(new CampLeaderRemovedEvent($preliveCamp->topic_num, $preliveCamp->camp_num, $preliveCamp->camp_leader_nick_id, true));
                        }

                        $camp->update();
                        Helpers::updateCampsInReview($camp);
                        $liveCamp = Camp::getLiveCamp($filter);
                        Util::checkParentCampChanged($data, true, $liveCamp);
                        ChangeAgreeLog::where('topic_num', '=', $data['topic_num'])->where('camp_num', '=', $data['camp_num'])->where('change_id', '=', $changeId)->where('change_for', '=', $data['change_for'])->delete();
                        $topic = $camp->topic;
                        if (isset($topic)) {
                            Util::dispatchJob($topic, $camp->camp_num, 1);
                        }

                        /** Archive and restoration of archive camp #574 */
                        if ($liveCamp->is_archive != $preLiveCamp->is_archive) {
                            // $camp->archive_action_time = time();
                            // $camp->update();
                            util::updateArchivedCampAndSupport($camp, $liveCamp->is_archive, $preLiveCamp->is_archive);
                        }
                        $nickName = Nickname::getNickName($liveCamp->submitter_nick_id);
                        //timeline start
                        if ($data['parent_camp_num'] != $data['old_parent_camp_num']) {
                            $timelineMessage = $nickName->nick_name . " changed the parent of camp   " . $liveCamp->camp_name;

                            $timeline_url = Util::getTimelineUrlgetTimelineUrl($topic->topic_num, $topic->topic_name, $liveCamp->camp_num, $liveCamp->camp_name, $topic->topic_name, "parent_change", null, $topic->namespace_id, $topic->submitter_nick_id);

                            Util::dispatchTimelineJob($topic->topic_num, $liveCamp->camp_num, 1, $timelineMessage, "parent_change", $liveCamp->id, $data['old_parent_camp_num'], $data['parent_camp_num'], null, time(), $timeline_url);
                        }
                        //end of timeline
                        //timeline start
                        if (Util::remove_emoji(strtolower(trim($preLiveCamp->camp_name))) != Util::remove_emoji(strtolower(trim($liveCamp->camp_name)))) {
                            $timelineMessage = $nickName->nick_name . " changed camp name from " . $preLiveCamp->camp_name . " to " . $liveCamp->camp_name;

                            $timeline_url = Util::getTimelineUrlgetTimelineUrl($topic->topic_num, $topic->topic_name, $liveCamp->camp_num, $liveCamp->camp_name, $topic->topic_name, "update_camp", null, $topic->namespace_id, $topic->submitter_nick_id);

                            Util::dispatchTimelineJob($topic->topic_num, $liveCamp->camp_num, 1, $timelineMessage, "update_camp", $liveCamp->id, null, null, null, time(), $timeline_url);
                        }
                        //end of timeline

                        /** Update check to send status of change */
                        $responseData["change_gone_live"] = true;
                    }
                    DB::commit();
                    $message = trans('message.success.camp_agree');
                } else {
                    DB::rollback();
                    return $this->resProvider->apiJsonResponse(400, trans('message.error.record_not_found'), '', '');
                }
            } else if ($data['change_for'] == 'topic') {
                $topic = Topic::where('id', $changeId)->first();
                $preliveTopic = Topic::getLiveTopic($topic->topic_num, 'default');
                if ($topic) {
                    $submitterNickId = $topic->submitter_nick_id;
                    $nickName = Nickname::getNickName($topic->submitter_nick_id);
                    /*
                    *   https://github.com/the-canonizer/Canonizer-Beta--Issue-Tracking/issues/232
                    *   Now support at the time of submition will be count as total supporter.
                    *   Also check if submitter is not a direct supporter, then it will be count as direct supporter
                    */
                    // $supporters = Support::getAllSupporters($data['topic_num'], $data['camp_num'], $submitterNickId);
                    // $supporters = Support::countSupporterByTimestamp((int)$data['topic_num'], (int)$data['camp_num'], $submitterNickId, $topic->submit_time);
                    [$totalSupporters, $totalSupportersCount] = Support::getTotalSupporterByTimestamp('topic', (int)$data['topic_num'], (int)$data['camp_num'], $submitterNickId, $topic->submit_time, ['topicNum' => $data['topic_num'], 'campNum' => $data['camp_num']]);

                    if ($submitterNickId > 0 && !in_array($submitterNickId, $agreed_supporters)) {
                        $agreeCount++;
                    }

                    if ($agreeCount == $totalSupportersCount) {
                        $topic->go_live_time = strtotime(date('Y-m-d H:i:s'));
                        $topic->update();
                        Helpers::updateTopicsInReview($topic);
                        ChangeAgreeLog::where('topic_num', '=', $data['topic_num'])->where('camp_num', '=', $data['camp_num'])->where('change_id', '=', $changeId)->where('change_for', '=', $data['change_for'])->delete();
                        if (isset($topic)) {
                            Util::dispatchJob($topic, $data['camp_num'], 1);

                            //timeline start
                            if (Util::remove_emoji(strtolower(trim($preliveTopic->topic_name))) != Util::remove_emoji(strtolower(trim($topic->topic_name)))) {

                                $timelineMessage = $nickName->nick_name . " changed topic name from " . $preliveTopic->topic_name . " to " . $topic->topic_name;

                                $timeline_url = Util::getTimelineUrlgetTimelineUrl($topic->topic_num, $topic->topic_name, 1, "Agreement", $topic->topic_name, "update_topic", null, $topic->namespace_id, $topic->submitter_nick_id);

                                Util::dispatchTimelineJob($topic->topic_num, 1, 1, $message = $timelineMessage, "update_topic", 1, null, null, null, time(), $timeline_url);
                            }
                            //end of timeline

                        }
                        /** Update check to send status of change */
                        $responseData["change_gone_live"] = true;
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

    /**
     * @OA\Post(
     *   path="/canonizer/api/agree-to-change",
     *   tags={"Topic"},
     *   summary="Agree to change for live job",
     *   description="Used to agree on a change for camp, topic, and statement from a live job.",
     *   operationId="agreeToChangeForLiveJob",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="Agree to change request body",
     *       @OA\JsonContent(
     *           required={"record_id", "change_for", "camp_num", "topic_num"},
     *           @OA\Property(
     *               property="record_id",
     *               type="integer",
     *               example=88,
     *               description="Record ID (required)"
     *           ),
     *           @OA\Property(
     *               property="change_for",
     *               type="string",
     *               example="topic",
     *               description="Type of change (topic, camp, statement) (required)"
     *           ),
     *           @OA\Property(
     *               property="camp_num",
     *               type="integer",
     *               example=1,
     *               description="Camp number (required)"
     *           ),
     *           @OA\Property(
     *               property="topic_num",
     *               type="integer",
     *               example=101,
     *               description="Topic number (required)"
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Agreement recorded successfully")
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad Request - Validation errors or exception occurred",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Invalid parameters")
     *       )
     *   )
     * )
     */

    public function agreeToChangeForLiveJob(Request $request, Validate $validate)
    {

        $validationErrors = $validate->validate($request, $this->rules->getAgreeToChangeForLiveJobValidationRules(), $this->validationMessages->getAgreeToChangeForLiveJobValidationMessages());
        $iscalledfromService = $request->called_from_service ?? false;

        if (!$iscalledfromService && $validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        if ($iscalledfromService && $request->header('Authorization') != 'Bearer: ' . env('API_TOKEN')) {
            return $this->resProvider->apiJsonResponse(401, 'Unauthorized', '', '');
        }

        $data = $request->all();
        $message = "";
        $pre_LiveId = $data['pre_LiveId'];
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
                    $model = Statement::where($where)->where('is_draft', 0)->first();
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

            if ($data['change_for'] == 'statement') {
                $statement = Statement::where('id', $changeId)->first();
                if ($statement) {
                    Helpers::updateStatementsInReview($statement);
                    ChangeAgreeLog::where('topic_num', '=', $data['topic_num'])->where('camp_num', '=', $data['camp_num'])->where('change_id', '=', $changeId)->where('change_for', '=', $data['change_for'])->delete();
                    $message = trans('message.success.statement_agree');
                } else {
                    return $this->resProvider->apiJsonResponse(400, trans('message.error.record_not_found'), '', '');
                }
            } else if ($data['change_for'] == 'camp') {
                $camp = Camp::where('id', $changeId)->first();
                $filter['topicNum'] = $data['topic_num'];
                $filter['campNum'] = $data['camp_num'];
                $preLiveCamp = Camp::where('id', $pre_LiveId)->first();
                if ($camp) {
                    DB::beginTransaction();
                    $data['parent_camp_num'] = $camp->parent_camp_num;
                    $data['old_parent_camp_num'] = $camp->old_parent_camp_num;
                    // Util::checkParentCampChanged($data, true, $liveCamp);
                    //$submitterNickId = $camp->submitter_nick_id;
                    $camp->go_live_time = strtotime(date('Y-m-d H:i:s'));
                    if ($camp->is_archive != $preLiveCamp->is_archive) {
                        $camp->archive_action_time = time();
                    }

                    $camp->update();
                    Helpers::updateCampsInReview($camp);
                    $liveCamp = Camp::getLiveCamp($filter);
                    Util::checkParentCampChanged($data, true, $liveCamp);
                    ChangeAgreeLog::where('topic_num', '=', $data['topic_num'])->where('camp_num', '=', $data['camp_num'])->where('change_id', '=', $changeId)->where('change_for', '=', $data['change_for'])->delete();
                    $topic = $camp->topic;

                    /** Archive and restoration of archive camp #574 */
                    if ($liveCamp->is_archive != $preLiveCamp->is_archive) {
                        util::updateArchivedCampAndSupport($camp, $liveCamp->is_archive, $preLiveCamp->is_archive);
                    }
                    $nickName = Nickname::getNickName($liveCamp->submitter_nick_id);

                    DB::commit();
                    Util::dispatchJob($topic, $camp->camp_num, 1);

                    //timeline start
                    if ($data['event_type'] == "parent_change") {
                        $timelineMessage = $nickName->nick_name . " changed the parent of camp   " . $liveCamp->camp_name;

                        $timeline_url = Util::getTimelineUrlgetTimelineUrl($topic->topic_num, $topic->topic_name, $liveCamp->camp_num, $liveCamp->camp_name, $topic->topic_name, "parent_change", null, $topic->namespace_id, $topic->submitter_nick_id);

                        Util::dispatchTimelineJob($topic->topic_num, $liveCamp->camp_num, 1, $timelineMessage, "parent_change", $liveCamp->id, $data['old_parent_camp_num'], $data['parent_camp_num'], null, time(), $timeline_url);
                    }
                    //end of timeline
                    //timeline start
                    if ($data['event_type'] == "update_camp") {
                        $timelineMessage = $nickName->nick_name . " changed camp name from " . $preLiveCamp->camp_name . " to " . $liveCamp->camp_name;

                        $timeline_url = Util::getTimelineUrlgetTimelineUrl($topic->topic_num, $topic->topic_name, $liveCamp->camp_num, $liveCamp->camp_name, $topic->topic_name, "update_camp", null, $topic->namespace_id, $topic->submitter_nick_id);

                        Util::dispatchTimelineJob($topic->topic_num, $liveCamp->camp_num, 1, $timelineMessage, "update_camp", $liveCamp->id, null, null, null, time(), $timeline_url);
                    }
                    //end of timeline
                    $message = trans('message.success.camp_agree');
                } else {
                    DB::rollback();
                    return $this->resProvider->apiJsonResponse(400, trans('message.error.record_not_found'), '', '');
                }
            } else if ($data['change_for'] == 'topic') {

                $topic = Topic::find($changeId);
                $preliveTopic = Topic::where('id', $pre_LiveId)->first();
                if ($topic) {
                    //$submitterNickId = $topic->submitter_nick_id;
                    $nickName = Nickname::getNickName($topic->submitter_nick_id);
                   // $topic->go_live_time = strtotime(date('Y-m-d H:i:s'));
                   // $topic->save();
                    $esBool = Topic::updateElasticSearch($topic);
                    /**
                     * Below code is commented due to following tickets:
                     * https://github.com/the-canonizer/Canonizer-Beta--Issue-Tracking/issues/1668
                     * https://github.com/the-canonizer/Canonizer-Beta--Issue-Tracking/issues/1609
                     */
                    // if($esBool){
                    //     $activityMsg = 'Elastic search updated for topic update / rename topic name is performed.';
                    // }else{
                    //     $activityMsg = 'Elastic search updated for topic update / rename topic name is not performed';
                    // }

                    // $activityLogData = [
                    //     'log_type' =>  "topic/camps",
                    //     'activity' => $activityMsg,
                    //     'url' => '',
                    //     'model' => $topic,
                    //     'topic_num' => $topic->topic_num,
                    //     'camp_num' =>  1,
                    //     'user' => $request->user(),
                    //     'nick_name' => '',
                    //     'description' => $topic->topic_name
                    // ];
                    // try {
                    //     dispatch(new ActivityLoggerJob($activityLogData))->onQueue(env('ACTIVITY_LOG_QUEUE'));
                    //  } catch (Exception $e) {
                    //     return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
                    // }

                    Helpers::updateTopicsInReview($topic);
                    ChangeAgreeLog::where('topic_num', '=', $data['topic_num'])->where('camp_num', '=', $data['camp_num'])->where('change_id', '=', $changeId)->where('change_for', '=', $data['change_for'])->delete();
                    if (isset($topic)) {
                        //Util::dispatchJob($topic, $data['camp_num'], 1);

                        //timeline start
                        if ($data['event_type'] == "update_topic") {
                            $timelineMessage = $nickName->nick_name . " changed topic name from " . $preliveTopic->topic_name . " to " . $topic->topic_name;
                            $timeline_url = Util::getTimelineUrlgetTimelineUrl($topic->topic_num, $topic->topic_name, 1, "Agreement", $topic->topic_name, "update_topic", null, $topic->namespace_id, $topic->submitter_nick_id);
                            Util::dispatchTimelineJob($topic->topic_num, 1, 1, $message = $timelineMessage, "update_topic", 1, null, null, null, time(), $timeline_url);
                        }
                        //end of timeline
                    }

                    $message = trans('message.success.topic_agree');
                }
            } else {
                return $this->resProvider->apiJsonResponse(400, trans('message.error.record_not_found'), '', '');
            }
            return $this->resProvider->apiJsonResponse(200, $message, $responseData, '');
        } catch (Exception $e) {
            Log::info("agreeToChangeForLiveJob error" . $e->getMessage());
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *   path="/manage-topic",
     *   tags={"Topic"},
     *   summary="Edit, update, or object to a topic record",
     *   description="This API is used to edit, update, or object to a topic record.",
     *   operationId="manageTopicHistory",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="Request body parameters for managing a topic",
     *       @OA\JsonContent(
     *           required={"topic_num", "topic_id", "nick_name", "submitter", "namespace_id", "event_type"},
     *           @OA\Property(
     *               property="topic_name",
     *               type="string",
     *               example="hello sandbox topic",
     *               description="The name of the topic"
     *           ),
     *           @OA\Property(
     *               property="namespace",
     *               type="integer",
     *               example=1,
     *               description="Namespace ID"
     *           ),
     *           @OA\Property(
     *               property="topic_num",
     *               type="integer",
     *               example=6475,
     *               description="Topic number"
     *           ),
     *           @OA\Property(
     *               property="topic_id",
     *               type="integer",
     *               example=8225,
     *               description="Unique topic ID"
     *           ),
     *           @OA\Property(
     *               property="nick_name",
     *               type="integer",
     *               example=709,
     *               description="Nick name of the user"
     *           ),
     *           @OA\Property(
     *               property="submitter",
     *               type="integer",
     *               example=709,
     *               description="Nick name ID of the user who previously added the statement"
     *           ),
     *           @OA\Property(
     *               property="namespace_id",
     *               type="integer",
     *               example=1,
     *               description="Topic namespace ID"
     *           ),
     *           @OA\Property(
     *               property="event_type",
     *               type="string",
     *               example="update",
     *               description="Possible values: objection, edit, update"
     *           ),
     *           @OA\Property(
     *               property="tags",
     *               type="array",
     *               @OA\Items(type="integer", example=6),
     *               description="Array of tag IDs associated with the topic"
     *           ),
     *           @OA\Property(
     *               property="note",
     *               type="string",
     *               nullable=true,
     *               example=null,
     *               description="Optional note for the topic"
     *           ),
     *           @OA\Property(
     *               property="is_rank_hidden",
     *               type="boolean",
     *               example=false,
     *               description="Boolean flag indicating if rank is hidden"
     *           ),
     *           @OA\Property(
     *               property="objection_reason",
     *               type="string",
     *               nullable=true,
     *               example="I disagree with the statement.",
     *               description="Objection reason if the user is objecting to a statement (optional)"
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Successful operation",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Success"),
     *           @OA\Property(property="error", type="string", nullable=true),
     *           @OA\Property(
     *               property="data",
     *               type="object",
     *               @OA\Property(property="topic_num", type="integer", example=6475)
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad Request - Validation errors or exception occurred",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Invalid parameters")
     *       )
     *   )
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
        $all['camp_num'] = $all['camp_num'] ?? 1;
        try {
            $nickNameIds = Nickname::getNicknamesIdsByUserId($request->user()->id);
            $nickNames = Nickname::personNicknameArray();
            if (!in_array($request->nick_name, $nickNameIds)) {
                return $this->resProvider->apiJsonResponse(400, trans('message.general.nickname_association_absence'), '', '');
            }
            if (!empty(Topic::ifTopicNameAlreadyTaken($all))) {
                return $this->resProvider->apiJsonResponse(400, trans('message.error.topic_name_alreday_exist'), '', Topic::ifTopicNameAlreadyTaken($all));
            }
            DB::beginTransaction();
            if ($all['event_type'] == "objection") {
                // $checkUserDirectSupportExists = Support::checkIfSupportExists($all['topic_num'], $nickNames);
                $topic = Topic::where('id', $all['topic_id'])->first();

                // Check if the change is already objected , then we can't object again
                if (!empty($topic->objector_nick_id)) {
                    return $this->resProvider->apiJsonResponse(400, trans('message.support.can_not_object'), '', '');
                }

                $filters = [
                    'topicNum' => $all['topic_num'],
                    'campNum' => $all['camp_num'],
                ];
                $checkUserDirectSupportExists = Support::ifIamSupporterForChange($filters['topicNum'], $filters['campNum'], $nickNames, $topic->submit_time);
                $checkIfIAmExplicitSupporter = Support::ifIamExplicitSupporterBySubmitTime($filters, $nickNames, $topic->submit_time, 'topic', false, 'ifIamExplicitSupporter');

                if ($checkUserDirectSupportExists < 1 && !$checkIfIAmExplicitSupporter) {
                    $message = trans('message.support.not_authorized_for_objection_topic');
                    return $this->resProvider->apiJsonResponse(400, $message, '', '');
                }
                $topic = Topic::where('id', $all['topic_id'])->first();
                $topic->objector_nick_id = $all['nick_name'];
                $topic->object_reason = $all['objection_reason'];
                $topic->object_time = $current_time;
                $topic->is_disabled =  !empty($request->is_disabled) ? $request->is_disabled : 0;
                $topic->is_one_level =  !empty($request->is_one_level) ? $request->is_one_level : 0;
                $topic->is_rank_hidden =  !empty($request->is_rank_hidden) ? $request->is_rank_hidden : 0;
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
                $topic->is_rank_hidden =  !empty($request->is_rank_hidden) ? $request->is_rank_hidden : 0;
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
                $topic->go_live_time = Carbon::parse('@' . $current_time)->addDay()->timestamp;
                $topic->language = 'English';
                $topic->note = isset($all['note']) ? $all['note'] : "";
                // $topic->grace_period = 0;
                $topic->grace_period = 1;

                $topic->is_disabled =  !empty($request->is_disabled) ? $request->is_disabled : 0;
                $topic->is_one_level =  !empty($request->is_one_level) ? $request->is_one_level : 0;
                $topic->is_rank_hidden =  !empty($request->is_rank_hidden) ? $request->is_rank_hidden : 0;
                $message = trans('message.success.topic_update');
            }

            $ifIamSingleSupporter = Support::ifIamSingleSupporter($all['topic_num'], $nickNames, 0);

            if (!$ifIamSingleSupporter) {
                $topic->go_live_time = Carbon::now()->addDay()->timestamp;
                $topic->grace_period = 1;
            }

            if ($all['event_type'] == "objection") {
                $topic->grace_period = 0;
            }

            $topic->save();

            // Check if the array exists for tags ...
            if ($request->has('tags') && is_array($request->tags)) {
                $topic->tags()->syncWithPivotValues($request->tags, ['topic_num' => $topic->topic_num]);
            }

            DB::commit();

            if ($all['event_type'] == "objection") {
                $this->objectedTopicNotification($all, $topic, $request);
            } else if ($all['event_type'] == "update") {

                Util::dispatchJob($topic, 1, 1);
                $currentTime = time();
                $delayCommitTimeInSeconds = (int)env('COMMIT_TIME_DELAY_IN_SECONDS');
                if (($currentTime < $topic->go_live_time && $currentTime >= $topic->submit_time) && $topic->grace_period && $topic->objector_nick_id == null) {
                    Util::dispatchJob($topic, 1, 1, $delayCommitTimeInSeconds);
                }
            }

            return $this->resProvider->apiJsonResponse(200, $message, '', '');
        } catch (Exception $e) {
            DB::rollback();
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage() . '' . $e->getLine());
        }
    }

    /**
     * @OA\Post(
     *   path="/discard/change",
     *   tags={"Topic"},
     *   summary="Discard a change",
     *   description="Used to discard a change for camp, topic, and statement.",
     *   operationId="discardChange",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="Discard change",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               required={"id", "type"}, 
     *               @OA\Property(
     *                   property="id",
     *                   description="Record ID",
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="type",
     *                   description="Type (topic, camp, statement)",
     *                   type="string",
     *               )
     *           )
     *       )
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
                if ($model instanceof Topic) {
                    $model->tags()->detach();
                }
                $model->delete();
            } else {
                throw new Exception('The Change is already submitted. You cannot discard it.');
            }

            return $this->resProvider->apiJsonResponse(200, $message, '', '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *   path="/hot-topic",
     *   tags={"Topic"},
     *   summary="Get Hot Topics",
     *   description="Fetches the most viewed topics within the last 30 days, excluding sandbox topics.",
     *   operationId="hotTopic",
     *   security={{"clientAuth":{}}},
     *   @OA\Parameter(
     *       name="per_page",
     *       in="query",
     *       description="Number of records per page",
     *       required=false,
     *       @OA\Schema(type="integer", example=10)
     *   ),
     *   @OA\Parameter(
     *       name="supporter_limit",
     *       in="query",
     *       description="Limit the number of supporters shown per topic",
     *       required=false,
     *       @OA\Schema(type="integer", example=5)
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Successful retrieval of hot topics",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Success"),
     *           @OA\Property(
     *               property="data",
     *               type="object",
     *               @OA\Property(
     *                   property="topics",
     *                   type="array",
     *                   @OA\Items(
     *                       type="object",
     *                       @OA\Property(property="id", type="integer", example=1),
     *                       @OA\Property(property="topic_num", type="integer", example=116),
     *                       @OA\Property(property="camp_num", type="integer", example=1),
     *                       @OA\Property(property="topic_name", type="string", example="Climate Change"),
     *                       @OA\Property(property="camp_name", type="string", example="Scientific Consensus"),
     *                       @OA\Property(property="views", type="integer", example=250),
     *                       @OA\Property(
     *                           property="supporterData",
     *                           type="array",
     *                           @OA\Items(
     *                               type="object",
     *                               @OA\Property(property="first_name", type="string", example="J"),
     *                               @OA\Property(property="middle_name", type="string", example="K"),
     *                               @OA\Property(property="last_name", type="string", example="Doe")
     *                           )
     *                       ),
     *                       @OA\Property(property="total_supporters_count", type="integer", example=15),
     *                       @OA\Property(property="statement", type="string", example="Climate change is real and caused by human activities."),
     *                       @OA\Property(
     *                           property="topicTags",
     *                           type="array",
     *                           @OA\Items(
     *                               type="string",
     *                               example="Environment"
     *                           )
     *                       )
     *                   )
     *               )
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad Request - Invalid parameters or exception",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Exception occurred"),
     *           @OA\Property(property="error", type="string", example="SQL error")
     *       )
     *   )
     * )
     */

   
    public function hotTopic(Request $request)
    {
        try {
            $perPage = $request->input('per_page', config('global.per_page'));
            $supporterLimit = $request->input('supporter_limit', 5);
            $page = $request->input('page', 1);
            $cacheKey = "hot_topics_{$perPage}_{$supporterLimit}_{$page}";

            $collection = Cache::remember($cacheKey, 600, function () use ($request, $perPage, $supporterLimit) {
                $namespaceIds = Namespaces::where('name', 'like', "%sandbox%")->pluck('id')->toArray();
                $date30DaysAgo = Carbon::now()->subDays(30)->startOfDay()->timestamp;

                $topics = Topic::join('topic_views', function ($join) use ($date30DaysAgo) {
                    $join->on('topic.topic_num', '=', 'topic_views.topic_num')
                        ->where('topic_views.updated_at', '>=', $date30DaysAgo);
                })
                    ->whereNotIn('namespace_id', $namespaceIds)
                    ->select('topic.*', DB::raw('SUM(topic_views.views) as total_views')) // Summing views directly in the query
                    ->groupBy('topic.topic_num') // Group by topic number
                    ->orderByDesc('total_views') // Order by the calculated total_views column
                    ->whereRaw('topic.go_live_time in (select max(topic.go_live_time) from topic where topic.topic_num=topic.topic_num and topic.objector_nick_id is null and topic.go_live_time <=' . time() . ' group by topic.topic_num)')
                    ->paginate($perPage);

                foreach ($topics as $topic) {
                    $filter['topicNum'] = $topic->topic_num;
                    $filter['campNum'] = $topic->camp_num ?? 1;

                    $liveCamp = Camp::getLiveCamp($filter);
                    $liveTopic = Topic::getLiveTopic($topic->topic_num, ['nofilter' => true]);

                    $topicTitle = $liveTopic->topic_name ?? '';
                    $campTitle = $liveCamp->camp_name ?? '';

                    $supporterData = Support::getAllSupporterNicknames($liveTopic->topic_num, null, $supporterLimit)->each(function ($supporter) {
                        $supporter->first_name = $supporter->first_name[0] ?? '';
                        $supporter->middle_name = $supporter->middle_name[0] ?? '';
                        $supporter->last_name = $supporter->last_name[0] ?? '';
                    });

                    // Get the tag IDs associated with $liveTopic
                    $topic->id = $liveTopic->id;
                    $topic->topic_num = $liveTopic->topic_num;
                    $topic->camp_num = $liveCamp->camp_num;
                    $topic->note = $liveTopic->note;
                    $topic->topic_name = $topicTitle;
                    $topic->camp_name = $campTitle;
                    $topic->namespace = $liveTopic->nameSpace->label ?? 1;
                    $topic->topicTags = $liveTopic->tags->makeHidden(['pivot']);
                    $topic->views = $topic->totalViews();
                    $topic->supporterData = $supporterData;
                    $topic->total_supporters_count = count($supporterData) < 5 ? 0 : count(Support::getAllSupporterOfTopic($liveTopic->topic_num)) - 5;
                    $getLiveStatement = Statement::getLiveStatement([
                        'topicNum' => $topic->topic_num,
                        'campNum' => $liveCamp->camp_num,
                        'asOf' => 'default',
                        'asOfDate' => '',
                    ]);
                    $getLiveStatement = Helpers::stripTagsExcept($getLiveStatement->parsed_value ?? null);
                    $topic->statement = Str::of($getLiveStatement)->trim();
                }

                return Util::getPaginatorResponse($topics);
            });

            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $collection, null);
        } catch (Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => trans('message.error.exception'),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @OA\Get(
     *   path="/featured-topic",
     *   tags={"Topic"},
     *   summary="Get Featured Topics",
     *   description="Fetches a list of featured topics that are marked as active.",
     *   operationId="featuredTopic",
     *   security={{"clientAuth":{}}},
     *   @OA\Parameter(
     *       name="per_page",
     *       in="query",
     *       description="Number of records per page",
     *       required=false,
     *       @OA\Schema(type="integer", example=10)
     *   ),
     *   @OA\Parameter(
     *       name="supporter_limit",
     *       in="query",
     *       description="Limit the number of supporters shown per topic",
     *       required=false,
     *       @OA\Schema(type="integer", example=5)
     *   ),
     *   @OA\Parameter(
     *       name="sort_by",
     *       in="query",
     *       description="Sorting order (ASC or DESC)",
     *       required=false,
     *       @OA\Schema(type="string", example="DESC")
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Successful retrieval of featured topics",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Success"),
     *           @OA\Property(
     *               property="data",
     *               type="object",
     *               @OA\Property(
     *                   property="topics",
     *                   type="array",
     *                   @OA\Items(
     *                       type="object",
     *                       @OA\Property(property="topic_num", type="integer", example=101),
     *                       @OA\Property(property="camp_num", type="integer", example=1),
     *                       @OA\Property(property="topic_name", type="string", example="Artificial Intelligence"),
     *                       @OA\Property(property="camp_name", type="string", example="Future of AI"),
     *                       @OA\Property(property="namespace", type="string", example="General"),
     *                       @OA\Property(
     *                           property="topicTags",
     *                           type="array",
     *                           @OA\Items(
     *                               type="string",
     *                               example="Technology"
     *                           )
     *                       ),
     *                       @OA\Property(property="views", type="integer", example=120),
     *                       @OA\Property(
     *                           property="supporterData",
     *                           type="array",
     *                           @OA\Items(
     *                               type="object",
     *                               @OA\Property(property="first_name", type="string", example="J"),
     *                               @OA\Property(property="middle_name", type="string", example="K"),
     *                               @OA\Property(property="last_name", type="string", example="Doe")
     *                           )
     *                       ),
     *                       @OA\Property(property="total_supporters_count", type="integer", example=15)
     *                   )
     *               )
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad Request - Invalid parameters or exception",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Exception occurred"),
     *           @OA\Property(property="error", type="string", example="SQL error")
     *       )
     *   )
     * )
     */

    public function featuredTopic(Request $request)
    {
        try {
            $perPage = $request->per_page ?? config('global.per_page');
            $supporterLimit = $request->supporter_limit ?? 5;
            $sortBy = $request->input('sort_by', 'DESC');
            $page = $request->input('page', 1);
            $cacheKey = "featured_topics_{$perPage}_{$supporterLimit}_{$sortBy}_{$page}";

            $collection = Cache::remember($cacheKey, 600, function () use ($request, $perPage, $supporterLimit, $sortBy) {
                $hotTopics = FeatureTopic::where('active', '1')->orderBy('id', 'DESC')->orderBy('id', $sortBy)
                    ->paginate($perPage);
                if (!empty($hotTopics)) {
                    foreach ($hotTopics as $hotTopic) {
                        $filter['topicNum'] = $hotTopic->topic_num;
                        $filter['campNum'] = $hotTopic->camp_num ?? 1;
                        $liveCamp = Camp::getLiveCamp($filter);
                        $liveTopic = Topic::getLiveTopic($hotTopic->topic_num, ['nofilter' => true]);
                        if (!empty($liveTopic)) {
                            $topicTitle = $liveTopic->topic_name;
                        }
                        if (!empty($liveCamp)) {
                            $campTitle = $liveCamp->camp_name;
                        }

                        $supporterData = Support::getAllSupporterNicknames($liveTopic->topic_num, null, $supporterLimit)->each(function ($supporter) {
                            $supporter->first_name = $supporter->first_name[0] ?? '';
                            $supporter->middle_name = $supporter->middle_name[0] ?? '';
                            $supporter->last_name = $supporter->last_name[0] ?? '';
                        });

                        // Get the tag IDs associated with $liveTopic
                        $hotTopic->topic_name = $topicTitle ?? "";
                        $hotTopic->camp_name = $campTitle ?? "";
                        $hotTopic->topic_num = $liveTopic->topic_num;
                        $hotTopic->camp_num = $liveTopic->camp_num ?? 1;
                        $hotTopic->namespace = $liveTopic->nameSpace->label ?? 1;
                        $hotTopic->topicTags = $liveTopic->tags->makeHidden(['pivot']);
                        $hotTopic->namespace_id = $liveTopic->namespace_id;
                        $hotTopic->views = Helpers::getCampViewsByDate($hotTopic->topic_num, $hotTopic->camp_num) ??  0;
                        $hotTopic->supporterData = $supporterData;
                        $hotTopic->total_supporters_count = count($supporterData) < 5 ? 0 : count(Support::getAllSupporterOfTopic($liveTopic->topic_num)) - 5;
                    }
                }
                return Util::getPaginatorResponse($hotTopics);
            });

            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $collection, null);
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
    
    /**
     * @OA\Get(
     *   path="/preferred-topic",
     *   tags={"Topic"},
     *   summary="Get Preferred Topics",
     *   description="Fetches a list of topics based on user preferences, including tags associated with the user.",
     *   operationId="preferredTopic",
     *   security={{"clientAuth":{}}},
     *   @OA\Parameter(
     *       name="is_random",
     *       in="query",
     *       description="Whether to fetch topics in random order",
     *       required=false,
     *       @OA\Schema(type="boolean", example=true)
     *   ),
     *   @OA\Parameter(
     *       name="per_page",
     *       in="query",
     *       description="Number of records per page",
     *       required=false,
     *       @OA\Schema(type="integer", example=10)
     *   ),
     *   @OA\Parameter(
     *       name="supporter_limit",
     *       in="query",
     *       description="Limit the number of supporters shown per topic",
     *       required=false,
     *       @OA\Schema(type="integer", example=5)
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Successful retrieval of preferred topics",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Success"),
     *           @OA\Property(
     *               property="data",
     *               type="object",
     *               @OA\Property(
     *                   property="topics",
     *                   type="array",
     *                   @OA\Items(
     *                       type="object",
     *                       @OA\Property(property="id", type="integer", example=101),
     *                       @OA\Property(property="topic_num", type="integer", example=202),
     *                       @OA\Property(property="camp_num", type="integer", example=1),
     *                       @OA\Property(property="note", type="string", example="Important topic"),
     *                       @OA\Property(property="topic_name", type="string", example="Artificial Intelligence"),
     *                       @OA\Property(property="camp_name", type="string", example="Future of AI"),
     *                       @OA\Property(property="namespace", type="string", example="General"),
     *                       @OA\Property(
     *                           property="tags",
     *                           type="array",
     *                           @OA\Items(
     *                               type="string",
     *                               example="Technology"
     *                           )
     *                       ),
     *                       @OA\Property(property="views", type="integer", example=150),
     *                       @OA\Property(
     *                           property="supporterData",
     *                           type="array",
     *                           @OA\Items(
     *                               type="object",
     *                               @OA\Property(property="first_name", type="string", example="J"),
     *                               @OA\Property(property="middle_name", type="string", example="K"),
     *                               @OA\Property(property="last_name", type="string", example="Doe")
     *                           )
     *                       ),
     *                       @OA\Property(property="total_supporters_count", type="integer", example=10),
     *                       @OA\Property(property="statement", type="string", example="AI will revolutionize the world.")
     *                   )
     *               )
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad Request - Invalid parameters or exception",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Exception occurred"),
     *           @OA\Property(property="error", type="string", example="SQL error")
     *       )
     *   )
     * )
     */

    public function preferredTopic(Request $request)
    {
        try {
            $isRandom = $request->is_random ?? false;
            $perPage = $request->per_page ?? config('global.per_page');
            $userTags = $request->user()->userActiveTags()->pluck('tag_id');
            $namespaceIds = Namespaces::where('name', 'like', "%sandbox%")->pluck('id')->toArray();
            $topics = Topic::with('tags')->whereHas('tags', function ($query) use ($userTags) {
                    $query->whereIn('tag_id', $userTags);
                })
                ->whereNotIn('namespace_id', $namespaceIds)
                ->whereRaw('topic.go_live_time in (select max(topic.go_live_time) from topic where topic.topic_num=topic.topic_num and topic.objector_nick_id is null and topic.go_live_time <= ' . time() . ' group by topic.topic_num)')
                ->orderBy('submit_time', 'DESC');
            if ($isRandom) {
                $topics = $topics->inRandomOrder()->paginate($perPage);
            } else {
                $topics = $topics->paginate($perPage);
            }
            $paginatedResponse = Util::getPaginatorResponse($topics);
            $topics = $topics->map(function ($topic) {
                $filter['topicNum'] = $topic->topic_num;
                $filter['campNum'] = $topic->camp_num ?? 1;
                $liveCamp = Camp::getLiveCamp($filter);
                $liveTopic = Topic::getLiveTopic($topic->topic_num, ['nofilter' => true]);
                $topicTitle = $liveTopic->topic_name ?? '';
                $campTitle = $liveCamp->camp_name ?? '';
                $supporterLimit = $request->supporter_limit ?? 5;

                $supporterData = Support::getAllSupporterNicknames($liveTopic->topic_num, null, $supporterLimit)->each(function ($supporter) {
                    $supporter->first_name = $supporter->first_name[0] ?? '';
                    $supporter->middle_name = $supporter->middle_name[0] ?? '';
                    $supporter->last_name = $supporter->last_name[0] ?? '';
                });

                $getLiveStatement = Statement::getLiveStatement([
                    'topicNum' => $topic->topic_num,
                    'campNum' => $liveCamp->camp_num,
                    'asOf' => 'default',
                    'asOfDate' => '',
                ]);
                $getLiveStatement = Helpers::stripTagsExcept($getLiveStatement->parsed_value ?? null);
                $getLiveStatement = Str::of($getLiveStatement)->trim();
                // Get the tag IDs associated with $liveTopic

                return [
                    'id' => $liveTopic->id,
                    'topic_num' => $liveTopic->topic_num,
                    'camp_num' => $liveCamp->camp_num,
                    'note' => $liveTopic->note,
                    'topic_name' => $topicTitle,
                    'camp_name' => $campTitle,
                    'namespace' => $liveTopic->nameSpace->label ?? 1,
                    'tags' => $topic->tags->makeHidden(['parent_id', 'is_active', 'pivot']),
                    'views' => $liveTopic->totalViews(),
                    'supporterData' => $supporterData,
                    'total_supporters_count' => count($supporterData) < 5 ? 0 : count(Support::getAllSupporterOfTopic($liveTopic->topic_num)) - 5,
                    'statement' => $getLiveStatement,
                ];
            });
            $paginatedResponse->items = $topics;

            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $paginatedResponse, null);
        } catch (Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => trans('message.error.exception'),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @OA\Post(
     *   path="/get-topic-history",
     *   tags={"Topic"},
     *   summary="Get topic history",
     *   description="Fetches the history of a topic based on filters such as event type and pagination.",
     *   operationId="getTopicHistory",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="Payload to retrieve topic history",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               type="object",
     *               required={"topic_num", "per_page", "event_type", "page"},
     *               @OA\Property(
     *                   property="topic_num",
     *                   description="Topic number",
     *                   type="integer",
     *                   example=116
     *               ),
     *               @OA\Property(
     *                   property="per_page",
     *                   description="Number of records per page",
     *                   type="integer",
     *                   example=10
     *               ),
     *               @OA\Property(
     *                   property="event_type",
     *                   description="Filter by event type. Possible values: objected, live, in_review, old, all",
     *                   type="string",
     *                   enum={"objected", "live", "in_review", "old", "all"},
     *                   example="live"
     *               ),
     *               @OA\Property(
     *                   property="page",
     *                   description="Page number for pagination",
     *                   type="integer",
     *                   example=1
     *               )
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Successful retrieval of topic history",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Success"),
     *           @OA\Property(
     *               property="data",
     *               type="array",
     *               @OA\Items(
     *                   type="object",
     *                   @OA\Property(property="id", type="integer", example=1),
     *                   @OA\Property(property="topic_num", type="integer", example=116),
     *                   @OA\Property(property="event_type", type="string", example="live"),
     *                   @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-04T12:34:56Z")
     *               )
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad Request - Invalid parameters",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Invalid parameters")
     *       )
     *   )
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

        $topics = Topic::where([
            'topic_num' => $filter['topicNum'],
        ])->get();

        if ($topics->count() < 1)
            return $this->resProvider->apiJsonResponse(404, '', null, trans('message.error.record_not_found'));

        try {
            $topicHistoryQuery = Topic::where('topic_num', $filter['topicNum'])->latest('submit_time');
            $liveTopic = Topic::getLiveTopic($filter['topicNum'], 'default');
            $topics = Topic::getTopicHistory($filter, $request, $topicHistoryQuery, $liveTopic);
            $response = $topics;
            $details->ifIamSupporter = null;
            $details->ifSupportDelayed = null;
            $details->ifIAmExplicitSupporter = null;
            $details->liveCamp = Camp::getLiveCamp($filter);
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
            $response->total_counts = Helpers::getHistoryCountsByChange($liveTopic, $filter);
            $response->live_record_id = Helpers::getLiveHistoryRecord($liveTopic, $filter);

            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $response, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
 
    /**
      * @OA\Post(
      *   path="/edit-topic",
      *   tags={"Topic"},
      *   summary="Get topic record for edit",
      *   description="Get topic details for editing",
      *   operationId="editTopicRecord",
      *   security={{"clientAuth":{}}},
      *   @OA\RequestBody(
      *       required=true,
      *       description="Edit topic",
      *       @OA\MediaType(
      *           mediaType="application/x-www-form-urlencoded",
      *           @OA\Schema(
      *               required={"record_id", "event_type"},
      *               @OA\Property(
      *                   property="record_id",
      *                   description="Record ID",
      *                   type="integer",
      *                   
      *               ),
      *               @OA\Property(
      *                   property="event_type",
      *                   description="Possible value is edit",
      *                   type="string",
      *               )
      *           )
      *       )
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
            $topic = Topic::find($request->record_id);
            $topic->tags = TopicTag::select('tag_id')->where('topic_id', $topic->id)->pluck('tag_id');

            if ($topic) {

                // if topic is agreed and live by another supporter, then it is not objectionable.
                if ($request->event_type == 'objection' && $topic->go_live_time <= time() && empty($topic->objector_nick_id)) {
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
        $data['history_link'] = config('global.APP_URL_FRONT_END') . '/' . $link;
        $data['type'] = "Topic";
        $data['namespace_id'] = $topic->namespace_id;

        $data['object'] =  Helpers::renderParentCampLinks($liveTopic->topic_num, 1, $liveTopic->topic_name, true, 'topic');
        // $data['object'] = $liveTopic->topic_name;

        $data['object_type'] = "";
        $data['nick_name'] = $nickName->nick_name;
        $data['forum_link'] = 'forum/' . $topic->topic_num . '-' . $liveTopic->topic_name . '/1/threads';
        $data['subject'] = $data['nick_name'] . " has objected to your proposed change.";
        $data['namespace_id'] = (isset($topic->namespace_id) && $topic->namespace_id)  ?  $topic->namespace_id : 1;
        $data['nick_name_id'] = $nickName->id;
        $data['help_link'] = config('global.APP_URL_FRONT_END') . '/' . General::getDealingWithDisagreementUrl();
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
            GetPushNotificationToSupporter::pushNotificationOnObject($topic->topic_num, 1, $all['submitter'], $all['nick_name'], config('global.notification_type.objectTopic'));
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    private function updateCampNotification($camp, $liveCamp, $link, $request)
    {
        $link = config('global.APP_URL_FRONT_END') . '/camp/history/' . $camp->topic_num . '/' . $camp->camp_num;
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

    /**
     * Soft-delete a topic. Permitted if the user is an admin
     * OR owns the nickname that submitted the topic.
     */
    public function deleteTopic(Request $request)
    {
        $topicNum = $request->input('topic_num');
        if (empty($topicNum)) {
            return $this->resProvider->apiJsonResponse(400, 'topic_num is required', '', '');
        }

        $topic = Topic::where('topic_num', $topicNum)
            ->where('is_disabled', 0)
            ->orderBy('id', 'desc')
            ->first();

        if (!$topic) {
            return $this->resProvider->apiJsonResponse(404, 'Topic not found', '', '');
        }

        $user = Auth::user();
        $isAdmin = $user && $user->type === 'admin';
        $nickIds = Nickname::getNicknamesIdsByUserId($user->id);
        $isCreator = in_array($topic->submitter_nick_id, $nickIds);

        if (!$isAdmin && !$isCreator) {
            return $this->resProvider->apiJsonResponse(403, 'You do not have permission to delete this topic', '', '');
        }

        try {
            Topic::where('topic_num', $topicNum)->update(['is_disabled' => 1]);

            // Remove from canonizer-service MongoDB if configured
            $appURL = env('CS_APP_URL');
            $apiToken = env('API_TOKEN');
            if (!empty($appURL) && !empty($apiToken)) {
                $endpoint = $appURL . '/api/v1/tree/delete';
                $headers = [
                    'Content-Type:application/x-www-form-urlencoded',
                    'X-Api-Token:' . $apiToken,
                ];
                Util::execute('POST', $endpoint, $headers, http_build_query(['topic_num' => $topicNum]));
            }

            return $this->resProvider->apiJsonResponse(200, 'Topic deleted', ['topic_num' => (int) $topicNum], '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, 'Failed to delete topic', '', $e->getMessage());
        }
    }

    /**
     * Admin or owner: restore a previously disabled topic.
     */
    public function restoreTopic(Request $request)
    {
        $topicNum = $request->input('topic_num');
        if (empty($topicNum)) {
            return $this->resProvider->apiJsonResponse(400, 'topic_num is required', '', '');
        }

        $topic = Topic::where('topic_num', $topicNum)->orderBy('id', 'desc')->first();
        if (!$topic) {
            return $this->resProvider->apiJsonResponse(404, 'Topic not found', '', '');
        }

        $user = Auth::user();
        $isAdmin = $user && $user->type === 'admin';
        $nickIds = Nickname::getNicknamesIdsByUserId($user->id);
        $isCreator = in_array($topic->submitter_nick_id, $nickIds);

        if (!$isAdmin && !$isCreator) {
            return $this->resProvider->apiJsonResponse(403, 'You do not have permission to restore this topic', '', '');
        }

        try {
            Topic::where('topic_num', $topicNum)->update(['is_disabled' => 0]);
            return $this->resProvider->apiJsonResponse(200, 'Topic restored. Run tree:all on the canonizer-service to re-index.', ['topic_num' => (int) $topicNum], '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, 'Failed to restore topic', '', $e->getMessage());
        }
    }

    /**
     * Admin: list all topics (live + disabled) with namespace label.
     */
    public function adminListTopics(Request $request)
    {
        try {
            $rows = DB::table('topic as t')
                ->leftJoin('nick_name as n', 'n.id', '=', 't.submitter_nick_id')
                ->leftJoin('namespace as ns', 'ns.id', '=', 't.namespace_id')
                ->select(
                    't.id',
                    't.topic_num',
                    't.topic_name',
                    't.namespace_id',
                    'ns.label as namespace_label',
                    't.submitter_nick_id',
                    'n.nick_name as submitter_nick_name',
                    't.is_disabled',
                    't.submit_time',
                    't.go_live_time'
                )
                ->orderBy('t.topic_num', 'desc')
                ->get();
            return $this->resProvider->apiJsonResponse(200, 'Success', $rows, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, 'Failed to list topics', '', $e->getMessage());
        }
    }

}

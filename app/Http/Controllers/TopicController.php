<?php

namespace App\Http\Controllers;

use stdClass;
use Exception;
use Throwable;
use App\Models\Camp;
use App\Facades\Util;
use App\Models\Topic;
use App\Models\Support;
use App\Models\Nickname;
use App\Models\Statement;
use App\Models\Namespaces;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Models\ChangeAgreeLog;
use App\Jobs\ActivityLoggerJob;
use App\Models\CampSubscription;
use App\Facades\PushNotification;
use App\Helpers\ResourceInterface;
use App\Helpers\ResponseInterface;
use Illuminate\Support\Facades\DB;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use Illuminate\Support\Facades\Event;
use App\Http\Request\ValidationMessages;
use App\Events\ThankToSubmitterMailEvent;
use App\Library\General;

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

        try {
            $current_time = time();
            $input = [
                "topic_name" => $request->topic_name,
                "namespace_id" => $request->namespace,
                "submit_time" => $current_time,
                "submitter_nick_id" => $request->nick_name,
                "go_live_time" =>  $current_time,
                "language" => 'English',
                "note" => isset($request->note) ? $request->note : "",
                "grace_period" => 0
            ];
            DB::beginTransaction();
            $topic = Topic::create($input);
            if ($topic) {
                Util::dispatchJob($topic, 1, 1);
                $topicInput = [
                    "topic_num" => $topic->topic_num,
                    "nick_name_id" => $request->nick_name,
                    "delegate_nick_name_id" => 0,
                    "start" =>  $current_time,
                    "camp_num" => 1,
                    "support_order" => 1,
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
                    $link = Util::getTopicCampUrl($topic->topic_num, 1, $topicLive, $camp, time());
                    $historylink = Util::topicHistoryLink($topic->topic_num, 1, $topic->topic_name, 'Aggreement', 'topic');
                    $dataEmail = (object) [
                        "type" => "topic",
                        "link" => $link,
                        "historylink" => $historylink,
                        "object" => $topic->topic_name . " / Agreement",
                    ];
                    Event::dispatch(new ThankToSubmitterMailEvent($request->user(), $dataEmail));
                    $activitLogData = [
                        'log_type' =>  "topic/camps",
                        'activity' => 'Topic created',
                        'url' => $link,
                        'model' => $topic,
                        'topic_num' => $topic->topic_num,
                        'camp_num' =>  1,
                        'user' => $request->user(),
                        'nick_name' => Nickname::getNickName($request->nick_name)->nick_name,
                        'description' => $request->topic_name
                    ];
                    dispatch(new ActivityLoggerJob($activitLogData))->onQueue(env('QUEUE_SERVICE_NAME'));
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
            $topic = Camp::getAgreementTopic($filter);
            $topic->topicSubscriptionId = "";
            if ($request->user()) {
                $topicSubscriptionData = CampSubscription::where('user_id', '=', $request->user()->id)->where('camp_num', '=', 0)->where('topic_num', '=', $filter['topicNum'])->where('subscription_start', '<=', strtotime(date('Y-m-d H:i:s')))->where('subscription_end', '=', null)->orWhere('subscription_end', '>=', strtotime(date('Y-m-d H:i:s')))->first();
                $topic->topicSubscriptionId = isset($topicSubscriptionData->id) ? $topicSubscriptionData->id : "";
            }
            if ($topic) {
                $topic->namespace_name = Namespaces::find($topic->namespace_id)->label;
                $topicRecord[] = $topic;
                $indexs = ['topic_num', 'camp_num', 'topic_name', 'namespace_name', 'topicSubscriptionId', 'namespace_id'];
                $topicRecord = $this->resourceProvider->jsonResponse($indexs, $topicRecord);
                $topicRecord = $topicRecord[0];
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
                $statement = Statement::where('id', '=', $id)->whereIn('submitter_nick_id', $nickNames)->first();
                if (!$statement) {
                    return $this->resProvider->apiJsonResponse(400, trans('message.error.record_not_found'), '', '');
                }
                $statement->grace_period = 0;
                $statement->update();
                $filter['topicNum'] = $statement->topic_num;
                $filter['campNum'] = $statement->camp_num;
                $directSupporter =  Support::getDirectSupporter($statement->topic_num, $statement->camp_num);
                $subscribers = Camp::getCampSubscribers($statement->topic_num, $statement->camp_num);
                $link = 'statement/history/' . $statement->topic_num . '/' . $statement->camp_num;
                $livecamp = Camp::getLiveCamp($filter);
                $data['object'] = $livecamp->topic->topic_name . " / " . $livecamp->camp_name;
                $data['support_camp'] = $livecamp->camp_name;
                $data['go_live_time'] = $statement->go_live_time;
                $data['type'] = 'statement : for camp ';
                $data['typeobject'] = 'statement';
                $data['note'] = $statement->note;
                $data['camp_num'] = $statement->camp_num;
                $nickName = Nickname::getNickName($statement->submitter_nick_id);
                $data['topic_num'] = $statement->topic_num;
                $data['nick_name'] = $nickName->nick_name;
                $data['forum_link'] = 'forum/' . $statement->topic_num . '-statement/' . $statement->camp_num . '/threads';
                $data['subject'] = "Proposed change to statement for camp " . $livecamp->topic->topic_name . " / " . $livecamp->camp_name . " submitted";
                $data['namespace_id'] = (isset($livecamp->topic->namespace_id) && $livecamp->topic->namespace_id)  ?  $livecamp->topic->namespace_id : 1;
                $data['nick_name_id'] = $nickName->id;
                Statement::mailSubscribersAndSupporters($directSupporter, $subscribers, $link, $data);
                $message = trans('message.success.statement_commit');
            }
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
     *       required=true,
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
        try {
            $log = new ChangeAgreeLog();
            $log->change_id = $changeId;
            $log->camp_num = $data['camp_num'];
            $log->topic_num = $data['topic_num'];
            $log->nick_name_id = $data['nick_name_id'];
            $log->change_for = $data['change_for'];
            if ($data['change_for'] == 'statement') {
                $statement = Statement::where('id', $changeId)->first();
                if ($statement) {
                    $log->save();
                    $submitterNickId = $statement->submitter_nick_id;
                    $agreeCount = ChangeAgreeLog::where('topic_num', '=', $data['topic_num'])->where('camp_num', '=', $data['camp_num'])->where('change_id', '=', $changeId)->where('change_for', '=', 'statement')->count();
                    $supporters = Support::getAllSupporters($data['topic_num'], $data['camp_num'], $submitterNickId);
                    if ($agreeCount == $supporters) {
                        $statement->go_live_time = strtotime(date('Y-m-d H:i:s'));
                        $statement->update();
                        ChangeAgreeLog::where('topic_num', '=', $data['topic_num'])->where('camp_num', '=', $data['camp_num'])->where('change_id', '=', $changeId)->where('change_for', '=', $data['change_for'])->delete();
                    }
                    $message = trans('message.success.statement_agree');
                } else {
                    return $this->resProvider->apiJsonResponse(400, trans('message.error.record_not_found'), '', '');
                }
            }
            return $this->resProvider->apiJsonResponse(200, $message, '', '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    public function manageTopic(Request $request, Validate $validate)
    {
        $all = $request->all();
        $current_time = time();
        try {
            $liveTopicData = Topic::select('topic.*')
                ->join('camp', 'camp.topic_num', '=', 'topic.topic_num')
                ->where('camp.camp_name', '=', 'Agreement')
                ->where('topic_name', $all['topic_name'])
                ->where('topic.objector_nick_id', "=", null)
                ->whereRaw('topic.go_live_time in (select max(go_live_time) from topic where objector_nick_id is null and go_live_time < "' . time() . '" group by topic_num)')
                ->where('topic.go_live_time', '<=', time())
                ->latest('submit_time')
                ->first();

            $nonLiveTopicData = Topic::select('topic.*')
                ->join('camp', 'camp.topic_num', '=', 'topic.topic_num')
                ->where('camp.camp_name', '=', 'Agreement')
                ->where('topic_name', $all['topic_name'])
                ->where('topic.objector_nick_id', "=", null)
                ->where('topic.go_live_time', ">", time())
                ->first();

            if ((isset($liveTopicData) && ( $liveTopicData->topic_num != $all['topic_num'])) || (isset($nonLiveTopicData) && ($nonLiveTopicData->topic_num != $all['topic_num']))) {
                return $this->resProvider->apiJsonResponse(400, trans('message.error.topic_name_alreday_exist'), '', '');
            }
            DB::beginTransaction();
            if ($all['event_type'] == "objection") {
                $topic = Topic::where('id', $all['objection_id'])->first();
                $topic->objector_nick_id = $all['nick_name'];
                $topic->object_reason = $all['objection_reason'];
                $topic->object_time = $current_time;
            }

            if ($all['event_type'] == "edit") {
                $topic = Topic::where('id', $all['topic_id'])->first();
                $topic->topic_name = isset($all['topic_name']) ? $all['topic_name'] : "";
                $topic->namespace_id = isset($all['namespace_id']) ? $all['namespace_id'] : "";
                $topic->submitter_nick_id = isset($all['nick_name']) ? $all['nick_name'] : "";
                $topic->note = isset($all['note']) ? $all['note'] : "";
            }

            if ($all['event_type'] == "update") {
                $topic = new Topic();
                $topic->topic_name = $all['topic_name'];
                $topic->namespace_id = $all['namespace_id'];
                $topic->submit_time = $current_time;
                $topic->submitter_nick_id = $all['nick_name'];
                $topic->go_live_time = $current_time;
                $topic->language = 'English';
                $topic->note = isset($all['note']) ? $all['note'] : "";
                $topic->grace_period = 0;
            }

            $nickNames = Nickname::personNicknameArray();
            $ifIamSingleSupporter = Support::ifIamSingleSupporter($all['topic_num'], 0, $nickNames);

            if (!$ifIamSingleSupporter) {
                $topic->go_live_time = strtotime(date('Y-m-d H:i:s', strtotime('+1 days')));
                $topic->go_live_time;
                $topic->grace_period = 1;
            }
            //dd( $topic);
            $topic->save();
            DB::commit();
            if ($all['event_type'] == "objection") {
                if (isset($topic)) {
                    Util::dispatchJob($topic, 1, 1);
                }
                $user = Nickname::getUserByNickName($all['submitter']);
                $liveTopic = Topic::select('topic.*')
                    ->where('topic.topic_num', $topic->topic_num)
                    ->where('topic.objector_nick_id', "=", null)
                    ->latest('topic.submit_time')
                    ->first();
                $link = 'topic-history/' . $topic->topic_num;
                $data['object'] = $liveTopic->topic_name;
                $nickName = Nickname::getNickName($all['nick_name']);
                $data['topic_link'] = Camp::getTopicCampUrl($topic->topic_num, 1);
                $data['type'] = "Topic";
                $data['namespace_id'] = $topic->namespace_id;
                $data['object'] = $liveTopic->topic_name;
                $data['object_type'] = "";
                $data['nick_name'] = $nickName->nick_name;
                $data['forum_link'] = 'forum/' . $topic->topic_num . '-' . $liveTopic->topic_name . '/1/threads';
                $data['subject'] = $data['nick_name'] . " has objected to your proposed change.";
                $data['namespace_id'] = (isset($topic->namespace_id) && $topic->namespace_id)  ?  $topic->namespace_id : 1;
                $data['nick_name_id'] = $nickName->id;
                $data['help_link'] = General::getDealingWithDisagreementUrl();
                $receiver = env('APP_ENV') == "production" ? $user->email : env('ADMIN_EMAIL');
                try {
                    //   Mail::to($receiver)->bcc(config('app.admin_bcc'))->send(new ObjectionToSubmitterMail($user, $link, $data));
                } catch (\Swift_TransportException $e) {
                    throw new \Swift_TransportException($e);
                }
            } else if ($all['event_type'] == "update") {
                Util::dispatchJob($topic, 1, 1);
            }
        } catch (Exception $e) {
            DB::rollback();
        }
    }
}

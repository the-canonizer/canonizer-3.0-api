<?php

namespace App\Http\Controllers;

use stdClass;
use Exception;
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
use App\Jobs\ActivityLoggerJob;
use App\Helpers\ResourceInterface;
use App\Helpers\ResponseInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Events\NotifySupportersEvent;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use Illuminate\Support\Facades\Event;
use App\Http\Request\ValidationMessages;
use App\Jobs\ObjectionToSubmitterMailJob;
use App\Facades\GetPushNotificationToSupporter;
use App\Helpers\Helpers;
use App\Library\wiki_parser\wikiParser as wikiParser;



class StatementController extends Controller
{
    public function __construct(ResponseInterface $respProvider, ResourceInterface $resProvider, ValidationRules $rules, ValidationMessages $validationMessages)
    {
        $this->rules = $rules;
        $this->validationMessages = $validationMessages;
        $this->resourceProvider  = $resProvider;
        $this->resProvider = $respProvider;
    }

    /**
     * @OA\Post(path="/get-camp-statement",
     *   tags={"Statement"},
     *   summary="get camp statement",
     *   description="Used to get statement.",
     *   operationId="getCampStatement",
     *   @OA\RequestBody(
     *       required=true,
     *       description="Get topics",
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

    public function getStatement(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getStatementValidationRules(), $this->validationMessages->getStatementValidationMessages());
        if ($validationErrors) {
            if ($validationErrors->error->has('topic_num')) {
                $topicRules = $validationErrors->error->get('topic_num');
                $statusCode = in_array(trans('message.error.camp_live_statement_not_found'), $topicRules) ? 404 : 400;
                $validationErrors->status_code = $statusCode;
            }
            return (new ErrorResource($validationErrors))->response()->setStatusCode($statusCode ?? 400);
        }
        $filter['topicNum'] = $request->topic_num;
        $filter['asOf'] = $request->as_of;
        $filter['asOfDate'] = $request->as_of_date;
        $filter['campNum'] = $request->camp_num;
        $statement = [];
        $message = null;
        try {
            $campStatement =  Statement::getLiveStatement($filter);
            if ($campStatement) {
                $WikiParser = new wikiParser;
                $campStatement->parsed_value = $campStatement->parsed_value; // $WikiParser->parse($campStatement->value);
                $campStatement->submitter_nick_name = $campStatement->submitterNickName->nick_name;
                $statement[] = $campStatement;
                $indexes = ['id', 'value', 'parsed_value', 'note', 'go_live_time', 'submit_time', 'submitter_nick_name'];
                $statement = $this->resourceProvider->jsonResponse($indexes, $statement);
            }

            if ($filter['asOf'] === 'default') {
                $inReviewChangesCount = Helpers::getChangesCount((new Statement()), $request->topic_num, $request->camp_num);
                if (!$campStatement && !$inReviewChangesCount) {
                    $message = trans('message.error.camp_live_statement_not_found');
                }
                $statement[0]['draft_record_id'] = Statement::getDraftRecord($filter['topicNum'], $filter['campNum']);
                $statement[0] = array_merge(empty($statement) ? $statement : $statement[0], ['in_review_changes' => $inReviewChangesCount]);
            }
            return $this->resProvider->apiJsonResponse(200, $message ?? trans('message.success.success'), $statement, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(path="/get-statement-history",
     *   tags={"Statement"},
     *   summary="get camp statement",
     *   description="This API is used to get camp statement history.",
     *   operationId="getCampStatementHistory",
     *   @OA\RequestBody(
     *       required=true,
     *       description="Get camp statement history",
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
     *                   property="event_type",
     *                   description="Possible values objected, live, in_review, old, all",
     *                   required=true,
     *                   type="string",
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
     *               ),
     *               @OA\Property(
     *                   property="per_page",
     *                   description="Records per page",
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
    public function getStatementHistory(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getStatementHistoryValidationRules(), $this->validationMessages->getStatementHistoryValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $filter['topicNum'] = $request->topic_num;
        $filter['campNum'] = $request->camp_num;
        $filter['type'] = isset($request->type) ? $request->type : 'all';
        $filter['asOf'] = $request->as_of;
        $filter['asOfDate'] = $request->as_of_date;
        $filter['currentTime'] = time();
        $filter['per_page'] = !empty($request->per_page) ? $request->per_page : config('global.per_page');
        $response = new stdClass();
        $response->statement = [];
        $response->ifIamSupporter = null;
        $response->ifSupportDelayed = null;
        $response->ifIAmExplicitSupporter = null;

        $statements = Statement::where([
            'topic_num' => $filter['topicNum'],
            'camp_num' => $filter['campNum'],
        ])->get();

        if ($statements->count() < 1)
            return $this->resProvider->apiJsonResponse(404, '', null, trans('message.error.camp_live_statement_not_found'));
        
        try {
            $response->topic = Camp::getAgreementTopic($filter);
            $response->liveCamp = Camp::getLiveCamp($filter);
            $response->parentCamp = Camp::campNameWithAncestors($response->liveCamp, $filter);
            $statement_query = Statement::where('topic_num', $filter['topicNum'])->where('camp_num', $filter['campNum'])->latest('submit_time');
            $campLiveStatement =  Statement::getLiveStatement($filter);

            if ($request->user()) {
                $nickNames = Nickname::personNicknameArray();
                $submitTime = $statement_query->first() ? $statement_query->first()->submit_time : null;
                $response->ifIamSupporter = Support::ifIamSupporter($filter['topicNum'], $filter['campNum'], $nickNames, $submitTime);
                $response->ifSupportDelayed = Support::ifIamSupporter($filter['topicNum'], $filter['campNum'], $nickNames, $submitTime,  true);
                $response->ifIAmExplicitSupporter = Support::ifIamExplicitSupporter($filter, $nickNames);

                ['is_disabled' => $response->parent_is_disabled, 'is_one_level' => $response->parent_is_one_level] = Camp::checkIfParentCampDisabledSubCampFunctionality($response->liveCamp);

                $response = Statement::statementHistory($statement_query, $response, $filter,  $campLiveStatement, $request);
            } else {
                $response = Statement::statementHistory($statement_query, $response, $filter,  $campLiveStatement, $request);
            }
            $response->draft_record_id = Statement::getDraftRecord($filter['topicNum'], $filter['campNum']);

            $response->total_counts = Helpers::getHistoryCountsByChange($campLiveStatement, $filter);
            if(!empty($campLiveStatement)) {
                $response->live_record_id = Helpers::getLiveHistoryRecord($campLiveStatement, $filter);
            }
            
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $response, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage().' '.$e->getLine().' '.$e->getFile());
        }
    }

    /**
     * @OA\Post(path="/edit-camp-statement",
     *   tags={"Statement"},
     *   summary="Get statement",
     *   description="Used to get statement details.",
     *   operationId="getStatement",
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
     *       description="Edit Statement",
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
    public function editStatement(Request $request, Validate $validate)
    {
        try {
            $validationErrors = $validate->validate($request, $this->rules->getEditCaseValidationRules(), $this->validationMessages->getEditCaseValidationMessages());
            if ($validationErrors) {
                return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
            }
            $statement = Statement::where('id', $request->record_id)->first();
            if ($statement) {
                // if statement is agreed and live by another supporter, then it is not objectionable.
                if ($request->event_type == 'objection' && $statement->go_live_time <= time() && empty($statement->objector_nick_id)) {
                    $response = collect($this->resProvider->apiJsonResponse(404, trans('message.error.objection_history_changed', ['history' => 'statement']), '', '')->original)->toArray();
                    $response['is_live'] = true;
                    return $response;
                }

                $filter['topicNum'] = $statement->topic_num;
                $filter['campNum'] = $statement->camp_num;
                $filter['asOf'] = 'default';
                $topic = Camp::getAgreementTopic($filter);
                $camp = Camp::getLiveCamp($filter);
                $parentCampNum = isset($camp->parent_camp_num) ? $camp->parent_camp_num : 0;
                $parentCamp = Camp::campNameWithAncestors($camp, $filter);
                $nickName = Nickname::topicNicknameUsed($statement->topic_num);
                $WikiParser = new wikiParser;
                $statement->parsed_value = $statement->parsed_value; // $WikiParser->parse($statement->value);
                $data = new stdClass();
                $data->statement = $statement;
                $data->topic = $topic;
                $data->parent_camp = $parentCamp;
                $data->nick_name = $nickName;
                $data->parent_camp_num = $parentCampNum;
                $response[0] = $data;
                $indexes = ['statement', 'topic', 'parent_camp', 'nick_name', 'parent_camp_num'];
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
     * @OA\Post(path="/store-camp-statement",
     *   tags={"Statement"},
     *   summary="Store/update/object camp statement",
     *   description="This API is used to store, update and object camp statement.",
     *   operationId="Store/update/object-CampStatementHistory",
     *   @OA\RequestBody(
     *       required=true,
     *       description="Get camp statement history",
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
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="note",
     *                   description="Note for camp statement",
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
     *                   property="statement",
     *                   description="Camp statement",
     *                   required=true,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="event_type",
     *                   description="Possible values objection, edit, create, update",
     *                   required=true,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="objection_reason",
     *                   description="Objection reason in case user is objecting to a statement",
     *                   required=false,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="statement_id",
     *                   description="Id of statement objected",
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
    public function storeStatement(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getStatementStoreValidationRules(), $this->validationMessages->getStatementStoreValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        if (!Gate::allows('nickname-check', $request->nick_name)) {
            //return $this->resProvider->apiJsonResponse(403, trans('message.error.invalid_data'), '', '');
        }

        $all = $request->all();
        $filters['topicNum'] = $all['topic_num'];
        $filters['campNum'] = $all['camp_num'];
        $filters['asOf'] = 'default';
        $eventType = $all['event_type'];
        try {
            // $totalSupport =  Support::getAllSupporters($all['topic_num'], $all['camp_num'], 0);
            // $loginUserNicknames =  Nickname::personNicknameIds();
            $nickNames = Nickname::personNicknameArray();
            $ifIamSingleSupporter = Support::ifIamSingleSupporter($all['topic_num'], $all['camp_num'], $nickNames);

            if ($eventType == 'objection') {
                $statement = Statement::where('id', $all['statement_id'])->first();

                // Check if the change is already objected , then we can't object again
                if (!empty($statement->objector_nick_id)) {
                    return $this->resProvider->apiJsonResponse(400, trans('message.support.can_not_object'), '', '');
                }

                $checkUserDirectSupportExists = Support::ifIamSupporterForChange($all['topic_num'], $filters['campNum'], $nickNames, $statement->submit_time);
                // This change is asked to implement in https://github.com/the-canonizer/Canonizer-Beta--Issue-Tracking/issues/193
                $checkIfIAmExplicitSupporter = Support::ifIamExplicitSupporterBySubmitTime($filters, $nickNames, $statement->submit_time, null, false, 'ifIamExplicitSupporter');

                if ($checkUserDirectSupportExists < 1 && !$checkIfIAmExplicitSupporter) {
                    $message = trans('message.support.not_authorized_for_objection');
                    return $this->resProvider->apiJsonResponse(400, $message, '', '');
                }
            }
            if (preg_match('/\bcreate\b|\bupdate\b/', $eventType)) {
                if (isset($all['is_draft']) && $all['is_draft'] && !$all['statement_id'] && Statement::getDraftRecord($all['topic_num'], $all['camp_num'])) {
                    $message = trans('message.error.draft_is_already_exists');
                    return $this->resProvider->apiJsonResponse(400, $message, '', '');
                }
                $statement = self::createOrUpdateStatement($all);
                $message = isset($all['is_draft']) && $all['is_draft'] ? trans('message.success.statement_draft_create') : trans('message.success.statement_create');
            } elseif ($eventType == 'edit') {
                $statement = self::editUpdatedStatement($all);
                $message = (isset($all['is_draft']) && $all['is_draft'] ? trans('message.success.draft_update') : trans('message.success.statement_update'));
            } else {
                $statement = self::objectStatement($all);
                $message = trans('message.success.statement_object');
            }

            $statement->go_live_time = strtotime(date('Y-m-d H:i:s', strtotime('+1 days')));

            /** Dispatch job for the case when the statement is in grace period by user B,
             * so schedule a job that will run and update the tree
             * also this will update the grace period flag as well.
             * */
            if ($statement->grace_period == 1) {
                $topic = Topic::getLiveTopic($all['topic_num']);
                $delayCommitTimeInSeconds = env('COMMIT_TIME_DELAY_IN_SECONDS'); // 1 hour commit time + 10 seconds for delay job
                Util::dispatchJob($topic, $all['camp_num'], 1, $delayCommitTimeInSeconds);
            }

            $statement->save();
            if (!isset($all['is_draft']) || !$all['is_draft']) {
                $livecamp = Camp::getLiveCamp($filters);
                $link = config('global.APP_URL_FRONT_END') . '/statement/history/' . $statement->topic_num . '/' . $statement->camp_num;

                if ($eventType == "create" && $statement->grace_period == 0
                ) {
                    $nickName = '';
                    $nicknameModel = Nickname::getNickName($all['nick_name']);
                    if (!empty($nicknameModel)) {
                        $nickName = $nicknameModel->nick_name;
                    }
                    // GetPushNotificationToSupporter::pushNotificationToSupporter($request->user(), $request->topic_num, $request->camp_num, config('global.notification_type.Statement'), null, $nickName);
                    $this->createdStatementNotification($livecamp, $link, $statement, $request);
                } else if ($eventType == "update" && $ifIamSingleSupporter) {
                    $this->updatedStatementNotification($livecamp, $link, $statement, $request);
                } else if ($eventType == "objection") {
                    $this->objectedStatementNotification($all, $livecamp, $link, $statement, $request);
                }
            }

            return $this->resProvider->apiJsonResponse(200, $message, '', '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    private function createOrUpdateStatement($all)
    {
        $goLiveTime = time();

        $statement = isset($all['statement_id']) ? Statement::find($all['statement_id']) : new Statement();
        $statement->value = $all['statement'] ?? "";
        $statement->parsed_value = $all['statement'] ?? "";
        $statement->topic_num = $all['topic_num'];
        $statement->camp_num = $all['camp_num'];
        $statement->note = $all['note'] ?? "";
        $statement->submit_time = strtotime(date('Y-m-d H:i:s'));
        $statement->submitter_nick_id = $all['nick_name'];
        $statement->go_live_time = $goLiveTime;
        $statement->language = 'English';
        $statement->grace_period = isset($all['is_draft']) && $all['is_draft'] ? 0 : 1;
        $statement->is_draft = isset($all['is_draft']) && $all['is_draft'] ? true : false;
        return $statement;
    }

    private function objectStatement($all)
    {
        $goLiveTime = time();
        $statement = Statement::where('id', $all['statement_id'])->first();
        $statement->objector_nick_id = $all['nick_name'];
        $statement->object_reason = $all['objection_reason'];
        $statement->go_live_time = $goLiveTime;
        $statement->object_time = time();
        $statement->grace_period = 0;
        return $statement;
    }

    private function editUpdatedStatement($all)
    {
        $statement = Statement::where('id', $all['statement_id'])->first();
        $statement->value = $all['statement'] ?? "";
        $statement->parsed_value = $all['statement'] ?? "";
        $statement->note = $all['note'] ?? "";
        $statement->submitter_nick_id = $all['nick_name'];
        if (isset($all['is_draft']) && $all['is_draft']) {
            $statement->submit_time = time();
            $statement->go_live_time = strtotime(date('Y-m-d H:i:s', strtotime('+1 days')));
            $statement->grace_period = 0;
        }
        return $statement;
    }

    private function createdStatementNotification($livecamp, $link, $statement, $request)
    {
        // $directSupporter = Support::getAllDirectSupporters($statement->topic_num, $statement->camp_num);
        // $subscribers = Camp::getCampSubscribers($statement->topic_num, $statement->camp_num);
        $dataObject['topic_num'] = $statement->topic_num;
        $dataObject['camp_num'] = $statement->camp_num;
        $dataObject['object'] = $livecamp->topic->topic_name . " >> " . $livecamp->camp_name;
        $dataObject['support_camp'] = $livecamp->camp_name;
        $dataObject['go_live_time'] = $statement->go_live_time;
        $dataObject['type'] = 'statement : for camp ';
        $dataObject['typeobject'] = 'statement';
        $dataObject['note'] = $statement->note;
        $nickName = Nickname::getNickName($statement->submitter_nick_id);
        $dataObject['nick_name'] = $nickName->nick_name;
        $dataObject['forum_link'] = 'forum/' . $statement->topic_num . '-statement/' . $statement->camp_num . '/threads';
        $dataObject['subject'] = "Proposed change to statement for camp " . $livecamp->topic->topic_name . " >> " . $livecamp->camp_name . " submitted";
        $dataObject['namespace_id'] = (isset($livecamp->topic->namespace_id) && $livecamp->topic->namespace_id)  ?  $livecamp->topic->namespace_id : 1;
        $dataObject['nick_name_id'] = $nickName->id;
        $dataObject['is_live'] = ($statement->go_live_time <=  time()) ? 1 : 0;
        $topic = Topic::getLiveTopic($livecamp->topic->topic_num, "");
        $notificationData = [
            "email" => [],
            "push_notification" => []
        ];
        $notificationData['email'] = $dataObject;

        $liveThread =  null;
        $threadId =  null;
        $getMessageData = GetPushNotificationToSupporter::getMessageData(Auth::user(), $topic, $livecamp, $liveThread, $threadId, config('global.notification_type.Statement'), $nickName->nick_name, null);
        if (!empty($getMessageData)) {
            $notificationData['push_notification'] = [
                "topic_num" => $livecamp->topic_num,
                "camp_num" => $livecamp->camp_num,
                "notification_type" => $getMessageData->notification_type,
                "title" => $getMessageData->title,
                "message_body" => $getMessageData->message_body,
                "link" => $getMessageData->link,
                "thread_id" => !empty($threadId) ? $threadId : null,
            ];
        }

        Event::dispatch(new NotifySupportersEvent($livecamp, $notificationData, config('global.notification_type.Statement'), $link, config('global.notify.both')));
        $activityLogData = [
            'log_type' =>  "topic/camps",
            'activity' => trans('message.activity_log_message.statement_create', ['nick_name' =>  $nickName->nick_name]),
            'url' => $link,
            'model' => $statement,
            'topic_num' => $statement->topic_num,
            'camp_num' =>  $statement->camp_num,
            'user' => $request->user(),
            'nick_name' => $nickName->nick_name,
            'description' => $statement->value
        ];
        dispatch(new ActivityLoggerJob($activityLogData))->onQueue(env('ACTIVITY_LOG_QUEUE'));
        // Util::mailSubscribersAndSupporters($directSupporter, $subscribers, $link, $dataObject);
    }

    private function updatedStatementNotification($livecamp, $link, $statement, $request)
    {
        $nickName = Nickname::getNickName($statement->submitter_nick_id);
        $activityLogData = [
            'log_type' =>  "topic/camps",
            'activity' => trans('message.activity_log_message.statement_update', ['nick_name' =>  $nickName->nick_name]),
            'url' => $link,
            'model' => $statement,
            'topic_num' => $statement->topic_num,
            'camp_num' =>  $statement->camp_num,
            'user' => $request->user(),
            'nick_name' => $nickName->nick_name,
            'description' => $statement->value
        ];
        dispatch(new ActivityLoggerJob($activityLogData))->onQueue(env('ACTIVITY_LOG_QUEUE'));
    }

    private function objectedStatementNotification($all, $livecamp, $link, $statement, $request)
    {
        $user = Nickname::getUserByNickName($all['submitter']);
        $nickName = Nickname::getNickName($all['nick_name']);
        $topicLive = Topic::getLiveTopic($statement->topic_num, ['nofilter' => true]);
        $data['topic_link'] = Util::getTopicCampUrlWithoutTime($statement->topic_num, $statement->camp_num, $topicLive, $livecamp);
        $data['history_link'] = config('global.APP_URL_FRONT_END') . '/statement/history/' . $statement->topic_num . '-' . Util::replaceSpecialCharacters($topicLive->topic_name) . '/' . $statement->camp_num . '-' . Util::replaceSpecialCharacters($livecamp->camp_name);
        $data['type'] = "Camp";

        // $data['object'] = $livecamp->topic->topic_name . " >> " . $livecamp->camp_name;
        $data['object'] = Helpers::renderParentCampLinks($livecamp->topic->topic_num, $livecamp->camp_num, $livecamp->topic->topic_name, true, 'statement');

        $data['object_type'] = "statement";
        $data['nick_name'] = $nickName->nick_name;
        $data['forum_link'] = 'forum/' . $statement->topic_num . '-statement/' . $statement->camp_num . '/threads';
        $data['subject'] = $data['nick_name'] . " has objected to your proposed change.";
        $data['namespace_id'] = (isset($livecamp->topic->namespace_id) && $livecamp->topic->namespace_id)  ?  $livecamp->topic->namespace_id : 1;
        $data['nick_name_id'] = $nickName->id;
        $data['help_link'] = config('global.APP_URL_FRONT_END') . '/' . General::getDealingWithDisagreementUrl();
        $activityLogData = [
            'log_type' =>  "topic/camps",
            'activity' => trans('message.activity_log_message.statement_object', ['nick_name' =>  $nickName->nick_name]),
            'url' => $link,
            'model' => $statement,
            'topic_num' => $statement->topic_num,
            'camp_num' =>  $statement->camp_num,
            'user' => $request->user(),
            'nick_name' =>  $nickName->nick_name,
            'description' => $statement->value
        ];
        try {
            dispatch(new ActivityLoggerJob($activityLogData))->onQueue(env('ACTIVITY_LOG_QUEUE'));
            dispatch(new ObjectionToSubmitterMailJob($user, $link, $data))->onQueue(env('NOTIFY_SUPPORTER_QUEUE'));
            GetPushNotificationToSupporter::pushNotificationOnObject($statement->topic_num, $statement->camp_num, $all['submitter'], $all['nick_name'], config('global.notification_type.objectStatement'));
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(path="/get-statement-comparison",
     *   tags={"Statement"},
     *   summary="get statement comparison",
     *   description="This API is used for compare two statement.",
     *   operationId="get-statement-comparison",
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
     *                  property="ids",
     *                  type="object",
     *                  @OA\Property(
     *                          property="status_code",
     *                          type="array"
     *                   ),
     *              )
     *          )
     *     ),
     *   ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */

    public function getStatementComparison(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getStatementComparisonValidationRules(), $this->validationMessages->getStatementComparisonValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $statement = [];
        try {

            $compare = !empty($request->compare) ? $request->compare : 'statement';

            if ($compare == 'statement') {
                $campStatement =  Statement::whereIn('id', $request->ids)->get();

                $WikiParser = new wikiParser;
                $currentTime = time();
                $currentLive = 0;
                if ($campStatement) {
                    foreach ($campStatement as $val) {

                        switch ($val) {
                            case $val->objector_nick_id !== NULL:
                                $status = "objected";
                                break;
                            case $currentTime < $val->go_live_time && $currentTime >= $val->submit_time:
                                $status = "in_review";
                                break;
                            case $currentLive != 1 && $currentTime >= $val->go_live_time:
                                $currentLive = 1;
                                $status = "live";
                                break;
                            default:
                                $status  = "old";
                        }
                        $namspaceId =  Topic::select('namespace_id')->where('topic_num', $val->topic_num)->first();
                        $statement['comparison'][] = array(
                            'go_live_time' => ($val->go_live_time),
                            'submit_time' => ($val->submit_time),
                            'object_time' => ($val->object_time),
                            'parsed_value' => $val->parsed_value, //$WikiParser->parse($val->value),
                            'value' => $val->value,
                            'topic_num' => $val->topic_num,
                            'camp_num' => $val->camp_num,
                            'id' => $val->id,
                            'note' => $val->note,
                            'submitter_nick_id' => $val->submitter_nick_id,
                            'objector_nick_id' => $val->objector_nick_id,
                            'object_reason' => $val->object_reason,
                            'proposed' => $val->proposed,
                            'replacement' => $val->replacement,
                            'language' => $val->language,
                            'grace_period' => $val->grace_period,
                            'submitter_nick_name' => Nickname::getUserByNickId($val->submitter_nick_id),
                            'status' => $status,
                            'namespace_id' => $namspaceId->namespace_id,
                        );
                    }
                }
                $filter['topicNum'] = $request->topic_num;
                $filter['campNum'] = $request->camp_num;
                $filter['asOf'] = "";
                $filter['asOfDate'] = "";
                $liveStatement =  Statement::getLiveStatement($filter);
                $latestRevision = Statement::where('topic_num', $request->topic_num)->where('camp_num', $request->camp_num)->latest('submit_time')->first();
                $statement['liveStatement'] = $liveStatement;
                if (isset($liveStatement)) {
                    $namspaceId =  Topic::select('namespace_id')->where('topic_num', $liveStatement->topic_num)->first();
                    $currentTime = time();
                    $currentLive = 0;
                    $statement['liveStatement']['go_live_time'] = ($liveStatement->go_live_time);
                    $statement['liveStatement']['submit_time'] = ($liveStatement->submit_time);
                    $statement['liveStatement']['object_time'] = ($liveStatement->object_time);
                    $statement['liveStatement']['parsed_value'] = $liveStatement->parsed_value; //$WikiParser->parse($liveStatement->value);
                    $statement['liveStatement']['submitter_nick_name'] = Nickname::getUserByNickId($liveStatement->submitter_nick_id);
                    $statement['liveStatement']['namespace_id']  = $namspaceId->namespace_id;
                    switch ($liveStatement) {
                        case $liveStatement->objector_nick_id !== NULL:
                            $statement['liveStatement']['status'] = "objected";
                            break;
                        case $currentTime < $liveStatement->go_live_time && $currentTime >= $liveStatement->submit_time:
                            $statement['liveStatement']['status'] = "in_review";
                            break;
                        case $currentLive != 1 && $currentTime >= $liveStatement->go_live_time:
                            $currentLive = 1;
                            $statement['liveStatement']['status'] = "live";
                            break;
                        default:
                            $statement['liveStatement']['status'] = "old";
                    }
                }
                $statement['latestRevision'] = ($latestRevision->submit_time);
            }
            if ($request->compare == 'topic') {
                $campStatement =  Topic::whereIn('id', $request->ids)->get();

                foreach ($campStatement as $val) {

                    $statement['comparison'][] = array(
                        'go_live_time' => ($val->go_live_time),
                        'submit_time' => ($val->submit_time),
                        'object_time' => ($val->object_time),
                        'parsed_value' => $val->topic_name,
                        'value' => $val->topic_name,
                        'topic_num' => $val->topic_num,
                        'camp_num' => $val->camp_num,
                        'id' => $val->id,
                        'note' => $val->note,
                        'submitter_nick_id' => $val->submitter_nick_id,
                        'objector_nick_id' => $val->objector_nick_id,
                        'object_reason' => $val->object_reason,
                        'proposed' => $val->proposed,
                        'replacement' => $val->replacement,
                        'language' => $val->language,
                        'grace_period' => $val->grace_period,
                        'submitter_nick_name' => Nickname::getUserByNickId($val->submitter_nick_id),
                        'status' => $status ?? null,
                        'namespace_id' => $val->namespace_id,
                        'namespace' => Namespaces::find($val->namespace_id)->label
                    );
                }
                $filter['topicNum'] = $request->topic_num;
                $filter['campNum'] = $request->camp_num;
                $filter['asOf'] = "";
                $filter['asOfDate'] = "";
                $liveStatement = Topic::getLiveTopic($request->topic_num, $request->asof ?? "default");
                $latestRevision = Topic::where('topic_num', $request->topic_num)->latest('submit_time')->first();
                $statement['liveStatement'] = $liveStatement;
                if (isset($liveStatement)) {
                    $namspaceId =  Topic::select('namespace_id')->where('topic_num', $liveStatement->topic_num)->first();
                    $currentTime = time();
                    $currentLive = 0;
                    $statement['liveStatement']['go_live_time'] = ($liveStatement->go_live_time);
                    $statement['liveStatement']['submit_time'] = ($liveStatement->submit_time);
                    $statement['liveStatement']['object_time'] = ($liveStatement->object_time);
                    $statement['liveStatement']['parsed_value'] = $liveStatement->topic_name;
                    $statement['liveStatement']['submitter_nick_name'] = Nickname::getUserByNickId($liveStatement->submitter_nick_id);
                    $statement['liveStatement']['namespace_id']  = $namspaceId->namespace_id;
                    $statement['liveStatement']['namespace'] = Namespaces::find($val->namespace_id)->label;
                    switch ($liveStatement) {
                        case $liveStatement->objector_nick_id !== NULL:
                            $statement['liveStatement']['status'] = "objected";
                            break;
                        case $currentTime < $liveStatement->go_live_time && $currentTime >= $liveStatement->submit_time:
                            $statement['liveStatement']['status'] = "in_review";
                            break;
                        case $currentLive != 1 && $currentTime >= $liveStatement->go_live_time:
                            $currentLive = 1;
                            $statement['liveStatement']['status'] = "live";
                            break;
                        default:
                            $statement['liveStatement']['status'] = "NULL";
                    }
                }
                $statement['latestRevision'] = ($latestRevision->submit_time);
            }
            if ($request->compare == 'camp') {
                $campStatement =  Camp::whereIn('id', $request->ids)->get();

                foreach ($campStatement as $val) {
                    $statement['comparison'][] = array(
                        'go_live_time' => ($val->go_live_time),
                        'submit_time' => ($val->submit_time),
                        'object_time' => ($val->object_time),
                        'parsed_value' => $val->camp_name,
                        'value' => $val->camp_name,
                        'topic_num' => $val->topic_num,
                        'camp_num' => $val->camp_num,
                        'id' => $val->id,
                        'note' => $val->note,
                        'submitter_nick_id' => $val->submitter_nick_id,
                        'objector_nick_id' => $val->objector_nick_id,
                        'object_reason' => $val->object_reason,
                        'proposed' => $val->proposed,
                        'replacement' => $val->replacement,
                        'language' => $val->language,
                        'grace_period' => $val->grace_period,
                        'submitter_nick_name' => Nickname::getUserByNickId($val->submitter_nick_id),
                        'status' => $status ?? null,
                        'key_words' => $val->key_words,
                        'namespace_id' => $val->namespace_id,
                        'camp_about_url' => $val->camp_about_url,
                        'camp_about_nick_id' => $val->camp_about_nick_id,
                        'camp_about_nick_name' => Nickname::getUserByNickId($val->camp_about_nick_id),
                        'parent_camp_name' => Camp::where('camp_num', $val->parent_camp_num)->where('topic_num', $val->topic_num)->latest('submit_time')->first()->camp_name ?? "",
                        'is_disabled' => $val->is_disabled,
                        'is_one_level' => $val->is_one_level,
                        'is_archive' => $val->is_archive,
                        'camp_leader_nick_id' => $val->camp_leader_nick_id,
                        'camp_leader_nick_name' => Nickname::getUserByNickId($val->camp_leader_nick_id),
                    );
                }
                $filter['topicNum'] = $request->topic_num;
                $filter['campNum'] = $request->camp_num;
                $filter['asOf'] = "";
                $filter['asOfDate'] = "";
                $liveStatement = Camp::getLiveCamp($filter);
                $latestRevision = Camp::where('topic_num', $request->topic_num)->where('camp_num', $request->camp_num)->latest('submit_time')->first();
                $statement['liveStatement'] = $liveStatement;
                if (isset($liveStatement)) {
                    $namspaceId =  Topic::select('namespace_id')->where('topic_num', $liveStatement->topic_num)->first();
                    $currentTime = time();
                    $currentLive = 0;
                    $statement['liveStatement']['go_live_time'] = ($liveStatement->go_live_time);
                    $statement['liveStatement']['submit_time'] = ($liveStatement->submit_time);
                    $statement['liveStatement']['object_time'] = ($liveStatement->object_time);
                    $statement['liveStatement']['parsed_value'] = $liveStatement->camp_name;
                    $statement['liveStatement']['camp_about_url'] = $liveStatement->camp_about_url;
                    $statement['liveStatement']['camp_about_nick_id'] = $liveStatement->camp_about_nick_id;
                    $statement['liveStatement']['camp_about_nick_name'] = Nickname::getUserByNickId($liveStatement->camp_about_nick_id);
                    $statement['liveStatement']['camp_leader_nick_id'] = $liveStatement->camp_leader_nick_id;
                    $statement['liveStatement']['camp_leader_nick_name'] = Nickname::getUserByNickId($liveStatement->camp_leader_nick_id);
                    $statement['liveStatement']['value'] = $liveStatement->camp_name;
                    $statement['liveStatement']['submitter_nick_name'] = Nickname::getUserByNickId($liveStatement->submitter_nick_id);
                    $statement['liveStatement']['namespace_id']  = $namspaceId->namespace_id;
                    $statement['liveStatement']['parent_camp_name'] = Camp::where('camp_num', $liveStatement->parent_camp_num)->where('topic_num', $liveStatement->topic_num)->latest('submit_time')->first()->camp_name ?? "";
                    switch ($liveStatement) {
                        case $liveStatement->objector_nick_id !== NULL:
                            $statement['liveStatement']['status'] = "objected";
                            break;
                        case $currentTime < $liveStatement->go_live_time && $currentTime >= $liveStatement->submit_time:
                            $statement['liveStatement']['status'] = "in_review";
                            break;
                        case $currentLive != 1 && $currentTime >= $liveStatement->go_live_time:
                            $currentLive = 1;
                            $statement['liveStatement']['status'] = "live";
                            break;
                        default:
                            $statement['liveStatement']['status'] = "NULL";
                    }
                    $statement['liveStatement']['is_one_level'] = ($liveStatement->is_one_level);
                    $statement['liveStatement']['is_one_level'] = ($liveStatement->is_one_level);
                }
                $statement['latestRevision'] = ($latestRevision->submit_time);
            }

            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $statement, null);
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), null, $e->getMessage());
        }
    }

    /**
     * @OA\Post(path="/parse-camp-statement",
     *   tags={"Statement"},
     *   summary="Parse a string using wiki parser",
     *   description="This API is used to parse a string through wiki parser.",
     *   operationId="wiki-parser",
     *   @OA\RequestBody(
     *       required=true,
     *       description="parse sting",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               @OA\Property(
     *                   property="value",
     *                   description="string to be parsed is required",
     *                   required=true,
     *                   type="string",
     *               )
     *          )
     *      )
     *   ), 
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */
    public function parseStatement(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getParseStatementValidationRules(), $this->validationMessages->getParseStatementValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {
            $WikiParser = new wikiParser;
            $parsedValue = $request->value; // $WikiParser->parse($request->value);
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $parsedValue, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}

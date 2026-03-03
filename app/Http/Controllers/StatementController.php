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
use Illuminate\Support\Carbon;

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
     * @OA\Post(
     *   path="/get-camp-statement",
     *   tags={"Statement"},
     *   summary="Get the live statement of a camp",
     *   description="Retrieves the live statement for a specific topic and camp.",
     *   operationId="getStatement",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="Get the statement for a topic and camp",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               @OA\Property(
     *                   property="topic_num",
     *                   description="The topic number",
     *                   type="integer",
     *                   example=123
     *               ),
     *               @OA\Property(
     *                   property="camp_num",
     *                   description="The camp number",
     *                   type="integer",
     *                   example=10
     *               ),
     *               @OA\Property(
     *                   property="as_of",
     *                   description="As of filter type (default, review)",
     *                   type="string",
     *                   example="default"
     *               ),
     *               @OA\Property(
     *                   property="as_of_date",
     *                   description="As of filter date (YYYY-MM-DD)",
     *                   type="string",
     *                   format="date",
     *                   example="2025-03-01"
     *               )
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Successful response",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Success"),
     *           @OA\Property(
     *               property="data",
     *               type="object",
     *               @OA\Property(property="id", type="integer", example=1),
     *               @OA\Property(property="value", type="string", example="Sample statement content"),
     *               @OA\Property(property="parsed_value", type="string", example="Parsed statement content"),
     *               @OA\Property(property="note", type="string", nullable=true),
     *               @OA\Property(property="go_live_time", type="string", format="date-time", example="2025-03-03T12:00:00Z"),
     *               @OA\Property(property="submit_time", type="string", format="date-time", example="2025-03-03T12:00:00Z"),
     *               @OA\Property(property="submitter_nick_name", type="string", example="JohnDoe"),
     *               @OA\Property(property="draft_record_id", type="integer", nullable=true, example=5),
     *               @OA\Property(property="grace_period_record_count", type="integer", nullable=true, example=2),
     *               @OA\Property(property="in_review_changes", type="integer", nullable=true, example=1)
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad Request",
     *       @OA\JsonContent(ref="#/components/schemas/ExceptionRes")
     *   ),
     *   @OA\Response(
     *       response=404,
     *       description="Camp live statement not found",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=404),
     *           @OA\Property(property="message", type="string", example="Camp live statement not found"),
     *           @OA\Property(property="error", type="string", nullable=true)
     *       )
     *   )
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

            if ($filter['asOf'] === 'default' || $filter['asOf'] === 'review' ) {
                $inReviewChangesCount = Helpers::getChangesCount((new Statement()), $request->topic_num, $request->camp_num);
                if (!$campStatement && !$inReviewChangesCount) {
                    $message = trans('message.error.camp_live_statement_not_found');
                }
                $statement[0]['draft_record_id'] = Statement::getDraftRecord($filter['topicNum'], $filter['campNum']);
                $statement[0]['grace_period_record_count'] = Statement::getGracePeriodRecordCount($filter['topicNum'], $filter['campNum']);
                $statement[0] = array_merge(empty($statement) ? $statement : $statement[0], ['in_review_changes' => $inReviewChangesCount]);
            }
            // if ($filter['asOf'] === 'review') {
            //     $inReviewChangesCount = Helpers::getChangesCount((new Statement()), $request->topic_num, $request->camp_num);
            //     if (!$campStatement && !$inReviewChangesCount) {
            //         $message = trans('message.error.camp_live_statement_not_found');
            //     }
            //     $statement[0]['draft_record_id'] = Statement::getDraftRecord($filter['topicNum'], $filter['campNum']);
            //     $statement[0]['grace_period_record_count'] = Statement::getGracePeriodRecordCount($filter['topicNum'], $filter['campNum']);
            //     $statement[0] = array_merge(empty($statement) ? $statement : $statement[0], ['in_review_changes' => $inReviewChangesCount]);
            // }
            return $this->resProvider->apiJsonResponse(200, $message ?? trans('message.success.success'), $statement, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *   path="/get-statement-history",
     *   tags={"Statement"},
     *   summary="Get camp statement history",
     *   description="This API is used to get camp statement history.",
     *   operationId="getCampStatementHistory",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="Get camp statement history",
     *       @OA\JsonContent(
     *           required={"topic_num", "camp_num", "event_type", "per_page", "page"},
     *           @OA\Property(
     *               property="topic_num",
     *               description="Topic number is required",
     *               type="integer",
     *               example=123
     *           ),
     *           @OA\Property(
     *               property="camp_num",
     *               description="Camp number is required",
     *               type="integer",
     *               example=10
     *           ),
     *           @OA\Property(
     *               property="event_type",
     *               description="Possible values: objected, live, in_review, old, all",
     *               type="string",
     *               example="live"
     *           ),
     *           @OA\Property(
     *               property="as_of",
     *               description="As of filter type",
     *               type="string",
     *               example="date"
     *           ),
     *           @OA\Property(
     *               property="as_of_date",
     *               description="As of filter date (YYYY-MM-DD)",
     *               type="string",
     *               format="date",
     *               example="2025-03-01"
     *           ),
     *           @OA\Property(
     *               property="per_page",
     *               description="Records per page",
     *               type="integer",
     *               example=10
     *           ),
     *           @OA\Property(
     *               property="page",
     *               description="Page number",
     *               type="integer",
     *               example=1
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Success"),
     *           @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad Request",
     *       @OA\JsonContent(ref="#/components/schemas/ExceptionRes")
     *   )
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
     * @OA\Post(
     *   path="/edit-camp-statement",
     *   tags={"Statement"},
     *   summary="Edit camp statement",
     *   description="This API allows editing a camp statement.",
     *   operationId="editCampStatement",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="Edit Statement",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               required={"record_id", "event_type"},
     *               @OA\Property(
     *                   property="record_id",
     *                   description="The ID of the statement to edit",
     *                   type="integer",
     *                   example=123
     *               ),
     *               @OA\Property(
     *                   property="event_type",
     *                   description="Possible values: edit, objected, live, in_review, old, all",
     *                   type="string",
     *                   example="edit"
     *               )
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="Statement updated successfully"),
     *           @OA\Property(property="statement_id", type="integer", example=123)
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Error message",
     *       @OA\JsonContent(
     *           @OA\Property(property="error", type="string", example="Invalid record_id provided")
     *       )
     *   )
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
     * @OA\Post(
     *   path="/store-camp-statement",
     *   tags={"Statement"},
     *   summary="Create, update, or object to a camp statement",
     *   description="This API allows users to create, update, or object to a camp statement.",
     *   operationId="storeStatement",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="Statement data",
     *       @OA\JsonContent(
     *           required={"topic_num", "camp_num", "event_type", "statement", "nick_name", "submitter"},
     *           @OA\Property(
     *               property="topic_num",
     *               description="The topic number",
     *               type="integer",
     *               example=116
     *           ),
     *           @OA\Property(
     *               property="camp_num",
     *               description="The camp number",
     *               type="integer",
     *               example=1
     *           ),
     *           @OA\Property(
     *               property="event_type",
     *               description="Event type (create, update, edit, objection)",
     *               type="string",
     *               example="update"
     *           ),
     *           @OA\Property(
     *               property="statement",
     *               description="The statement content",
     *               type="string",
     *               example="<p>The statement content goes here.</p>"
     *           ),
     *           @OA\Property(
     *               property="nick_name",
     *               description="Nickname ID of the submitter",
     *               type="integer",
     *               example=709
     *           ),
     *           @OA\Property(
     *               property="submitter",
     *               description="Submitter's ID",
     *               type="integer",
     *               example=1
     *           ),
     *           @OA\Property(
     *               property="note",
     *               description="Additional notes",
     *               type="string",
     *               example="Continuously improving."
     *           ),
     *           @OA\Property(
     *               property="objection_reason",
     *               description="Reason for objection (if applicable)",
     *               type="string",
     *               nullable=true,
     *               example=null
     *           ),
     *           @OA\Property(
     *               property="camp_id",
     *               description="Camp ID",
     *               type="integer",
     *               nullable=true,
     *               example=null
     *           ),
     *           @OA\Property(
     *               property="key_words",
     *               description="Keywords related to the statement",
     *               type="string",
     *               nullable=true,
     *               example=null
     *           ),
     *           @OA\Property(
     *               property="is_draft",
     *               description="Set to true if saving as a draft",
     *               type="boolean",
     *               example=false
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Statement successfully created or updated",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Statement created successfully"),
     *           @OA\Property(
     *               property="data",
     *               type="object",
     *               @OA\Property(property="draft_record_id", type="integer", example=5, nullable=true)
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad Request",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Invalid request parameters"),
     *           @OA\Property(property="error", type="object")
     *       )
     *   ),
     *   @OA\Response(
     *       response=403,
     *       description="Unauthorized action",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=403),
     *           @OA\Property(property="message", type="string", example="Invalid data or permission denied")
     *       )
     *   )
     * )
     */


    public function storeStatement(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getStatementStoreValidationRules(), $this->validationMessages->getStatementStoreValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        if (!Gate::allows('nickname-check', $request->nick_name)) {
            return $this->resProvider->apiJsonResponse(403, trans('message.error.invalid_data'), '', '');
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
            $ifIamSingleSupporter = Support::ifIamSingleSupporter($all['topic_num'], $nickNames, $all['camp_num']);

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

            return $this->resProvider->apiJsonResponse(200, $message, (isset($all['is_draft']) && $all['is_draft'] ? [ "draft_record_id" => $statement->id] : ''), '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\POST(
     *   path="/post-statement-count",
     *   tags={"Statement"},
     *   summary="Get count of post-submission changes",
     *   description="This API checks if there are any live or in-review statements submitted after a given statement ID.",
     *   operationId="postStatementCount",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="Provide topic number, camp number, and statement ID",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               @OA\Property(
     *                   property="topic_num",
     *                   description="Topic number is required",
     *                   type="integer",
     *                   example=1
     *               ),
     *               @OA\Property(
     *                   property="camp_num",
     *                   description="Camp number is required",
     *                   type="integer",
     *                   example=2
     *               ),
     *               @OA\Property(
     *                   property="statement_id",
     *                   description="Statement ID to check for newer submissions",
     *                   type="integer",
     *                   example=10
     *               )
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *           @OA\Property(property="post_changes_count", type="integer", example=3)
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Validation error or exception",
     *       @OA\JsonContent(
     *           @OA\Property(property="error", type="string", example="Invalid request data")
     *       )
     *   )
     * )
     */

    public function postStatementCount(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getPostStatementCountValidationRules(), $this->validationMessages->getPostStatementCountValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        $all = $request->all();
        try {
            /**
             * Checking on submititon of draft, if there are any other live and in_review statements then it will show submittion popup and ask for confirmation
             */
            $postChanges = Statement::where([
                'topic_num' => $all['topic_num'],
                'camp_num' => $all['camp_num'],
                'objector_nick_id' => null,
                'is_draft' => 0,
            ])->where('id', '>', $all['statement_id'])->count();

            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), ['post_changes_count' => $postChanges], '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

        /**
     * @OA\Post(
     *   path="/get-statement-comparison",
     *   tags={"Statement"},
     *   summary="Compare two statements",
     *   description="This API compares two statements based on provided IDs, topic number, and camp number.",
     *   operationId="getStatementComparison",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="Request Body JSON Parameters",
     *       @OA\JsonContent(
     *           required={"ids", "topic_num", "camp_num", "compare"},
     *           @OA\Property(
     *               property="ids",
     *               type="array",
     *               description="Array of statement IDs to compare",
     *               @OA\Items(type="integer", example=818)
     *           ),
     *           @OA\Property(
     *               property="topic_num",
     *               type="integer",
     *               description="Topic number",
     *               example=116
     *           ),
     *           @OA\Property(
     *               property="camp_num",
     *               type="integer",
     *               description="Camp number",
     *               example=1
     *           ),
     *           @OA\Property(
     *               property="compare",
     *               type="string",
     *               description="Comparison type (e.g., statement, summary)",
     *               example="statement"
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *           @OA\Property(property="comparison_result", type="string", example="Statements are 85% similar."),
     *           @OA\Property(property="differences", type="array", @OA\Items(type="string", example="Sentence X is different in Statement A"))
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Error message",
     *       @OA\JsonContent(
     *           @OA\Property(property="error", type="string", example="Invalid statement IDs provided.")
     *       )
     *   )
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
                $campStatement =  Topic::whereIn('id', $request->ids)->with('tags:id,title')->get();

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
                        'namespace' => Namespaces::find($val->namespace_id)->label,
                        'is_rank_hidden' => $val->is_rank_hidden,
                        'tags' => $val->tags->makeHidden(['pivot']),
                    );
                }
                $filter['topicNum'] = $request->topic_num;
                $filter['campNum'] = $request->camp_num;
                $filter['asOf'] = "";
                $filter['asOfDate'] = "";
                $liveStatement = Topic::getLiveTopic($request->topic_num, $request->asof ?? "default");
                $liveStatement->tags->makeHidden(['pivot']);
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
                        'parent_camp_name' => Camp::where('camp_num', $val->parent_camp_num)->where('topic_num', $val->topic_num)->where('grace_period', 0)->whereNull('objector_nick_id')->where('go_live_time', '<', Carbon::now()->timestamp)->latest('submit_time')->first()->camp_name ?? "",
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
                    $statement['liveStatement']['parent_camp_name'] = Camp::where('camp_num', $liveStatement->parent_camp_num)->where('topic_num', $liveStatement->topic_num)->where('grace_period', 0)->whereNull('objector_nick_id')->where('go_live_time', '<', Carbon::now()->timestamp)->latest('submit_time')->first()->camp_name ?? "";
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
     * @OA\POST(
     *   path="/parse-camp-statement",
     *   tags={"Statement"},
     *   summary="Parse a string using wiki parser",
     *   description="This API parses a string through the wiki parser and returns the parsed result.",
     *   operationId="wiki-parser",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="String to be parsed",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               required={"value"},
     *               @OA\Property(
     *                   property="value",
     *                   description="String to be parsed",
     *                   type="string",
     *                   example="'''Bold Text'''"
     *               )
     *           )
     *       )
     *   ), 
     *   @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *           @OA\Property(property="parsed_value", type="string", example="<b>Bold Text</b>")
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Error message",
     *       @OA\JsonContent(
     *           @OA\Property(property="error", type="string", example="Invalid input")
     *       )
     *   )
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

    private function createOrUpdateStatement($all)
    {
        $goLiveTime = time();

        if ($draftId = Statement::getDraftRecord($all['topic_num'], $all['camp_num'], [$all['nick_name']])) {
            Statement::find($draftId)->delete();
        }

        $statement = new Statement();
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
            // $statement->submit_time = time();
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

}

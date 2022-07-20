<?php

namespace App\Http\Controllers;

use stdClass;
use Exception;
use App\Models\Camp;
use App\Facades\Util;
use App\Models\Topic;
use App\Models\Support;
use App\Models\Nickname;
use App\Models\Statement;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Helpers\ResourceInterface;
use App\Helpers\ResponseInterface;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use App\Http\Request\ValidationMessages;
use App\Library\wiki_parser\wikiParser as wikiParser;
use App\Library\General;
use App\Jobs\ObjectionToSubmitterMailJob;


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
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $filter['topicNum'] = $request->topic_num;
        $filter['asOf'] = $request->as_of;
        $filter['asOfDate'] = $request->as_of_date;
        $filter['campNum'] = $request->camp_num;
        $statement = [];
        try {
            $campStatement =  Statement::getLiveStatement($filter);
            if ($campStatement) {
                $WikiParser = new wikiParser;
                $campStatement->parsed_value = $WikiParser->parse($campStatement->value);
                $campStatement->submitter_nick_name = $campStatement->submitterNickName->nick_name;
                $statement[] = $campStatement;
                $indexes = ['id', 'value', 'parsed_value', 'note', 'go_live_time', 'submit_time', 'submitter_nick_name'];
                $statement = $this->resourceProvider->jsonResponse($indexes, $statement);
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $statement, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(path="/get-statement-history",
     *   tags={"Statement"},
     *   summary="get camp newsfeed",
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
        try {
            $response->topic = Camp::getAgreementTopic($filter);
            $response->liveCamp = Camp::getLiveCamp($filter);
            $response->parentCamp = Camp::campNameWithAncestors($response->liveCamp, $filter);
            $statement_query = Statement::where('topic_num', $filter['topicNum'])->where('camp_num', $filter['campNum'])->latest('submit_time');
            $campLiveStatement =  Statement::getLiveStatement($filter);
            if ($request->user()) {
                $nickNames = Nickname::personNicknameArray();
                $submitTime = $campLiveStatement ? $campLiveStatement->submit_time : null;
                $response->ifIamSupporter = Support::ifIamSupporter($filter['topicNum'], $filter['campNum'], $nickNames, $submitTime);
                $response->ifSupportDelayed = Support::ifIamSupporter($filter['topicNum'], $filter['campNum'], $nickNames, $submitTime,  true);
                $response = Statement::statementHistory($statement_query, $response, $filter,  $campLiveStatement, $request);
            } else {
                $response = Statement::statementHistory($statement_query, $response, $filter,  $campLiveStatement, $request);
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $response, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Get(path="/edit-camp-statement/{id}",
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
     *   @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="get a camp statment from this id",
     *         @OA\Schema(
     *              type="integer"
     *         ) 
     *    ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */
    public function editStatement($id)
    {
        try {
            $statement = Statement::where('id', $id)->first();
            if ($statement) {
                $filter['topicNum'] = $statement->topic_num;
                $filter['campNum'] = $statement->camp_num;
                $filter['asOf'] = 'default';
                $topic = Camp::getAgreementTopic($filter);
                $camp = Camp::getLiveCamp($filter);
                $parentCampNum = isset($camp->parent_camp_num) ? $camp->parent_camp_num : 0;
                $parentCamp = Camp::campNameWithAncestors($camp, $filter);
                $nickName = Nickname::topicNicknameUsed($statement->topic_num);
                $WikiParser = new wikiParser;
                $statement->parsed_value = $WikiParser->parse($statement->value);
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
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $response, '');
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
        // $validationErrors = $validate->validate($request, $this->rules->getStatementStoreValidationRules(), $this->validationMessages->getStatementStoreValidationMessages());
        // if ($validationErrors) {
        //     return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        // }
        $all = $request->all();
        $filters['topicNum'] = $all['topic_num'];
        $filters['campNum'] = $all['camp_num'];
        $filters['asOf'] = 'default';
        $eventType = $all['event_type'];
        try {
            $totalSupport =  Support::getAllSupporters($all['topic_num'], $all['camp_num'], 0);
            $loginUserNicknames =  Nickname::personNicknameIds();
            $nickNames = Nickname::personNicknameArray();
            $ifIamSingleSupporter = Support::ifIamSingleSupporter($all['topic_num'], $all['camp_num'], $nickNames);
            if (preg_match('/\bcreate\b|\bupdate\b/', $eventType )) {
                $statement = self::createOrUpdateStatement($all);
                $message = trans('message.success.statement_create');
            } else {
                  ($eventType == 'edit') ? ($statement = self::editUpdatedStatement($all) and $message = trans('message.success.statement_update') ) : ($statement = self::objectStatement($all) and $message = trans('message.success.statement_object'));
            }
            $statement->grace_period = in_array($all['submitter'], $loginUserNicknames) ? 0 : 1;
            if ($all['camp_num'] > 1) {
                if (!$totalSupport || $ifIamSingleSupporter || ($totalSupport && in_array($all['submitter'], $loginUserNicknames))) {
                    $statement->grace_period = 0;
                } else {
                    $statement->grace_period = 1;
                }
            } elseif ($all['camp_num'] == 1 && $ifIamSingleSupporter) {
                $statement->grace_period = 0;
            }

            if (!$ifIamSingleSupporter) {
                $statement->go_live_time = strtotime(date('Y-m-d H:i:s', strtotime('+1 days')));
                $statement->grace_period = 1;
            }

            $statement->save();
            $livecamp = Camp::getLiveCamp($filters);
            $link = config('global.APP_URL_FRONT_END') . '/statement/history/' . $statement->topic_num . '/' . $statement->camp_num;

            if ($eventType == "create" && $statement->grace_period == 0) {
                $this->createdStatementNotification($livecamp, $link, $statement);
            } else if ($eventType == "objection") {
                $this->objectedStatementNotification($all, $livecamp, $link, $statement);
            }

            return $this->resProvider->apiJsonResponse(200, $message, '', '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    private function createOrUpdateStatement($all)
    {
        $goLiveTime = time();
        $statement = new Statement();
        $statement->value = $all['statement'] ?? "";
        $statement->topic_num = $all['topic_num'];
        $statement->camp_num = $all['camp_num'];
        $statement->note = $all['note'] ?? "";
        $statement->submit_time = strtotime(date('Y-m-d H:i:s'));
        $statement->submitter_nick_id = $all['nick_name'];
        $statement->go_live_time = $goLiveTime;
        $statement->language = 'English';
        $statement->grace_period = 1;
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
        $statement->note = $all['note'] ?? "";
        $statement->submitter_nick_id = $all['nick_name'];
        return $statement;
    }

    private function createdStatementNotification($livecamp, $link, $statement)
    {
        $directSupporter = Support::getDirectSupporter($statement->topic_num, $statement->camp_num);
        $subscribers = Camp::getCampSubscribers($statement->topic_num, $statement->camp_num);
        $dataObject['topic_num'] = $statement->topic_num;
        $dataObject['camp_num'] = $statement->camp_num;
        $dataObject['object'] = $livecamp->topic->topic_name . " / " . $livecamp->camp_name;
        $dataObject['support_camp'] = $livecamp->camp_name;
        $dataObject['go_live_time'] = $statement->go_live_time;
        $dataObject['type'] = 'statement : for camp ';
        $dataObject['typeobject'] = 'statement';
        $dataObject['note'] = $statement->note;
        $nickName = Nickname::getNickName($statement->submitter_nick_id);
        $dataObject['nick_name'] = $nickName->nick_name;
        $dataObject['forum_link'] = 'forum/' . $statement->topic_num . '-statement/' . $statement->camp_num . '/threads';
        $dataObject['subject'] = "Proposed change to statement for camp " . $livecamp->topic->topic_name . " / " . $livecamp->camp_name . " submitted";
        $dataObject['namespace_id'] = (isset($livecamp->topic->namespace_id) && $livecamp->topic->namespace_id)  ?  $livecamp->topic->namespace_id : 1;
        $dataObject['nick_name_id'] = $nickName->id;
        $dataObject['is_live'] = ($statement->go_live_time <=  time()) ? 1 : 0;
        Statement::mailSubscribersAndSupporters($directSupporter, $subscribers, $link, $dataObject);
    }

    private function objectedStatementNotification($all, $livecamp, $link, $statement)
    {
        $user = Nickname::getUserByNickName($all['submitter']);
        $nickName = Nickname::getNickName($all['nick_name']);
        $topicLive = Topic::getLiveTopic($statement->topic_num, ['nofilter' => true]);
        $data['topic_link'] = Util::getTopicCampUrl($statement->topic_num, $statement->camp_num, $topicLive, $livecamp, time());
        $data['type'] = "Camp";
        $data['object'] = $livecamp->topic->topic_name . " / " . $livecamp->camp_name;
        $data['object_type'] = "statement";
        $data['nick_name'] = $nickName->nick_name;
        $data['forum_link'] = 'forum/' . $statement->topic_num . '-statement/' . $statement->camp_num . '/threads';
        $data['subject'] = $data['nick_name'] . " has objected to your proposed change.";
        $data['namespace_id'] = (isset($livecamp->topic->namespace_id) && $livecamp->topic->namespace_id)  ?  $livecamp->topic->namespace_id : 1;
        $data['nick_name_id'] = $nickName->id;
        $data['help_link'] = config('global.APP_URL_FRONT_END') . '/' . General::getDealingWithDisagreementUrl();
        try {
            dispatch(new ObjectionToSubmitterMailJob($user, $link, $data))->onQueue(env('QUEUE_SERVICE_NAME'));
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
            $campStatement =  Statement::whereIn('id', $request->ids)->get();
            if ($campStatement) {
                $WikiParser = new wikiParser;
                $currentTime = time();
                $currentLive = 0;
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
                        'go_live_time' => Util::convertUnixToDateFormat($val->go_live_time),
                        'submit_time' => Util::convertUnixToDateFormat($val->submit_time),
                        'object_time' => Util::convertUnixToDateFormat($val->object_time),
                        'parsed_value' => $WikiParser->parse($val->value),
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
                    $statement['liveStatement']['go_live_time'] = Util::convertUnixToDateFormat($liveStatement->go_live_time);
                    $statement['liveStatement']['submit_time'] = Util::convertUnixToDateFormat($liveStatement->submit_time);
                    $statement['liveStatement']['object_time'] = Util::convertUnixToDateFormat($liveStatement->object_time);
                    $statement['liveStatement']['parsed_value'] = $WikiParser->parse($liveStatement->value);
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
                $statement['latestRevision'] = Util::convertUnixToDateFormat($latestRevision->submit_time);
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $statement, null);
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), null, $e->getMessage());
        }
    }
}

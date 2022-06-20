<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Statement;
use App\Models\Camp;
use App\Models\Nickname;
use App\Models\Support;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Http\Resources\ErrorResource;
use App\Helpers\ResponseInterface;
use App\Helpers\ResourceInterface;
use App\Http\Request\ValidationRules;
use App\Http\Request\ValidationMessages;
use App\Library\wiki_parser\wikiParser as wikiParser;
use stdClass;
use App\Helpers\Util;

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
     *   tags={"Camp"},
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
                $campStatement->go_live_time = Util::convertUnixToDateFormat($campStatement->go_live_time);
                $WikiParser = new wikiParser;
                $campStatement->parsed_value = $WikiParser->parse($campStatement->value);
                $statement[] = $campStatement;
                $indexes = ['id', 'value', 'parsed_value', 'note', 'go_live_time'];
                $statement = $this->resourceProvider->jsonResponse($indexes, $statement);
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $statement, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(path="/get-statement-history",
     *   tags={"Camp"},
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
     *              )
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
        $response = new stdClass();
        $response->statement = [];
        $response->ifIamSupporter = null;
        $response->ifSupportDelayed = null;
        try {
            $response->topic = Camp::getAgreementTopic($filter);
            $response->topic->go_live_time = Util::convertUnixToDateFormat($response->topic->go_live_time);
            $response->topic->submit_time = Util::convertUnixToDateFormat($response->topic->submit_time);
            $response->liveCamp = Camp::getLiveCamp($filter);
            $response->liveCamp->go_live_time = Util::convertUnixToDateFormat($response->liveCamp->go_live_time);
            $response->liveCamp->submit_time = Util::convertUnixToDateFormat($response->liveCamp->submit_time);
            $response->parentCamp = Camp::campNameWithAncestors($response->liveCamp, $filter);
            if ($request->user()) {
                $response = Statement::getHistoryAuthUsers($response, $filter, $request);
            } else {
                $response = Statement::getHistoryUnAuthUsers($response, $filter);
            }
            $indexes = ['statement', 'topic', 'liveCamp', 'parentCamp', 'ifSupportDelayed', 'ifIamSupporter'];
            $data[0] = $response;
            $data = $this->resourceProvider->jsonResponse($indexes, $data);
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $data, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Get(path="/edit-camp-statement/{id}",
     *   tags={"Camp"},
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
                $statement->go_live_time = Util::convertUnixToDateFormat($statement->go_live_time);
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
     *   tags={"Camp"},
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
     *               @OA\Property(
     *                   property="parent_camp_num",
     *                   description="Parent camp number of camp where adding statement",
     *                   required=true,
     *                   type="integer",
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
     *                   property="objection",
     *                   description="True if user want to object a statement",
     *                   required=false,
     *                   type="boolean",
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
        $all = $request->all();
        $filters['topicNum'] = $all['topic_num'];
        $filters['campNum'] = $all['camp_num'];
        $filters['asOf'] = 'default';
        $go_live_time = time();
        try {
            $totalSupport =  Support::getAllSupporters($all['topic_num'], $all['camp_num'], 0);
            $loginUserNicknames =  Nickname::personNicknameIds();
            $statement = new Statement();
            $statement->value = isset($all['statement']) ? $all['statement'] : "";
            $statement->topic_num = $all['topic_num'];
            $statement->camp_num = $all['camp_num'];
            $statement->note = isset($all['note']) ? $all['note'] : "";
            $statement->submit_time = strtotime(date('Y-m-d H:i:s'));
            $statement->submitter_nick_id = $all['nick_name'];
            $statement->go_live_time = $go_live_time;
            $statement->language = 'English';
            $statement->grace_period = 1;
            $message =  trans('message.success.statement_create');
            $nickNames = Nickname::personNicknameArray();
            $ifIamSingleSupporter = Support::ifIamSingleSupporter($all['topic_num'], $all['camp_num'], $nickNames);
            if (!$ifIamSingleSupporter) {
                $statement->go_live_time = strtotime(date('Y-m-d H:i:s', strtotime('+1 days')));
                $go_live_time = $statement->go_live_time;
                $statement->grace_period = 1;
            }
            if (isset($all['objection']) && $all['objection'] == 1) {
                $message = trans('message.success.statement_object');
                $statement = Statement::where('id', $all['statement_id'])->first();
                $statement->objector_nick_id = $all['nick_name'];
                $statement->object_reason = $all['objection_reason'];
                $statement->go_live_time = $go_live_time;
                $statement->object_time = time();
                $statement->grace_period = 0;
            }
            $statement->grace_period = in_array($all['submitter'], $loginUserNicknames) ? 0 : 1;
            if ($all['camp_num'] > 1) {
                if ($totalSupport <= 0) {
                    $statement->grace_period = 0;
                } else if ($totalSupport > 0 && in_array($all['submitter'], $loginUserNicknames)) {
                    $statement->grace_period = 0;
                } else if ($ifIamSingleSupporter) {
                    $statement->grace_period = 0;
                } else {
                    $statement->grace_period = 1;
                }
            }
            if ($all['camp_num'] == 1 && $ifIamSingleSupporter) {
                $statement->grace_period = 0;
            }
            $statement->save();
            return $this->resProvider->apiJsonResponse(200, $message, '', '');
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
                foreach ($campStatement as $val) {
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
                    );
                }
                $filter['topicNum'] = $request->topic_num;
                $filter['campNum'] = $request->camp_num;
                $filter['asOf']="";
                $filter['asOfDate']="";
                $liveStatement=  Statement::getLiveStatement($filter);
                $statement['liveStatement'] = $liveStatement;
                if(isset($liveStatement)){
                    $statement['liveStatement']['go_live_time'] = Util::convertUnixToDateFormat($liveStatement->go_live_time);
                    $statement['liveStatement']['submit_time'] = Util::convertUnixToDateFormat($liveStatement->submit_time);
                    $statement['liveStatement']['object_time'] = Util::convertUnixToDateFormat($liveStatement->object_time);
                    $statement['liveStatement']['parsed_value'] = $WikiParser->parse($liveStatement->value);
                    $statement['liveStatement']['submitter_nick_name'] = Nickname::getUserByNickId($liveStatement->submitter_nick_id);
                }

            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $statement, null);
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), null, $e->getMessage());
        }
    }
}

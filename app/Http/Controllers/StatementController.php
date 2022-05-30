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
                $campStatement->go_live_time = date('m/d/Y, h:i:s A', $campStatement->go_live_time);
                $WikiParser = new wikiParser;
                $campStatement->parsed_value = $WikiParser->parse($campStatement->value);
                $statement[] = $campStatement;
                $indexs = ['id', 'value', 'parsed_value', 'note', 'go_live_time'];
                $statement = $this->resourceProvider->jsonResponse($indexs, $statement);
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
            $response->topic->go_live_time = date('m/d/Y, h:i:s A', $response->topic->go_live_time);
            $response->topic->submit_time = date('m/d/Y, h:i:s A', $response->topic->submit_time);
            $response->liveCamp = Camp::getLiveCamp($filter);
            $response->liveCamp->go_live_time = date('m/d/Y, h:i:s A', $response->liveCamp->go_live_time);
            $response->liveCamp->submit_time = date('m/d/Y, h:i:s A', $response->liveCamp->submit_time);
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

    public function editStatement($id)
    {
        $statement = [];
        try {
            $statement = Statement::where('id', $id)->first();
            if ($statement) {
                $filter['topicNum'] = $statement->topic_num;
                $filter['campNum'] = $statement->camp_num;
                $filter['asOf'] = 'default';
                $topic = Camp::getAgreementTopic($filter);
                $camp = Camp::getLiveCamp($filter);
                $parentcampnum = isset($camp->parent_camp_num) ? $camp->parent_camp_num : 0;
                $parentcamp = Camp::campNameWithAncestors($camp, $filter);
                $nickNames = Nickname::topicNicknameUsed($statement->topic_num);
                $statement->go_live_time = date('m/d/Y, h:i:s A', $statement->go_live_time);
                $WikiParser = new wikiParser;
                $statement->parsed_value = $WikiParser->parse($statement->value);
                $response[0] = new stdClass();
                $response[0]->statement = $statement;
                $response[0]->topic = $topic;
                $response[0]->parent_camp = $parentcamp;
                $response[0]->nick_names = $nickNames;
                $response[0]->parentcampnum = $parentcampnum;
                $indexs = ['statement', 'topic', 'parent_camp', 'nick_names', 'parentcampnum'];
                $response = $this->resourceProvider->jsonResponse($indexs, $response);
                $response=$response[0];
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $response, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    public function storeStatement(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getStatementStoreValidationRules(), $this->validationMessages->getStatementStoreValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $statement = [];
        try {
            $all = $request->all();
            $filters['topicNum']=$all['topic_num'];
            $filters['campNum']=$all['camp_num'];
            $filters['asOf']='default';
            $totalSupport =  Support::getAllSupporters($all['topic_num'], $all['camp_num'],0);
            $loginUserNicknames =  Nickname::personNicknameIds();
            $statement = new Statement();
            $statement->value = isset($all['statement']) ? $all['statement'] : "";
            $statement->topic_num = $all['topic_num'];
            $statement->camp_num = $all['camp_num'];
            $statement->note = isset($all['note']) ? $all['note'] : "";
            $statement->submit_time = strtotime(date('Y-m-d H:i:s'));
            $statement->submitter_nick_id = $all['nick_name'];
            $go_live_time=time();
            $statement->go_live_time = $go_live_time;
            $statement->language = 'English';
            $statement->grace_period = 1;
            $eventtype = "create";
            $message =  trans('message.success.statement_create');
            $nickNames = Nickname::personNicknameArray();
            $ifIamSingleSupporter = Support::ifIamSingleSupporter($all['topic_num'], $all['camp_num'], $nickNames);
            if (isset($all['camp_num'])) {
                if (!$ifIamSingleSupporter) {
                    $statement->go_live_time = strtotime(date('Y-m-d H:i:s', strtotime('+1 days')));
                    $go_live_time = $statement->go_live_time;
                }
                if (isset($all['objection']) && $all['objection'] == 1) {
                    $message = trans('message.success.statement_object');
                    $statement = Statement::where('id', $all['statement_id'])->first();
                    $eventtype = "OBJECTION";
                    $statement->objector_nick_id = $all['nick_name'];
                    $statement->object_reason = $all['objection_reason'];
                    $statement->go_live_time = $go_live_time;
                    $statement->object_time = time();
                }
            } 
            if($all['camp_num']) {
                if(in_array($all['submitter'] , $loginUserNicknames)  ){
                    $statement->grace_period = 0;
                }
                else {
                    $statement->grace_period = 1;
                }
            } 
            if($all['camp_num'] > 1) {
                if($totalSupport <= 0){
                    $statement->grace_period = 0;
                } else if($totalSupport > 0 && in_array($all['submitter'] , $loginUserNicknames)){
                    $statement->grace_period = 0;
                } else if($ifIamSingleSupporter) {
                    $statement->grace_period = 0;
                } else{
                    $statement->grace_period = 1;
                }
            }  
            if(isset($all['camp_num']) && isset($all['topic_num'])) {
                if($all['camp_num'] == 1 && $ifIamSingleSupporter) {
                    $statement->grace_period = 0;
                } 
            }
            if(isset($all['camp_num']) && isset($all['objection'])) {
                if($all['objection']) {
                    $statement->grace_period = 0;
                } 
            }
            if (!$ifIamSingleSupporter) {
                $statement->grace_period = 1;
            }
            $statement->save();
            if ($eventtype == "create") {
                try{
                }catch(\Swift_TransportException $e){
                } 
             } else if ($eventtype == "OBJECTION") {
                try{
                }catch(\Swift_TransportException $e){
                } 
            } 
            return $this->resProvider->apiJsonResponse(200, $message, '', '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}

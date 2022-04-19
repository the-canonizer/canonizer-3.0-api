<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Statement;
use App\Models\Nickname;
use App\Models\Support;
use App\Models\Camp;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Http\Resources\ErrorResource;
use App\Helpers\ResponseInterface;
use App\Helpers\ResourceInterface;
use App\Http\Request\ValidationRules;
use App\Http\Request\ValidationMessages;
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
                $campStatement->go_live_time = date('m/d/Y, H:i:s A', $campStatement->go_live_time);
                $statement[] = $campStatement;
                $indexs = ['id', 'value', 'note', 'go_live_time'];
                $statement = $this->resourceProvider->jsonResponse($indexs, $statement);
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $statement, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), $e->getMessage(), '');
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
        $filter['type'] = isset($request->type) ? $request->type :'all';
        $response = new stdClass();
        $response->statement = [];
        $response->ifIamSupporter = null;
        $response->ifSupportDelayed = null;
        $currentTime = time();
        $currentLive = 0;
        $nickNames = null;
        try {
            $response->topic = Camp::getAgreementTopic($filter);
            $response->liveCamp = Camp::getLiveCamp($filter);
            $response->parentCamp = Camp::campNameWithAncestors($response->liveCamp, $filter);
            $statementHistory = Statement::getHistory($filter['topicNum'], $filter['campNum']);
            $submitTime = (count($statementHistory)) ? $statementHistory[0]->submit_time : null;
            if ($request->user()) {
                $nickNames = Nickname::personNicknameArray();
                $response->ifIamSupporter = Support::ifIamSupporter($filter['topicNum'], $filter['campNum'], $nickNames, $submitTime);
                $response->ifSupportDelayed = Support::ifIamSupporter($filter['topicNum'], $filter['campNum'], $nickNames, $submitTime, $delayed = true);
                if (count($statementHistory) > 0) {
                    foreach ($statementHistory as $val) {
                        $submitterUserID = Nickname::getUserIDByNickNameId($val->submitter_nick_id);
                        $submittime = $val->submit_time;
                        $starttime = time();
                        $endtime = $submittime + 60 * 60;
                        $interval = $endtime - $starttime;
                        if ($val->objector_nick_id !== NULL) {
                            $val['status'] = "objected";
                        } elseif ($currentTime < $val->go_live_time && $currentTime >= $val->submit_time) {
                            $val['status'] = "in_review";
                        } elseif ($currentLive != 1 && $currentTime >= $val->go_live_time) {
                            $currentLive = 1;
                            $val['status'] = "live";
                        } else {
                            $val['status'] = "old";
                        }
                        if ($interval > 0 && $val->grace_period > 0  && $request->user()->id != $submitterUserID) {
                            continue;
                        } else {
                            ($filter['type']==$val['status'] || $filter['type']=='all') ? array_push($response->statement, $val) : null;    
                        }
                    }
                }
            } else {
                $statementHistory = Statement::getHistory($filter['topicNum'], $filter['campNum']);
                if (count($statementHistory) > 0) {
                    foreach ($statementHistory as $arr) {
                        $submittime = $arr->submit_time;
                        $starttime = $currentTime;
                        $endtime = $submittime + 60 * 60;
                        $interval = $endtime - $starttime;
                    }
                    if (($arr->grace_period < 1 && $interval < 0) || $currentTime > $arr->go_live_time) {
                        $arr['status'] = "live";
                        array_push($response->statement, $arr);
                    }
                }
            }
            $indexes = ['statement', 'topic', 'liveCamp', 'parentCamp', 'ifSupportDelayed', 'ifIamSupporter'];
            $data[0]=$response;
            $data = $this->resourceProvider->jsonResponse($indexes, $data);
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $data, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), $e->getMessage(), '');
        }
    }
}

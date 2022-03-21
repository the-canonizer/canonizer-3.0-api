<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Camp;
use App\Models\Statement;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Http\Resources\ErrorResource;

class StatementController extends Controller
{

   /**
     * @OA\Post(path="/get-camp-statement",
     *   tags={"statement"},
     *   summary="get camp statement",
     *   description="Used to get statement.",
     *   operationId="getCampStatement",
     *   @OA\Parameter(
     *     name="topic_num",
     *     required=true,
     *     in="query",
     *     description="topic number is required",
     *     @OA\Schema(
     *         type="Integer"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="camp_num",
     *     required=true,
     *     in="query",
     *     description="Camp number is required",
     *     @OA\Schema(
     *         type="Integer"
     *     )
     *   ), 
     *   @OA\Parameter(
     *     name="as_of",
     *     required=false,
     *     in="query",
     *     description="As of filter type",
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="as_of_date",
     *     required=false,
     *     in="query",
     *     description="As of filter date",
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */


    
    public function getStatement(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getStatementValidationRules(), $this->validationMessages->getStamenetValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $filter['topicNum'] = $request->topic_num;
        $filter['asOf'] = $request->as_of;
        $filter['asOfDate'] = $request->as_of_date;
        $filter['campNum'] = $request->camp_num;
        $statement = [];
        $topic = Camp::getAgreementTopic($filter);
        $camp = Camp::getLiveCamp($filter);
        if (!empty($camp) && !empty($topic)) {
            $parentcamp = Camp::campNameWithAncestors($camp, '', $topic->topic_name,$filter);
        } else {
            $parentcamp = "N/A";
        }
        try {
            $campStatement =  Statement::getLiveStatement($filter);
            if ($campStatement) {
                $statement[] = $campStatement;
                $statement = $this->resourceProvider->jsonResponse('Statement', $statement);
                $statement[0]['parentCamps']=$parentcamp;
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $statement, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), $e->getMessage(), '');
        }
    }
}

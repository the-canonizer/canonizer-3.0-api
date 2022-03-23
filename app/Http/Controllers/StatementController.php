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
     *   @OA\RequestBody(
     *       required=true,
     *       description="Get topics",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *                 @OA\Property(
     *                     property="topic_num",
     *                     description="topic number is required",
     *                     required=true,
     *                     type="integer",
     *                     format="int32"
     *                 ),
     *                 @OA\Property(
     *                     property="camp_num",
     *                     description="Camp number is required",
     *                     required=true,
     *                     type="integer",
     *                     format="int32"
     *                 ),
     *                 @OA\Property(
     *                     property="as_of",
     *                     description="As of filter type",
     *                     required=false,
     *                     type="string",
     *                 ),
     *                  @OA\Property(
     *                     property="as_of_date",
     *                     description="As of filter date",
     *                     required=false,
     *                     type="string",
     *                 )
     *            )
     *        )
     *   )
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
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
        $topic = Camp::getAgreementTopic($filter);
        $camp = Camp::getLiveCamp($filter);
        $parentCamp = (!empty($camp) && !empty($topic)) ? Camp::campNameWithAncestors($camp, '', $topic->topic_name,$filter) : 'N/A';
        try {
            $campStatement =  Statement::getLiveStatement($filter);
            if ($campStatement) {
                $statement[] = $campStatement;
                $statement = $this->resourceProvider->jsonResponse('Statement', $statement);
                $statement[0]['parentCamps']=$parentCamp;
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $statement, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), $e->getMessage(), '');
        }
    }
}

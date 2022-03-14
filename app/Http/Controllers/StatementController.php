<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Camp;
use App\Models\Statement;
use App\Helpers\ResponseInterface;
use App\Helpers\ResourceInterface;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Http\Request\ValidationMessages;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;

class StatementController extends Controller
{
    public function __construct(ResponseInterface $respProvider, ResourceInterface $resProvider)
    {
        $this->resourceProvider = $resProvider;
        $this->responseProvider = $respProvider;
        $this->rules = new ValidationRules;
        $this->validationMessages = new ValidationMessages;
    }

    public function getStatement(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getStatementValidateionRules(), $this->validationMessages->getStamenetValidationMessages());
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
            return $this->responseProvider->apiJsonResponse(200, trans('message.success.success'), $statement, '');
        } catch (Exception $e) {
            return $this->responseProvider->apiJsonResponse(400, trans('message.error.exception'), $e->getMessage(), '');
        }
    }
}

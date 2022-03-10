<?php

namespace App\Http\Controllers;

use App\Models\Statement;
use App\Helpers\ResponseInterface;
use App\Helpers\ResourceInterface;
use App\Http\Request\StatementRequest;

class StatementController extends Controller
{
    public function __construct(ResponseInterface $respProvider, ResourceInterface $resProvider)
    {
        $this->resourceProvider = $resProvider;
        $this->responseProvider = $respProvider;
    }

    public function getStatement(StatementRequest $request)
    {
        $filter['topicNum'] = $request->topic_num;
        $filter['asOf'] = $request->as_of;
        $filter['asOfDate'] = $request->as_of_date;
        $filter['campNum'] = $request->camp_num;
        $statement = [];
        try {
            $campStatement =  Statement::getLiveStatement($filter);
            if ($campStatement) {
                $statement[] = $campStatement;
                $statement = $this->resourceProvider->jsonResponse('Statement', $statement);
            }
            return $this->responseProvider->apiJsonResponse(200, trans('message.success.success'), $statement, '');
        } catch (Exception $e) {
            return $this->responseProvider->apiJsonResponse(400, trans('message.error.exception'), $e->getMessage(), '');
        }
    }
}

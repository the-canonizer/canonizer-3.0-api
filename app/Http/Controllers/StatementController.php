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

    public function get(StatementRequest $request)
    {
        $topicnum = $request->topicnum;
        $filter['asof'] = $request->asof;
        $filter['asofdate'] = $request->asofdate;
        $parentcampnum = $request->parentcampnum;
        $statement = [];
        try {
            $campStatement =  Statement::getLiveStatement($topicnum, $parentcampnum, $filter);
            if ($campStatement) {
                $statement[] = $campStatement;
                $statement = $this->resourceProvider->jsonResponse('Statement', $statement);
            }
            return $this->responseProvider->apiJsonResponse(200, config('message.success.success'), $statement, '');
        } catch (Exception $e) {
            return $this->responseProvider->apiJsonResponse(400, config('message.error.exception'), $e->getMessage(), '');
        }
    }
}

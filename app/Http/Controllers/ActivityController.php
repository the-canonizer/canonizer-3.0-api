<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Models\ActivityUser;
use App\Http\Request\Validate;
use App\Facades\Util;
use App\Helpers\ResponseInterface;
use App\Helpers\ResourceInterface;
use App\Http\Request\ValidationRules;
use App\Http\Request\ValidationMessages;
use App\Http\Resources\ErrorResource;

class ActivityController extends Controller
{
    public function __construct(ResponseInterface $respProvider, ResourceInterface $resProvider, ValidationRules $rules, ValidationMessages $validationMessages)
    {
        $this->rules = $rules;
        $this->validationMessages = $validationMessages;
        $this->resourceProvider  = $resProvider;
        $this->resProvider = $respProvider;
    }

    public function getActivityLog(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getActivityLogValidationRules(), $this->validationMessages->getActivityLogValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $perPage = !empty($request->per_page) ? $request->per_page : config('global.per_page');
        $logType=$request->log_type;
        try {
            $log = ActivityUser::whereHas('Activity', function ($query) use ($logType) { 
                $query->where('log_name', $logType);
            })->with(['Activity' => function ($query) use ( $logType ) {
                $query->where('log_name', $logType);
            }])->where('user_id',$request->user()->id)->latest()->paginate($perPage);
            $log = Util::getPaginatorResponse($log);
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $log, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ActivityLogger;
use App\Models\ActivityLog;
use App\Models\ActivityUser;
use App\Http\Request\Validate;
use App\Facades\Util;

class ActivityController extends Controller
{
    public function getActivityLog(Request $request, Validate $validate)
    {
        // $validationErrors = $validate->validate($request, $this->rules->getAdsValidationRules(), $this->validationMessages->getAdsValidationMessages());
        // if ($validationErrors) {
        //     return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        // }
        $per_page = !empty($request->per_page) ? $request->per_page : config('global.per_page');

        try {
            $log = ActivityUser::whereHas('Activity', function ($query) use ($per_page) { 
                $query->where('log_name', "News Added");
            })->with(['Activity' => function ($query) use ( $per_page ) {
                $query->where('log_name', "News Added");
            }])->where('user_id',$request->user()->id)->latest()->paginate($per_page);
            $log = Util::getPaginatorResponse($log);
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $log, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}



//with(['Activity'])
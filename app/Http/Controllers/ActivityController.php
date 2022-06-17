<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Models\ActivityUser;
use App\Models\ActivityLog;
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

   /**
     * @OA\Post(path="/get-activity-log",
     *   tags={"Activity Log"},
     *   summary="Get activity log",
     *   description="This is used to get activity log.",
     *   operationId="GetActivityLog",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *   ),
     *   @OA\RequestBody(
     *       required=true,
     *       description="Get activity log",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               @OA\Property(
     *                   property="per_page",
     *                   description="Number of records per page",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="page",
     *                   description="page number",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="log_type",
     *                   description="Type of log",
     *                   required=true,
     *                   type="string",
     *               )
     *           )
     *       )  
     *    ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message"),
     *   @OA\Response(response=401, description="Unauthenticated")
     *  )
     */

    public function getActivityLog(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getActivityLogValidationRules(), $this->validationMessages->getActivityLogValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $perPage = $request->per_page ?? config('global.per_page');
        $logType=$request->log_type;
        try {
            $log = ActivityUser::whereHas('Activity', function ($query) use ($logType) { 
                $query->where('log_name', $logType);
            })->with('Activity')->where('user_id',$request->user()->id)->latest()->paginate($perPage);
            $log = Util::getPaginatorResponse($log);
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $log, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    public function getCampActivityLog(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getCampActivityLogValidationRules(), $this->validationMessages->getCampActivityLogValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {
            $log = ActivityLog::whereJsonContains('properties->topic_num', (int) $request->topic_num)->whereJsonContains('properties->camp_num', (int) $request->camp_num)->latest()->take(10)->get();
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $log, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}
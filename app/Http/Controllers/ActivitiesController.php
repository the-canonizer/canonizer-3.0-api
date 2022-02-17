<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivitiesController extends Controller
{

    /**
     * Get the recent activites.
     *
     * @return \Illuminate\Http\Response
     */

    public function getRecentActivities(Request $request)
    {
        $logType = $request->input('log_type', 'BaseModel');
        $pageSize = $request->input('page_size', 20);
        $pageNumber = $request->input('page_number', 1);
        $skip = ($pageNumber - 1) * $pageSize;

        try {
            $activties = Activity::where('log_name', $logType)
                ->orderBy('created_at', 'asc')
                ->skip($skip)
                ->limit($pageSize)
                ->get();

            return $this->resProvider->apiJsonResponse(200, config('message.success.success'), $activties, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, config('message.error.exception'), '', $e->getMessage());
        }

    }
}

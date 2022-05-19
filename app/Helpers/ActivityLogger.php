<?php

namespace App\Helpers;

use App\Models\Camp;
use App\Models\ActivityUser;
use App\Helpers\CampForum;

class ActivityLogger
{
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

    public static function logActivity($log_type, $url, $activity, $model, $topic_num, $camp_num, $user)
    {
        $users = [];
        $activityLog = activity($log_type)
            ->performedOn($model)
            ->causedBy($user)
            ->withProperties(['topic_num' => $topic_num, 'camp_num' => $camp_num, 'url' => $url])
            ->log($activity . ' by ' . $user->getUserFullName());
        if ($log_type == 'threads') {
            $users = CampForum::getThreadLogUsers($topic_num, $camp_num);
        } elseif($log_type == 'topic/camps') {
            $users = Camp::getCampSubscribers($topic_num, $camp_num);
        }
        foreach ($users as $user) {
            $activityUser = new ActivityUser();
            $activityUser->activity_id = $activityLog->id;
            $activityUser->user_id = $user;
            $activityUser->save();
        }
    }
}

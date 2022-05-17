<?php

namespace App\Helpers;

use App\Models\Camp;
use App\Models\ActivityUser;

class ActivityLogger
{
    public static function logActivity($log_type, $activity, $model, $topic_num, $camp_num, $user)
    {
        $activityLog = activity($log_type)
            ->performedOn($model)
            ->causedBy($user)
            ->withProperties(['topic_num' => $topic_num, 'camp_num' => $camp_num])
            ->log($activity.' by ' . $user->getUserFullName());
        $subscribers = Camp::getCampSubscribers($topic_num, $camp_num);
        foreach ($subscribers as $subscriber) {
            $activityUser = new ActivityUser();
            $activityUser->activity_id = $activityLog->id;
            $activityUser->user_id = $subscriber;
            $activityUser->save();
        }
    }
}

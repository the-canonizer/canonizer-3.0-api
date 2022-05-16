<?php

namespace App\Helpers;

use App\Models\Camp;
use App\Models\ActivityUser;

class ActivityLogger
{
    public static function logActivity($log_name, $model, $topic_num, $camp_num, $user)
    {
        $activity = activity($log_name)
            ->performedOn($model)
            ->causedBy($user)
            ->withProperties(['topic_num' => $topic_num, 'camp_num' => $camp_num])
            ->log('News added by ' . $user->getUserFullName());
        $subscribers = Camp::getCampSubscribers($topic_num, $camp_num);
        foreach ($subscribers as $subscriber) {
            $activityUser = new ActivityUser();
            $activityUser->activity_id = $activity->id;
            $activityUser->user_id = $subscriber;
            $activityUser->save();
        }
    }
}

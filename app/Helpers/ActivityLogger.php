<?php

namespace App\Helpers;

use App\Models\Camp;
use App\Models\ActivityUser;
use App\Helpers\CampForum;

class ActivityLogger
{
    public static function logActivity($log_type, $url, $activity, $model, $topic_num, $camp_num, $user)
    {
        $users = [];
        $activityLog = activity($log_type)
            ->performedOn($model)
            ->causedBy($user)
            ->withProperties(['topic_num' => $topic_num, 'camp_num' => $camp_num, 'url' => $url])
            ->log($activity . ' by ' . $user->getUserFullName());

        $subscribers = Camp::getCampSubscribers($topic_num, $camp_num);
        $supporters = Camp::getDirectCampSupporterIds($topic_num, $camp_num);
        $users = array_unique(array_merge($subscribers, $supporters));

        foreach ($users as $user) {
            $activityUser = new ActivityUser();
            $activityUser->activity_id = $activityLog->id;
            $activityUser->user_id = $user;
            $activityUser->save();
        }
    }
}

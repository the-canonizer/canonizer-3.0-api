<?php

namespace App\Services;

use App\Models\Camp;
use App\Models\Topic;
use App\Models\Support;
use App\Models\Nickname;
use App\Helpers\CampForum;
use App\Helpers\Helpers;

class CampService
{
    /**
     * Get camp record data.
     *
     * @param int $topicNum
     * @param int $campNum
     * @param array $filter
     * @param mixed $user
     * @return array
     */
    public function getCampRecordData($topicNum, $campNum, $filter, $user = null)
    {
        $camp = [];
        
        $livecamp = Camp::getLiveCamp($filter);
        if ($livecamp) {
            $livecamp->nick_name = $livecamp->nickname->nick_name ?? trans('message.general.nickname_association_absence');
            $parentCamp = Camp::campNameWithAncestors($livecamp, $filter);
            if ($user) {
                $campSubscriptionData = Camp::getCampSubscription($filter, $user->id);
                $livecamp->flag = $campSubscriptionData['flag'];
                $livecamp->subscriptionId = $campSubscriptionData['camp_subscription_data'][0]['subscription_id'] ?? null;
                $livecamp->subscriptionCampName = $campSubscriptionData['camp_subscription_data'][0]['camp_name'] ?? null;
            }
            
            // Optimization: Use cached camps map for parent name lookup
            $campsMap = Camp::getTopicLiveCampsMap($topicNum);
            if ($livecamp->parent_camp_num != null && $livecamp->parent_camp_num > 0 && isset($campsMap[$livecamp->parent_camp_num])) {
                $parentCampName = $campsMap[$livecamp->parent_camp_num]->camp_name;
            } else {
                $parentCampName = null;
            }
            
            // Optimization: Batch fetch nicknames
            $nickIds = [
                $livecamp->camp_about_nick_id,
                $livecamp->submitter_nick_id,
                $livecamp->camp_leader_nick_id
            ];
            $nickIds = array_filter(array_unique($nickIds));
            $nicknames = Nickname::whereIn('id', $nickIds)->get()->keyBy('id');

            $livecamp->camp_about_nick_name = $nicknames[$livecamp->camp_about_nick_id]->nick_name ?? null;
            $livecamp->submitter_nick_name = $nicknames[$livecamp->submitter_nick_id]->nick_name ?? null;
            $livecamp->camp_leader_nick_name = $nicknames[$livecamp->camp_leader_nick_id]->nick_name ?? '';
            $livecamp->nick_name = $livecamp->camp_about_nick_name ?? trans('message.general.nickname_association_absence');

            $livecamp->parent_camp_name = $parentCampName;
            ['is_disabled' => $livecamp->parent_is_disabled, 'is_one_level' => $livecamp->parent_is_one_level] = Camp::checkIfParentCampDisabledSubCampFunctionality($livecamp);
            
            // Format response to match API structure
            $indexs = ['topic_num', 'camp_num', 'camp_name', 'key_words', 'camp_about_url', 'nick_name', 'flag', 'subscriptionId', 'subscriptionCampName', 'parent_camp_name', 'is_disabled', 'is_one_level', 'camp_about_nick_name', 'submitter_nick_name', 'camp_about_nick_id', 'submitter_nick_id', 'note', 'camp_about_url', 'is_archive', 'direct_archive', 'submit_time', 'go_live_time', 'camp_leader_nick_id', 'camp_leader_nick_name', 'parent_is_disabled', 'parent_is_one_level'];
            
            // Manually map fields to avoid ResourceInterface dependency if possible, or use a helper
            // For now, we will just return the object with additional dynamic properties
            // Ideally, we should use a Resource class or a simple array mapping
            
            $campData = [];
            foreach($indexs as $index) {
                $campData[$index] = $livecamp->$index ?? null;
            }
            
            $campData['parentCamps'] = $parentCamp;
            
            if ($filter['asOf'] === 'default') {
                $inReviewChangesCount = Helpers::getChangesCount((new Camp()), $topicNum, $campNum);
                $campData['in_review_changes'] = $inReviewChangesCount;
            }
            
            return $campData;
        }

        return null;
    }
}

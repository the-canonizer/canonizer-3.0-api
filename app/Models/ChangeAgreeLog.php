<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ChangeAgreeLog extends Model
{
    protected $table = 'change_agree_logs';
    public $timestamps = false;

    public static function getAgreedSupporter($topicNum, $campNum, $changeNum, $changeFor, $submitterNickId, $submit_time, $includeExplicitCount = false)
    {

        $agreedSupporters = self::where('topic_num', '=', $topicNum)
            ->where('camp_num', '=', $campNum)
            ->where('change_id', '=', $changeNum)
            ->where('change_for', '=', $changeFor)
            ->get()->pluck('nick_name_id')->toArray();

        if ($submitterNickId > 0 && !in_array($submitterNickId, $agreedSupporters)) {
            $agreedSupporters[] = $submitterNickId;
        }

        if($includeExplicitCount) {
            $nickNames = Nickname::personNicknameArray();
            $additionalFilter = [
                'topicNum' => $topicNum,
                'campNum' => $campNum,
            ];
            $agreedSupporters[] = self::ifIamExplicitSupporterBySubmitTime($additionalFilter, $nickNames, $submit_time, null, true, 'supporters')->pluck('nick_name_id')->toArray();
        }
        
        // $agreedSupporters = Nickname::select('id', 'nick_name')->whereIn('id', $agreedSupporters)->get()->toArray();

        return [$agreedSupporters, count($agreedSupporters)];
    }
}

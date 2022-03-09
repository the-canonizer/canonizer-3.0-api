<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Statement extends Model
{
    protected $table = 'statement';
    public $timestamps = false;
    protected static $tempArray = [];

    const AGREEMENT_CAMP = "Agreement";

    public static function getLiveStatement($topicnum, $campnum, $filter = array())
    {
        if ((isset($filter['asof']) && $filter['asof'] == "default")) {
            return self::where('topic_num', $topicnum)
                ->where('camp_num', $campnum)
                ->where('objector_nick_id', '=', NULL)
                ->where('go_live_time', '<=', time())
                ->orderBy('submit_time', 'desc')
                ->first();
        } else {
            if (isset($filter['asof']) && $filter['asof'] == "review") {
                return self::where('topic_num', $topicnum)
                    ->where('camp_num', $campnum)
                    ->where('objector_nick_id', '=', NULL)
                    ->orderBy('submit_time', 'desc')
                    ->first();
            } else if (isset($filter['asof']) && $filter['asof'] == "bydate") {
                $asofdate = strtotime(date('Y-m-d H:i:s', strtotime($filter['asofdate'])));
                return self::where('topic_num', $topicnum)
                    ->where('camp_num', $campnum)
                    ->where('objector_nick_id', '=', NULL)
                    ->where('go_live_time', '<=', $asofdate)
                    ->orderBy('submit_time', 'desc')
                    ->first();
            } else {
                return self::where('topic_num', $topicnum)
                    ->where('camp_num', $campnum)
                    ->where('objector_nick_id', '=', NULL)
                    ->where('go_live_time', '<=', time())
                    ->orderBy('submit_time', 'desc')
                    ->first();
            }
        }
    }
}

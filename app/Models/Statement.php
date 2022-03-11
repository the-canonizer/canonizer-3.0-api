<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Statement extends Model
{
    protected $table = 'statement';

    public static function getLiveStatement($filter = array())
    {
        $filterName = $filter['asOf'];
        if (!$filterName) {
            $filter['asOf'] = 'others';
        }
        return self::asOfFilter($filter);
    }

    private function defaultAsOfFilter($filter)
    {
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', $filter['campNum'])
            ->where('objector_nick_id', '=', NULL)
            ->where('go_live_time', '<=', time())
            ->orderBy('submit_time', 'desc')
            ->first();
    }

    private function reviewAsofFilter($filter)
    {
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', $filter['campNum'])
            ->where('objector_nick_id', '=', NULL)
            ->orderBy('submit_time', 'desc')
            ->first();
    }

    private function byDateFilter($filter)
    {
        $asofdate = strtotime(date('Y-m-d H:i:s', strtotime($filter['asOfDate'])));
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', $filter['campNum'])
            ->where('objector_nick_id', '=', NULL)
            ->where('go_live_time', '<=', $asofdate)
            ->orderBy('submit_time', 'desc')
            ->first();
    }

    private function otherFilter($filter)
    {
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', $filter['campNum'])
            ->where('objector_nick_id', '=', NULL)
            ->where('go_live_time', '<=', time())
            ->orderBy('submit_time', 'desc')
            ->first();
    }

    private function asOfFIlter($filter)
    {
        $asOfFilter = [
            'default' => self::defaultAsOfFilter($filter),
            'review'  => self::reviewAsofFilter($filter),
            'bydate'  => self::byDateFilter($filter),
            'others'  => self::otherFilter($filter)
        ];
        return $asOfFilter[$filter['asOf']];
    }
}

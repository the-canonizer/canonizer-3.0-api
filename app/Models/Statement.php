<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Library\wiki_parser\wikiParser as wikiParser;
use App\Models\Nickname;
use App\Models\Support;

class Statement extends Model
{
    protected $table = 'statement';

    public static function getLiveStatement($filter = array())
    {
        $filterName = $filter['asOf'];
        if (!$filterName) {
            $filter['asOf'] = 'default';
        }
        return self::liveStatementAsOfFilter($filter);
    }

    public static function defaultAsOfFilter($filter)
    {
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', $filter['campNum'])
            ->where('objector_nick_id', '=', NULL)
            ->where('go_live_time', '<=', time())
            ->orderBy('submit_time', 'desc')
            ->first();
    }

    public static function reviewAsofFilter($filter)
    {
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', $filter['campNum'])
            ->where('objector_nick_id', '=', NULL)
            ->orderBy('submit_time', 'desc')
            ->first();
    }

    public static function byDateFilter($filter)
    {
        $asofdate = strtotime(date('Y-m-d H:i:s', strtotime($filter['asOfDate'])));
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', $filter['campNum'])
            ->where('objector_nick_id', '=', NULL)
            ->where('go_live_time', '<=', $asofdate)
            ->orderBy('submit_time', 'desc')
            ->first();
    }

    private function liveStatementAsOfFilter($filter)
    {
        $asOfFilter = [
            'default' => self::defaultAsOfFilter($filter),
            'review'  => self::reviewAsofFilter($filter),
            'bydate'  => self::byDateFilter($filter),
        ];
        return $asOfFilter[$filter['asOf']];
    }

    public static function checkIfFileInUse($shortCode = '')
    {
        if ($shortCode) {
            $result = self::where('value', 'like', '%' . $shortCode . '%')->count();
            return ($result > 0) ? true : false;
        }
        return false;
    }

    public static function getHistory($topicnum, $campnum)
    {
        return self::where('topic_num', $topicnum)->where('camp_num', $campnum)->latest('submit_time')->get();
    }

    public function objectorNickName()
    {
        return $this->hasOne('App\Models\Nickname', 'id', 'objector_nick_id');
    }

    public static function getHistoryAuthUsers($response, $filter, $request)
    {
        $currentTime = time();
        $currentLive = 0;
        $statementHistory = self::getHistory($filter['topicNum'], $filter['campNum']);
        $submitTime = (count($statementHistory)) ? $statementHistory[0]->submit_time : null;
        $nickNames = Nickname::personNicknameArray();
        $response->ifIamSupporter = Support::ifIamSupporter($filter['topicNum'], $filter['campNum'], $nickNames, $submitTime);
        $response->ifSupportDelayed = Support::ifIamSupporter($filter['topicNum'], $filter['campNum'], $nickNames, $submitTime, $delayed = true);
        if (count($statementHistory) > 0) {
            foreach ($statementHistory as $val) {
                $submitterUserID = Nickname::getUserIDByNickNameId($val->submitter_nick_id);
                $submittime = $val->submit_time;
                $starttime = time();
                $endtime = $submittime + 60 * 60;
                $interval = $endtime - $starttime;
                $val->objector_nick_name = null;
                switch ($val) {
                    case $val->objector_nick_id !== NULL:
                        $val->status = "objected";
                        $val->objector_nick_name = $val->objectorNickName->nick_name;
                        $val->unsetRelation('objectorNickName');
                        break;
                    case $currentTime < $val->go_live_time && $currentTime >= $val->submit_time:
                        $val->status = "in_review";
                        break;
                    case $currentLive != 1 && $currentTime >= $val->go_live_time:
                        $currentLive = 1;
                        $val->status = "live";
                        break;
                    default:
                        $val->status = "old";
                }
                if ($interval > 0 && $val->grace_period > 0  && $request->user()->id != $submitterUserID) {
                    continue;
                } else {
                    $WikiParser = new wikiParser;
                    $val->parsed_value = $WikiParser->parse($val->value);
                    $val->go_live_time = date('m/d/Y, h:i:s A', $val->go_live_time);
                    $val->submit_time = date('m/d/Y, h:i:s A', $val->submit_time);
                    ($filter['type'] == $val->status || $filter['type'] == 'all') ? array_push($response->statement, $val) : null;
                }
            }
        }
        return $response;
    }
    
    public static function getHistoryUnAuthUsers($response, $filter)
    {
        $currentTime = time();
        $currentLive = 0;
        $statementHistory = self::getHistory($filter['topicNum'], $filter['campNum']);
        if (count($statementHistory) > 0) {
            foreach ($statementHistory as $arr) {
                $submittime = $arr->submit_time;
                $starttime = $currentTime;
                $endtime = $submittime + 60 * 60;
                $interval = $endtime - $starttime;
                if ((($arr->grace_period < 1 && $interval < 0) || $currentTime >= $arr->go_live_time) && $arr->objector_nick_id == NULL && $currentLive != 1) {
                    $currentLive = 1;
                    $arr['status'] = "live";
                    $WikiParser = new wikiParser;
                    $arr->parsed_value = $WikiParser->parse($arr->value);
                    array_push($response->statement, $arr);
                }
            }
        }
        return $response;
    }
}

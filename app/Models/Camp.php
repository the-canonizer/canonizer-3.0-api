<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Camp extends Model
{
    protected $table = 'camp';
    public $timestamps = false;


    public static function campLink($topicNum,$campNum,$title,$campName){
        $title = preg_replace('/[^A-Za-z0-9\-]/', '-', $title);
        $campName = preg_replace('/[^A-Za-z0-9\-]/', '-', $campName);
        $topicId = $topicNum . "-" . $title;
        $campId = $campNum . "-" .$campName;
        $queryString = (app('request')->getQueryString()) ? '?'.app('request')->getQueryString() : "";
        return $link = url('topic/' . $topicId . '/' . $campId .'#statement');
    }

    public static function getAgreementTopic($filter = array())
    {
        $filterName = $filter['asOf'];
        if (!$filterName) {
            $filter['asOf'] = 'default';
        }
        return self::AgreementTopicasOfFilter($filter);
    }

    private function AgreementTopicasOfFIlter($filter)
    {
        $asOfFilter = [
            'default' => self::AgreementTopicdefaultAsOfFilter($filter),
            'review'  => self::AgreementTopicreviewAsofFilter($filter),
            'bydate'  => self::AgreementTopicbyDateFilter($filter),
        ];
        return $asOfFilter[$filter['asOf']];
    }

    public static function AgreementTopicdefaultAsOfFilter($filter)
    {
        return self::select('topic.topic_name', 'topic.namespace_id', 'camp.*', 'namespace.name as namespace_name', 'namespace.name')
            ->join('topic', 'topic.topic_num', '=', 'camp.topic_num')
            ->join('namespace', 'topic.namespace_id', '=', 'namespace.id')
            ->where('topic.topic_num', $filter['topicNum'])->where('camp_name', '=', 'Agreement')
            ->where('camp.objector_nick_id', '=', NULL)
            ->where('topic.objector_nick_id', '=', NULL)
            ->where('camp.go_live_time', '<=', time())
            ->where('topic.go_live_time', '<=', time())
            ->latest('topic.submit_time')->first();
    }

    public static function AgreementTopicreviewAsofFilter($filter)
    {
        return self::select('topic.topic_name', 'topic.namespace_id', 'camp.*', 'namespace.name as namespace_name', 'namespace.name')
            ->join('topic', 'topic.topic_num', '=', 'camp.topic_num')
            ->join('namespace', 'topic.namespace_id', '=', 'namespace.id')
            ->where('camp.topic_num', $filter['topicNum'])->where('camp_name', '=', 'Agreement')
            ->where('camp.objector_nick_id', '=', NULL)
            ->where('topic.objector_nick_id', '=', NULL)
            ->latest('topic.submit_time')->first();
    }

    public static function AgreementTopicbyDateFilter($filter)
    {
        $asofdate = strtotime(date('Y-m-d H:i:s', strtotime($filter['asOfDate'])));
        return self::select('topic.topic_name', 'topic.namespace_id', 'camp.*', 'namespace.name as namespace_name', 'namespace.name')
            ->join('topic', 'topic.topic_num', '=', 'camp.topic_num')
            ->join('namespace', 'topic.namespace_id', '=', 'namespace.id')
            ->where('camp.topic_num', $filter['topicNum'])->where('camp_name', '=', 'Agreement')
            ->where('camp.objector_nick_id', '=', NULL)
            ->where('topic.objector_nick_id', '=', NULL)
            ->where('topic.go_live_time', '<=', $asofdate)
            ->latest('topic.go_live_time')->first();
    }

    public static function getLiveCamp($filter = array())
    {
        $filterName = $filter['asOf'];
        if (!$filterName) {
            $filter['asOf'] = 'default';
        }
        return self::LiveCampasOfFilter($filter);
    }

    private function LiveCampasOfFIlter($filter)
    {
        $asOfFilter = [
            'default' => self::LiveCampdefaultAsOfFilter($filter),
            'review'  => self::LiveCampreviewAsofFilter($filter),
            'bydate'  => self::LiveCampbyDateFilter($filter),
        ];
        return $asOfFilter[$filter['asOf']];
    }

    public static function LiveCampdefaultAsOfFilter($filter)
    {
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', '=', $filter['campNum'])
            ->where('objector_nick_id', '=', NULL)
            ->where('go_live_time', '<=', time())
            ->latest('submit_time')->first();
    }

    public static function LiveCampreviewAsofFilter($filter)
    {
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', '=', $filter['campNum'])
            ->where('objector_nick_id', '=', NULL)
            ->latest('submit_time')->first();
    }

    public static function LiveCampbyDateFilter($filter)
    {
        $asofdate = strtotime(date('Y-m-d H:i:s', strtotime($filter['asOfDate'])));
        if (isset($filter['nofilter']) && $filter['nofilter']) {
            $asofdate  = time();
        }
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', '=', $filter['campNum'])
            ->where('objector_nick_id', '=', NULL)
            ->where('go_live_time', '<=', $asofdate)
            ->latest('submit_time')->first();
    }

    public function scopeCampNameWithAncestors($query, $camp, $campname = '', $title = '', $filter = array(), $breadcrum = false)
    {
        $as_of_time = time();
        if (isset($filter['asOf']) && $filter['asOf'] == 'bydate') {
            $as_of_time = strtotime($filter['asOfDate']);
        }
        if (!empty($camp)) {
            if ($campname != '') {
                $campname =  ($camp->camp_name) . '/ ' . ($campname);
            } else {
                $campname =  ($camp->camp_name);
            }
            if (isset($camp) && $camp->parent_camp_num) {
                $pcamp = Camp::where('topic_num', $camp->topic_num)
                    ->where('camp_num', $camp->parent_camp_num)
                    ->where('objector_nick_id', '=', NULL)
                    ->where('go_live_time', '<=', $as_of_time)
                    ->orderBy('submit_time', 'DESC')->first();
                return self::campNameWithAncestors($pcamp, $campname, $title, $filter, $breadcrum);
            }
        }
        return $campname;
    }
}

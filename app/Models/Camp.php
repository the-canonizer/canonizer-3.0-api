<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Camp extends Model
{
    protected $table = 'camp';
    public $timestamps = false;
    const AGREEMENT_CAMP = "Agreement";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['topic_num', 'parent_camp_num', 'key_words', 'language', 'note', 'submit_time', 'submitter_nick_id', 'go_live_time', 'title', 'camp_name', 'camp_num'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public static function campLink($topicNum, $campNum, $title, $campName)
    {
        $title = preg_replace('/[^A-Za-z0-9\-]/', '-', $title);
        $campName = preg_replace('/[^A-Za-z0-9\-]/', '-', $campName);
        $topicId = $topicNum . "-" . $title;
        $campId = $campNum . "-" . $campName;
        $queryString = (app('request')->getQueryString()) ? '?' . app('request')->getQueryString() : "";
        return $link = url('topic/' . $topicId . '/' . $campId . '#statement');
    }

    public static function getAgreementTopic($filter = array())
    {
        $filterName = $filter['asOf'];
        if (!$filterName) {
            $filter['asOf'] = 'default';
        }
        return self::agreementTopicAsOfFilter($filter);
    }

    private function agreementTopicAsOfFilter($filter)
    {
        $asOfFilter = [
            'default' => self::agreementTopicDefaultAsOfFilter($filter),
            'review'  => self::agreementTopicReviewAsOfFilter($filter),
            'bydate'  => self::agreementTopicByDateFilter($filter),
        ];
        return $asOfFilter[$filter['asOf']];
    }

    public static function agreementTopicDefaultAsOfFilter($filter)
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

    public static function agreementTopicReviewAsOfFilter($filter)
    {
        return self::select('topic.topic_name', 'topic.namespace_id', 'camp.*', 'namespace.name as namespace_name', 'namespace.name')
            ->join('topic', 'topic.topic_num', '=', 'camp.topic_num')
            ->join('namespace', 'topic.namespace_id', '=', 'namespace.id')
            ->where('camp.topic_num', $filter['topicNum'])->where('camp_name', '=', 'Agreement')
            ->where('camp.objector_nick_id', '=', NULL)
            ->where('topic.objector_nick_id', '=', NULL)
            ->latest('topic.submit_time')->first();
    }

    public static function agreementTopicByDateFilter($filter)
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
            $filter['asOf'] = 'other';
        }
        return self::liveCampAsOfFilter($filter);
    }

    private function liveCampAsOfFilter($filter)
    {
        $asOfFilter = [
            'default' => self::liveCampDefaultAsOfFilter($filter),
            'review'  => self::liveCampReviewAsOfFilter($filter),
            'bydate'  => self::liveCampByDateFilter($filter),
            'other'  => self::liveCampOtherAsOfFilter($filter),
        ];
        return $asOfFilter[$filter['asOf']];
    }

    public static function liveCampOtherAsOfFilter($filter)
    {
        return self::where('topic_num', $filter['topicNum'])
            ->where('objector_nick_id', '=', NULL)
            ->latest('submit_time')->first();
    }

    public static function liveCampDefaultAsOfFilter($filter)
    {
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', '=', $filter['campNum'])
            ->where('objector_nick_id', '=', NULL)
            ->where('go_live_time', '<=', time())
            ->latest('submit_time')->first();
    }

    public static function liveCampReviewAsOfFilter($filter)
    {
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', '=', $filter['campNum'])
            ->where('objector_nick_id', '=', NULL)
            ->latest('submit_time')->first();
    }

    public static function liveCampByDateFilter($filter)
    {
        $asOfDate = isset($filter['asOfDate']) ? strtotime(date('Y-m-d H:i:s', strtotime($filter['asOfDate']))) :  strtotime(date('Y-m-d H:i:s'));
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', '=', $filter['campNum'])
            ->where('objector_nick_id', '=', NULL)
            ->where('go_live_time', '<=', $asOfDate)
            ->latest('submit_time')->first();
    }

    public static function campNameWithAncestors($camp, $campName = '', $title = '', $filter = array(), $breadCrum = false)
    {
        $as_of_time = time();
        if (isset($filter['asOf']) && $filter['asOf'] == 'bydate') {
            $as_of_time = strtotime($filter['asOfDate']);
        }
        if (!empty($camp)) {
            $campName = $campName != '' ?  ($camp->camp_name) . '/ ' . ($campName) : ($camp->camp_name);
            if ($camp->parent_camp_num) {
                $pCamp = Camp::where('topic_num', $camp->topic_num)
                    ->where('camp_num', $camp->parent_camp_num)
                    ->where('objector_nick_id', '=', NULL)
                    ->where('go_live_time', '<=', $as_of_time)
                    ->orderBy('submit_time', 'DESC')->first();
                return self::campNameWithAncestors($pCamp, $campName, $title, $filter, $breadCrum);
            }
        }
        return $campName;
    }

    public function nickname()
    {
        return $this->hasOne('App\Models\Nickname', 'id', 'camp_about_nick_id');
    }
    
    public static function getAllParentCamp($topicNum, $filter, $asOfDate = null)
    {
        if($filter == 'bydate'){
            $asOfDate = strtotime(date('Y-m-d H:i:s', strtotime($asOfDate)));
        }else{
            $asOfDate = time();
        }

        return self::where('topic_num', $topicNum)
        ->where('objector_nick_id', '=', NULL)
        ->where('go_live_time', '<=', $asOfDate)
        ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNum . ' and objector_nick_id is null and go_live_time < '.$asOfDate.' group by camp_num)')
        ->orderBy('submit_time', 'desc')->orderBy('camp_name','desc')->groupBy('camp_num')->get();
    }
}

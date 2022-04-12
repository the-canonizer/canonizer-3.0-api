<?php

namespace App\Models;

use App\Facades\Util;
use Laravel\Passport\HasApiTokens;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Camp extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, HasApiTokens  , Authorizable, HasFactory;

    protected $table = 'camp';
    public $timestamps = false;
    const AGREEMENT_CAMP = "Agreement";
    protected static $chilcampArray = [];
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

    public static function campNameWithAncestors($camp, $filter = array(), $campNames = array(), $index=0) :array
    {
        $as_of_time = time();
        if (isset($filter['asOf']) && $filter['asOf'] == 'bydate') {
            $as_of_time = strtotime($filter['asOfDate']);
        }
        if ($camp) {
            $campNames[$index]['camp_name']=$camp->camp_name;
            $campNames[$index]['topic_num']=$camp->topic_num;
            $campNames[$index]['camp_num']=$camp->camp_num;
            $index++;
            if ($camp->parent_camp_num) {
                $pCamp = Camp::where('topic_num', $camp->topic_num)
                    ->where('camp_num', $camp->parent_camp_num)
                    ->where('objector_nick_id', '=', NULL)
                    ->where('go_live_time', '<=', $as_of_time)
                    ->orderBy('submit_time', 'DESC')->first();
                return self::campNameWithAncestors($pCamp, $filter, $campNames, $index);
            }
        }
        return $campNames;
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

    public static function getAllChildCamps($camp):array
     {
        $campArray = [];
        if ($camp) {
            $key = $camp->topic_num . '-' . $camp->camp_num . '-' . $camp->parent_camp_num;
            $key1 = $camp->topic_num . '-' . $camp->parent_camp_num . '-' . $camp->camp_num;
           if (in_array($key, Camp::$chilcampArray) || in_array($key1, Camp::$chilcampArray)) {
                return [];
            }
            Camp::$chilcampArray[] = $key;
            Camp::$chilcampArray[] = $key1;
            $campArray[] = $camp->camp_num;
            $childCamps = Camp::where('topic_num', $camp->topic_num)->where('parent_camp_num', $camp->camp_num)->where('go_live_time', '<=', time())->groupBy('camp_num')->latest('submit_time')->get();
            foreach ($childCamps as $child) {
                $latestParent = Camp::where('topic_num', $child->topic_num)
                ->where('camp_num', $child->camp_num)->where('go_live_time', '<=', time())->latest('submit_time')->first();
                if($latestParent->parent_camp_num == $camp->camp_num ){ 
                    $campArray = array_merge($campArray, self::getAllChildCamps($child)); 

                }
                
            }
        }

        return $campArray;
    }

    public static function getCampSubscribers($topic_num, $camp_num = 1){

        $users_data = [];
       
        if ($camp_num) {
            $filter['topicNum'] = $topic_num;
            $filter['asOf'] = '';
            $filter['campNum'] = $camp_num;
            $oneCamp = self::getLiveCamp($filter);
        } else {
            $oneCamp = self::getLiveCampFromTopic($topic_num, ['nofilter' => true]);
        }
        $childCampData = [];
        if (isset($oneCamp) && isset($oneCamp->camp_name)) {
            if ($camp_num) {
                $childCampData = $oneCamp->campChild($topic_num, $camp_num);
            } else {
                $childCampData = self::campChildFromTopic($topic_num);
            }
        }
        $child_camps = [];
        if (count($childCampData) > 0) {
            foreach ($childCampData as $key => $child) {
                $child_camps[$key] = $child->camp_num;
            }
        }
       
        $users = CampSubscription::select('user_id')->where('topic_num', '=', $topic_num);

        if (count($child_camps) > 0) {
            $users->where('subscription_end',NULL);
            $users->whereIn('camp_num', $child_camps);
        }else {
            $users->whereIn('camp_num', [0, $camp_num]);
        }
        $usersData = $users->get();

        if (count($usersData)) {
            foreach ($usersData as $user) {
                $users_data[] = $user->user_id;
            }
        }
        return  array_unique($users_data);
    }

    public static function checkifSubscriber($subscribers,$user){
        $flag = false;
        foreach($subscribers as $sub){
            if($sub == $user->id){
                $flag = true;
                break;
            }
        }
        return $flag;
    }

    public static function checkifDirectSupporter($directSupporter,$nick_id)
    {
        $flag =false;
        foreach($directSupporter as $sup){
            if($sup->nick_name_id == $nick_id){
                $flag = true;
                break;
            }
        }
        return $flag;
    }

    public static function getSubscriptionList($userid,$topic_num,$camp_num=1):array 
    {
        $list = [];
        $filter['topicNum'] = $topic_num;
        $filter['asOf'] = '';
        $filter['campNum'] = $camp_num;
        $oneCamp = self::getLiveCamp($filter);
         self::clearChildCampArray();
        $childCamps = array_unique(self::getAllChildCamps($oneCamp));
       
        $subscriptions = CampSubscription::where('user_id','=',$userid)->where('topic_num','=',$topic_num)->where('subscription_start','<=',strtotime(date('Y-m-d H:i:s')))->where('subscription_end','=',null)->orWhere('subscription_end','>=',strtotime(date('Y-m-d H:i:s')))->get();
        if(isset($subscriptions ) && count($subscriptions ) > 0){
            foreach($subscriptions as $subs){
                if($camp_num!=1){
                    if(!in_array($subs->camp_num, $childCamps) && $subs->camp_num != 0){
                        continue;
                    }
                }
                $filter['topicNum'] = $subs->topic_num;
                $filter['asOf'] = '';
                $filter['campNum'] = $subs->camp_num;
                $topic = self::getLiveCamp($filter);
                $topicLive = Topic::getLiveTopic($subs->topic_num,['nofilter'=>true]);
                $title = preg_replace('/[^A-Za-z0-9\-]/', '-', ($topic->title != '') ? $topic->title : $topic->camp_name);
                $link = Util::getTopicCampUrl($topic_num,$subs->camp_num, $topicLive, $topic, time());
                if($subs->camp_num == 0){
                    $link = Util::getTopicCampUrl($topic_num,$subs->camp_num, $topicLive, $topic, time());
                    if(!empty($topicLive)){
                        $list[]= '<a href="'.$link.'">'.$topicLive->topic_name.'</a>';
                    }
                }else{
                    $list[]= '<a href="'.$link.'">'.$topicLive->camp_name.'</a>';
                }
            }
        }
        return $list;
    }

    public function campChild($topicnum, $parentcamp) 
    {

        $childsData = Camp::where('topic_num', '=', $topicnum)
                        ->where('parent_camp_num', '=', $parentcamp)
                        ->where('camp_name', '!=', 'Agreement')
                        ->get()->unique('camp_num');
        return $childsData;
    }
    
    public static function clearChildCampArray()
    {
        self::$chilcampArray=[];
    }
}

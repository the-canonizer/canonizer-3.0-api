<?php

namespace App\Models;

use App\Facades\Util;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use App\Models\CampSubscription;
use App\Library\wiki_parser\wikiParser as wikiParser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;



class Camp extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, HasApiTokens, Authorizable, HasFactory;

    protected $table = 'camp';
    public $timestamps = false;
    const AGREEMENT_CAMP = "Agreement";
    protected static $chilcampArray = [];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['topic_num','is_disabled','is_one_level', 'parent_camp_num', 'key_words', 'language', 'note', 'submit_time', 'submitter_nick_id', 'go_live_time', 'title', 'camp_name', 'camp_num','camp_about_nick_id','camp_about_url', 'objector_nick_name'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public function objectorNickName()
    {
        return $this->hasOne('App\Models\Nickname', 'id', 'objector_nick_id');
    }

    public function topic()
    {
        return $this->hasOne('App\Models\Topic', 'topic_num', 'topic_num')
            ->where('go_live_time', '<=', time())
            ->where('objector_nick_id', '=', NULL)
            ->orderBy('go_live_time', 'DESC');
    }

    public static function campLink($topicNum, $campNum, $title, $campName)
    {
        $title = preg_replace('/[^A-Za-z0-9\-]/', '-', $title);
        $campName = preg_replace('/[^A-Za-z0-9\-]/', '-', $campName);
        $topicId = $topicNum . "-" . $title;
        $campId = $campNum . "-" . $campName;
        $queryString = (app('request')->getQueryString()) ? '?' . app('request')->getQueryString() : "";
        return $link = config('global.APP_URL_FRONT_END') . ('/topic/' . $topicId . '/' . $campId . '#statement');
    }

    public static function getAgreementTopic($filter = array())
    {
        $filterName = isset($filter['asOf']) ?  $filter['asOf'] : '';
        if (!$filterName) {
            $filter['asOf'] = 'default';
        }
        return self::agreementTopicAsOfFilter($filter);
    }

    private static function agreementTopicAsOfFilter($filter)
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
            ->where('topic.grace_period', 0) 
            ->latest('topic.go_live_time')->first();
    }

    public static function agreementTopicByDateFilter($filter)
    {
        $asOfdate = isset($filter['asOfDate']) ? strtotime(date('Y-m-d H:i:s', strtotime($filter['asOfDate']))) :  strtotime(date('Y-m-d H:i:s'));
        return self::select('topic.topic_name', 'topic.namespace_id', 'camp.*', 'namespace.name as namespace_name', 'namespace.name')
            ->join('topic', 'topic.topic_num', '=', 'camp.topic_num')
            ->join('namespace', 'topic.namespace_id', '=', 'namespace.id')
            ->where('camp.topic_num', $filter['topicNum'])->where('camp_name', '=', 'Agreement')
            ->where('camp.objector_nick_id', '=', NULL)
            ->where('topic.objector_nick_id', '=', NULL)
            ->where('topic.go_live_time', '<=', $asOfdate)
            ->latest('topic.go_live_time')->first();
    }

    public static function getLiveCamp($filter = array())
    {
        $filterName = isset($filter['asOf']) ?  $filter['asOf'] : '';
        if (!$filterName) {
            $filter['asOf'] = 'default';
        }
        return self::liveCampAsOfFilter($filter);
    }

    private static function liveCampAsOfFilter($filter)
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
            ->latest('go_live_time')->first();
    }

    public static function liveCampReviewAsOfFilter($filter)
    {
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', '=', $filter['campNum'])
            ->where('objector_nick_id', '=', NULL)
            ->where('grace_period', 0) 
            ->latest('go_live_time')->first();
    }

    public static function liveCampByDateFilter($filter)
    {
        $asOfDate = isset($filter['asOfDate']) ? strtotime(date('Y-m-d H:i:s', strtotime($filter['asOfDate']))) :  strtotime(date('Y-m-d H:i:s'));
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', '=', $filter['campNum'])
            ->where('go_live_time', '<=', $asOfDate)
            ->latest('go_live_time')->first();
    }

    public static function campNameWithAncestors($camp, $filter = array(), $campNames = array(), $index = 0): array
    {
        $as_of_time = time();
        if (isset($filter['asOf']) && $filter['asOf'] == 'bydate') {
            $as_of_time = strtotime($filter['asOfDate']);
        }
        if ($camp) {
            $campNames[$index]['camp_name'] = $camp->camp_name;
            $campNames[$index]['topic_num'] = $camp->topic_num;
            $campNames[$index]['camp_num'] = $camp->camp_num;
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
        return array_reverse($campNames);
    }

    public function nickname()
    {
        return $this->hasOne('App\Models\Nickname', 'id', 'camp_about_nick_id');
    }

    public static function getAllParentCamp($topicNum, $filter, $asOfDate = null)
    {
        if ($filter == 'bydate') {
            $asOfDate = strtotime(date('Y-m-d H:i:s', strtotime($asOfDate)));
        } else {
            $asOfDate = time();
        }

        return self::where('topic_num', $topicNum)
            ->where('objector_nick_id', '=', NULL)
            ->where('go_live_time', '<=', $asOfDate)
            ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNum . ' and objector_nick_id is null and go_live_time < ' . $asOfDate . ' group by camp_num)')
            ->orderBy('submit_time', 'desc')->orderBy('camp_name', 'desc')->groupBy('camp_num')->get();
    }

    public static function getParentCamp($topicNum, $campNum, $filter, $asOfDate = null)
    {
        if ($filter == 'bydate') {
            $asOfDate = strtotime(date('Y-m-d H:i:s', strtotime($asOfDate)));
        } else {
            $asOfDate = time();
        }

        return self::where('topic_num', $topicNum)
            ->where('objector_nick_id', '=', NULL)
            ->where('camp_num', '=', $campNum)
            ->where('go_live_time', '<=', $asOfDate)
            ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNum . ' and objector_nick_id is null and go_live_time < ' . $asOfDate . ' and camp_num='. $campNum .' group by camp_num)')
            ->orderBy('submit_time', 'desc')->orderBy('camp_name', 'desc')->groupBy('camp_num')->first();
    }

    public static function getAllChildCamps($camp): array
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
                if ($latestParent->parent_camp_num == $camp->camp_num) {
                    $campArray = array_merge($campArray, self::getAllChildCamps($child));
                }
            }
        }

        return $campArray;
    }

    public static function getAllParent($camp, $camparray = array())
    {
        if (!empty($camp)) {
            if ($camp->parent_camp_num) {
                $camparray[] = $camp->parent_camp_num;
                $filter['topicNum'] = $camp->topic_num;
                $filter['asOf'] = '';
                $filter['campNum'] = $camp->parent_camp_num;
                $pcamp = self::getLiveCamp($filter);
                return self::getAllParent($pcamp, $camparray);
            }
        }
        return $camparray;
    }

    public static function getCampSubscribers($topic_num, $camp_num = 1)
    {
        $users_data = [];
        $users = CampSubscription::select('user_id')->where('topic_num', '=', $topic_num)
            ->whereIn('camp_num', [0, $camp_num])
            ->whereNull('subscription_end')
            ->get();
        if (count($users)) {
            foreach ($users as $user) {
                array_push($users_data, $user->user_id);
            }
        }
        if ($camp_num) {
            $filter['topicNum'] = $topic_num;
            $filter['asOf'] = '';
            $filter['campNum'] = $camp_num;
            $onecamp = self::getLiveCamp($filter);
        } else {
            $onecamp = self::where('topic_num', $topic_num)
                ->where('objector_nick_id', '=', NULL)
                ->where('go_live_time', '<=', time())
                ->latest('submit_time')->first();
        }
        $childCampData = [];
        $parent_camps = [];
        if (isset($onecamp) && isset($onecamp->camp_name)) {
            if ($camp_num) {
                $childCampData = $onecamp->campChild($topic_num, $camp_num);
            } else {
                $childCampData = self::campChildFromTopic($topic_num);
            }
            $parent_camps = self::getAllParent($onecamp);
        }
        $child_camps = [];
        if (count($childCampData) > 0) {
            foreach ($childCampData as $key => $child) {
                $child_camps[$key] = $child->camp_num;
            }
        }
        if (count($child_camps) > 0 || count($parent_camps) > 0) {
            $camps = array_unique(array_merge($child_camps, $parent_camps));
            $usersData = CampSubscription::select('user_id')->where('topic_num', '=', $topic_num)
                ->whereIn('camp_num', $camps)
                ->where('subscription_end', '=', null)
                ->get();
            if (count($usersData)) {
                foreach ($usersData as $user) {
                    array_push($users_data, $user->user_id);
                }
            }
        }
        return  array_unique($users_data);
    }

    public static function checkifSubscriber($subscribers, $user)
    {
        $flag = false;
        foreach ($subscribers as $sub) {
            if ($sub == $user->id) {
                $flag = true;
                break;
            }
        }
        return $flag;
    }

    public static function checkifDirectSupporter($directSupporter, $nick_id)
    {
        $flag = false;
        foreach ($directSupporter as $sup) {
            if ($sup->nick_name_id == $nick_id) {
                $flag = true;
                break;
            }
        }
        return $flag;
    }

    public static function getSubscriptionList($userid, $topic_num, $camp_num = 1): array
    {
        $list = [];
        $filter['topicNum'] = $topic_num;
        $filter['asOf'] = '';
        $filter['campNum'] = $camp_num;
        $oneCamp = self::getLiveCamp($filter);
        self::clearChildCampArray();
        $childCamps = array_unique(self::getAllChildCamps($oneCamp));

        $subscriptions = CampSubscription::where('user_id', '=', $userid)->where('topic_num', '=', $topic_num)->where('subscription_start', '<=', strtotime(date('Y-m-d H:i:s')))->where('subscription_end', '=', null)->orWhere('subscription_end', '>=', strtotime(date('Y-m-d H:i:s')))->get();
        if (isset($subscriptions) && count($subscriptions) > 0) {
            foreach ($subscriptions as $subs) {
                if ($camp_num != 1) {
                    if (!in_array($subs->camp_num, $childCamps) && $subs->camp_num != 0) {
                        continue;
                    }
                }
                $filter['topicNum'] = $subs->topic_num;
                $filter['asOf'] = '';
                $filter['campNum'] = $subs->camp_num;
                $liveCamp = self::getLiveCamp($filter);
                $topicLive = Topic::getLiveTopic($subs->topic_num, ['nofilter' => true]);
                if ($subs->camp_num == 0) {
                    $link = Util::getTopicCampUrl($topic_num, 1, $topicLive, $liveCamp, time());
                    if (!empty($topicLive)) {
                        $list[] = '<a href="' . $link . '">' . $topicLive->topic_name . '</a>';
                    }
                } else {
                    $link = Util::getTopicCampUrl($topic_num, $subs->camp_num, $topicLive, $liveCamp, time());
                    $list[] = '<a href="' . $link . '">' . $liveCamp->camp_name . '</a>';
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
        self::$chilcampArray = [];
    }

    public static function getCampSubscription($filter, $userid = null): array
    {
        $returnArr = array('flag' => 0, 'camp_subscription_data' => []);
        $camp_subscription = CampSubscription::select('id as subscription_id')->where('user_id', '=', $userid)->where('camp_num', '=', $filter['campNum'])->where('topic_num', '=', $filter['topicNum'])->where('subscription_start', '<=', strtotime(date('Y-m-d H:i:s')))->where('subscription_end', '=', null)->orWhere('subscription_end', '>', strtotime(date('Y-m-d H:i:s')))->get();
        $flag = sizeof($camp_subscription) > 0  || 0;
        if (!$flag) {
            $onecamp = self::getLiveCamp($filter);
            $childCampData = [];
            if ($onecamp) {
                $childCampData = $onecamp->campChild($filter['topicNum'], $filter['campNum']);
            }
            $child_camps = [];
            if (count($childCampData) > 0) {
                foreach ($childCampData as $key => $child) {
                    $child_camps[$key] = $child->camp_num;
                }
            }
            if (count($child_camps) > 0) {
                $camp_subs_child = CampSubscription::where('user_id', '=', $userid)->whereIn('camp_num', $child_camps)->where('topic_num', '=', $filter['topicNum'])->where('subscription_start', '<=', strtotime(date('Y-m-d H:i:s')))->where('subscription_end', '=', null)->orWhere('subscription_end', '>', strtotime(date('Y-m-d H:i:s')))->get();
                $flag = ($camp_subs_child && sizeof($camp_subs_child) > 0);
                if ($flag) {
                    $flag = 2;
                }
                foreach ($child_camps as $camp) {
                    $camp_subscription = CampSubscription::select('camp_subscription.id as subscription_id', 'camp.camp_name as camp_name')->join("camp", function ($join) {
                        $join->on("camp.topic_num", "=", "camp_subscription.topic_num")
                            ->on("camp.camp_num", "=", "camp_subscription.camp_num");
                    })->where('user_id', '=', $userid)->where('camp_subscription.camp_num', '=', $camp)->where('camp_subscription.topic_num', '=', $filter['topicNum'])->where('camp_subscription.subscription_start', '<=', strtotime(date('Y-m-d H:i:s')))->where('camp_subscription.subscription_end', '=', null)->orWhere('camp_subscription.subscription_end', '>', strtotime(date('Y-m-d H:i:s')))->orderBy('camp.go_live_time', 'DESC')->limit(1)->get();
                    if (sizeof($camp_subscription) > 0) {
                        $returnArr = array('flag' => $flag, 'camp_subscription_data' => $camp_subscription);
                        break;
                    }
                }
            }
        } else {
            $returnArr = array('flag' => 1, 'camp_subscription_data' => $camp_subscription);
        }
        return $returnArr;
    }


    public static function getDirectCampSupporterIds($topicid, $campnum)
    {
        $users = [];
        $directSupporter = Support::getDirectSupporter($topicid, $campnum);
        foreach ($directSupporter as $supporter) {
            $user = \App\Helpers\CampForum::getUserFromNickId($supporter->nick_name_id);
            $userId = $user->id ?? null;
            $users[] = $userId;
        }
        return $users;
    }

    public static function validateParentsupport($topicNum, $campNum, $userNicknames) 
    {
        $filter = self::getLiveCampFilter($topicNum, $campNum);
        $oneCamp = self::getLiveCamp($filter);   

        if(empty($oneCamp)){
            return 'notfound';
        }

        if ($oneCamp->count() <= 0) {
            return 'notlive';
        }       

        $parentcamps = self::getAllParent($oneCamp);
        $mysupports = Support::where('topic_num', $topicNum)->whereIn('camp_num', $parentcamps)->whereIn('nick_name_id', $userNicknames)->where('end', '=', 0)->orderBy('support_order', 'ASC')->get();
        
        return (count($mysupports)) ? $mysupports : false;
    }

    public static function validateChildsupport($topicNum, $campNum, $userNicknames) 
    {
        $filter = self::getLiveCampFilter($topicNum, $campNum);
        $oneCamp = self::getLiveCamp($filter);

        $childCamps = array_unique(self::getAllChildCamps($oneCamp,$includeLiveCamps=true));        
        $mysupports = Support::where('topic_num', $topicNum)->whereIn('camp_num', $childCamps)->whereIn('nick_name_id', $userNicknames)->where('end', '=', 0)->orderBy('support_order', 'ASC')->groupBy('camp_num')->get();

        return (count($mysupports)) ? $mysupports : false;
    }

    public static function getLiveCampFilter($topicNum, $campNum)
    {
        $filter =  ['topicNum' => $topicNum, 'campNum' => $campNum];
        return $filter;
    }

    public static function getCampNameByTopicIdCampId($topicNum, $campNum, $as_of_time){
        $parentCampName = "";
        $filter = self::getLiveCampFilter($topicNum, $campNum);
        $campDetails = self::getLiveCamp($filter);

        //$campDetails = Camp::where('topic_num', $topicNum)->where('camp_num', '=', $campNum)->where('objector_nick_id', '=', NULL)->where('go_live_time', '<=', $as_of_time)->orderBy('submit_time', 'DESC')->first();
        if(!empty($campDetails)) {

            $parentCampName = $campDetails->camp_name;
        }
        
        return $parentCampName;
    }

    public static function getAllLiveCampsInTopic($topicnum){ 
        return self::where('topic_num', '=', $topicnum)
                        ->where('camp_name', '!=', 'Agreement')
                        ->where('objector_nick_id', '=', NULL)
                        ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicnum . ' and objector_nick_id is null and go_live_time < "' . time() . '" group by camp_num)')
                        ->where('go_live_time', '<=', time())
                        ->groupBy('camp_num')
                        ->orderBy('submit_time', 'desc')
                        ->get();
    }

    public static function getAllNonLiveCampsInTopic($topicnum){ 
        return self::where('topic_num', '=', $topicnum)
                        ->where('camp_name', '!=', 'Agreement')
                        ->where('objector_nick_id', '=', NULL)
                        ->where('go_live_time',">",time())
                        ->groupBy('camp_num')
                        ->orderBy('submit_time', 'desc')
                        ->get();
    }

    public static function campHistory($statement_query, $filter, $response,  $liveCamp)
    {
        $statement_query->when($filter['type'] == "objected", function ($q) {
            $q->where('objector_nick_id', '!=', NULL);
        });

        $statement_query->when($filter['type'] == "in_review", function ($q) use ($filter) {
            $q->where('go_live_time', '>', $filter['currentTime'])
                ->where('objector_nick_id', NULL)
                ->where('submit_time', '<=', $filter['currentTime']);
        });
        
        $statement_query->when($filter['type'] == "old", function ($q) use ($filter,  $liveCamp) {
            $q->where('go_live_time', '<=', $filter['currentTime'])
                ->where('objector_nick_id', NULL)
                ->where('id', '!=', $liveCamp->id)
                ->where('submit_time', '<=', $filter['currentTime']);
        });

        $response->statement = Util::getPaginatorResponse($statement_query->paginate($filter['per_page']));
        $response = self::filterCampHistory($response, $filter, $liveCamp);
        return $response;
    }

    public static function filterCampHistory($response, $filter, $liveCamp)
    {
        $data = $response->statement;
        unset($response->statement);
        $data->details = $response;
        $statementHistory = [];
        if (isset($data->items) && count($data->items) > 0) {
                $nickNameIds = Nickname::getNicknamesIdsByUserId($filter['userId']);
                foreach ($data->items as $val) {
                $submitterUserID = Nickname::getUserIDByNickNameId($val->submitter_nick_id);
                $submittime = $val->submit_time;
                $starttime = time();
                $endtime = $submittime + 60 * 60;
                $interval = $endtime - $starttime;
                $val->objector_nick_name = null;
                $val->submitter_nick_name=NickName::getNickName($val->submitter_nick_id)->nick_name ?? null;
                $val->parent_camp_name = isset($val->parent_camp_num) && $val->parent_camp_num !=0 ? self::getParentCamp($val->topic_num, $val->parent_camp_num, 'default')->camp_name : null;
                $val->isAuthor = $submitterUserID == $filter['userId']  ?  true : false ;
                $val->agreed_to_change = 0;
                switch ($val) {
                    case $val->objector_nick_id !== NULL:
                        $val->status = "objected";
                        $val->objector_nick_name =  $val->objectorNickName->nick_name;
                        $val->unsetRelation('objectorNickName');
                        break;
                    case $filter['currentTime'] < $val->go_live_time && $filter['currentTime'] >= $val->submit_time:
                        $val->agreed_to_change = (int) ChangeAgreeLog::whereIn('nick_name_id', $nickNameIds)
                        ->where('change_for', '=', 'camp')
                        ->where('change_id', '=', $val->id)
                        ->exists(); 
                        $val->status = "in_review";
                        break;
                    case $liveCamp->id == $val->id && $filter['type'] != "old":
                        $val->status = "live";
                        break;
                    default:
                        $val->status = "old";
                }
                if (($interval > 0 && $val->grace_period > 0)  && (( $filter['userId']  != $submitterUserID ) || !isset($filter['userId'] )) ) {
                    continue;
                } else {
                    $WikiParser = new wikiParser;
                    $val->parsed_value = $WikiParser->parse($val->value);
                    array_push($statementHistory, $val);
                }
            }
            $data->items = $statementHistory;
        }
        return  $data;
    }

    public static function campChildFromTopic($topicnum)
    {
        return self::where('topic_num', '=', $topicnum)
                        ->get()->unique('camp_num');
       
    }

    public static function IfTopicCampNameAlreadyExists($all)
    {
        $liveCamps = self::getAllLiveCampsInTopic($all['topic_num']);
        $nonLiveCamps = self::getAllNonLiveCampsInTopic($all['topic_num']);
        $camp_existsLive = 0;
        $camp_existsNL = 0;
        if (!empty($liveCamps)) {
            foreach ($liveCamps as $value) {
                if (strtolower(trim($value->camp_name)) == strtolower(trim($all['camp_name']))) {
                    if (isset($all['camp_num']) && array_key_exists('camp_num', $all) && $all['camp_num'] == $value->camp_num) {
                        $camp_existsLive = 0;
                    } else {
                        $camp_existsLive = 1;
                    }
                }
            }
        }
        if (!empty($nonLiveCamps)) {
            foreach ($nonLiveCamps as $value) {
                if (strtolower(trim($value->camp_name)) == strtolower(trim($all['camp_name']))) {
                    if (isset($all['camp_num']) && array_key_exists('camp_num', $all) && $all['camp_num'] == $value->camp_num) {
                        $camp_existsNL = 0;
                    } else {
                        $camp_existsNL = 1;
                    }
                }
            }
        }
        return ($camp_existsLive || $camp_existsNL);
    }

    public static function filterParentCampForForm($parentCamps = []) {
        $campHierarchy = array();
        foreach ($parentCamps as $camp){
            $camp['children'] = [];
            $campHierarchy[$camp->parent_camp_num ?? 0][] = $camp;
        }
        $tree = self::createTree($campHierarchy, $campHierarchy[0]);

        $parents = self::createParentForForm($tree);

        return $parents;

    }

    public static function createParentForForm($tree = []) {
        $parents = [];
        foreach ($tree as $camp) {
            if ($camp->is_disabled != 1) {
                if ($camp->is_one_level == 1) {
                    $camp['children'] = [];
                }
                $parents[] = $camp;
                if (!empty($camp['children'])) {
                    $children = self::createParentForForm($camp['children']);
                    $parents = array_merge($parents, $children);
                }
            }
        }
        return $parents;
    }

    public static function createTree(&$list, $parent){
        $tree = array();
        foreach ($parent as $l){
            if(isset($list[$l->camp_num])){
                $l['children'] = self::createTree($list, $list[$l->camp_num]);
            }
            $tree[] = $l;
        } 
        return $tree;
    }
    /**
     * Get the camp tree count.
     * @param int $topicNumber
     * @param int $nickNameId
     * @param int $asOfTime
     *
     * @return $expertCamp
     */

    public static function getExpertCamp($topicnum, $nick_name_id, $asOfTime)
    {
        $camps = new Collection;
        $camps = Cache::remember("$topicnum-bydate-support-$asOfTime", 2, function () use ($topicnum, $asOfTime) {
                return $expertCamp = self::where('topic_num', '=', $topicnum)
                    ->where('objector_nick_id', '=', null)
                    ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicnum . ' and objector_nick_id is null group by camp_num)')
                    ->where('go_live_time', '<', $asOfTime)
                    ->orderBy('submit_time', 'desc')
                    ->groupBy('camp_num')
                    ->get();
            });

        $expertCamp = $camps->filter(function($item) use($nick_name_id){
            return  $item->camp_about_nick_id == $nick_name_id;
        })->last();
        
        return $expertCamp;
       
    }

    public static function getParentFromParent($parent_camp_num, $topic_num)
    {
        if (!empty($parent_camp_num)) {
            $parent = array();
            $selfData = self::where('topic_num', $topic_num)->where('camp_num', $parent_camp_num)->orderBy('id', 'desc')->first();
            $parent[] = $selfData;
                if ($selfData->parent_camp_num) {
                    $push = self::getParentFromParent($selfData->parent_camp_num, $selfData->topic_num);
                    $parent = array_merge($parent,$push);
                    return $parent;
                }else{
                    return $parent;
                }
            }
        }
}

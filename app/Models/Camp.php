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
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use App\Models\Search;
use App\Helpers\ElasticSearch;
use App\Jobs\ForgetCacheKeyJob;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Jobs\ActivityLoggerJob;

class Camp extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, HasApiTokens, Authorizable, HasFactory;

    protected $table = 'camp';
    public $timestamps = false;
    const AGREEMENT_CAMP = "Agreement";
    protected static $chilcampArray = [];
    protected static $childtempArray = [];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['topic_num','is_disabled','is_one_level', 'parent_camp_num', 'key_words', 'language', 'note', 'submit_time', 'submitter_nick_id', 'go_live_time', 'title', 'camp_name', 'camp_num','camp_about_nick_id','camp_about_url', 'objector_nick_name', 'camp_leader_nick_id'];
    protected $parent_change_in_review;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public static function boot() 
    {
        parent::boot();
            
        static::saved(function($item) {
            //forget cache
            self::forgetCache($item);

            $liveTopic = Topic::getLiveTopic($item->topic_num);            
            $namespace = Namespaces::find($liveTopic->namespace_id);            
            $namespaceLabel = 'no-namespace';
            if (!empty($namespace)) {
                $namespaceLabel = Namespaces::getNamespaceLabel($namespace, $namespace->name);
                $namespaceLabel = Namespaces::stripAndChangeSlashes($namespaceLabel);
            }
            $type = "camp";
            $typeValue = $item->camp_name;
            $topicNum = $item->topic_num;
            $campNum = $item->camp_num;
            $campName = $item->camp_name;
            $goLiveTime = $item->go_live_time;
            $namespace = $namespaceLabel; //fetch namespace
            $breadcrumb = '';
            $link =  self::campLink($topicNum, $campNum, $liveTopic->topic_name, $campName, true);
            if($item->camp_num == 1){
                $type = "topic";
                $typeValue = $liveTopic->topic_name;
                $id = "topic-". $topicNum;
                $link = self::campLink($topicNum, $campNum, $typeValue, $campName, true);
            }else{             
                $id = "camp-". $topicNum . "-" . $campNum;
                // breadcrumb
                $breadcrumb = Search::getCampBreadCrumbData($liveTopic, $topicNum, $campNum);
            }

           
            if($item->is_archive && $item->go_live_time <= time()){
                ElasticSearch::deleteData($id);
                return;
            }

            if($item->go_live_time <= time()){
                ElasticSearch::ingestData($id, $type, $typeValue, $topicNum, $campNum, $link, $goLiveTime, $namespace, $breadcrumb);
            }

         });
    }

    public static function forgetCache($item)
    {
        $cacheKeysToRemove = [
            'live_camp_default-' . $item->topic_num . '-' . $item->camp_num,
            'live_camp_review-' . $item->topic_num . '-' . $item->camp_num,
            'live_camp_other-' . $item->topic_num
        ];
        foreach ($cacheKeysToRemove as $key) {
            Cache::forget($key);
        }
        if ($item->go_live_time > time()) {
            dispatch(new ForgetCacheKeyJob($cacheKeysToRemove, Carbon::createFromTimestamp($item->go_live_time)));
        }
    }


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

    public static function campLink($topicNum, $campNum, $title, $campName, $forSearch = false)
    {
        $title = preg_replace('/[^A-Za-z0-9\-]/', '-', $title);
        $campName = preg_replace('/[^A-Za-z0-9\-]/', '-', $campName);
        $topicId = $topicNum . "-" . $title;
        $campId = $campNum . "-" . $campName;
        $queryString = (app('request')->getQueryString()) ? '?' . app('request')->getQueryString() : "";
        if($forSearch){
            $link = 'topic/' . $topicId . '/' . $campId ;
        }else{
            $link = config('global.APP_URL_FRONT_END') . ('/topic/' . $topicId . '/' . $campId );
        }
        return $link;
        
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

    public static function getLiveCamp($filter = array(), $onlyColumns = [])
    {
        $filterName = isset($filter['asOf']) ?  $filter['asOf'] : '';
        if (!$filterName) {
            $filter['asOf'] = 'default';
        }
        return self::liveCampAsOfFilter($filter, $onlyColumns);
    }

    private static function liveCampAsOfFilter($filter, $onlyColumns = [])
    {
        $asOfFilter = [
            'default' => self::liveCampDefaultAsOfFilter($filter, $onlyColumns),
            'review'  => self::liveCampReviewAsOfFilter($filter, $onlyColumns),
            'bydate'  => self::liveCampByDateFilter($filter, $onlyColumns),
            'other'  => self::liveCampOtherAsOfFilter($filter, $onlyColumns),
        ];
        return $asOfFilter[$filter['asOf']];
    }

    public static function liveCampOtherAsOfFilter($filter, $onlyColumns = [])
    {
        $cacheKey = 'live_camp_other-' . $filter['topicNum'];
        $camp = Cache::remember($cacheKey, (int)env('CACHE_TIMEOUT_IN_SECONDS'), function () use ($filter) {
            return self::where('topic_num', $filter['topicNum'])
                ->where('objector_nick_id', '=', NULL)
                ->latest('submit_time')->first();
        });
        return $camp;
    }

    public static function liveCampDefaultAsOfFilter($filter, $onlyColumns = [])
    {
        $cacheKey = 'live_camp_default-' . $filter['topicNum'] . '-' . $filter['campNum'];
        $camp = Cache::remember($cacheKey, (int)env('CACHE_TIMEOUT_IN_SECONDS'), function () use ($filter) {
            return self::where('topic_num', $filter['topicNum'])
                ->where('camp_num', '=', $filter['campNum'])
                ->where('objector_nick_id', '=', NULL)
                ->where('go_live_time', '<=', time())
                ->latest('go_live_time')->first();
        });
        return $camp;
    }

    public static function liveCampReviewAsOfFilter($filter, $onlyColumns = [])
    {
        $cacheKey = 'live_camp_review-' . $filter['topicNum'] . '-' . $filter['campNum'];
        $camp = Cache::remember($cacheKey, (int)env('CACHE_TIMEOUT_IN_SECONDS'), function () use ($filter) {
            return self::where('topic_num', $filter['topicNum'])
                ->where('camp_num', '=', $filter['campNum'])
                ->where('objector_nick_id', '=', NULL)
                ->where('grace_period', 0) 
                ->latest('go_live_time')->first();
        });
        return $camp;
    }

    public static function liveCampByDateFilter($filter, $onlyColumns = [])
    {
        $asOfDate = isset($filter['asOfDate']) ? strtotime(date('Y-m-d H:i:s', strtotime($filter['asOfDate']))) :  strtotime(date('Y-m-d H:i:s'));
        return self::when($onlyColumns, function (Builder $query, $onlyColumns) {
                return $query->select($onlyColumns);
            })
            ->where('topic_num', $filter['topicNum'])
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
                if(isset($filter['asOf']) && $filter['asOf'] == 'review') {
                    $pCamp = Camp::where('topic_num', $camp->topic_num)
                    ->where('camp_num', $camp->parent_camp_num)
                    ->where('grace_period', 0)
                    ->where('objector_nick_id', '=', NULL)
                    ->orderBy('go_live_time', 'DESC')->first();
                } else {
                    $pCamp = Camp::where('topic_num', $camp->topic_num)
                        ->where('camp_num', $camp->parent_camp_num)
                        ->where('objector_nick_id', '=', NULL)
                        ->where('go_live_time', '<=', $as_of_time)
                        ->orderBy('submit_time', 'DESC')->first();
                }
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
            ->where('is_archive', '=', 0)
            ->where('go_live_time', '<=', $asOfDate)
            ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNum . ' and objector_nick_id is null and go_live_time < ' . $asOfDate . ' group by camp_num)')
            ->orderBy('camp_name', 'ASC')->groupBy('camp_num')->get();
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
        self::clearChildCampArray();
        $camparray = [];
        Camp::$chilcampArray = [];
        Camp::$childtempArray = [];
        try {
            if ($camp) {
                $key = $camp->topic_num . '-' . $camp->camp_num . '-' . $camp->parent_camp_num;
                $key1 = $camp->topic_num . '-' . $camp->parent_camp_num . '-' . $camp->camp_num;
                if (in_array($key, Camp::$chilcampArray) || in_array($key1, Camp::$childtempArray)) {
                    return [];
                }
                Camp::$chilcampArray[] = $key;
                Camp::$childtempArray[] = $key1;
                $camparray[] = $camp->camp_num;
                $childCamps = Camp::where('topic_num', $camp->topic_num)
                    ->where('parent_camp_num', $camp->camp_num)
                    ->where('go_live_time', '<=', time())
                    ->groupBy('camp_num')
                    ->latest('submit_time')
                    ->get();
                foreach ($childCamps as $child) {
                    $latestParent = Camp::where('topic_num', $child->topic_num)
                        ->where('camp_num', $child->camp_num)
                        ->where('go_live_time', '<=', time())
                        ->where('objector_nick_id', NULL)
                        ->latest('submit_time')
                        ->first();
                    
                    if ($latestParent->parent_camp_num == $camp->camp_num) {
                        $camparray = array_merge($camparray, self::getAllChildCamps($child));
                    }
                }
            }
        } catch (Exception $e) {
            Util::logMessage("Error :: getAllChildCamps :: ".$e->getMessage());
        }
       
        return $camparray;
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
        try {

            self::clearChildCampArray();
            $childCamps = array_unique(self::getAllChildCamps($oneCamp));
            // #1291 notify parent camps subscribers
            $parentCamps = array_unique(self::getAllParent($oneCamp));
            $camps = array_unique(array_merge($childCamps, $parentCamps));
           // $childCamps = array_unique(self::getAllChildCamps($oneCamp));
           // $parentCamps = array_unique(self::getAllParent($onecamp));
           // $camps = array_unique(array_merge($childCamps, $parentCamps));
            $subscriptions = CampSubscription::where('user_id', '=', $userid)->where('topic_num', '=', $topic_num)->where('subscription_start', '<=', strtotime(date('Y-m-d H:i:s')))->where('subscription_end', '=', null)->orWhere('subscription_end', '>=', strtotime(date('Y-m-d H:i:s')))->get();
            if (isset($subscriptions) && count($subscriptions) > 0) {
                foreach ($subscriptions as $subs) {
                    if ($camp_num != 1) {
                        if (!in_array($subs->camp_num, $camps) && $subs->camp_num != 0) {  // && $subs->camp_num != 0 - removed
                            continue;
                        }
                    }
                    $filter['topicNum'] = $subs->topic_num;
                    $filter['asOf'] = '';
                    $filter['campNum'] = $subs->camp_num;
                    $liveCamp = self::getLiveCamp($filter);
                    $topicLive = Topic::getLiveTopic($subs->topic_num, ['nofilter' => true]);
                    if ($subs->camp_num == 0) {
                        $link = Util::getTopicCampUrlWithoutTime($topic_num, 1, $topicLive, $liveCamp);
                        if (!empty($topicLive)) {
                            $list[] = '<a href="' . $link . '">' . $topicLive->topic_name . '</a>';
                        }
                    } else {
                        $link = Util::getTopicCampUrlWithoutTime($topic_num, $subs->camp_num, $topicLive, $liveCamp);
                        $list[] = '<a href="' . $link . '">' . $liveCamp->camp_name . '</a>';
                    }
                }
            }
        } catch(Exception $e) {
            Util::logMessage("Error:: getSubscriptionList :: ". $e->getMessage());
            $list = [];
        }
        
        return $list;
    }

    public function campChild($topicnum, $parentcamp)
    {

        $childsData = Camp::where('topic_num', '=', $topicnum)
            ->where('parent_camp_num', '=', $parentcamp)
            ->where('camp_name', '!=', 'Agreement')
            ->where('objector_nick_id', '=', NULL)->where('old_parent_camp_num','=', NULL)->orderBy('submit_time','DESC')
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

    public static function getImplicitCampSupporterIds($topicid, $campnum)
    {
        $users = [];
        $directSupporter = Support::getAllDirectSupporters($topicid, $campnum);
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

    public static function validateChildsupport($topicNum, $campNum, $userNicknames, $checkArchive = false) 
    {
        $filter = self::getLiveCampFilter($topicNum, $campNum);
        $oneCamp = self::getLiveCamp($filter);

        $childCamps = array_unique(self::getAllChildCamps($oneCamp,$includeLiveCamps=true));        
        if($checkArchive){
            $mysupports = Support::where('topic_num', $topicNum)->whereIn('camp_num', $childCamps)->whereIn('nick_name_id', $userNicknames)->where('end', '!=', 0)->where('reason','=','archived')->where('archive_support_flag', 0)->orderBy('support_order', 'ASC')->groupBy('camp_num')->get();
        }else{
            $mysupports = Support::where('topic_num', $topicNum)->whereIn('camp_num', $childCamps)->whereIn('nick_name_id', $userNicknames)->where('end', '=', 0)->orderBy('support_order', 'ASC')->groupBy('camp_num')->get();
        }
        

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
  
        $statement_query->when($filter['type'] == "live", function ($q) use ($liveCamp) {
            $q->where('id', $liveCamp->id);
        });
        
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
                $val->camp_about_nick_name = NickName::getNickName($val->camp_about_nick_id)->nick_name ?? null;
                $val->submitter_nick_name=NickName::getNickName($val->submitter_nick_id)->nick_name ?? null;
                $val->parent_camp_name = isset($val->parent_camp_num) && $val->parent_camp_num !=0 ? self::getParentCamp($val->topic_num, $val->parent_camp_num, 'default')->camp_name : null;
                $val->isAuthor = $submitterUserID == $filter['userId']  ?  true : false ;
                $val->agreed_to_change = 0;

                /*
                *   https://github.com/the-canonizer/Canonizer-Beta--Issue-Tracking/issues/232 
                *   Now support at the time of submition will be count as total supporter. 
                *   Also check if submitter is not a direct supporter, then it will be count as direct supporter   
                */
                $supportersByTimeStamp =  Support::getTotalSupporterByTimestamp('camp', (int)$filter['topicNum'], (int)$filter['campNum'], $val->submitter_nick_id, $submittime, $filter + ['change_id' => $val->id], false);
                $val->total_supporters = $supportersByTimeStamp[1];

                $supportersListByTimeStamp = $supportersByTimeStamp[0];
                $agreed_supporters = ChangeAgreeLog::where('topic_num', '=', $filter['topicNum'])
                    ->where('camp_num', '=', $filter['campNum'])
                    ->where('change_id', '=', $val->id)
                    ->where('change_for', '=', 'camp')
                    ->get()->pluck('nick_name_id')->toArray();
                
                $val->agreed_supporters = count($agreed_supporters);

                if($val->submitter_nick_id > 0 && !in_array($val->submitter_nick_id, $agreed_supporters)) 
                {   
                    $val->agreed_supporters++;
                }

                $nickNames = Nickname::personNicknameArray();
                $val->ifIamSupporter = Support::ifIamSupporterForChange($filter['topicNum'], $filter['campNum'], $nickNames, $submittime);
                $val->ifIAmExplicitSupporter = Support::ifIamExplicitSupporterBySubmitTime($filter, $nickNames, $submittime, null, false, 'ifIamExplicitSupporter');

                /**
                 * Camp Leader: Camp leader can't object to that change.
                 */
                $val->ifICanAgreeAndObject = true;
                if(in_array($liveCamp->camp_leader_nick_id, $nickNames) && $liveCamp->camp_leader_nick_id !== $val->camp_leader_nick_id) {
                    $val->ifICanAgreeAndObject = false;
                }

                $val->camp_leader_nick_name = NickName::getNickName($val->camp_leader_nick_id)->nick_name ?? '';

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
                        if($val->is_archive == 0 && ($val->archive_action_time != 0 || $liveCamp->is_archive === 1)){
                            $val->ifIamSupporter = Support::checkIfArchiveSupporter($filter['topicNum'], $nickNames);
                            $explicitSupporters = Support::ifIamArchiveExplicitSupporters($filter);
                            $val->ifIAmExplicitSupporter =  $explicitSupporters['ifIamExplicitSupporter'];
                            $explicitSupporters =   (count( $explicitSupporters['supporters'])) ? $explicitSupporters['supporters']->pluck('nick_name_id')->toArray() : [];
                            $revokableSupporter = Support::getSupportersNickNameOfArchivedCamps((int)$filter['topicNum'], [(int)$filter['campNum']], $val->is_archive, 1)->pluck('nick_name_id')->toArray();
                            $archiveSupporters = Support::filterArchivedSupporters($revokableSupporter, $explicitSupporters, $val->submitter_nick_id, $supportersListByTimeStamp);
                            $val->total_supporters = $val->total_supporters + count(array_unique($archiveSupporters['direct_supporters'])) + count(array_unique($archiveSupporters['explicit_supporters']));
                        }
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

    public static function filterParentCampForForm($parentCamps = [],$topic_num = null, $existingParent = null) {
        $campHierarchy = array();
        foreach ($parentCamps as $camp){
            $camp['children'] = [];
            $campHierarchy[$camp->parent_camp_num ?? 0][] = $camp;
        }
        $tree = self::createTree($campHierarchy, $campHierarchy[0]);

        $parents = self::createParentForForm($tree);
        $parents = self::removeKeyFromArray($parents, 'children');
        if (!empty($topic_num) && !empty($existingParent)) {
            $existingParentCamp = self::getLiveCamp(['topicNum' => $topic_num, 'campNum' => $existingParent, 'asOf' => 'default']);
            if (!empty($existingParentCamp)) {
                $parents = array_merge($parents, [$existingParentCamp]);
            }
        }
        $parents = array_map("unserialize", array_unique(array_map("serialize", $parents)));
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

    public static function removeKeyFromArray($array, $keyToRemove) {
        foreach ($array as $ele) {
            unset($ele[$keyToRemove]);
        }
        return $array;
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
            // $selfData = self::where('topic_num', $topic_num)->where('camp_num', $parent_camp_num)->orderBy('id', 'desc')->first();
            $selfData = self::where('topic_num',  $topic_num)
            ->where('camp_num', '=', $parent_camp_num)
            ->where('objector_nick_id', '=', NULL)
            ->where('go_live_time', '<=', time())
            ->latest('go_live_time')->first();
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

    public static function getAllLiveChildCamps($camp, $includeLiveCamps=false) 
    {
        $camparray = [];
        Camp::$chilcampArray = [];
        Camp::$childtempArray = [];

        if ($camp) {
            $key = $camp->topic_num . '-' . $camp->camp_num . '-' . $camp->parent_camp_num;
            $key1 = $camp->topic_num . '-' . $camp->parent_camp_num . '-' . $camp->camp_num;
            if (in_array($key, Camp::$chilcampArray) || in_array($key1, Camp::$childtempArray)) {
                return [];/** Skip repeated recursions* */
            }
            Camp::$chilcampArray[] = $key;
            Camp::$childtempArray[] = $key1;
            $camparray[] = $camp->camp_num;
            if($includeLiveCamps){
                //adding go_live_time condition Sunil Talentelgia //->where('go_live_time', '<=', time())
                $childCamps = Camp::where('topic_num', $camp->topic_num)->where('parent_camp_num', $camp->camp_num)->where('go_live_time', '<=', time())->groupBy('camp_num')->latest('submit_time')->get();
           
            }else{
                $childCamps = Camp::where('topic_num', $camp->topic_num)->where('parent_camp_num', $camp->camp_num)->groupBy('camp_num')->latest('submit_time')->get();
            }
            foreach ($childCamps as $child) {
                /***
                 ** Adding check to skip camps rejected ones 
                **/
                if($includeLiveCamps){
                    $latestParent = Camp::where('topic_num', $child->topic_num)->where('camp_num', $child->camp_num)->latest('submit_time')->where('go_live_time', '<=', time())->where('objector_nick_id', NULL)->first();
                }else{
                    $latestParent = Camp::where('topic_num', $child->topic_num)->where('camp_num', $child->camp_num)->where('objector_nick_id', NULL)->latest('submit_time')->first();
                }

                if($latestParent->parent_camp_num == $camp->camp_num )
                { 
                    $camparray = array_merge($camparray, self::getAllChildCamps($child));
                }
            }
        }

        return $camparray;
    }

    public static function checkAllLiveCampsInTopic($topicnum){ 
        return self::where('topic_num', '=', $topicnum)
                        ->where('objector_nick_id', '=', NULL)
                        ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicnum . ' and objector_nick_id is null and go_live_time < "' . time() . '" group by camp_num)')
                        ->where('go_live_time', '<=', time())
                        ->groupBy('camp_num')
                        ->orderBy('submit_time', 'desc')
                        ->get();
    }

    public static function checkAllNonLiveCampsInTopic($topicnum){ 
        return self::where('topic_num', '=', $topicnum)
                        ->where('objector_nick_id', '=', NULL)
                        ->where('go_live_time',">",time())
                        ->groupBy('camp_num')
                        ->orderBy('submit_time', 'desc')
                        ->get();
    }

    public static function archiveChildCamps($topicNum, $childCamps, $isArchive = 1, $directArchive = 0)
    {
        foreach($childCamps as $campNum)
        {
            $camp = Camp::where('topic_num', $topicNum)
                        ->where('camp_num', $campNum)
                        ->where('go_live_time', '<=', time())
                        ->where('objector_nick_id', NULL)
                        ->latest('submit_time')
                        ->first();
            $camp->is_archive = $isArchive;
            $camp->archive_action_time = 0;    // when change is agreed, revert archive_action to default state
            $camp->direct_archive = $directArchive;
            $camp->update();
        }
        return;
    }

    public static function checkIfUnarchiveChangeIsSubmitted($liveCamp)
    {
        if (!$liveCamp->is_archive)
            return false;

        return self::where([
            'topic_num' => $liveCamp->topic_num,
            'camp_num' => $liveCamp->camp_num,
            'objector_nick_id' => null,
            'grace_period' => 0,
        ])->where('go_live_time', '>', time())->exists();
    }
    
    public static function checkIfParentCampDisabledSubCampFunctionality($camp)
    {
        if (empty($camp->parent_camp_num)) {
            return ['is_disabled' => $camp->is_disabled, 'is_one_level' => $camp->is_one_level];
        }
        $camp = self::getLiveCamp(['topicNum' => $camp->topic_num, 'campNum' => $camp->parent_camp_num]);

        ['is_disabled' => $parentIsDisabled, 'is_one_level' => $parentIsOneLevel] = self::checkIfParentCampDisabledSubCampFunctionality($camp);
        return ['is_disabled' => $camp->is_disabled || $parentIsDisabled, 'is_one_level' => $camp->is_one_level || $parentIsOneLevel];
    }

    public static function getCampLeaderNickId($topic_num, $camp_num, $as_of = 'default') {
        $camp = self::getLiveCamp(['topicNum' => $topic_num, 'campNum' => $camp_num, 'asOf' => $as_of], ['camp_leader_nick_id']);
        return $camp && $camp->camp_leader_nick_id > 0 ? $camp->camp_leader_nick_id : null;
    }

    public static function updateCampLeaderFromLiveCamp(int $topic_num, int $camp_num, ?int $new_camp_leader_nick_id)
    {
        // Replicate live camp with minor tweaks.
        $camp = self::getLiveCamp(['topicNum' => $topic_num, 'campNum' => $camp_num, 'asOf' => 'default'])->replicate();
        $old_camp_leader_nick_id = $camp->camp_leader_nick_id;
        if ($new_camp_leader_nick_id !== $camp->camp_leader_nick_id) {

            $camp->fill([
                'submit_time' => time(),
                'go_live_time' => time(),
                'camp_leader_nick_id' => $new_camp_leader_nick_id,
            ]);
            $camp->save();

            // Dispatch job to update mongoDB cache.
            $topic = Topic::getLiveTopic($topic_num);
            Util::dispatchJob($topic, $camp_num, 1);

            // Log of system assigned/remove camp leader
            if(!is_null($new_camp_leader_nick_id)) {
                self::dispatchCampLeaderActivityLogJob($topic, $camp, $new_camp_leader_nick_id, request()->user(), 'assigned');
            }

            if(!is_null($old_camp_leader_nick_id)) {
                self::dispatchCampLeaderActivityLogJob($topic, $camp, $old_camp_leader_nick_id, request()->user(), 'removed');
            }
        }
    }

    public static function dispatchCampLeaderActivityLogJob($topic, $camp, $nick_name_id, User $user, $action = 'others')
    {
        $nickName = Nickname::getNickName($nick_name_id)->nick_name;
        $link = Util::getTopicCampUrlWithoutTime($topic->topic_num, $camp->camp_num, $topic, $camp, time());

        switch ($action) {
            case 'assigned':
                $activityMessage = trans('message.activity_log_message.assigned_as_camp_leader', ['nick_name' => $nickName]);
                break;

            case 'removed':
                $activityMessage = trans('message.activity_log_message.removed_as_camp_leader', ['nick_name' => $nickName]);
                break;

            default:
                $activityMessage = '';
                break;
        }

        $activitLogData = [
            'log_type' => 'topic/camps',
            'activity' => $activityMessage,
            'url' => $link,
            'model' => $camp,
            'topic_num' => $topic->topic_num,
            'camp_num' => $camp->camp_num,
            'user' => $user,
            'nick_name' => $nickName,
            'description' =>  $camp->camp_name
        ];
        dispatch(new ActivityLoggerJob($activitLogData))->onQueue(env('ACTIVITY_LOG_QUEUE'));
    }

    public static function getNominatedCampLeaderInReviewChanges($topic_num, $camp_num, $camp_leader_nick_id, $submit_time = null) {
        $submit_time = !is_null($submit_time) ? $submit_time : time();
        return self::where([
            ['topic_num', '=', $topic_num],
            ['camp_num', '=', $camp_num],
            ['camp_leader_nick_id', '=', $camp_leader_nick_id],
            ['submit_time', '<', $submit_time],
            ['go_live_time', '>', Carbon::now()->timestamp]
        ])->whereNull('objector_nick_id')->get();
    }
}

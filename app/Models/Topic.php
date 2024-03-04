<?php

namespace App\Models;

use App\Models\Camp;
use App\Facades\Util;
use App\Models\Search;
use App\Helpers\ElasticSearch;
use App\Jobs\ForgetCacheKeyJob;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

use App\Library\wiki_parser\wikiParser as wikiParser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Topic extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, HasApiTokens, Authorizable, HasFactory;

    protected $table = 'topic';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['topic_name','is_disabled','is_one_level', 'namespace_id', 'submit_time', 'submitter_nick_id', 'go_live_time', 'language', 'note', 'grace_period', 'topic_num'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public static function boot()
    {
        static::created(function ($model) {
            ## while creating topic for very first time ##
            ## this will not run when updating ##
           // dd($model);
            if ($model->topic_num == '' || $model->topic_num == null) {
                $nextTopicNum = DB::table('topic')->max('topic_num');
                $nextTopicNum++;
                $model->topic_num = $nextTopicNum;
                $model->update();

                ## create agreement ##
                $camp = new Camp();
                $camp->topic_num = $model->topic_num;
                $camp->parent_camp_num = null;
                $camp->camp_num = 1;
                $camp->key_words = '';
                $camp->language = $model->language;
                $camp->note = $model->note;
                $camp->submit_time = time();
                $camp->submitter_nick_id = $model->submitter_nick_id;
                $camp->go_live_time = $model->go_live_time;
                $camp->title = $model->topic_name;
                $camp->camp_name = Camp::AGREEMENT_CAMP;
                $camp->camp_leader_nick_id = $model->submitter_nick_id;

                $camp->save();

                Camp::dispatchCampLeaderActivityLogJob($model, $camp, $camp->camp_leader_nick_id, request()->user(), 'assigned');
            }
        });

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
            $typeValue = $item->topic_name;
            $topicNum = $item->topic_num;
            $campNum = 1;
            $campName = 'Agreement';
            $goLiveTime = $item->go_live_time;
            $namespace = $namespaceLabel; //fetch namespace
            $breadcrumb = '';
            $link =  ''; //self::campLink($topicNum, $campNum, $liveTopic->topic_name, $campName, true);
            if($campNum == 1){            
                $type = "topic";
                $typeValue = $liveTopic->topic_name;
                $id = "topic-". $topicNum;
                $link = self::topicLink($topicNum, $campNum, $typeValue, $campName, true);
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



        parent::boot();
    }

    public static function forgetCache($item)
    {
        $cacheKeysToRemove = [
            'live_topic_default-' . $item->topic_num,
            'live_topic_review-' . $item->topic_num
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

    public function nameSpace()
    {
        return $this->hasOne('App\Models\Namespaces', 'id', 'namespace_id');
    }

    public static function getLiveTopic($topicNum, $filter = array(), $asofdate = null)
    {
        $liveTopicCacheKey = 'live_topic_default-' . $topicNum;
        $reviewTopicCacheKey = 'live_topic_review-' . $topicNum;
        switch ($filter) {
            case "default":
                $topic = Cache::remember($liveTopicCacheKey, (int)env('CACHE_TIMEOUT_IN_SECONDS'), function () use ($topicNum) {
                    return self::where('topic_num', $topicNum)
                        ->where('objector_nick_id', '=', NULL)
                        ->where('go_live_time', '<=', time())
                        ->latest('submit_time')->first();
                });
                return $topic;
                break;
            case "review":
                $topic = Cache::remember($reviewTopicCacheKey, (int)env('CACHE_TIMEOUT_IN_SECONDS'), function () use ($topicNum) {
                    return self::where('topic_num', $topicNum)
                        ->where('objector_nick_id', '=', NULL)
                        ->where('grace_period', 0) 
                        ->latest('submit_time')->first();
                });
                return $topic;
                break;
            case "bydate":
                $asOfDate = strtotime(date('Y-m-d H:i:s', strtotime($asofdate)));
                return self::where('topic_num', $topicNum)
                    ->where('go_live_time', '<=', $asOfDate)
                    ->where(function($query) use($asOfDate) {
                        return $query->where('objector_nick_id', '=', NULL)
                        ->orWhere('go_live_time', $asOfDate);
                    })
                    ->latest('go_live_time')->first();
                break;
            default:
                $topic = Cache::remember($liveTopicCacheKey, (int)env('CACHE_TIMEOUT_IN_SECONDS'), function () use ($topicNum) {
                    return self::where('topic_num', $topicNum)
                        ->where('objector_nick_id', '=', NULL)
                        ->where('go_live_time', '<=', time())
                        ->latest('submit_time')->first();
                });
                return $topic;
        }
    }

    public static function topicLink($topicNum, $campNum = 1, $title, $campName = 'Agreement', $forSearch = false)
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
        
        
        //return $link = config('global.APP_URL_FRONT_END') . ('/topic/' . $topicId . '/' . $campId);
    }

    public static function getTopicHistory($filter, $request, $topicHistoryQuery )
    {
        $liveTopic = Topic::getLiveTopic($filter['topicNum'],'default');

        $topicHistoryQuery->when($filter['type'] == "old", function ($q) use ($filter, $liveTopic) {
                $q->where('go_live_time', '<=', $filter['currentTime'])
                ->where('objector_nick_id', NULL)
                ->where('id', '!=', $liveTopic->id)
                ->where('submit_time', '<', $filter['currentTime']);
        });

        $topicHistoryQuery->when($filter['type'] == "live", function ($q) use ($liveTopic) {
                $q->where('id', $liveTopic->id);
        });

         $topicHistoryQuery->when($filter['type'] == "in_review", function ($q) use ($filter) {
                $q->where('go_live_time', '>', $filter['currentTime'])
                ->where('objector_nick_id' , NULL)
                ->where('submit_time', '<=', $filter['currentTime']);
        });

        $topicHistoryQuery->when($filter['type'] == "objected", function ($q) {
            $q->where('objector_nick_id', '!=', NULL);
        });

        $topicHistoryQuery->when($filter['type'] == "all", function ($q) use ($filter) {
            $q->where('submit_time', '<=', $filter['currentTime']);
        });

        $response = Util::getPaginatorResponse($topicHistoryQuery->paginate($filter['per_page']));
        $response = self::filterTopicHistory($response, $filter, $liveTopic, $request);
        return $response;
    }


    public static function filterTopicHistory($response, $filter, $liveTopic, $request)
    {
        $topicHistory = [];
        if (isset($response->items) && count($response->items) > 0) {
            $nickNameIds = isset($request->user()->id) ? Nickname::getNicknamesIdsByUserId($request->user()->id) : [];
            foreach ($response->items as $val) {
                $submitterUserID = Nickname::getUserIDByNickNameId($val->submitter_nick_id);
                $submittime = $val->submit_time;
                $starttime = time();
                $endtime = $submittime + 60 * 60;
                $interval = $endtime - $starttime;
                $val->objector_nick_name = null;
                $namespace = Namespaces::find($val->namespace_id);
                $namespaceLabel = '';
                if (!empty($namespace)) {
                    $namespaceLabel = Namespaces::getNamespaceLabel($namespace, $namespace->name);
                }
                $val->namespace = $namespaceLabel;
                $val->unsetRelation('nameSpace');
                $val->submitter_nick_name=NickName::getNickName($val->submitter_nick_id)->nick_name;
                $val->isAuthor = (isset($request->user()->id) && $submitterUserID == $request->user()->id) ?  true : false ;
                $val->agreed_to_change = 0;

                /*
                *   https://github.com/the-canonizer/Canonizer-Beta--Issue-Tracking/issues/232 
                *   Now support at the time of submition will be count as total supporter. 
                *   Also check if submitter is not a direct supporter, then it will be count as direct supporter   
                */
                $val->total_supporters = Support::getTotalSupporterByTimestamp('topic', (int)$filter['topicNum'], (int)$filter['campNum'], $val->submitter_nick_id, $submittime, $filter)[1];
                $agreed_supporters = ChangeAgreeLog::where('topic_num', '=', $filter['topicNum'])
                    ->where('camp_num', '=', $filter['campNum'])
                    ->where('change_id', '=', $val->id)
                    ->where('change_for', '=', 'topic')
                    ->get()->pluck('nick_name_id')->toArray();
                
                $val->agreed_supporters = count($agreed_supporters);

                if($val->submitter_nick_id > 0 && !in_array($val->submitter_nick_id, $agreed_supporters)) 
                {   
                    $val->agreed_supporters++;
                }

                $nickNames = Nickname::personNicknameArray();
                $val->ifIamSupporter = Support::ifIamSupporterForChange($filter['topicNum'], $filter['campNum'], $nickNames, $submittime);
                $val->ifIAmExplicitSupporter = Support::ifIamExplicitSupporterBySubmitTime($filter, $nickNames, $submittime, null, false, 'ifIamExplicitSupporter');


                switch ($val) {
                    case $val->objector_nick_id !== NULL:
                        $val->status = "objected";
                        $val->objector_nick_name = $val->objectorNickName->nick_name;
                        $val->unsetRelation('objectorNickName');
                        break;
                    case $filter['currentTime'] < $val->go_live_time && $filter['currentTime'] >= $val->submit_time:
                        $val->agreed_to_change = (int) ChangeAgreeLog::whereIn('nick_name_id', $nickNameIds)
                        ->where('change_for', '=', 'topic')
                        ->where('change_id', '=', $val->id)
                        ->exists(); 
                        $val->status = "in_review";
                        break;
                    case $liveTopic->id == $val->id && $filter['type'] != "old":
                        $val->status = "live";
                        break;
                    default:
                        $val->status = "old";
                }
                if (($interval > 0 && $val->grace_period > 0)  && (( isset($request->user()->id) && $request->user()->id != $submitterUserID ) || !isset($request->user()->id)) ) {
                    continue;
                } else {
                    $WikiParser = new wikiParser;
                    $val->parsed_value = $WikiParser->parse($val->value);
                    array_push($topicHistory, $val);
                }
            }
            $response->items = $topicHistory;
        }
        return  $response;
    }

    public static function ifTopicNameAlreadyTaken($data)
    {
        $liveTopicData = self::select('topic.*')
        ->join('camp','camp.topic_num','=','topic.topic_num')
        ->where('camp.camp_name','=','Agreement')
        ->where('topic.topic_name', $data['topic_name'])
        ->where('topic.objector_nick_id',"=",null)
        ->whereRaw('topic.go_live_time in (select max(go_live_time) from topic where objector_nick_id is null and go_live_time < "' . time() . '" group by topic_num)')
        ->where('topic.go_live_time', '<=', time())                            
        ->latest('submit_time')
        ->first();

        $nonLiveTopicData = self::select('topic.*')
        ->join('camp','camp.topic_num','=','topic.topic_num')
        ->where('camp.camp_name','=','Agreement')
        ->where('topic_name', $data['topic_name'])
        ->where('topic.objector_nick_id',"=",null)
        ->where('topic.go_live_time',">",time())
        ->first();

        if((isset($liveTopicData) && $liveTopicData->topic_num != $data['topic_num'])) {
            $response['topic_name'][] = trans('message.validation_topic_store.topic_name_unique');
            $response['existed_topic_reference']["topic_name"] = $liveTopicData->topic_name ?? "";
            $response['existed_topic_reference']["topic_num"] = $liveTopicData->topic_num ?? "";

        } else if ((isset($nonLiveTopicData) && $nonLiveTopicData->topic_num != $data['topic_num'])) {
            $response['topic_name'][] = trans('message.validation_topic_store.topic_name_unique');
            $response['existed_topic_reference']["topic_name"] = $nonLiveTopicData->topic_name ?? "";
            $response['existed_topic_reference']["topic_num"] = $nonLiveTopicData->topic_num ?? "";
            $response['existed_topic_reference']["under_review"] = 1;
        }

        return $response ?? [];
    }

    public static function getTopicFirstName($topicNumber) {
        return self::where('topic_num', $topicNumber)->pluck('topic_name')->first();
    }


}

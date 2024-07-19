<?php

namespace App\Models;

use Carbon\Carbon;
use App\Facades\Util;
use App\Models\Nickname;
use App\Helpers\ElasticSearch;
use App\Jobs\ForgetCacheKeyJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use App\Library\wiki_parser\wikiParser as wikiParser;

class Statement extends Model
{
    protected $table = 'statement';
    public $timestamps = false;


    /**
     * 
     */ 
    public static function boot() 
    {
        parent::boot();
            
        static::saved(function($item) 
        {
            //forget cache
            self::forgetCache($item);
            
            $topicNum  = $item->topic_num;
            $campNum = $item->camp_num;
            $liveTopic = Topic::getLiveTopic($item->topic_num);
            $filter['topicNum'] = $item->topic_num;
            $filter['asOf'] = '';
            $filter['campNum'] = $item->camp_num;
            $liveCamp = Camp::getLiveCamp($filter);
            $id = "statement-" .$item->topic_num ."-" . $item->camp_num;
            $type = "statement";
            $typeValue = $item->parsed_value;
            $goLiveTime = $item->go_live_time;
            $namespace = '';
            $campName = $liveCamp->camp_name;
            if (!empty($namespace)) {
                $namespaceLabel = Namespaces::getNamespaceLabel($namespace, $namespace->name);
                $namespaceLabel = Namespaces::stripAndChangeSlashes($namespaceLabel);
            }            
            $link = '';  //Camp::campLink($topicNum, $campNum, $liveTopic->topic_name, $campName);
            // breadcrumb
            $breadcrumb = Search::getCampBreadCrumbData($liveTopic, $topicNum, $campNum);
            
            if($item->go_live_time <= time()){ 
                // then update table
                ElasticSearch::ingestData($id, $type, $typeValue, $topicNum, $campNum, $link, $goLiveTime, $namespace, $breadcrumb);
            }
        });
    }

    public static function forgetCache($item)
    {
        $cacheKeysToRemove = [
            'live_statement_default-' . $item->topic_num . '-' . $item->camp_num,
            'live_statement_review-' . $item->topic_num . '-' . $item->camp_num
        ];
        foreach ($cacheKeysToRemove as $key) {
            Cache::forget($key);
        }
        if ($item->go_live_time > time()) {
            dispatch(new ForgetCacheKeyJob($cacheKeysToRemove, Carbon::createFromTimestamp($item->go_live_time)));
        }
    }


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
        $cacheKey = 'live_statement_default-' . $filter['topicNum'] . '-' . $filter['campNum'];
        $statement = Cache::remember($cacheKey, (int)env('CACHE_TIMEOUT_IN_SECONDS'), function () use ($filter) {
            return self::where('topic_num', $filter['topicNum'])
                ->where('camp_num', $filter['campNum'])
                ->where('objector_nick_id', '=', NULL)
                ->where('go_live_time', '<=', time())
                ->orderBy('submit_time', 'desc')
                ->first();
        });
        return $statement;
    }

    public static function reviewAsofFilter($filter)
    {
        $cacheKey = 'live_statement_review-' . $filter['topicNum'] . '-' . $filter['campNum'];
        $statement = Cache::remember($cacheKey, (int)env('CACHE_TIMEOUT_IN_SECONDS'), function () use ($filter) {
            return self::where('topic_num', $filter['topicNum'])
                ->where('camp_num', $filter['campNum'])
                ->where('objector_nick_id', '=', NULL)
                ->where('grace_period', 0) 
                ->orderBy('go_live_time', 'desc')
                ->first();
        });
        return $statement;
    }

    public static function byDateFilter($filter)
    {
        $asofdate = strtotime(date('Y-m-d H:i:s', strtotime($filter['asOfDate'])));
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', $filter['campNum'])
            ->where('go_live_time', '<=', $asofdate)
            ->orderBy('go_live_time', 'desc')
            ->first();
    }

    private static function liveStatementAsOfFilter($filter)
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

    public function objectorNickName()
    {
        return $this->hasOne('App\Models\Nickname', 'id', 'objector_nick_id');
    }

    public function submitterNickName()
    {
        return $this->hasOne('App\Models\Nickname', 'id', 'submitter_nick_id');
    }

    public static function statementHistory($statement_query, $response, $filter, $campLiveStatement, $request)
    {
        $statement_query->where('is_draft', 0);
        $statement_query->when($filter['type'] == "objected", function ($q) {
            $q->where('objector_nick_id', '!=', NULL);
        });

        $statement_query->when($filter['type'] == "in_review", function ($q) use ($filter) {
            $q->where('go_live_time', '>', $filter['currentTime'])
                ->where('submit_time', '<=', $filter['currentTime'])
                ->where('objector_nick_id', NULL);
        });

        $statement_query->when($filter['type'] == "live", function ($q) use ($campLiveStatement) {
            $q->where('id',  $campLiveStatement->id ?? 0);
        });

        $statement_query->when($filter['type'] == "old", function ($q) use ($filter,  $campLiveStatement) {
            $q->where('go_live_time', '<=', $filter['currentTime'])
                ->where('objector_nick_id', NULL)
                ->where('submit_time', '<=', $filter['currentTime']);
                
            if(!is_null($campLiveStatement)) {
                $q->where('id', '!=', $campLiveStatement->id);
            }
        });


        $response->statement = Util::getPaginatorResponse($statement_query->paginate($filter['per_page']));
        $response = self::filterStatementHistory($response, $filter, $request, $campLiveStatement);
        return $response;
    }

    public static function filterStatementHistory($response, $filter, $request, $campLiveStatement)
    {
        $data = $response->statement;
        unset($response->statement);
        $data->details = $response;
        $statementHistory = [];
        $nickNameIds = isset($request->user()->id) ? Nickname::getNicknamesIdsByUserId($request->user()->id) : [];
        if (isset($data->items) && count($data->items) > 0) {
            foreach ($data->items as $val) {
                $submitterUserID = Nickname::getUserIDByNickNameId($val->submitter_nick_id);
                $submittime = $val->submit_time;
                $starttime = time();
                $endtime = $submittime + 60 * 60;
                $interval = $endtime - $starttime;
                $val->objector_nick_name = null;
                $val->agreed_to_change = 0;
                $val->submitter_nick_name=NickName::getNickName($val->submitter_nick_id)->nick_name;
                $val->isAuthor = (isset($request->user()->id) && $submitterUserID == $request->user()->id) ?  true : false ;
                
                /*
                *   https://github.com/the-canonizer/Canonizer-Beta--Issue-Tracking/issues/232 
                *   Now support at the time of submition will be count as total supporter. 
                *   Also check if submitter is not a direct supporter, then it will be count as direct supporter   
                */
                // $val->total_supporters = Support::getAllSupporters($filter['topicNum'], $filter['campNum'], $val->submitter_nick_id);
                $val->total_supporters = Support::getTotalSupporterByTimestamp('statement', (int)$filter['topicNum'], (int)$filter['campNum'], $val->submitter_nick_id, $submittime, $filter)[1];
                
                $agreed_supporters = ChangeAgreeLog::where('topic_num', '=', $filter['topicNum'])
                    ->where('camp_num', '=', $filter['campNum'])
                    ->where('change_id', '=', $val->id)
                    ->where('change_for', '=', 'statement')
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
                    case $val->is_draft === 1:
                        $val->status = "draft";
                        $val->agreed_supporters = 0;
                        $val->total_supporters = 0;
                        break;
                    case $val->objector_nick_id !== NULL:
                        $val->status = "objected";
                        $val->objector_nick_name = $val->objectorNickName->nick_name;
                        $val->unsetRelation('objectorNickName');
                        break;
                    case $filter['currentTime'] < $val->go_live_time && $filter['currentTime'] >= $val->submit_time:
                        $val->agreed_to_change = (int) ChangeAgreeLog::whereIn('nick_name_id', $nickNameIds)
                        ->where('change_for', '=', 'statement')
                        ->where('change_id', '=', $val->id)
                        ->exists(); 
                        $val->status = "in_review";
                        break;
                    case $campLiveStatement->id == $val->id && $filter['type'] != "old":
                        $val->status = "live";
                        break;
                    default:
                        $val->status = "old";
                }
                if (($interval > 0 && $val->grace_period > 0)  && (( isset($request->user()->id) && $request->user()->id != $submitterUserID ) || !isset($request->user()->id)) ) {
                    continue;
                } else {
                    $WikiParser = new wikiParser;
                    $val->parsed_value = $val->parsed_value;// $WikiParser->parse($val->value);
                    array_push($statementHistory, $val);
                }
            }
        }
        $data->items = $statementHistory;
        return  $data;
    }

    public static function getDraftRecord(int $topic_num, int $camp_num)
    {
        if ($draft = self::where('topic_num', $topic_num)->where('camp_num', $camp_num)->where('is_draft', 1)->first()) {
            return $draft->id;
        }
        return null;
    }
}

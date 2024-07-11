<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Facades\Util;
use Carbon\Carbon;
use DB;
use App\Helpers\ElasticSearch;
use App\Helpers\TopicSupport;
use App\Models\Nickname;

class Support extends Model
{

    protected $primaryKey = 'support_id';
    protected $table = 'support';
    public $timestamps = false;


    protected $fillable = ['reason','citation_link','reason_summary','is_system_generated','nick_name_id', 'topic_num', 'camp_num', 'delegate_nick_name_id', 'start', 'end', 'flags', 'support_order'];


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public function getStartAttribute($value)
    {
        return date("Y-m-d", strtotime($value));
    }

    /** on adding or removing support it needs to be updated on elastic search agains nickname for support count */
    public static function boot() 
    {
        parent::boot();
            
        static::saved(function($item) 
        {    
            $type = "nickname";
            $id = "nickname-" . $item->nick_name_id;
            // get nickname
            $nickNameId = $item->nick_name_id;
            $nicknameModel = Nickname::getNickName($item->nick_name_id);
    
            $typeValue = $nicknameModel->nick_name;
            $topicNum = 0;
            $campNum = 0;
            $goLiveTime = '';
            $namespace = '';
            $breadcrumb = '';
            $supportCount = self::getTotalSupportedCamps([$item->nick_name_id]);
            $namespaceId = 1; //default
            $userId = Nickname::getUserIDByNickNameId($item->nick_name_id);
            $link = Nickname::getNickNameLink($item->nick_name_id, $namespaceId,'','',true);
            $statementNum =  '';
            
            if($nicknameModel->private){
                return;
            }
            //echo $supportCount;
            ElasticSearch::ingestData($id, $type, $typeValue, $topicNum, $campNum, $link, $goLiveTime, $namespace, $breadcrumb, $statementNum, $nickNameId, $supportCount);
        
        });
    }

    public static function getAllDirectSupporters($topic_num,$camp_num=1){
        $filter = [
            'topicNum' => $topic_num,
            'campNum'  => $camp_num
        ];
        $camp  = Camp::getLiveCamp($filter);
        Camp::clearChildCampArray();
        $subCampIds = array_unique(Camp::getAllChildCamps($camp));
        $directSupporter = [];
        $alreadyExists = [];
        foreach ($subCampIds as $camp_id) {            
            $data = self::getDirectSupporter($topic_num, $camp_id);
            if(isset($data) && count($data) > 0){
                foreach($data as $key=>$value){
                    if(!in_array($value->nick_name_id, $alreadyExists,TRUE)){
                        $directSupporter[] = $value;
                         $alreadyExists[] = $value->nick_name_id;
                     }  
                }
                  
            }
            
        }
        return $directSupporter;
    }

    public static function getDirectSupporter($topic_num, $camp_num = 1, $addition_columns = [])
    {
        $as_of_time = time();
        return Support::where('topic_num', '=', $topic_num)
            ->where('camp_num', '=', $camp_num)
            ->where('delegate_nick_name_id', 0)
            ->whereRaw("(start <= $as_of_time) and ((end = 0) or (end > $as_of_time))")
            ->orderBy('start', 'DESC')
            ->groupBy('nick_name_id')
            ->select(array_merge(['nick_name_id', 'support_order', 'topic_num', 'camp_num'], $addition_columns))
            ->get();
    }

    public static function ifIamSupporter($topic_num, $camp_num, $nick_names, $submit_time = null, $delayed = false)
    {
        if ($submit_time) {
            if ($delayed) {
                $support = self::where('topic_num', '=', $topic_num)->where('camp_num', '=', $camp_num)->whereIn('nick_name_id', $nick_names)->where('delegate_nick_name_id', 0)->where('end', '=', 0)->where('start', '>', $submit_time)->first();
            } else {
                $support = self::where('topic_num', '=', $topic_num)->where('camp_num', '=', $camp_num)->whereIn('nick_name_id', $nick_names)->where('delegate_nick_name_id', 0)->where('end', '=', 0)->where('start', '<=', $submit_time)->first();
            }
        } else {
            $support = self::where('topic_num', '=', $topic_num)->where('camp_num', '=', $camp_num)->whereIn('nick_name_id', $nick_names)->where('delegate_nick_name_id', 0)->where('end', '=', 0)->first();
        }

        return !empty($support) ? $support->nick_name_id : 0;
    }

    /*
    *   https://github.com/the-canonizer/Canonizer-Beta--Issue-Tracking/issues/232 
    *   Now support at the time of submition will be count as total supporter. 
    *   Also check if submitter is not a direct supporter, then it will be count as direct supporter   
    */
    public static function ifIamSupporterForChange($topic_num, $camp_num, $nick_names, $submit_time = null, $delayed = false)
    {
        $support = self::where('topic_num', '=', $topic_num)
            ->where('camp_num', '=', $camp_num)
            ->whereIn('nick_name_id', $nick_names)
            ->where('delegate_nick_name_id', 0)
            ->whereRaw('? between `start` and IF(`end` > 0, `end`, 9999999999)', [$submit_time])
            ->first();

        return !empty($support) ? $support->nick_name_id : 0;
    }


    /*Delegation support 
     *   1.  check if user has any support
     *   2.  if yes, then find any sub-deleagted which will go recursively
     *   3. remove support for existing.
     *   4. add new support 
     *  5. All these above will work in recursive ways
     * 
    public static function addDelegationSupport($support = array(), $topicNum, $nickNameId, $deleagtedNicknameId)
    {
        $existingSupport =  self::getActiveSupporInTopic($topicNum, $nickNameId);
        $delegators = [];
        if (count($existingSupport) > 0) {
            $delegators = self::getDelegatorForNicknameId($topicNum, $nickNameId);
            self::removeSupport($topicNum, $nickNameId);
        }

        self::addSupport($support, $topicNum, $nickNameId, $deleagtedNicknameId);

        if (isset($delegators) && count($delegators) > 0) {
            foreach ($delegators as $delegator) {
                return self::addDelegationSupport($support, $topicNum, $delegator->nick_name_id, $delegator->delegate_nick_name_id);
            }
        }
    }*/

    /**
     *  Add Support
     *  @param support is array of support for which new support is to be added
     */
    public static function addSupport($support, $topicNum, $nickNameId, $deleagtedNicknameId)
    {
        foreach ($support as $sp) {

            $model = new Support();
            $model->topic_num = $topicNum;
            $model->camp_num = $sp->camp_num;
            $model->nick_name_id = $nickNameId;
            $model->delegate_nick_name_id = $deleagtedNicknameId;
            $model->support_order = $sp->support_order;
            $model->start = time();
            $model->save();
        }
        return;
    }

    /**
     *  Get Active support
     *  @param $topicNum is integer, topic number
     *  @param $nicknameId  nick id of user from which topic is being supported and yet not ended.
     *  
     */
    public static function getActiveSupporInTopic($topicNum, $nickNameId)
    {
        $usersNickNames = Nickname::getAllNicknamesByNickId($nickNameId);
        $supports = self::getActiveSupporInTopicWithAllNicknames($topicNum, $usersNickNames);

        return $supports;
    }

    /**
     *  Fetch the delegator of given NickID in specified topic
     */
    public static function getDelegatorForNicknameId($topicNum, $nickNameId)
    { 
        $usersNickNames = Nickname::getAllNicknamesByNickId($nickNameId);
        $delegatorsSupport = self::getActiveDelegators($topicNum, $usersNickNames);

        return $delegatorsSupport;
    }

    /**
     * Remove support from of user from topic
     * This will remove support from all nicknames of that user with nick id @param $nickNameId
     * 
     */
    public static function removeSupport($topicNum, $nickNameId='', $campNum = '')
    {
        $usersNickNames = Nickname::getAllNicknamesByNickId($nickNameId);
        self::removeSupportWithAllNicknames($topicNum, $campNum, $usersNickNames);

        return;
    }

    public static function getActiveDelegators($topicNum, $usersNickNames)
    {
        $delegators = self::where('topic_num', '=', $topicNum)
            ->whereIn('delegate_nick_name_id', $usersNickNames)
            ->where('end', '=', 0)
            ->groupBy('nick_name_id')
            ->get();

        return $delegators;
    }

    public static function getActiveSupporInTopicWithAllNicknames($topicNum, $nickNames, $onlyDirectSupport = false)
    {
        if($onlyDirectSupport){
            $supports = self::where('topic_num', '=', $topicNum)
            ->whereIn('nick_name_id', $nickNames)
            ->where('delegate_nick_name_id', 0)
            ->orderBy('support_order', 'ASC')
            ->where('end', '=', '0')->get();
        }else{
            $supports = self::where('topic_num', '=', $topicNum)
            ->whereIn('nick_name_id', $nickNames)
            ->orderBy('support_order', 'ASC')
            ->where('end', '=', '0')->get();
        }

        return $supports;
    }

    public static function removeSupportWithAllNicknames($topicNum, $campNum = array(), $nickNames = array(), $reason = null, $reason_summary = null, $citation_link = null, $addition_data = [])
    {
        // dd($topicNum, $campNum, $nickNames, $reason, $reason_summary, $citation_link);

        $campNumToRemove = [];
        if (empty($campNum) && isset($addition_data['route'], $addition_data['action'], $addition_data['type'])) {
            if ($addition_data['route'] === 'support/update' && $addition_data['action'] === 'all' && $addition_data['type'] === 'direct') {
                $campNumToRemove = Support::where([
                    'topic_num' => $topicNum,
                    'end' => 0,
                ])->whereIn('nick_name_id', $nickNames)->get()->pluck('camp_num')->toArray();
            }
        }

        if (!empty($campNum)) {
            $supports = self::where('topic_num', '=', $topicNum)->where('end', 0)
                ->whereIn('camp_num', $campNum)
                ->whereIn('nick_name_id', $nickNames)
                ->update(['end' => time(),'reason'=>$reason,'reason_summary'=>$reason_summary,'citation_link'=>$citation_link]);
        } else {
            $supports = self::where('topic_num', '=', $topicNum)->where('end', 0)
                ->whereIn('nick_name_id', $nickNames)
                ->update(['end' => time(),'reason'=>$reason,'reason_summary'=>$reason_summary,'citation_link'=>$citation_link]);
        }

        // Check camp leader remove his support
        if (empty($campNum))
            $campNum = $campNumToRemove;
        foreach ($campNum as $camp_num) {
            $camp_leader = Camp::getCampLeaderNickId($topicNum, $camp_num);
            if (!is_null($camp_leader) && in_array($camp_leader, $nickNames)) {
                $oldest_direct_supporter = TopicSupport::findOldestDirectSupporter($topicNum, $camp_num, $camp_leader, false, true);
                Camp::updateCampLeaderFromLiveCamp($topicNum, $camp_num, $oldest_direct_supporter->nick_name_id ?? null);
            }
        }

        return;
    }

    public static function promoteUpDelegates($topicNum, $nickNames, $delgateNickNameId = '')
    {
        /* 
        * In case of promotion of Delegate supporters, removing previous support and adding new support as direct supporter
        * Ticket: https://github.com/the-canonizer/Canonizer-Beta--Issue-Tracking/issues/695
        */

        $delgateNickNameId = (isset($delgateNickNameId) && !empty($delgateNickNameId)) ? $delgateNickNameId : 0;

        $delegatedSupporters = self::where('topic_num', '=', $topicNum)
            ->whereIn('delegate_nick_name_id', $nickNames)
            ->where('end', '=', 0)
            ->get();

        foreach ($delegatedSupporters as $key => $delegatedSupporter) {
            $newSupport = new self($delegatedSupporter->toArray());
            $newSupport->start = time();
            $newSupport->reason = "This old delegated supporter is now promoted as a direct supporter";
            $newSupport->delegate_nick_name_id = $delgateNickNameId;
            $newSupport->is_system_generated = 1;
            $newSupport->save();

            $delegatedSupporter->update(['end' => time()]);
        }
    }

    public static function getAllSupporters($topic, $camp, $excludeNickID)
    {
        $nickNameToExclude = [$excludeNickID];
        $support = self::where('topic_num', '=', $topic)->where('camp_num', '=', $camp)
            ->where('end', '=', 0)
            ->where('nick_name_id', '!=', $excludeNickID)
            ->where('delegate_nick_name_id', 0)->groupBy('nick_name_id')->get();
        $camp = Camp::where('camp_num', '=', $camp)->where('topic_num', '=', $topic)->first();
        $allChildren = Camp::getAllChildCamps($camp);
        $supportCount = 0;
        if (sizeof($support) > 0 || count($support) > 0) {
            foreach ($support as $sp) {
                $nickNameToExclude[] = $sp->nick_name_id;
            }
        }
        if (sizeof($allChildren) > 0) {
            foreach ($allChildren as $campnum) {
                $supportData = self::where('topic_num', $topic)
                    ->where('camp_num', $campnum)
                    ->whereNotIn('nick_name_id', $nickNameToExclude)
                    ->where('delegate_nick_name_id', 0)
                    ->where('end', '=', 0)
                    ->orderBy('support_order', 'ASC')
                    ->get();
                if (count($supportData) > 0) {
                    foreach ($supportData as $sp) {
                        $nickNameToExclude[] = $sp->nick_name_id;
                    }
                    $supportCount = $supportCount + count($supportData);
                }
            }
        }
        return count($support) + $supportCount;
    }

    public static function getTotalSupporterByTimestamp($action, $topicNum, $campNum, $submitterNickId, $submit_time = null, $additionalFilter = [], $includeCampLeader = true)
    {
        $submit_time = $submit_time ?: Carbon::now()->timestamp;

        // Number of supporters who were the supporter when change is submitted and then removed their support
        $totalSupporters[] = self::where('topic_num', '=', $topicNum)
            ->where('camp_num', '=', $campNum)
            ->where('delegate_nick_name_id', 0)
            ->whereRaw('? between `start` and `end`', [$submit_time])
            ->get()->pluck('nick_name_id')->toArray();


        // Number of supporters who are the supporter when change is submitted
        $totalSupporters[] = self::where('topic_num', '=', $topicNum)
            ->where('camp_num', '=', $campNum)
            ->where('delegate_nick_name_id', 0)
            ->where('start', '<', $submit_time)
            ->where('end', '=', 0)
            ->get()->pluck('nick_name_id')->toArray();

        // Also include explicit support count in total...
        if (count($additionalFilter)) {
            $nickNames = Nickname::personNicknameArray();
            $totalSupporters[] = self::ifIamExplicitSupporterBySubmitTime($additionalFilter, $nickNames, $submit_time, null, true, 'supporters')->pluck('nick_name_id')->toArray();
        }
        $totalSupporters = array_unique(array_merge(...$totalSupporters));

        if ($submitterNickId > 0 && !in_array($submitterNickId, $totalSupporters)) {
            $totalSupporters[] = $submitterNickId;
        }

        /**
         * Camp Leader: Camp leader can't object and agree, so exclude him from total supporter
         */
        if ($action === 'camp' && !$includeCampLeader && isset($additionalFilter['change_id'])) {
            $liveCamp = Camp::getLiveCamp(['topicNum' => $topicNum, 'campNum' => $campNum], ['camp_leader_nick_id']);
            $theChange = Camp::where(['topic_num' => $topicNum, 'camp_num' => $campNum, 'id' => $additionalFilter['change_id']])->first();

            if (!is_null($liveCamp->camp_leader_nick_id) && $liveCamp->camp_leader_nick_id !== $theChange->camp_leader_nick_id && $liveCamp->camp_leader_nick_id !== $submitterNickId) {
                $totalSupporters = array_values(array_filter($totalSupporters, function ($value) use ($liveCamp) {
                    return (int)$value > 0 && $value !== $liveCamp->camp_leader_nick_id;
                }));
            }
        }
        $totalSupporters = Nickname::select('id', 'nick_name')->whereIn('id', $totalSupporters)->get()->toArray();

        return [$totalSupporters, count($totalSupporters)];
    }

    public static function ifIamSingleSupporter($topic_num, $camp_num = 0, $userNicknames)
    {
        $supportFlag = 1;

        $query = self::where('topic_num', $topic_num)
            ->whereNotIn('nick_name_id', $userNicknames)
            ->where('delegate_nick_name_id', 0)
            ->where('end', '=', 0)
            ->orderBy('support_order', 'ASC');

        $query->when($camp_num != 0, function ($q) use ($camp_num) {
            $q->where('camp_num', $camp_num);
        });

        $otherSupports = $query->get();
        $otherSupports->filter(function ($item) use ($camp_num) {
            if ($camp_num) {
                return $item->camp_num == $camp_num;
            }
        });

        if ( count($otherSupports) ) {
            return 0;
        }

        if ( !$camp_num ) {
            $support = self::where('topic_num', $topic_num)
                ->whereNotIn('nick_name_id', $userNicknames)
                ->where('delegate_nick_name_id', 0)->where('end', '=', 0)
                ->orderBy('support_order', 'ASC')->count();
            if ( $support )
                return 0;

        }

        $camp = Camp::where('camp_num', '=', $camp_num)
            ->where('topic_num', '=', $topic_num)
            ->first();

        Camp::clearChildCampArray();
        $allChildren = Camp::getAllChildCamps($camp);

        foreach ($allChildren as $campnum) {
            $support = self::where('topic_num', $topic_num)
                ->where('camp_num', $campnum)
                ->whereNotIn('nick_name_id', $userNicknames)
                ->where('delegate_nick_name_id', 0)
                ->where('end', '=', 0)
                ->orderBy('support_order', 'ASC')->get();

            if (sizeof($support) > 0) {
                return 0;
            }
        }

        return  $supportFlag;
    }


    public static function checkIfSupportExists($topicNum, $nickNameId = [], $camps = [])
    {

        $support = self::where('topic_num', '=', $topicNum)
        ->whereIn('nick_name_id', $nickNameId);
        if(!empty($camps)){
            $support = $support->whereIn('camp_num', $camps);
        }
        $support = $support->where('end', '=', '0')->count();
        
        return $support;
    }


    public static function getDelgatedSupportInTopic($topicNum, $nickNames)
    {
        return Support::where('topic_num', $topicNum)
            ->where('end', '=', 0)
            ->where('delegate_nick_name_id','!=',0)
            ->whereIn('nick_name_id', $nickNames)
            ->get();
    }

    public static function getSupportedCampsList($topicNum, $user_id)
    {
       $query =  "SELECT  t2.topic_num,t2.camp_num, t2.support_order,t1.title,t2.camp_name, t2.start,t2.end,t2.support_id,t3.namespace_id,t2.nick_name_id,t2.delegate_nick_name_id
                    FROM
                        (SELECT a.topic_num,a.title 
                            FROM
                            camp a 
                            INNER JOIN 
                                (SELECT topic_num, MAX(go_live_time) AS live_time 
                                    FROM
                                    camp 
                                    WHERE objector_nick_id IS NULL 
                                    AND camp_num = 1 
                                    AND go_live_time <= UNIX_TIMESTAMP(NOW()) 
                                    GROUP BY topic_num) b 
                                    ON a.topic_num = b.topic_num 
                                    AND a.go_live_time = b.live_time 
                                    GROUP BY  a.topic_num, a.title) t1,
                            (SELECT b.topic_num,b.camp_num, c.support_order,b.title AS topic_name,b.camp_name,c.start,c.end, c.delegate_nick_name_id,c.support_id,c.nick_name_id
                            FROM
                                (SELECT a.* 
                                FROM camp a INNER JOIN 
                                    (SELECT  topic_num,camp_num,MAX(`go_live_time`) AS live_time 
                                    FROM camp 
                                        WHERE objector_nick_id IS NULL 
                                        AND go_live_time <= UNIX_TIMESTAMP(NOW()) 
                                        AND topic_num = $topicNum
                                    GROUP BY topic_num, camp_num) b 
                            ON a.topic_num = b.topic_num 
                            AND a.camp_num = b.camp_num 
                            AND a.go_live_time = b.live_time) b,
                            support c 
                            WHERE b.camp_num = c.camp_num 
                            AND b.topic_num = c.topic_num 
                            AND c.nick_name_id IN 
                            (SELECT 
                                id 
                            FROM nick_name WHERE user_id = $user_id) 
                            AND c.end = 0) t2,
                        (SELECT
                                a.topic_num,
                                a.namespace_id,
                                a.go_live_time
                            FROM
                                topic a,
                                (SELECT
                                topic_num,
                                go_live_time
                            FROM
                                topic
                            WHERE 
                                go_live_time <= UNIX_TIMESTAMP(NOW())
                            GROUP BY topic_num
                                ) b
                            WHERE 
                            a.topic_num = b.topic_num
                            AND a.go_live_time = b.go_live_time
                            AND objector_nick_id IS NULL
                            AND a.go_live_time <= UNIX_TIMESTAMP(NOW())
                            GROUP BY topic_num,
                                namespace_id) t3 

                    WHERE t1.topic_num = t2.topic_num AND t1.topic_num = t3.topic_num ORDER BY t2.support_order ASC,t2.start DESC, t2.topic_num";


        $result = DB::select($query);

        $result = array_map(function ($value) {
          return (array) $value;
        }, $result);
        return $result;
    }

    public static function ifIamImplicitSupporter($filter,$nickNames,$submit_time = null){
        $liveCamp = Camp::getLiveCamp($filter);
        $allChildCamps = Camp::getAllChildCamps($liveCamp);
        $support = self::where('topic_num','=',$filter['topicNum'])->whereIn('camp_num',$allChildCamps)->whereIn('nick_name_id',$nickNames)->where('delegate_nick_name_id',0)->where('end','=',0)
        ->where(function($query) use ($submit_time)
        {
        if ($submit_time) {
            $query->where('start','<=',$submit_time);
        }
        })->first();

        return isset($support) ? $support->nick_name_id : 0 ;
    }

    public static function ifIamExplicitSupporter($filter,$nickNames, $type = null){
            Camp::clearChildCampArray();
            $childCamps = [];
            if($type == "topic"){
                $childCamps = Camp::select('camp_num')->where('topic_num', $filter['topicNum'])
                ->where('go_live_time', '<=', time())
                ->groupBy('camp_num')
                ->get()
                ->toArray();
            }else{
                $liveCamp = Camp::getLiveCamp($filter);
                $childCamps = array_unique(Camp::getAllChildCamps($liveCamp));
                $key = array_search($liveCamp->camp_num, $childCamps, true);
                if ($key !== false) {
                    unset($childCamps[$key]);
                }
            }
            $mysupports = Support::where('topic_num', $filter['topicNum'])
                            ->whereIn('camp_num', $childCamps)
                            ->whereIn('nick_name_id', $nickNames)
                            ->where('end', '=', 0)
                            ->where('delegate_nick_name_id', '=', 0)
                            ->orderBy('support_order', 'ASC')
                            ->groupBy('camp_num')->get();
            return (count($mysupports)) ? true : false;
    }

    public static function ifIamExplicitSupporterBySubmitTime($filter, $nickNames, $submittime, $type = null, $includeImplicitSupporters = false, $returnKey = '')
    {
        Camp::clearChildCampArray();
        $childCamps = [];
        if ($type == "topic") {
            $childCamps = Camp::select('camp_num')->where('topic_num', $filter['topicNum'])
            ->where('go_live_time', '<=', time())
                ->groupBy('camp_num')
                ->get()
                ->toArray();
        } else {
            $liveCamp = Camp::getLiveCamp($filter);
            $childCamps = array_unique(Camp::getAllChildCamps($liveCamp));
            $key = array_search($liveCamp->camp_num, $childCamps, true);
            if ($key !== false) {
                unset($childCamps[$key]);
            }
        }

        $query = Support::query();

        $query->where('topic_num', $filter['topicNum']);

        // if (count($childCamps) > 0)
            $query->whereIn('camp_num', $childCamps);

        if (!$includeImplicitSupporters) {
            $query->whereIn('nick_name_id', $nickNames);
        }

        $query->whereRaw('? between `start` and IF(`end` > 0, `end`, 9999999999)', [$submittime]) // check submittime is within start and end
            ->where('delegate_nick_name_id', '=', 0)->orderBy('support_order', 'ASC');

        if (!$includeImplicitSupporters) {
            $query->groupBy('camp_num');
        }

        $mysupports = $query->get();

        $returnData = [
            'supporters' => $mysupports,
            'supporter_count' => count($mysupports) ?? 0,
            'ifIamExplicitSupporter' => (count($mysupports)) ? true : false,
        ];

        if (strlen($returnKey) > 0)
            return $returnData[$returnKey];

        return $returnData;
    }

    /**
     * Remove Support along with  delegates
     */

    public static function removeSupportWithDelegates($topicNum, $campNum, $nickId)
    {
        $supports = self::where('topic_num', '=', $topicNum)
                    ->where('camp_num', $campNum)
                    ->where('nick_name_id', $nickId)->first();
        $supports->update(['end' => time()]);

        $campLeaderNickId = Camp::getCampLeaderNickId($topicNum, $campNum);
        if ($supports->nick_name_id === $campLeaderNickId) {
            $oldest_direct_supporter = TopicSupport::findOldestDirectSupporter($topicNum, $campNum, null, false, true);
            Camp::updateCampLeaderFromLiveCamp($topicNum, $campNum, $oldest_direct_supporter->nick_name_id ?? null);
        }

        $delegators = self::getDelegatorForNicknameId($topicNum, $nickId);

        if(count($delegators)){
            foreach($delegators as $delegator)
            {
                return self::removeSupportWithDelegates($topicNum, $campNum, $delegator->nick_name_id);
            }
        }
    }

    public static function reOrderSupport($topicNum, $nickNames, $reason = null,$reason_summary = null,$citation_link = null)
    {
        if(!empty($nickNames)){
            $support = self::getActiveSupporInTopicWithAllNicknames($topicNum, $nickNames);
            
            $order = 1;
            foreach($support as $support)
            {
                if($order === $support->support_order){
                    $order++;
                    continue;
                }

                $support->support_order = $order;
                $support->reason = $reason;
                $support->reason_summary = $reason_summary;
                $support->citation_link = $citation_link;
                $support->update();

                //update delegators support order as well
                self::updateDeleagtorsSupportOrder($topicNum, $support->nick_name_id, $support->camp_num, $order);
                $order++;
            }
        }

        return;
    }

    public static function updateDeleagtorsSupportOrder($topicNum, $nicknameId, $campNum, $order)
    {
        $delegators = self::getDelegatorForNicknameId($topicNum, $nicknameId);
        if(count($delegators))
        { 
             self::where('topic_num', '=', $topicNum)
            ->where('camp_num', $campNum)
            ->whereIn('delegate_nick_name_id', [$nicknameId])
            ->update(['support_order' => $order]);

            foreach($delegators as $delegator){               
                return self::updateDeleagtorsSupportOrder($topicNum, $delegator->nick_name_id, $campNum, $order); 
            }
        }
        return;
    }

    public static function checkIfDelegateSupportExists($topicNum, $nickNames, $deleagtedNicknameId)
    {
       
        $support = self::where('topic_num', '=', $topicNum)
        ->where('nick_name_id', $deleagtedNicknameId)
        ->whereIn('delegate_nick_name_id', $nickNames)
        ->where('end', '=', '0')->count();
        return $support;
    }

    public static function removeSupportByCamps($topicNum, $campNum = array(),$reason = null,$reason_summary = null,$citation_link = null)
    {
        if (!empty($campNum)) {
            $supports = self::where('topic_num', '=', $topicNum)
                ->whereIn('camp_num', $campNum)
                ->where('end', '=',  0)
                ->update(['end' => time(),'reason'=>$reason,'reason_summary'=>$reason_summary,'citation_link'=>$citation_link]);
        }
        return;
    }

    public static function getSupportersNickNameIdInCamps($toppicNum, $camps)
    {
        return self::where('topic_num', '=', $toppicNum)
                                ->whereIn('camp_num', $camps)
                                ->where('end', '=', 0)
                                ->groupBy('nick_name_id')->pluck('nick_name_id')->toArray();
    }

    public static function getSupportersNickNameOfArchivedCamps($topicNum, $camps, $updatedArchiveStatus = 1, $directSupport = 0)
    {
        $query = self::where('topic_num', '=', $topicNum)
                                ->whereIn('camp_num', $camps);
                                

        if(!$updatedArchiveStatus){
            $query->where('reason','=','archived')
                   ->where('end', '!=', 0)
                   ->where('archive_support_flag','=',0);
        }else{
            $query->where('end', '=', 0);
        }

        if($directSupport)
        { 
            $query->where('delegate_nick_name_id','=', 0);
        }
        
        $supporters = $query->groupBy('nick_name_id')->get();
        return $supporters;
    }

    public static function getLastSupportOrderInTopicByNickId($topicNum, $nickId)
    {
        $nickNames = Nickname::getAllNicknamesByNickId($nickId);
        $support = self::where('topic_num', '=', $topicNum)
                        ->whereIn('nick_name_id', $nickNames)
                        ->orderBy('support_order', 'DESC')
                        ->where('end', '=', '0')->first();

        return $support;
    }


    public static function setSupportToIrrevokable($toppicNum, $camps, $archivedFlag = false)
    {
        if($archivedFlag){
            return self::where('topic_num', '=', $toppicNum)
                                ->whereIn('camp_num', $camps)
                                ->where('end', '!=', 0)
                                ->where('reason','=','archived')
                                ->where('archive_support_flag','=',0)
                                ->update(['archive_support_flag' => 1, 'archive_support_flag_date' => time()]);
        }
    }

    /**
     *
     *  On Unarchive check which camps support needs to be revoked
     */
    public static function getSupportToBeRevoked($toppicNum)
    {
        return self::where('topic_num', '=', $toppicNum)
                                ->where('end', '!=', 0)
                                ->where('reason','=','archived')
                                ->where('archive_support_flag','=',0)
                                ->orderBy('support_order', 'ASC')
                                ->get();
    }

    public static function checkIfArchiveSupporter($toppicNum, $nickNames)
    {
        $support = self::where('topic_num', '=', $toppicNum)
                                ->where('end', '!=', 0)
                                ->whereIn('nick_name_id', $nickNames)
                                ->where('reason','=','archived')
                                ->where('archive_support_flag','=',0)->first();

        return !empty($support) ? $support->nick_name_id : 0;
    }

    public static function getAllRevokedSupporters($toppicNum)
    {
        return self::where('topic_num', '=', $toppicNum)
            ->where('end', 0)
            ->where('reason', '=', 'unarchived')
            ->where('archive_support_flag', '=', 0)
            ->orderBy('support_order', 'ASC')
            // ->groupBy('camp_num')
            ->get();
    }

    public static function ifIamArchiveExplicitSupporters($filter, $updatedArchiveStatus = 1, $returnKey = '')
    {
        Camp::clearChildCampArray();
        $childCamps = [];
        $liveCamp = Camp::getLiveCamp($filter);
        $childCamps = array_unique(Camp::getAllChildCamps($liveCamp));
        $key = array_search($liveCamp->camp_num, $childCamps, true);
        if ($key !== false) {
            unset($childCamps[$key]);
        }

       
        $query = Support::where('topic_num', $filter['topicNum'])
                        ->whereIn('camp_num', $childCamps)                       
                        ->where('delegate_nick_name_id', '=', 0);
                    
        if(!$updatedArchiveStatus){  // when un-archiving camp
           $query =  $query->where('reason','=','archived')
                            ->where('archive_support_flag','=',0);
        }else{
            $query->where('end', '!=', 0);
        }

        $mysupports = $query->get();              

        $returnData = [
            'supporters' => $mysupports,
            'supporter_count' => count($mysupports) ?? 0,
            'ifIamExplicitSupporter' => (count($mysupports)) ? true : false,
        ];
        
        if (strlen($returnKey) > 0)
            return $returnData[$returnKey];
        return $returnData;
    }

    public static function filterArchivedSupporters($revokableSupporters, $explicitSupporters,$submitterNickId,$supportersListByTimeStamp)
    {
        $idsToExclude = array_unique(array_merge(array_column($supportersListByTimeStamp, 'id'),[$submitterNickId]));
        $archiveDirectSupporters = array_diff($revokableSupporters,$idsToExclude);
        $explicitSupporters = array_diff($explicitSupporters,$idsToExclude);

        return $response = [
            'direct_supporters' => $archiveDirectSupporters,
            'explicit_supporters' => $explicitSupporters,
        ];
    }

    public static function getTotalSupportedCamps($nicknames = array())
    {
        return self::whereIn('nick_name_id', $nicknames)
            ->where('end', 0)->count();
    }
}

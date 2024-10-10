<?php

namespace App\Models;

use App\Facades\Util;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Library\General;
use Exception;
use App\Helpers\ElasticSearch;
use App\Models\Support;

class Nickname extends Model {

    protected $table = 'nick_name';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nick_name'
    ];


    public static function boot() 
    {
        parent::boot();
            
        static::saved(function($item) 
        {    
            $type = "nickname";
            $id = "nickname-" . $item->id;
            $typeValue = $item->nick_name;
            $topicNum = 0;
            $campNum = 0;
            $goLiveTime = '';
            $namespace = '';
            $breadcrumb = '';
            $supportCount = Support::getTotalSupportedCamps([$item->id]);
            $namespaceId = 1; //default
           // $userId = self::getUserIDByNickNameId($item->id);
            $link = self::getNickNameLink($item->id, $namespaceId,'','',true);
            $statementNum =  '';
            
            if($item->private){
                ElasticSearch::deleteData($id);
                return;
            }
            ElasticSearch::ingestData($id, $type, $typeValue, $topicNum, $campNum, $link, $goLiveTime, $namespace, $breadcrumb);
        
        });
    }

    public function getCreateTimeAttribute($value){
        return date("Y-m-d", strtotime($value));
    }
    

     /**
     * Check whether nick already exists or not
     * @param string $nickname
     * @return boolean 
     */
    public static function isNicknameExists($nickname) {

        $nickname = self::where('nick_name', $nickname)->first();
        return (empty($nickname)) ? false : true;
    }

    public static function createNickname($userID, $input) {
        // Create nickname
        $nickname = new Nickname();
        $nickname->user_id = $userID;
        $nickname->nick_name = substr($input['nick_name'], 0, 50);
        $nickname->private = $input['visibility_status'];
        $nickname->create_time = time();
        $nickname->save();

        return $nickname;
    }

    public static function getAllNicknames($userID, $private = '')
    {
        if(isset($private) && $private != '')
        {
            $nicknames = self::where('user_id', $userID)->where('private','=',$private)->orderBy('nick_name', 'ASC')->get();
        }else
        {
            $nicknames = self::where('user_id', $userID)->orderBy('nick_name', 'ASC')->get();
        }
        
        return $nicknames;
    }

    public static function getNicknamesIdsByUserId($userID)
    {
        return self::where('user_id', $userID)->pluck('id')->toArray();
    }

    public static function getNickNameLink($userId, $namespaceId, $topicNum='', $campNum='', $forSearch = false){
        if($forSearch){
            $link = '/user/supports/'.$userId .'?canon='.$namespaceId;
        }else{
            $link = config('global.APP_URL_FRONT_END') . ('/user/supports/'.$userId .'?canon='.$namespaceId);
        }

        return $link;
    }

    public function camps() {
        return $this->hasMany('App\Models\Camp', 'nick_name_id', 'nick_name_id');
    }

    public static function personNicknameArray($nickId = '') {

        $userNickname = array();
        if(isset($nickId) && !empty($nickId)){
            $nicknames = self::personAllNicknamesByAnyNickId($nickId);
        }else{
            $nicknames = self::personNickname();
        }

        foreach ($nicknames as $nickname) {
            $userNickname[] = $nickname->id;
        }
        return $userNickname;
    }

    public static function personNickname()
    {
        if (Auth::check()) {
            return self::select('id', 'nick_name')->where('user_id', Auth::user()->id)->orderBy('nick_name', 'ASC')->get();
        }
        return [];
    }

    public static function personAllNicknamesByAnyNickId($nick_id)
    {
        $nickName = self::find($nick_id);
        if ($nickName)
            return self::select('id', 'nick_name')->where('user_id', $nickName->user_id)->orderBy('nick_name', 'ASC')->get();
        else
            return [];
    }

   public static function topicNicknameUsed($topic_num)
    {
        $personNicknameArray = self::personNicknameArray();
        $usedNickid = 0;
        $mysupports = Support::select('nick_name_id')->where('topic_num', $topic_num)->whereIn('nick_name_id', $personNicknameArray)->where('end', '=', 0)->groupBy('topic_num')->orderBy('support_order', 'ASC')->first();
        if (empty($mysupports)) {
            $mycamps = Camp::select('submitter_nick_id')->where('topic_num', $topic_num)->whereIn('submitter_nick_id', $personNicknameArray)->orderBy('submit_time', 'DESC')->first();
            if (empty($mycamps)) {
                $mystatement = Statement::select('submitter_nick_id')->where('topic_num', $topic_num)->whereIn('submitter_nick_id', $personNicknameArray)->orderBy('submit_time', 'DESC')->first();
                if (empty($mystatement)) {
                    $mytopic = Topic::select('submitter_nick_id')->where('topic_num', $topic_num)->whereIn('submitter_nick_id', $personNicknameArray)->orderBy('submit_time', 'DESC')->first();
                    if (empty($mytopic)) {
                        $myNews = NewsFeed::select('submitter_nick_id')->where('topic_num', $topic_num)->whereIn('submitter_nick_id', $personNicknameArray)->orderBy('submit_time', 'DESC')->first();
                        if (empty($myNews)) {
                            $mythread = Thread::select('user_id')->where('topic_id', $topic_num)->whereIn('user_id', $personNicknameArray)->orderBy('created_at', 'DESC')->first();
                            if (!empty($mythread)) {
                                $usedNickid = $mythread->user_id;
                            }else{
                                $currentTopicThreadsIds =Thread::select('id')->where('topic_id', $topic_num)->get();
                                $latestReply =  Reply::select('user_id')->whereIn('c_thread_id', $currentTopicThreadsIds)->whereIn('user_id', $personNicknameArray)->orderBy('created_at', 'DESC')->first();
                                if(!empty($latestReply)) {
                                    $usedNickid = $latestReply->user_id;
                                }
                            }
                        }else{
                            $usedNickid = $myNews->submitter_nick_id;
                        }
                    } else {
                        $usedNickid = $mytopic->submitter_nick_id;
                    }
                } else {
                    $usedNickid = $mystatement->submitter_nick_id;
                }
            } else {
                $usedNickid = $mycamps->submitter_nick_id;
            }
        } else {
            $usedNickid = $mysupports->nick_name_id;
        }
        if ($usedNickid) {
            return self::where('id', '=', $usedNickid)->orderBy('nick_name', 'ASC')->get();
        } else
            return self::personNickname();
    }

    public function getSupportCampListNamesEmail($supported_camp = [],$topic_num,$camp_num = 1){
        $returnHtml = [];
        $filter['topicNum'] = $topic_num;
        $filter['asOf'] = '';
        $filter['campNum'] =  $camp_num;
        $onecamp = Camp::getLiveCamp($filter);
        Camp::clearChildCampArray();
        
        try {
            $childCamps = array_unique(Camp::getAllChildCamps($onecamp));
            if(sizeof($supported_camp) > 0){
                foreach ($supported_camp as $key => $value) {
                    if($key == $topic_num){
                        $h = 1;
                        if(isset($value['array'])){
                            ksort($value['array']);
                            foreach($value['array'] as $i => $supportData ){
                                foreach($supportData as $j => $support){
                                    if(count($childCamps) > 0 && in_array($support['camp_num'], $childCamps)){
                                        $returnHtml[]=  '<a href="'.$support['link'].'">'.$support['camp_name'].'</a>'; 
                                    } else{
                                        $returnHtml[]=  '<a href="'.$support['link'].'">'.$support['camp_name'].'</a>'; 
                                    }
                                }
                            }
                        }else{
                        $returnHtml[] =  '<a href="'.$value['link'].'">'.$value['camp_name'].'</a>'; 
                        }
                    }                
                }
            } 
        } catch(Exception $e) {
            Util::logMessage("Error :: getSupportCampListNamesEmail :: ".$e->getMessage());
            $returnHtml = [];
        }
            
        return $returnHtml;                  
    }

    public static function getNickName($nick_id) {

        return self::find($nick_id);
    }

    public static function getUserByNickName($nick_id) {
        $nickname = self::find($nick_id);
        return User::find($nickname->user_id);
    }

    public function getSupportCampList($namespace = 1,$filter = array(),$topic_num = null) {
        $as_of_time = time();
        $as_of_clause = '';

        $topic_num_cond = '';
        if(!empty($topic_num)){
            $topic_num_cond = 'and u.topic_num = '.$topic_num;
        }

        $namespace = isset($_REQUEST['namespace']) ? $_REQUEST['namespace'] : $namespace;

        if ((isset($filter['asof']) && $filter['asof'] == 'bydate')) {
                $as_of_time = strtotime(date('Y-m-d H:i:s', strtotime($filter['asofdate'])));
                $as_of_clause = "and go_live_time < $as_of_time";   
        } else {
            $as_of_clause = 'and go_live_time < ' . $as_of_time;
        }

        if(isset($filter['nofilter']) && $filter['nofilter']){
                    $as_of_time  = time();
                    $as_of_clause = 'and go_live_time < ' . $as_of_time;
        }
        $supports = [];
        try {
            $sql = "select u.topic_num, u.camp_num, u.title,u.camp_name, p.support_order, p.delegate_nick_name_id from support p, 
            (select s.title,s.topic_num,s.camp_name,s.submit_time,s.go_live_time, s.camp_num from camp s,
            (select topic_num, camp_num, max(go_live_time) as camp_max_glt from camp
            where objector_nick_id is null $as_of_clause group by topic_num, camp_num) cz,
            (select t.topic_num, t.topic_name, t.namespace, t.go_live_time from topic t,
            (select ts.topic_num, max(ts.go_live_time) as topic_max_glt from topic ts
            where ts.namespace_id=$namespace and ts.objector_nick_id is null $as_of_clause group by ts.topic_num) tz
            where t.namespace_id=$namespace and t.topic_num = tz.topic_num and t.go_live_time = tz.topic_max_glt) uz
            where s.topic_num = cz.topic_num and s.camp_num=cz.camp_num and s.go_live_time = cz.camp_max_glt and s.topic_num=uz.topic_num) u
            where u.topic_num = p.topic_num and ((u.camp_num = p.camp_num) or (u.camp_num = 1)) and p.nick_name_id = {$this->id} and
            (p.start < $as_of_time) and ((p.end = 0) or (p.end > $as_of_time)) and u.go_live_time < $as_of_time $topic_num_cond order by u.submit_time DESC";
        
            $results = DB::select($sql);
        
            foreach ($results as $rs) {
                $topic_num = $rs->topic_num;
                $camp_num = $rs->camp_num;
                $filter['topicNum'] = $topic_num;
                $filter['asOf'] = '';
                $filter['campNum'] =  $camp_num;
                $livecamp = Camp::getLiveCamp($filter);
                $topicLive = Topic::getLiveTopic($topic_num,['nofilter'=>true]);
                $title = preg_replace('/[^A-Za-z0-9\-]/', '-', ($livecamp->title != '') ? $livecamp->title : $livecamp->camp_name);
                // $topic_id = $topic_num . "-" . $title;
                $url = Util::getTopicCampUrlWithoutTime($topicLive->topic_num, $livecamp->camp_num, $topicLive, $livecamp, time());
                if ($rs->delegate_nick_name_id && $camp_num != 1 ) {
                    $supports[$topic_num]['array'][$rs->support_order][] = ['camp_name' => $livecamp->camp_name, 'camp_num' => $camp_num, 'link' => $url ,'delegate_nick_name_id'=>$rs->delegate_nick_name_id];
                } else if ($camp_num == 1) {
                    if($rs->title ==''){
                        // $topicData = Topic::where('topic_num','=',$topic_num)->where('go_live_time', '<=', time())->latest('submit_time')->get();
                        $liveTopic = Topic::getLiveTopic($topic_num,['nofilter'=>true]);
                        $title = preg_replace('/[^A-Za-z0-9\-]/', '-', $liveTopic->topic_name);
                        //  $topitopic_idc_id = $topic_num . "-" . $title;
                    }
                    $supports[$topic_num]['camp_name'] = ($rs->camp_name != "") ? $livecamp->camp_name : $livecamp->title;
                    $supports[$topic_num]['link'] = $url; 
                    $supports[$topic_num]['title'] = $title;
                    if($rs->delegate_nick_name_id){
                        $supports[$topic_num]['delegate_nick_name_id'] = $rs->delegate_nick_name_id;
                    }
                } else {
                    $supports[$topic_num]['array'][$rs->support_order][] = ['camp_name' =>$livecamp->camp_name, 'camp_num' => $camp_num, 'link' => $url];
                }
            }

        } catch(Exception $e) {
            Util::logMessage("Error :: getSupportCampList :: ". $e->getMessage());
            $supports = [];
        }
        return $supports;
    }

    public static function getUserIDByNickNameId($nick_id) {

        $nickname = self::find($nick_id);
        if (!empty($nickname)) {
            return $nickname->user_id;
        }

        return null;
    }

    public static function getAllNicknamesByNickId($nickId)
    {
        $userId = self::getUserIDByNickNameId($nickId);
        
        if($userId){
            return $allNickNames = self::getNicknamesIdsByUserId($userId);
        }

        return [];        
    }


   
    public function getNicknameSupportedCampList($namespace = 1,$filter = array(),$topic_num = null) 
    {
        $as_of_time = time();
        $as_of_clause = '';

        $topic_num_cond = '';
        /** ticket can-833 supported camp should appear regardless of topic num */
        //if(!empty($topic_num)){
           // $topic_num_cond = 'and u.topic_num = '.$topic_num;
       // }
       
        if ((isset($filter['asof']) && $filter['asof'] == 'bydate')) {
                $as_of_time = strtotime(date('Y-m-d H:i:s', strtotime($filter['asofdate'])));
                $as_of_clause = "and go_live_time < $as_of_time";   
        } else {
            $as_of_clause = 'and go_live_time < ' . $as_of_time;
        }

        if(isset($filter['nofilter']) && $filter['nofilter']){
                    $as_of_time  = time();
                    $as_of_clause = 'and go_live_time < ' . $as_of_time;
            }

        $sql = "select u.topic_num, u.camp_num, u.title,u.camp_name, p.support_order, p.delegate_nick_name_id from support p, 
        (select s.title,s.topic_num,s.camp_name,s.submit_time,s.go_live_time, s.camp_num from camp s,
            (select topic_num, camp_num, max(go_live_time) as camp_max_glt from camp
                where objector_nick_id is null $as_of_clause group by topic_num, camp_num) cz,
                (select t.topic_num, t.topic_name, t.namespace, t.go_live_time from topic t,
                    (select ts.topic_num, max(ts.go_live_time) as topic_max_glt from topic ts
                        where ts.namespace_id=$namespace and ts.objector_nick_id is null $as_of_clause group by ts.topic_num) tz
                            where t.namespace_id=$namespace and t.topic_num = tz.topic_num and t.go_live_time = tz.topic_max_glt) uz
                where s.topic_num = cz.topic_num and s.camp_num=cz.camp_num and s.go_live_time = cz.camp_max_glt and s.topic_num=uz.topic_num) u
        where u.topic_num = p.topic_num and ((u.camp_num = p.camp_num) or (u.camp_num = 1)) and p.nick_name_id = {$this->id} and
        (p.start < $as_of_time) and ((p.end = 0) or (p.end > $as_of_time)) and u.go_live_time < $as_of_time $topic_num_cond order by p.support_order ASC, u.submit_time DESC";
        
        $results = DB::select($sql);
        return $results;
    }
    
    public static function personNicknameIds() {
        if (Auth::check()) {
            return DB::table('nick_name')->where('user_id', Auth::user()->id)->orderBy('nick_name', 'ASC')->pluck('id')->toArray();
        }
        return [];
    }

    public static function getUserByNickId($nick_id) {
        $nickname = self::find($nick_id);
        return $nickname->nick_name ?? "";
    }

    public static function getNickSupportUser($user, $nick_id)
    {
        $nickname = self::find($nick_id);

        if (!$nickname)
            return "not_found";

        $userByNickId = self::getUserByNickName($nick_id);
        if ($user && $user->id == $userByNickId->id) {
           return DB::table('nick_name')->select('id', 'nick_name', 'private')->where('user_id', $nickname->user_id)->orderBy('nick_name', 'ASC')->get();
        } else if ($nickname->private == 1) {
            return DB::table('nick_name')->select('id', 'nick_name', 'private')->where('id', $nick_id)->orderBy('nick_name', 'ASC')->get();
        } else if ($nickname->private == 0) {
            return DB::table('nick_name')->select('id', 'nick_name', 'private')->where('user_id', $nickname->user_id)->where('private', 0)->orderBy('nick_name', 'ASC')->get();
        }
    }
}

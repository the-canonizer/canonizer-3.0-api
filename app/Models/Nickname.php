<?php

namespace App\Models;

use App\Facades\Util;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Library\General;

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
        $nickname->owner_code = Util::canon_encode($userID);
        $nickname->nick_name = $input['nick_name'];
        $nickname->private = $input['visibility_status'];
        $nickname->create_time = time();
        $nickname->save();

        return $nickname;
    }

    public static function getAllNicknames($userID, $private='')
    {
        $ownerCode = Util::canon_encode($userID);

        if(isset($private) && $private != '')
        {
            $nicknames = self::where('owner_code', $ownerCode)->where('private','=',$private)->get();
        }else
        {
            $nicknames = self::where('owner_code', $ownerCode)->get();
        }
        
        return $nicknames;
    }
    
    public static function getNicknamesIdsByUserId($userID)
    {
        $ownerCode = Util::canon_encode($userID);

        $nicknames = self::where('owner_code', $ownerCode)->pluck('id')->toArray();
        return $nicknames;
    }

    public static function getNickNameLink($userId, $namespaceId, $topicNum='', $campNum=''){
        return url('user/supports/'.$userId .'?topicnum='.$topicNum . '&campnum='.$campNum .'&namespace='.$namespaceId);
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

    public static function personNickname() {
        if (Auth::check()) {
           $userid = Auth::user()->id;
           $encode = Util::canon_encode($userid);

       return DB::table('nick_name')->select('id', 'nick_name')->where('owner_code', $encode)->orderBy('nick_name', 'ASC')->get();
      }
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
            return self::where('id', '=', $usedNickid)->get();
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
                            }
                          }
                        }
                    }else{
                       $returnHtml[] =  '<a href="'.$value['link'].'">'.$value['camp_name'].'</a>'; 
                    }
               }                
            }
        }     
      return $returnHtml;                  
    }

    public static function getNickName($nick_id) {

        return self::find($nick_id);
    }

    public static function getUserByNickName($nick_id) {
        $nickname = self::find($nick_id);
        $userId = Util::canon_decode($nickname->owner_code);
        return User::find($userId);
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
        $supports = [];
        foreach ($results as $rs) {
            $topic_num = $rs->topic_num;
            $camp_num = $rs->camp_num;
            $filter['topicNum'] = $topic_num;
            $filter['asOf'] = '';
            $filter['campNum'] =  $camp_num;
            $livecamp = Camp::getLiveCamp($filter);
            $topicLive = Topic::getLiveTopic($topic_num,['nofilter'=>true]);
            $title = preg_replace('/[^A-Za-z0-9\-]/', '-', ($livecamp->title != '') ? $livecamp->title : $livecamp->camp_name);
            $topic_id = $topic_num . "-" . $title;
            $url = Util::getTopicCampUrl($topicLive->topic_num, 1, $topicLive, $livecamp, time());
            if ($rs->delegate_nick_name_id && $camp_num != 1 ) {
                $supports[$topic_num]['array'][$rs->support_order][] = ['camp_name' => $livecamp->camp_name, 'camp_num' => $camp_num, 'link' => $url ,'delegate_nick_name_id'=>$rs->delegate_nick_name_id];
            } else if ($camp_num == 1) {
                if($rs->title ==''){
                    $topicData = Topic::where('topic_num','=',$topic_num)->where('go_live_time', '<=', time())->latest('submit_time')->get();
                    $liveTopic = Topic::getLiveTopic($topic_num,['nofilter'=>true]);
                    $title = preg_replace('/[^A-Za-z0-9\-]/', '-', $liveTopic->topic_name);
                     $topic_id = $topic_num . "-" . $title;
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
        return $supports;
    }

    public static function getUserIDByNickNameId($nick_id) {

        $nickname = self::find($nick_id);
        if (!empty($nickname)) {
            $ownerCode = $nickname->owner_code;
            return General::canon_decode($ownerCode);
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

        return $results;
    }
    
    public static function personNicknameIds() {
        if (Auth::check()) {
            $userid = Auth::user()->id;

            $encode = General::canon_encode($userid);

            return DB::table('nick_name')->where('owner_code', $encode)->orderBy('nick_name', 'ASC')->pluck('id')->toArray();
        }
        return [];
    }

    public static function getUserByNickId($nick_id) {
        $nickname = self::find($nick_id);
        return $nickname->nick_name;
    }

    
}

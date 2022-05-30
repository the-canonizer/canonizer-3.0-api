<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Facades\Util;

class Support extends Model
{

    protected $primaryKey = 'support_id';
    protected $table = 'support';
    public $timestamps = false;


    protected $fillable = ['nick_name_id', 'topic_num', 'camp_num', 'delegate_nick_name_id', 'start', 'end', 'flags', 'support_order'];


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

    public static function getDirectSupporter($topic_num, $camp_num = 1)
    {
        $as_of_time = time();
        return Support::where('topic_num', '=', $topic_num)
            ->where('camp_num', '=', $camp_num)
            ->where('delegate_nick_name_id', 0)
            ->whereRaw("(start <= $as_of_time) and ((end = 0) or (end > $as_of_time))")
            ->orderBy('start', 'DESC')
            ->groupBy('nick_name_id')
            ->select(['nick_name_id', 'support_order', 'topic_num', 'camp_num'])
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


    /** Delegation support 
     *   1.  check if user has any support
     *   2.  if yes, then find any sub-deleagted which will go recursively
     *   3. remove support for existing.
     *   4. add new support 
      *  5. All these above will work in recursive ways
    */

    public static function addDelegationSupport($support = array(),$topicNum, $nickNameId,$deleagtedNicknameId)
    {
        $existingSupport =  self::getActiveSupporInTopic($topicNum,$nickNameId);
        $delegators = [];
        if(count($existingSupport) > 0)
        {
            $delegators = self::getDelegatorForNicknameId($topicNum,$nickNameId);
            self::removeSupport($topicNum,$nickNameId);
           
        }

        self::addSupport($support,$topicNum,$nickNameId,$deleagtedNicknameId);  

        if(isset($delegators) && count($delegators) > 0){
            foreach($delegators as $delegator){
                return self::addDelegationSupport($support, $topicNum, $delegator->nick_name_id, $delegator->delegate_nick_name_id);
            }
        }
    }

    /**
     *  Add Support
     *  @param support is array of support for which new support is to be added
     */
    public static function addSupport($support,$topicNum,$nickNameId,$deleagtedNicknameId)
    {
        foreach($support as $sp){

           $model = new Support();
           $model->topic_num = $topicNum;
           $model->camp_num = $sp->camp_num;
           $model->nick_name_id = $nickNameId;
           $model->delegate_nick_name_id = $deleagtedNicknameId;
           $model->support_order = $sp->support_order;
           $model->start =time();
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
        $supports = self::getActiveSupporInTopicWithAllNicknames($topicNum,$usersNickNames);       
    
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
    public static function removeSupport($topicNum, $nickNameId, $campNum='')
    {
        $usersNickNames = Nickname::getAllNicknamesByNickId($nickNameId);
        self::removeSupportWithAllNicknames($topicNum, $campNum, $usersNickNames);

        return;
    }

    /*
     * Remove Direct Support
     
    public static function removeDirectSupport($topicNum ,$campNum = '', $nickNamesArray = array(), $action='')
    {
        if((isset($action) && $action == 'all') || $cammNum == '')  //abandon entire topic and promote deleagte
        {
           
            ///$getAllActiveSupport = self::getActiveSupporInTopicWithAllNicknames($topicNum, $nickNamesArray);

            self::removeSupportWithAllNicknames($topicNum, $campNum, $nickNamesArray);
            self::promoteDelegatesToDirect($topicNum, $nickNamesArray);

            return;
            
        }


    }*/

    public static function getActiveDelegators($topicNum, $usersNickNames)
    {
        $delegators = self::where('topic_num', '=', $topicNum)
                                ->whereIn('delegate_nick_name_id', $usersNickNames)
                                ->where('end', '=', 0)
                                ->get();

        return $delegators;

    }

    public static function getActiveSupporInTopicWithAllNicknames($topicNum,$nickNames)
    {
        $supports = self::where('topic_num', '=', $topicNum)
                        ->whereIn('nick_name_id', $nickNames)
                        ->orderBy('support_order', 'ASC')
                        ->where('end', '=', '0')->get();

        return $supports;
    }

    public static function removeSupportWithAllNicknames($topicNum,$campNum='', $nickNames = array())
    {
        $supports = self::where('topic_num', '=', $topicNum)
                    ->whereIn('nick_name_id', $nickNames)
                    ->update(['end' => time()]);

        return;
    }

    public static function promoteDelegatesToDirect($topicNum, $nickNames)
    {
        $supports = self::where('topic_num', '=', $topicNum)
                    ->whereIn('delegate_nick_name_id', $nickNames)
                    ->where('end', '=', 0)
                    ->update(['delegate_nick_name_id' => 0]);

        return;
    }

    public static function getAllSupporters($topic,$camp,$excludeNickID){
        $nickNametoExclude = [$excludeNickID];
        $support = self::where('topic_num','=',$topic)->where('camp_num','=',$camp)
                ->where('end','=',0)
                ->where('nick_name_id','!=',$excludeNickID)
                ->where('delegate_nick_name_id',0)->groupBy('nick_name_id')->get(); 
        $camp = Camp::where('camp_num','=',$camp)->where('topic_num','=',$topic)->first();
        $allChildren = Camp::getAllChildCamps($camp);
        $supportCount = 0;
        if(sizeof($support) > 0 || count($support) >0){
            foreach($support as $sp){
                array_push( $nickNametoExclude, $sp->nick_name_id);
            }
        }
        if(sizeof($allChildren) > 0 ){
        foreach($allChildren as $campnum){
            $supportData = self::where('topic_num',$topic)->where('camp_num',$campnum)->whereNotIn('nick_name_id',$nickNametoExclude)->where('delegate_nick_name_id',0)->where('end','=',0)->orderBy('support_order','ASC')->get();
            if(count($supportData) > 0){
                    foreach($supportData as $sp){
                        array_push($nickNametoExclude, $sp->nick_name_id);
                    }
                    $supportCount = $supportCount + count($supportData);
                }
            }
        }
        return count($support)+$supportCount;
    }

    public static function ifIamSingleSupporter($topic_num,$camp_num=0,$userNicknames) {
        $othersupports = [];
        $supportFlag = 1;
        if($camp_num != 0){
         $othersupports = self::where('topic_num',$topic_num)->where('camp_num',$camp_num)->whereNotIn('nick_name_id',$userNicknames)->where('delegate_nick_name_id',0)->where('end','=',0)->orderBy('support_order','ASC')->get();
         }else{
             $othersupports = self::where('topic_num',$topic_num)->whereNotIn('nick_name_id',$userNicknames)->where('delegate_nick_name_id',0)->where('end','=',0)->orderBy('support_order','ASC')->get();
         }
 
 
         $othersupports->filter(function($item) use($camp_num){
             if($camp_num){
                 return $item->camp_num == $camp_num;
             }
         });
        
         if(count($othersupports) > 0){
                 $supportFlag = 0;
         }else{
             if($camp_num != 0){
                 $camp = Camp::where('camp_num','=',$camp_num)->where('topic_num','=',$topic_num)->first();
                 Camp::clearChildCampArray();
                 $allChildren = Camp::getAllChildCamps($camp);
                 if(sizeof($allChildren) > 0 ){
                     foreach($allChildren as $campnum){
                         $support = self::where('topic_num',$topic_num)->where('camp_num',$campnum)->whereNotIn('nick_name_id',$userNicknames)->where('delegate_nick_name_id',0)->where('end','=',0)->orderBy('support_order','ASC')->get();
                         if(sizeof($support) > 0){
                             $supportFlag = 0;
                             break;
                         }
                     }
                 }
             }else{
                 $support = self::where('topic_num',$topic_num)->whereNotIn('nick_name_id',$userNicknames)->where('delegate_nick_name_id',0)->where('end','=',0)->orderBy('support_order','ASC')->get();
                       if(sizeof($support) > 0){
                             $supportFlag = 0;
                         }
             }
             
         }
         return  $supportFlag;
        
     }	
}

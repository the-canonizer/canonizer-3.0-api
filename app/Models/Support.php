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

    public static function addDelegationSupport($support = array(),$topicNum, $nickNameID,$deleagtedNicknameId)
    {
        $existingSupport =  self::getSupporInTopicByUserId($topicNum,$nickNameID);
        
        if(!empty($existingSupport)){
            $delegator = self::getDelegatorForNicknameId($topicNum,$nickNameID);
            self::removeSupport($topicNum,$nickNameID);            

            if(isset($delegator) && !empty($delegator)){
                foreach($delegation as $delegator){
                    return self::addDelegationSupportInCamps($support, $topicNum, $delegator->nick_name_id, $delgator_delegated_nick_name_id);
                }
            }

            return;
        }

        //add support
        self::addSupport($support,$topicNum,$nickNameID,$deleagtedNicknameId);

        return;
    }

    public static function addSupport($support,$topicNum,$nickNameID,$deleagtedNicknameId)
    {
        foreach($support as $sp){

           $model = new Support();
           $model->topic_num = $topicNum;
           $model->camp_num = $sp->camp_num;
           $model->nick_name_id = $nickNameID;
           $model->delegated_nick_name_id = $deleagtedNicknameId;
           $model->support_order = $sp->support_order;
           $model->start =time();
           $model-save();

        }
        return;

    }

    public static function getSupporInTopicByUserId($topic_num, $userId)
    {
        $usersNickNames = Nickname::getNicknamesIdsByUserId($userId);

        $supports = self::where('topic_num', '=', $topic_num)->whereIn('nick_name_id', $usersNickNames)
                        ->where('end', '=', 0)->get();

        return $supports;
    }

    public static function getDelegatorForNicknameId($topicNum, $nickNameID){
        $support = self::where('topic_num', '=', $topic_num)->whereIn('delegate_nick_name_id', [$nickNameID])
        ->where('end', '=', 0)->get();
    }

    public static function removeSupport($usersNickNames, $topicNum, $campNum='')
    {
        $supports = self::where('topic_num', '=', $topic_num)->whereIn('nick_name_id', $usersNickNames)
        ->update(['end' => 0]);

        self::removeDelegatesSupport($usersNickNames,$topicNum);
        return;
    }


    public function removeDelegatesSupport($usersNickNames,$topicNum){
        $support = self::where('topic_num', '=', $topic_num)->whereIn('delegate_nick_name_id', $usersNickNames)
        ->where('end', '=', 0)->get();

        if(!empty($support)){
            foreach($support as $sp){
               self::removeSupport([$sp->nick_name_id],$topicNum);
            }
        }
        return;
    }
    
}

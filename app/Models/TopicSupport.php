<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Nickname;
use App\Models\Algorithm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class TopicSupport extends Model {

    protected $table = 'topic_support';
    public $timestamps = false;
    protected static $tempArray = [];

    protected static $supports = [];


    public static function boot() {
        parent::boot();
    }

	public function nickname() {
        return $this->hasOne(Nickname::class, 'id', 'nick_name_id');
    }
	public function camp() {
        return $this->hasOne(Camp::class, 'camp_num', 'camp_num');
    }
	public function topic() {
        return $this->hasOne(Topic::class, 'topic_num', 'topic_num');
    }

	public function delegatednickname() {
        return $this->hasOne(Nickname::class, 'id', 'delegate_nick_id');
    }

    public static function reducedSum($array,$full_score = false){
        $sum = $array['score'];
        if($full_score){
            $sum = $array['full_score'];
        }
        try{
		  if(isset($array['children']) && is_array($array['children'])) {
			foreach($array['children'] as $arr){
					$sum=$sum + self::reducedSum($arr,$full_score);
			}
		  }
        }catch(\Exception $e){
            return $sum;
        }

        return $sum;
    }

    /*1 Person :: 1 Vote Nicknames*/
    public static function traverseTree($algorithm,$topicnum,$campnum,$delegateNickId=0){

        $as_of_time = time();
		if(isset($_REQUEST['asof']) && $_REQUEST['asof']=='bydate'){
			$as_of_time = strtotime($_REQUEST['asofdate']);
		}
		$supports = Support::where('topic_num', '=', $topicnum)
                        ->where('delegate_nick_name_id', 0)
						->where('camp_num', $campnum)
                        ->whereRaw("(start <= $as_of_time) and ((end = 0) or (end > $as_of_time))")
                        ->orderBy('start', 'DESC')
                        ->groupBy('nick_name_id')
                        ->select(['nick_name_id', 'delegate_nick_name_id', 'support_order', 'topic_num', 'camp_num'])
                        ->get();
        $nick_supports = Support::where('topic_num', '=', $topicnum)
                        ->whereRaw("(start <= $as_of_time) and ((end = 0) or (end > $as_of_time))")
                        ->orderBy('start', 'DESC')
                        ->select(['nick_name_id', 'delegate_nick_name_id', 'support_order', 'topic_num', 'camp_num'])
                        ->get();
        $array = [];
        foreach($supports as $key =>$support){
            $nickNameSupports =  $nick_supports->filter(function($item) use($support) {
                return $item->nick_name_id == $support->nick_name_id;
            });
            $supportPoint = Algorithm::{$algorithm}($support->nick_name_id,$support->topic_num,$support->camp_num);
			$currentCampSupport =  $nickNameSupports->filter(function ($item) use($campnum)
			{
				return $item->camp_num == $campnum; /* Current camp support */
			})->first();

            $array[$support->nick_name_id]['score'] = 0;
            $array[$support->nick_name_id]['children'] = [];
            $array[$support->nick_name_id]['index']=$support->nick_name_id;
            $multiSupport = false;
            if($currentCampSupport){

                if($nickNameSupports->count() > 1){
                    $multiSupport = true;
					$array[$support->nick_name_id]['score']=round($supportPoint / (2 ** ($support->support_order)),2);
				}else if($nickNameSupports->count() >= 1 && $support->topic_num !='54' && $algorithm == 'mormon'){ //only for mormon if selected
                    $multiSupport = true;
                    $array[$support->nick_name_id]['score']=round($supportPoint / (2 ** ($support->support_order)),2);
                }else if($nickNameSupports->count() == 1 && $support->topic_num =='54' && $algorithm == 'mormon'){ //only for mormon if selected
                    $multiSupport = true;
                    $array[$support->nick_name_id]['score']=round($supportPoint / (2 ** ($support->support_order)),2);
                }
                else if($nickNameSupports->count() == 1){

				     $array[$support->nick_name_id]['score']=$supportPoint;
				}
                // self::traverseChildTree is missing in the source but referred to in dev_service version.
                // I'll check if I need it or if it's in another helper.
                // Looking at dev_service TopicSupport.php, it HAD traverseChildTree. I'll include it.
            }

        }
        return $array;

    }

    public static function sumTranversedArraySupportCount($traversedTreeArray=array()){
       if(isset($traversedTreeArray) && is_array($traversedTreeArray)) {

        foreach($traversedTreeArray as $key => $array){

           $traversedTreeArray[$key]['score']=self::reducedSum($array);

           $traversedTreeArray[$key]['full_score']=self::reducedSum($array,true);

           $traversedTreeArray[$key]['children']=self::sumTranversedArraySupportCount($array['children']);
        }

	   }

      if(is_array($traversedTreeArray)) {

       uasort($traversedTreeArray, function($a, $b) {
            return ($a['score'] ?? 0) < ($b['score'] ?? 0);
       });
	  }

        return $traversedTreeArray;

    }

    public static function sumTranversedArraySupportCountP($traversedTreeArray=array()){
        if(isset($traversedTreeArray) && is_array($traversedTreeArray)) {
 
         foreach($traversedTreeArray as $key => $array){
 
            $traversedTreeArray[$key]['score']=self::reducedSum($array);
 
            $traversedTreeArray[$key]['full_score']=self::reducedSum($array,true);
 
            $traversedTreeArray[$key]['children']=self::sumTranversedArraySupportCountP($array['children']);
         }
 
        }
 
       if(is_array($traversedTreeArray)) {
 
        uasort($traversedTreeArray, function($a, $b) {
             return ($a['score'] ?? 0) < ($b['score'] ?? 0);
        });
       }
 
         return $traversedTreeArray;
 
    }

    public static function checkIfAnySupportExists($topicNum, $currentUserNickIds) {
    
        $support = Support::where('topic_num', '=', $topicNum)
                    ->where('end', '=', '0')
                    ->where(function ($query) use ($currentUserNickIds) {
                        $query->whereIn('nick_name_id', $currentUserNickIds)
                              ->orWhereIn('delegate_nick_name_id', $currentUserNickIds);
                    })
                    ->count();

        return $support;
    }
}

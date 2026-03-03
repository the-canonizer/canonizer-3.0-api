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

    public static function reducedSum($array, $full_score = false)
    {
        $sum = $full_score ? ($array['full_score'] ?? ($array['score'] ?? 0)) : ($array['score'] ?? 0);
        $subKey = isset($array['children']) ? 'children' : (isset($array['delegates']) ? 'delegates' : null);
        
        try {
            if ($subKey && isset($array[$subKey]) && is_array($array[$subKey])) {
                foreach ($array[$subKey] as $arr) {
                    $sum = $sum + self::reducedSum($arr, $full_score);
                }
            }
        } catch (\Exception $e) {
            return $sum;
        }

        return $sum;
    }

    public static function traverseChildTree($algorithm,$topicnum,$campnum,$delegateNickId,$parent_support_order,$multiSupport){

        /*Delegated Support */
        if(!static::$supports){
             $as_of_time = time();
            if(isset($_REQUEST['asof']) && $_REQUEST['asof']=='bydate'){
                $as_of_time = strtotime($_REQUEST['asofdate']);
            }
            static::$supports = Support::where('topic_num', '=', $topicnum)
                            ->whereRaw("(start <= $as_of_time) and ((end = 0) or (end > $as_of_time))")
                            ->orderBy('start', 'DESC')
                            ->select(['nick_name_id', 'delegate_nick_name_id', 'support_order', 'topic_num', 'camp_num'])
                            ->get();
        }

        $delegatedSupports =  static::$supports->filter(function($item) use ($delegateNickId){
                return $item->delegate_nick_name_id == $delegateNickId;
        });

        $array = [];
        foreach($delegatedSupports as $support){

            $supportPoint = Algorithm::{$algorithm}($support->nick_name_id,$support->topic_num,$support->camp_num);
            $array[$support->nick_name_id]['index']=$support->nick_name_id;
            if($multiSupport){
                $array[$support->nick_name_id]['score'] = round($supportPoint / (2 ** ($parent_support_order)),2);
            }else{
                $array[$support->nick_name_id]['score'] = $supportPoint;
            }
            $array[$support->nick_name_id]['children'] = self::traverseChildTree($algorithm,$topicnum,$campnum,$support->nick_name_id,$parent_support_order,$multiSupport);
        }

        return $array;
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
        
        static::$supports = $nick_supports;

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
                $array[$support->nick_name_id]['children'] = self::traverseChildTree($algorithm,$topicnum,$campnum,$support->nick_name_id,$currentCampSupport->support_order,$multiSupport);
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

    public static function sortTraversedSupportCountTreeArray($traversedTreeArray){
        $array = array_values($traversedTreeArray);
        usort($array,'self::sortByOrder');
        return $array;
    }

    public static function sortByOrder($a, $b)
	{
        $a = $a['score'] ?? 0;
        $b = $b['score'] ?? 0;

        if ($a == $b) return 0;
        return ($a > $b) ? -1 : 1;
	}
}

<?php

namespace App\Helpers;

use Throwable;
use App\Models\Camp;
use App\Models\User;
use App\Models\Topic;
use App\Models\Support;
use App\Models\Nickname;
use App\Models\Algorithm;

use Illuminate\Support\Arr;
use DB;


class SupportAndScoreCount
{
    
    public static function getSupporterWithScore($algorithm, $topicNum, $campNum, $asOfTime,)
    {
        $topic_support = Support::where('topic_num', '=', $topicNum)
        ->join("nick_name","nick_name.id", "=", "support.nick_name_id")
        ->where('delegate_nick_name_id', 0)
        ->where('camp_num', '=', $campNum)
        ->whereRaw("(start <= $asOfTime) and ((end = 0) or (end > $asOfTime))")
        ->orderBy('camp_num','ASC')->orderBy('support_order','ASC')
        ->select(['nick_name_id', 'delegate_nick_name_id', 'support_order', 'topic_num', 'camp_num', 'nick_name'])
        ->get();

        $supporters = [];
        foreach($topic_support as $support){
            $support_total = 0;
            $supportPoint = Algorithm::{$algorithm}($support->nick_name_id,$support->topic_num,$support->camp_num,$asOfTime);
            if($support->support_order > 1){
                $support_total = $support_total + round($supportPoint * 1 / (2 ** ($support->support_order)), 3);
            }else{
                $support_total = $support_total + $supportPoint;
            }

            $supporter = [
                'nick_name' => $support->nick_name,
                'score' => $support_total,
            ];

            $delegates = self::getSubDelegates($support_total, $algorithm, $topicNum, $campNum, $support->nick_name_id, $asOfTime);
            
            if(isset($delegates) && !empty($delegates)){
                $supporter['score'] = $delegates['score_count'];
                $supporter['delegates'] = $delegates['childs'];
            }

            array_push($supporters, $supporter);
            
        }

        return $supporters;

    }

    private static function getSubDelegates($totalSupport, $algorithm, $topicNum, $campNum, $delegateNickNameId, $asOfTime)
    {
        $supporter = [];
        $delegates = Support::where('topic_num', '=', $topicNum)
                        ->join("nick_name","nick_name.id", "=", "support.nick_name_id")
                        ->where('delegate_nick_name_id', '=',  $delegateNickNameId)
                        ->where('camp_num', '=', $campNum)
                        ->whereRaw("(start <= $asOfTime) and ((end = 0) or (end > $asOfTime))")
                        ->select(['nick_name_id', 'delegate_nick_name_id', 'support_order', 'topic_num', 'camp_num', 'nick_name'])
                        ->get();

        
        if(count($delegates) > 0){
            foreach($delegates as $support){
                $temp = [];
                $supportPoint = Algorithm::{$algorithm}($support->nick_name_id,$support->topic_num,$support->camp_num,$asOfTime);
                if($support->support_order > 1){
                    $totalSupport = $totalSupport + round($supportPoint * 1 / (2 ** ($support->support_order)), 3);
                }else{
                    $totalSupport = $totalSupport + $supportPoint;
                }
                $supporter = [
                    'childs' => [
                            'nick_name' => $support->nick_name,
                            'score' => 1
                    ],
                    'score_count' => $totalSupport
                ];

                return $supporter;
               
            }
        }else{
            return false;
        }
        
    }



}

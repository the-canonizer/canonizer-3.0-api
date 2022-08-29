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

            $liveTopic = Topic::getLiveTopic($topicNum, ['nofilter'=>true]);
            $namespaceId = (isset($liveTopic->namespace_id) && $liveTopic->namespace_id ) ? $liveTopic->namespace_id : 1; 

            $supporter = [
                'nick_name' => $support->nick_name,
                'nick_name_id' => $support->nick_name_id,
                'nick_name_link' => Nickname::getNickNameLink($support->nick_name_id, $namespaceId, $topicNum, $campNum),
                'score' => $support_total,
            ];

            $delegates = self::getSubDelegates($support_total, $algorithm, $topicNum, $campNum, $support->nick_name_id, $asOfTime, $namespaceId);
            
            if(isset($delegates) && !empty($delegates)){
                $supporter['score'] = $delegates['score_count'];
                $supporter['delegates'] = $delegates['childs'];
            }

            array_push($supporters, $supporter);
            
        }

        return $supporters;

    }

    private static function getSubDelegates($totalSupport, $algorithm, $topicNum, $campNum, $delegateNickNameId, $asOfTime, $namespaceId = 1)
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
                            'score' => 1,
                            'nick_name_id' => $support->nick_name_id,
                            'delegate_nick_name_id' => $support->delegate_nick_name_id,
                            'nick_name_link' => Nickname::getNickNameLink($support->nick_name_id, $namespaceId, $topicNum, $campNum)
                    ],
                    'score_count' => $totalSupport
                ];

                return $supporter;
               
            }
        }else{
            return false;
        }
        
    }

    /**
     * 
     */
    public function getCampAndNickNameWiseSupportTree($algorithm, $topicNumber,$asOfTime){
        try{

            $is_add_reminder_back_flag = 1;//($algorithm == 'blind_popularity') ? 1 : 0;
            $nick_name_support_tree=[];
            $nick_name_wise_support=[];
            $camp_wise_support = [];
            $camp_wise_score = [];
            $topic_support = Support::where('topic_num', '=', $topicNumber)
            ->where('delegate_nick_name_id', 0)
            ->whereRaw("(start <= $asOfTime) and ((end = 0) or (end > $asOfTime))")
            ->orderBy('camp_num','ASC')->orderBy('support_order','ASC')
            ->select(['nick_name_id', 'delegate_nick_name_id', 'support_order', 'topic_num', 'camp_num'])
            ->get();
            
            if(count($topic_support) > 0){
               foreach($topic_support as $support){
                        if(array_key_exists($support->nick_name_id, $nick_name_wise_support)){
                                array_push($nick_name_wise_support[$support->nick_name_id],$support);
                        }else{
                            $nick_name_wise_support[$support->nick_name_id] = [];
                            array_push($nick_name_wise_support[$support->nick_name_id],$support);
                        }                   
               }
            }
            foreach($nick_name_wise_support as $nickNameId=>$support_camp){
                $multiSupport =  count($support_camp) > 1 ? 1 : 0;
               foreach($support_camp as $support){                
                    $support_total = 0; 
                    $nick_name_support_tree[$support->nick_name_id][$support->support_order][$support->camp_num]['score'] = 0;
                    $camp_wise_score[$support->camp_num][$support->support_order][$support->nick_name_id]['score'] = 0;
                    $supportPoint = AlgorithmService::{$algorithm}($support->nick_name_id,$support->topic_num,$support->camp_num,$asOfTime);
                    if($multiSupport){
                            $support_total = $support_total + round($supportPoint * 1 / (2 ** ($support->support_order)), 3);
                        }else{
                            $support_total = $support_total + $supportPoint;
                        }                    
                        $nick_name_support_tree[$support->nick_name_id][$support->support_order][$support->camp_num]['score'] = $support_total;
                        $camp_wise_score[$support->camp_num][$support->support_order][$support->nick_name_id]['score'] =  $support_total;
                }
            }
            if(count($nick_name_support_tree) > 0){
                foreach($nick_name_support_tree as $nickNameId=>$scoreData){
                    ksort($scoreData);
                    $index = 0;
                    foreach($scoreData as $support_order=>$camp_score){
                        $index = $index +1;
                        $multiSupport =  count($camp_score) > 1 ? 1 : 0;
                       foreach($camp_score as $campNum=>$score){
                            if($support_order > 1 && $index == count($scoreData)  && $is_add_reminder_back_flag){
                                if(array_key_exists($nickNameId,$nick_name_support_tree) && array_key_exists(1,$nick_name_support_tree[$nickNameId]) && count(array_keys($nick_name_support_tree[$nickNameId][1])) > 0){
                                $campNumber = array_keys($nick_name_support_tree[$nickNameId][1])[0];
                                $nick_name_support_tree[$nickNameId][1][$campNumber]['score']=$nick_name_support_tree[$nickNameId][1][$campNumber]['score'] + $score['score'];
                                $camp_wise_score[$campNumber][1][$nickNameId]['score'] = $camp_wise_score[$campNumber][1][$nickNameId]['score'] + $score['score'];
                                $delegateTree = $this->delegateSupportTree($algorithm, $topicNumber,$campNumber, $nickNameId, 1,$camp_wise_score[$campNumber][1][$nickNameId]['score'],$multiSupport ,[],$asOfTime);
                                $nick_name_support_tree[$nickNameId][1][$campNumber]['delegates'] = $delegateTree;
                            }
                        }
                        $delegateTree = $this->delegateSupportTree($algorithm, $topicNumber,$campNum, $nickNameId, $support_order, $nick_name_support_tree[$nickNameId][$support_order][$campNum]['score'],$multiSupport,[],$asOfTime);
                        $nick_name_support_tree[$nickNameId][$support_order][$campNum]['delegates'] = $delegateTree;
                       }
                    }
                }
            }
        
            return ['camp_wise_tree'=>$camp_wise_score,'nick_name_wise_tree'=>$nick_name_support_tree];

        }catch (CampTreeCountException $th) {
            throw new CampTreeCountException("Camp Tree Count with Mind Expert Algorithm Exception");
        }
    }


    public function delegateSupportTree($algorithm, $topicNumber, $campnum, $delegateNickId, $parent_support_order, $parent_score, $multiSupport,$array=[],$asOfTime)
    {
        try{
            $nick_name_support_tree=[];
        $nick_name_wise_support=[];
        $is_add_reminder_back_flag = ($algorithm == 'blind_popularity') ? 1 : 0;
		/* Delegated Support */
        if (!Arr::exists($this->sessionTempArray, "topic-support-{$topicNumber}")){
            $supportData = Support::where('topic_num', '=', $topicNumber)
            ->whereRaw("(start <= $asOfTime) and ((end = 0) or (end > $asOfTime))")
            ->orderBy('start', 'DESC')
            ->select(['support_order', 'camp_num', 'nick_name_id', 'delegate_nick_name_id', 'topic_num'])
            ->get();
            $this->sessionTempArray["topic-support-{$topicNumber}"] = $supportData;
            $delegatedSupports = $this->sessionTempArray["topic-support-{$topicNumber}"]->filter(function($item) use ($delegateNickId) {
                return $item->delegate_nick_name_id == $delegateNickId;
            });
        }else{
            $delegatedSupports = $this->sessionTempArray["topic-support-{$topicNumber}"]->filter(function($item) use ($delegateNickId) {
                return $item->delegate_nick_name_id == $delegateNickId;
            });
        }

        
        
        if(count($delegatedSupports) > 0){
           foreach($delegatedSupports as $support){
                    if(array_key_exists($support->nick_name_id, $nick_name_wise_support)){
                            array_push($nick_name_wise_support[$support->nick_name_id],$support);
                    }else{
                        $nick_name_wise_support[$support->nick_name_id] = [];
                        array_push($nick_name_wise_support[$support->nick_name_id],$support);
                    }              
           }
        }
        
        foreach($nick_name_wise_support as $nickNameId=>$support_camp){
           foreach($support_camp as $support){ 
               if($support->camp_num == $campnum){
                    $support_total = 0; 
                    $supportPoint = AlgorithmService::{$algorithm}($support->nick_name_id,$support->topic_num,$support->camp_num,$asOfTime);
                    if($multiSupport){
                        $support_total = $support_total + round($supportPoint * 1 / (2 ** ($support->support_order)), 3);
                    }else{
                        $support_total = $support_total + $supportPoint;
                    } 
                    $nick_name_support_tree[$support->nick_name_id]['score'] = ($is_add_reminder_back_flag) ? $parent_score : $support_total;
                    $delegateTree = $this->delegateSupportTree($algorithm, $topicNumber,$campnum, $support->nick_name_id, $parent_support_order,$parent_score,$multiSupport,[],$asOfTime);
                    $nick_name_support_tree[$support->nick_name_id]['delegates'] = $delegateTree;
                }               
               }
        }
       return $nick_name_support_tree;

       }catch (CampTreeCountException $th) {
            throw new CampTreeCountException("Camp Tree Count with Mind Expert Algorithm Exception");
        }
    }



}

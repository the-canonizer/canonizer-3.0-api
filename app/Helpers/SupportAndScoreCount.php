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
    private $traversetempArray = [];
    private $sessionTempArray = [];

    public function getSupporterWithScore($algorithm, $topicNum, $campNum, $asOfTime)
    {
        if(!Arr::exists($this->sessionTempArray, "score_tree_{$topicNum}_{$algorithm}"))
        {
            $score_tree = $this->getCampAndNickNameWiseSupportTree($algorithm, $topicNum, $asOfTime);
            $this->sessionTempArray["score_tree_{$topicNum}_{$algorithm}"] = $score_tree;
        
        }else{
            $score_tree = $this->sessionTempArray["score_tree_{$topicNum}_{$algorithm}"];
        }

        $supports = Support::where('topic_num', '=', $topicNum)
                    ->join("nick_name","nick_name.id", "=", "support.nick_name_id")
                    ->where('delegate_nick_name_id', 0)
                    ->where('camp_num', '=', $campNum)
                    ->whereRaw("(start <= $asOfTime) and ((end = 0) or (end > $asOfTime))")
                    ->orderBy('camp_num','ASC')->orderBy('support_order','ASC')
                    ->select(['nick_name_id', 'delegate_nick_name_id', 'support_order', 'topic_num', 'camp_num', 'nick_name'])
                    ->get();
        
        $totalCampScore = 0;
        $array = [];
        $liveTopic = Topic::getLiveTopic($topicNum, ['nofilter'=>true]);
        $namespaceId = (isset($liveTopic->namespace_id) && $liveTopic->namespace_id ) ? $liveTopic->namespace_id : 1; 

        foreach($supports as $key =>$support){            
            $array[$support->nick_name_id] = [
                    'score' => 0,
                    'support_order' => $support->support_order,
                    'nick_name' => $support->nick_name,
                    'nick_name_id' => $support->nick_name_id,
                    'nick_name_link' => Nickname::getNickNameLink($support->nick_name_id, $namespaceId, $topicNum, $campNum),
                    'delegates' => []    
                ];

            $currentCampSupport = 0;
            $supportPoint=0;
            $multiSupport = false;
            $supportOrder = 0;
            
            if(array_key_exists('nick_name_wise_tree',$score_tree) && count($score_tree['nick_name_wise_tree'][$support->nick_name_id]) > 0){
                $multiSupport = count($score_tree['nick_name_wise_tree'][$support->nick_name_id]) > 1 ? true: false;
                foreach($score_tree['nick_name_wise_tree'][$support->nick_name_id] as $supp_order=>$tree_node){
                    if(count($tree_node) > 0){
                        foreach($tree_node as $camp_num=>$camp_score){                          
                            if($camp_num == $campNum){
                                $currentCampSupport = 1;
                                $supportOrder = $supp_order;
                                $delegateTree = $camp_score['delegates'];               
                                $supportPoint = $supportPoint + $camp_score['score'];
                                break; 
                            }
                        }
                    }
                }
            }

            if($currentCampSupport){
                $array[$support->nick_name_id]['score'] = $supportPoint;
                $array[$support->nick_name_id]['delegates'] = $this->traverseChildTree($algorithm, $topicNum, $campNum, $support->nick_name_id, $supportOrder, $multiSupport, $delegateTree, $asOfTime, $namespaceId);
                       
            }
        }


        $array = self::sortTraversedSupportCountTreeArray(self::sumTranversedArraySupportCount($array));
        return $array;

    }

    public function traverseChildTree($algorithm, $topicNum, $campNum, $delegateNickId, $parentSupportOrder, $multiSupport, $delegateTree = [], $asOfTime, $namespaceId = 1)
    {
     
        $delegatedSupports = Support::where('topic_num', '=', $topicNum)
                    ->join("nick_name","nick_name.id", "=", "support.nick_name_id")
                    ->where('delegate_nick_name_id', '=', $delegateNickId)
                    ->where('camp_num', '=', $campNum)
                    ->whereRaw("(start <= $asOfTime) and ((end = 0) or (end > $asOfTime))")
                    ->orderBy('camp_num','ASC')->orderBy('support_order','ASC')
                    ->select(['nick_name_id', 'delegate_nick_name_id', 'support_order', 'topic_num', 'camp_num', 'nick_name'])
                    ->get();
        
        $array = [];
        foreach($delegatedSupports as $support){ 
            if($support->camp_num == $campNum){ 
                    $array[$support->nick_name_id]['score'] =$delegateTree[$support->nick_name_id]['score'];
                    $array[$support->nick_name_id]['support_order'] = $support->support_order;
                    $array[$support->nick_name_id]['nick_name'] = $support->nick_name;
                    $array[$support->nick_name_id]['nick_name_id'] = $support->nick_name_id;
                    $array[$support->nick_name_id]['nick_name_link'] = Nickname::getNickNameLink($support->nick_name_id, $namespaceId, $topicNum, $campNum);
                    $array[$support->nick_name_id]['delegate_nick_name_id'] = $support->delegate_nick_name_id;
                    $delegateArr = $delegateTree[$support->nick_name_id]['delegates'];
                    $array[$support->nick_name_id]['delegates'] = $this->traverseChildTree($algorithm, $topicNum, $campNum, $support->nick_name_id, $parentSupportOrder, $multiSupport,$delegateArr, $asOfTime, $namespaceId);
                }  
            }

        return $array;
    }

    /**
     * this calculate the score with delegates and sumup score added back to parent
     * in support tree
    */

    public static function sumTranversedArraySupportCount($traversedTreeArray=array())
    {
        if(isset($traversedTreeArray) && is_array($traversedTreeArray)) {
            foreach($traversedTreeArray as $key => $array){
                
                $traversedTreeArray[$key]['score'] = self::reducedSum($array);            
                $traversedTreeArray[$key]['delegates']=self::sumTranversedArraySupportCount($array['delegates']);
            }         
        }
       
        if(is_array($traversedTreeArray)) 
        {          
                uasort($traversedTreeArray, function($a, $b) {
                    return $a['score'] < $b['score'];
                });
        }       
        return $traversedTreeArray;
     }

     public static function reducedSum($array = []){
        $sum = $array['score'];
        try{
		  if(isset($array['delegates']) && is_array($array['delegates'])) {	
			foreach($array['delegates'] as $arr){
					$sum=$sum + self::reducedSum($arr);
			}
		  }
        }catch(\Exception $e){
            return $sum;
        }
		
        return $sum;
    }

    public static function sortTraversedSupportCountTreeArray($traversedTreeArray = array())
    {
        $array = array_values($traversedTreeArray);
        usort($array,'self::sortByOrder');
        return $array;
    }

    public static function sortByOrder($a, $b)
	{
        $a = $a['score'];
        $b = $b['score'];
        if ($a == $b) return 0;
        return ($a > $b) ? -1 : 1;
	}

    /**
     * get camp and nickname wise score under topic
     */
    public function getCampAndNickNameWiseSupportTree($algorithm, $topicNumber,$asOfTime)
    {
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
                $supportPoint = Algorithm::{$algorithm}($support->nick_name_id,$support->topic_num,$support->camp_num,$asOfTime);
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
        
        return ['camp_wise_tree' => $camp_wise_score, 'nick_name_wise_tree' => $nick_name_support_tree];

    }


    public function delegateSupportTree($algorithm, $topicNumber, $campnum, $delegateNickId, $parent_support_order, $parent_score, $multiSupport,$array=[],$asOfTime)
    {
        
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
                    $supportPoint = Algorithm::{$algorithm}($support->nick_name_id,$support->topic_num,$support->camp_num,$asOfTime);
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
    }

    public function getDelegatesScore($tree)
    {
        $score = 0;
        if(count($tree['delegates']) > 0){
            foreach($tree['delegates'] as $nick=>$delScore){
                $score = $score + $delScore['score'];
                if(count($delScore['delegates']) > 0){
                    $score = $score + $this->getDelegatesScore($delScore);
                }
            }
        }

        return $score;
        
    }

    public function getCampTotalSupportScore($algorithm, $topicNum, $startCamp = 1, $asOfTime, $asOf = '')
    {
        if($asOf == 'review') {
            $topicChild = Camp::where('topic_num', '=', $topicNum)
                            ->where('camp_name', '!=', 'Agreement')
                            ->where('objector_nick_id', '=', null)
                            ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNum . ' and objector_nick_id is null group by camp_num)')
                            ->groupBy('camp_num')
                            ->orderBy('submit_time', 'desc')
                            ->get();

        } else {
            $topicChild = Camp::where('topic_num', '=', $topicNum)
                            ->where('camp_name', '!=', 'Agreement')
                            ->where('objector_nick_id', '=', null)
                            ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNum . ' and objector_nick_id is null and go_live_time <= ' . $asOfTime . ' group by camp_num)')
                            ->where('go_live_time', '<=', $asOfTime)
                            ->groupBy('camp_num')
                            ->orderBy('submit_time', 'desc')
                            ->get();
        }
        
        $this->sessionTempArray["topic-child-{$topicNum}"] = $topicChild;
        

        $tree = [];
        
        $tree[$startCamp]['topic_num'] = $topicNum;
        $tree[$startCamp]['camp_num'] = $startCamp;
        $tree[$startCamp]['score'] = $this->getCamptSupportCount($algorithm, $topicNum, $startCamp, $asOfTime);
        $tree[$startCamp]['children'] = $this->traverseCampTree($algorithm, $topicNum, $startCamp, null, $asOfTime);
        $reducedTree = $this->sumTranversedTreeScore($tree);        
        $sortTree = $this->sortTree($reducedTree);

        return $sortTree;

    }

    /**
     * get total support score count of selected camp under tree
     * 
     */
    public function getCamptSupportCount($algorithm, $topicNum, $campNum, $asOfTime, $nickNameId = null) 
    {   $asOfTime = time();
        if(!Arr::exists($this->sessionTempArray, "score_tree_{$topicNum}_{$algorithm}")){
            $score_tree = $this->getCampAndNickNameWiseSupportTree($algorithm, $topicNum,$asOfTime);
            $this->sessionTempArray["score_tree_{$topicNum}_{$algorithm}"] = $score_tree;
        }else{
            $score_tree = $this->sessionTempArray["score_tree_{$topicNum}_{$algorithm}"];
        } 
        
        $support_total = 0;
        if(array_key_exists('camp_wise_tree',$score_tree) && count($score_tree['camp_wise_tree']) > 0 && array_key_exists($campNum,$score_tree['camp_wise_tree'])){
            if(count($score_tree['camp_wise_tree'][$campNum]) > 0){
                foreach($score_tree['camp_wise_tree'][$campNum] as $order=>$tree_node){                                                        
                    if(count($tree_node) > 0){
                        foreach($tree_node as $nick=>$score){
                            $delegate_arr = $score_tree['nick_name_wise_tree'][$nick][$order][$campNum];
                            $delegate_score = $this->getDelegatesScore($delegate_arr); 
                            $support_total =$support_total + $score['score'] + $delegate_score;
                        }
                    }
                }    
            }
        } 
        return $support_total;
    }


    public function traverseCampTree($algorithm, $topicNum, $parentCamp, $lastParent = null, $asOfTime) 
    {
        $key = $topicNum . '-' . $parentCamp . '-' . $lastParent;

        if (in_array($key, $this->traversetempArray)) {  /** Skip repeated recursions* */
            return;           
        }

        $this->traversetempArray[] = $key;
        $childs = $this->campChildrens($topicNum, $parentCamp);        
        $array = [];
        foreach ($childs as $key => $child) 
        {
            $array[$child->camp_num]['score'] = $this->getCamptSupportCount($algorithm, $child->topic_num, $child->camp_num, $asOfTime);
            $children = $this->traverseCampTree($algorithm, $child->topic_num, $child->camp_num, $child->parent_camp_num, $asOfTime);
            $array[$child->camp_num]['children'] = is_array($children) ? $children : [];
        }
        return $array;
    }

     /**
     * Get the child camps.
     *
     * @param int $topicNumber
     * @param int $parentCamp
     * @param int $campNumber
     * @param array $filter
     *
     * @return array $childs
     */

    public function campChildrens($topicNum, $parentCamp, $campNum = null, $filter = array())
    {
        $childs = $this->sessionTempArray["topic-child-{$topicNum}"]->filter(function ($item) use ($parentCamp, $campNum) {
            if ($campNum) {
                return $item->parent_camp_num == $parentCamp && $item->camp_num == $campNum;
            } else {
                return $item->parent_camp_num == $parentCamp;
            }
        });

        return $childs;
    }

    /**
     * sumup score count of child camps and added back them to parent camp
     */
    public static function sumTranversedTreeScore($traversedTreeArray=array())
    {
        
        if(isset($traversedTreeArray) && is_array($traversedTreeArray)) {
 
            foreach($traversedTreeArray as $key => $array){
    
                $traversedTreeArray[$key]['score']=self::reducedTreeSum($array);    
                $traversedTreeArray[$key]['children']=self::sumTranversedTreeScore($array['children']);
            } 
        }
 
        if(is_array($traversedTreeArray)) 
        {
                uasort($traversedTreeArray, function($a, $b) {
                    return $a['score'] < $b['score'];
                });
        } 
        
        return $traversedTreeArray; 
     }

     public static function reducedTreeSum($array = [])
     {
        $sum = $array['score'];
        if(isset($array['children']) && is_array($array['children'])) {	
            foreach($array['children'] as $arr){
                    $sum=$sum + self::reducedTreeSum($arr);
            }
        }
        return $sum;
    }

    public static function sortTree($tree)
    {
        $node = reset($tree);        
        unset($node['children']);
        return $node;
    }



}

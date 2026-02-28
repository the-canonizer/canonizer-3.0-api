<?php

namespace App\Services\Api\v1;

use App\Facades\Services\CampServiceFacade as CampService;
use App\Facades\Repositories\TimelineRepositoryFacade as TimelineRepository;
use App\Helpers\DateTimeHelper;
use App\Services\Api\v1\TopicService;
use App\Services\Api\v1\AlgorithmService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class TimelineService
{
    public function prepareMongoArr($tree, $topic = null, $reviewTopic = null, $asOfDate = null, $algorithm = null, $topicCreatedByNickId = null, $message, $type, $id=null, $old_parent_id=null, $new_parent_id=null, $topic_name, $camp_num, $camp_name, $k,$rootUrl, $url)
    {
        $namespaceId = isset($topic->namespace_id) ? $topic->namespace_id : '';
        $reviewNamespaceId = isset($reviewTopic->namespace_id) ? $reviewTopic->namespace_id : '';
        $topicTitle = isset($tree[1]['title']) ? $tree[1]['title'] :  '';
        $topicNumber = isset($tree[1]['topic_id']) ? $tree[1]['topic_id'] :  '';
        
        $mongoArr = [
                "asoftime_".$asOfDate."_".$k => array(
                    "event" => array(
                        'message'=>$message,
                        'type'=> $type,
                        'id'=> $id,
                        'old_parent_id'=> $old_parent_id,
                        'new_parent_id'=> $new_parent_id,
                        'nickname_id'=>$topicCreatedByNickId,
                        'namespaceId' => $namespaceId,
                        'camp_num' =>$camp_num,
                        'url' =>isset($url)? $url: $this->getTimelineUrl($topicNumber, $topic_name, $camp_num, $camp_name, $topicTitle, $type, $rootUrl,$namespaceId,$topicCreatedByNickId)
                    ),
                    "payload_response" => $this->array_single_dimensional($tree)
                ), 
        ];
        
        return $mongoArr;
    }

    public function getConditions($topicNumber, $algorithm)
    {
        return [
            'topic_id' => $topicNumber,
            'algorithm_id' => $algorithm
        ];
    }

    public function upsertTimeline($topicNumber, $algorithm, $asOfTime, $updateAll = 0, $request = [], $message, $type, $id, $old_parent_id, $new_parent_id, $timelineType="", $topic_name, $camp_num, $camp_name, $k=0, $url=null)
    {
        $algorithms = (new AlgorithmService())->getCacheAlgorithms($updateAll, $algorithm, "timeline");
        if($timelineType=="history"){
            $rootUrl =  $this->getRootUrlHistory($request);
        }
        else{
            $rootUrl =  $this->getRootUrl($request);
        }
        $startCamp = 1;
        $topicCreatedByNickId = TopicService::getTopicAuthor($topicNumber);
        foreach ($algorithms as $algo) { 
            try {
                if($timelineType=="history"){
                    $tree = (new CampService())->prepareCampTimeline($algo, $topicNumber, $asOfTime, $startCamp, $rootUrl,$nickNameId = null, $asOf = 'bydate', $fetchTopicHistory = 0);
                    $topic = (new TopicService())->getLiveTopic($topicNumber, $asOfTime, ['nofilter' => false],$asOf = 'bydate', $fetchTopicHistory = 0);
                }
                else{
                    $tree = (new CampService())->prepareCampTimeline($algo, $topicNumber, $asOfTime, $startCamp, $rootUrl);
                    $topic = (new TopicService())->getLiveTopic($topicNumber, $asOfTime, ['nofilter' => false]);
                }
                $topicInReview = (new TopicService())->getReviewTopic($topicNumber);
                $asOfDate = $asOfTime;
                $mongoArr = $this->prepareMongoArr($tree, $topic, $topicInReview, $asOfDate, $algo, $topicCreatedByNickId, $message, $type, $id, $old_parent_id, $new_parent_id, $topic_name, $camp_num, $camp_name, $k,$rootUrl,$url);
                $conditions = $this->getConditions($topicNumber, $algo);

            } catch (\Exception $th) {
                return ["data" => [], "code" => 401, "success" => false, "error" => $th->getMessage()];
            }

            $tree = TimelineRepository::upsertTimeline($mongoArr, $conditions);
        }

        return $tree;
    }

    public function getRootUrl($request){
         $url = request()->headers->get('referer');
         $url = rtrim($url,"/");
         $rootUrl = isset($url) ? $url:env('REFERER_URL');
         return $rootUrl;
    }

    public function array_single_dimensional($tree)
    {
        $singleDimensional = [];
        foreach ($tree as $item) {
            $children =  isset($item['children']) ? $item['children'] : null;
            unset($item['children']);
            $singleDimensional[] = $item;
            if ( !empty($children) ){
                $childrenSingleDimensional = $this->array_single_dimensional($children);
                $singleDimensional = array_merge($singleDimensional, $childrenSingleDimensional); 
            }
        }
        return $singleDimensional;
    }

    public function getRootUrlHistory($request){
        $rootUrl = env('REFERER_URL');
        return $rootUrl;
    }

     public function getTimelineUrl($topic_num, $topic_name, $camp_num, $camp_name, $topicTitle, $type, $rootUrl, $namespaceId, $topicCreatedByNickId)
     {
        try {
            $topic_name = isset($topic_name)?$topic_name:$topicTitle;
            $camp_num = isset($camp_num)?$camp_num:1;
            $camp_name =isset($camp_name)?$camp_name:"Agreement";
            if($type =="create_topic" || $type =="create_camp" || $type=="archive_camp" || $type=="unarchived_camp" || $type=="direct_support_added" || $type=="direct_support_removed" || $type=="delegate_support_start" || $type=="delegate_support_removed"){
                $urlPortion =  '/topic/' . $topic_num . '-' . $this->replaceSpecialCharacters($topic_name) . '/' . $camp_num . '-' . $this->replaceSpecialCharacters($camp_name);
            }
            else if($type =="update_topic"){
                $urlPortion =  '/topic/history/' . $topic_num . '-' . $this->replaceSpecialCharacters($topic_name);
            }
            else if($type =="update_camp"  || $type =="parent_change"){
                $urlPortion =  '/camp/history/' . $topic_num . '-' . $this->replaceSpecialCharacters($topic_name). '/' . $camp_num . '-' . $this->replaceSpecialCharacters($camp_name);
            }
            else{
                $urlPortion =  '/topic/' . $topic_num . '-' . $this->replaceSpecialCharacters($topic_name) . '/' . $camp_num . '-' . $this->replaceSpecialCharacters($camp_name);
            }
            return $urlPortion;

        } catch (\Exception $th) {
             throw new \Exception("URL Exception");
         }
    }

    public function replaceSpecialCharacters($info){
        return preg_replace('/[^A-Za-z0-9\-]/', '-', $info);
    }

    public function getTopicConditions($topicNumber)
    {
        return [
            'topic_id' => $topicNumber
        ];
    }
}

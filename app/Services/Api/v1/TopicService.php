<?php

namespace App\Services\Api\v1;

use App\Facades\Services\CampServiceFacade as CampService;
use App\Helpers\DateTimeHelper;
use App\Facades\Repositories\TimelineRepositoryFacade as TimelineRepository;
use App\Facades\Repositories\TreeRepositoryFacade as TreeRepository;
use App\Facades\Repositories\TopicRepositoryFacade as TopicRepository;
use App\Models\Camp;
use App\Models\Topic;

class TopicService
{
    public function getLiveTopic($topicNumber, $asOfTime, $filter = array(), $asOf = 'default', $fetchTopicHistory = 0)
    {
        $topic =  Topic::where('topic_num', $topicNumber);
        if($asOf == 'default' || $asOf == 'review' || $asOf == 'bydate' && !$fetchTopicHistory) { 
            $topic->where('objector_nick_id', NULL);
        }

        if($asOf == 'default') {
            $topic->where('go_live_time', '<=', time());
        }
        if($asOf == 'bydate') {
            $topic->where('go_live_time', '<=', $asOfTime);
        }

        $liveTopic = $topic->orderBy('go_live_time', 'desc')->first(); 

        return $liveTopic;
    }

    public function getReviewTopic($topicNumber)
    {
        $topic = Topic::where('topic_num', $topicNumber)
            ->where('grace_period', 0)
            ->where('objector_nick_id', NULL)
            ->orderBy('go_live_time', 'desc')->first(); 

          return $topic;
    }

    public function getTopicsWithScore($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $filter, $nickNameIds, $search, $asof = 'default', $archive = 0, $sort = false, $page = 'home', $topic_tags = [])
    {
        return TopicRepository::getTopicsWithPagination($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $nickNameIds, $asof, $search, $filter, true, $archive, $sort, $page, $topic_tags);
    }

    public function getTotalTopics($namespaceId, $asofdate, $algorithm, $filter, $nickNameIds, $search, $asof = 'default', $archive = 0)
    {
        $totalTopics = TopicRepository::getTotalTopics($namespaceId, $asofdate, $algorithm, $nickNameIds, $asof, $search, $filter, $archive);
        return $totalTopics;
    }

    public function sortTopicsBasedOnScore($topics, $algorithm, $asOfTime, $page = 'home'){

        if(sizeof($topics) > 0){
                 foreach ($topics as $key => $value) {
                    $campData = Camp::where('topic_num',$value->topic_num)->where('camp_num',$value->camp_num)->first();
                    if( $campData){
                        $reducedTree = CampService::prepareCampTree($algorithm, $value->topic_num, $asOfTime, $value->camp_num);
                        $topics[$key]->score = !is_string($reducedTree[$value->camp_num]['score']) ? $reducedTree[$value->camp_num]['score'] : 0;
                        $topics[$key]->topic_score = !is_string($reducedTree[$value->camp_num]['score']) ? $reducedTree[$value->camp_num]['score'] : 0;
                        $topics[$key]->topic_full_score = !is_string($reducedTree[$value->camp_num]['full_score']) ? $reducedTree[$value->camp_num]['full_score'] : 0;
                        $topics[$key]->topic_id = $reducedTree[$value->camp_num]['topic_id'];
                        $topics[$key]->topic_name = $reducedTree[$value->camp_num]['title'];
                        $topics[$key]->tree_structure[1]['review_title'] = $reducedTree[$value->camp_num]['review_title'];

                        if ($page === 'browse') {
                            $topics[$key]->tree_structure[1]['support_tree'] = CampService::getSupportTree($algorithm, $value->topic_num, 1, $asOfTime);
                        }
                        
                        $topics[$key]->as_of_date = (new DateTimeHelper())->getAsOfDate($value->go_live_time);
                    }else{
                        $topics[$key]->score = 0;
                        $topics[$key]->topic_score = 0;
                        $topics[$key]->topic_full_score = 0;
                        $topics[$key]->topic_id = $value->topic_num;
                        $topics[$key]->topic_name = $value->title;
                        $topics[$key]->tree_structure[1]['review_title'] = $value->title;
                        $topics[$key]->as_of_date = (new DateTimeHelper())->getAsOfDate($value->go_live_time);
                    }
                    unset($topics[$key]->topic_num, $topics[$key]->camp_num, $topics[$key]->title, $topics[$key]->go_live_time, $topics[$key]->support);
                }
                $topics = collect(collect($topics)->sortByDesc('score'))->values();
                return $topics;
        }else{
            return $topics;
        }
    }

    public function filterTopicCollection($topics, $filter){
        $filteredTopics = $topics->filter(function ($value, $key) use($filter) {
            return $value->score > $filter;
        });
        return $filteredTopics;
    }

    public static function getTopicCreatedDate($topicNumber){
        return Topic::where('topic_num', $topicNumber)
                ->pluck('submit_time')
                ->first();
    }

    public static function checkTopicInMySql($topicNumber, $asOfTime) {
        return Topic::where('topic_num', $topicNumber)->where('submit_time', '<=', $asOfTime)->first();
    }

    public static function getTopicAuthor($topicNumber) {
        return Topic::where('topic_num', $topicNumber)->pluck('submitter_nick_id')
        ->first();
    }
}

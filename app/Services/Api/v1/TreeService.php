<?php

namespace App\Services\Api\v1;

use App\Facades\Services\CampServiceFacade as CampService;
use App\Facades\Repositories\TreeRepositoryFacade as TreeRepository;
use App\Helpers\DateTimeHelper;
use App\Services\Api\v1\TopicService;
use App\Services\Api\v1\AlgorithmService;
use Illuminate\Support\Facades\Log;

class TreeService
{
    public function prepareMongoArr($tree, $topic = null, $reviewTopic = null, $asOfDate = null, $algorithm = null, $topicCreatedByNickId = null)
    {
        $namespaceId = isset($topic->namespace_id) ? $topic->namespace_id : '';
        $reviewNamespaceId = isset($reviewTopic->namespace_id) ? $reviewTopic->namespace_id : '';
        $topicScore = isset($tree[1]['score']) && !is_string($tree[1]['score']) ? $tree[1]['score'] : 0;
        $topicFullScore = isset($tree[1]['full_score']) && !is_string($tree[1]['full_score']) ? $tree[1]['full_score'] : 0;
        $topicTitle = isset($tree[1]['title']) ? $tree[1]['title'] :  '';
        $topicNumber = isset($tree[1]['topic_id']) ? $tree[1]['topic_id'] :  '';
        $submitter_nick_id = isset($tree[1]['submitter_nick_id']) ? $tree[1]['submitter_nick_id'] :  '';

        $mongoArr = [
        "topic_id" => $topicNumber,
        "topic_name" => $topicTitle,
        "algorithm_id" => $algorithm,
        "tree_structure" => $tree,
        "namespace_id" => $namespaceId,
        "review_namespace_id" => $reviewNamespaceId,
        "topic_score" => $topicScore,
        "topic_full_score" => $topicFullScore,
        "as_of_date" => $asOfDate,
        "submitter_nick_id" =>$submitter_nick_id,
        "created_by_nick_id"=>$topicCreatedByNickId
        ];

        return $mongoArr;
    }

    public function getConditions($topicNumber, $algorithm, $asOfDate)
    {
        return [
            'topic_id' => $topicNumber,
            'algorithm_id' => $algorithm,
            'as_of_date' => $asOfDate
        ];
    }

    public function upsertTree($topicNumber, $algorithm, $asOfTime, $updateAll = 0, $request = [])
    {
        $algorithms = (new AlgorithmService())->getCacheAlgorithms($updateAll, $algorithm,"tree");
        $rootUrl =  $this->getRootUrl($request);
        $asOf = $request['asOf'] ?? 'default';
        $startCamp = 1;
        $topicCreatedByNickId = (new TopicService())->getTopicAuthor($topicNumber);
        $rtnTree = '';
        foreach ($algorithms as $algo) {
            Log::info('Processing algorithm: ' . $algo);
            try {
                $tree = CampService::prepareCampTree($algo, $topicNumber, $asOfTime, $startCamp, $rootUrl, null, $asOf);
                Log::info('Tree prepared for algorithm: ' . $algo);
                $topic = (new TopicService())->getLiveTopic($topicNumber, $asOfTime, ['nofilter' => false]);
                $topicInReview = (new TopicService())->getReviewTopic($topicNumber);
                $asOfDate = (new DateTimeHelper())->getAsOfDate($asOfTime);
                $mongoArr = $this->prepareMongoArr($tree, $topic, $topicInReview, $asOfDate, $algo, $topicCreatedByNickId);
                $conditions = $this->getConditions($topicNumber, $algo, $asOfDate);
                Log::info('Conditions and MongoArr prepared for algorithm: ' . $algo);

            } catch (\Exception $th) {
                Log::error('Error in upsertTree loop: ' . $th->getMessage());
                return ["data" => [], "code" => 401, "success" => false, "error" => $th->getMessage()];
            }

            Log::info('Calling TreeRepository::upsertTree for algorithm: ' . $algo);
            $tree = TreeRepository::upsertTree($mongoArr, $conditions);
            if ($algorithm == $algo) {
                $rtnTree = $tree;
            }
        }

        return $rtnTree;
    }

    public function getTopicTreeFromMysql($topicNumber, $algorithm, $asOfTime, $updateAll = 0, $request = [], $fetchTopicHistory = 0){

        $rootUrl =  $this->getRootUrl($request);
        $asOf = $request->asOf ?? 'default';
        $startCamp = 1;
        try {
           $tree = CampService::prepareCampTree($algorithm, $topicNumber, $asOfTime, $startCamp, $rootUrl, $nickNameId = null, $asOf, $fetchTopicHistory);
        }
        catch (\Exception $th) {
            return ["data" => [], "code" => 401, "success" => false, "error" => $th->getMessage()];
        }

        return $tree;
    }

    public function getRootUrl($request){
         $url = request()->headers->get('referer');
         $url = rtrim($url,"/");
         $rootUrl = isset($url) ? $url:env('REFERER_URL');
         return $rootUrl;
    }
}

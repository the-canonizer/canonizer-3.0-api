<?php

namespace App\Http\Controllers\Api\v1;

use App\Facades\Helpers\UtilHelperFacade;
use App\Facades\Services\CampServiceFacade;
use App\Facades\Services\TopicServiceFacade;
use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\RemoveTopicsRequest;
use App\Http\Requests\TopicRequest;
use App\Http\Resources\TopicResource;
use App\Models\Nickname;
use App\Models\Support;
use App\Models\Tag;
use App\Models\Timeline;
use App\Models\TopicSupport;
use App\Models\Tree;
use App\Models\Statement;
use App\Models\TopicView;
use App\Services\Api\v1\AlgorithmService;
use Throwable;

class TopicController extends Controller
{
    public function getAll(TopicRequest $request)
    {
        try {
            $pageNumber = $request->input('page_number');
            $pageSize = $request->input('page_size');
            $namespaceId = $request->input('namespace_id') !== "" ? (int) $request->input('namespace_id') : $request->input('namespace_id');
            $asofdateTime = (int) $request->input('asofdate');
            $algorithm = $request->input('algorithm');
            $search = $request->input('search');
            $asof = $request->input('asof');
            $filter = (float) $request->input('filter') ?? null;
            $nickNameIds = $request->input('user_email') ? Helpers::getNickNamesByEmail($request->input('user_email')) : [];
            $currentUserNickIds = $request->input('current_user') ? Helpers::getNickNamesByEmail($request->input('current_user')) : [];
            $today = Helpers::getStartOfTheDay(time());
            $skip = ($pageNumber - 1) * $pageSize;
            $archive = ($request->has('is_archive')) ? $request->input('is_archive') : 0;
            $totalCount = 0;
            $sort = ($request->has('sort')) ?  $request->input('sort') : false;
            $page = $request->input('page') ?: "home";
            $topic_tags = $request->input('topic_tags') ?: [];

            $commandStatement = "php artisan tree:all";
            $commandSignature = "tree:all";
            $commandStatus = UtilHelperFacade::getCommandRuningStatus($commandStatement, $commandSignature);
            $algorithms = (array)AlgorithmService::getAlgorithmKeyList("tree");
            $topicsFoundInMongo = Tree::count();

            if ($asofdateTime >= $today && $topicsFoundInMongo && !$commandStatus && in_array($algorithm, $algorithms)) {
                $topicsResponse = TopicServiceFacade::getTopicsWithScore($namespaceId, $today, $algorithm, $skip, $pageSize, $filter, $nickNameIds, $search, $asof, $archive, $sort, $page, $topic_tags);
                if (isset($topicsResponse['topics'])) {
                    $topics = $topicsResponse['topics'];
                    $totalCount = $topicsResponse['totalCount'] ?? 0;
                } else {
                    $topics = $topicsResponse;
                }
            } else {
                $topics = CampServiceFacade::getAllAgreementTopicCamps($pageSize, $skip, $asof, $asofdateTime, $namespaceId, $nickNameIds, $search, false, $archive, $sort, $topic_tags);
                if ($page === 'browse') {
                    $totalCount = CampServiceFacade::getAllAgreementTopicCamps($pageSize, $skip, $asof, $asofdateTime, $namespaceId, $nickNameIds, $search, true, $archive, $sort, $topic_tags);
                }
                $topics = TopicServiceFacade::sortTopicsBasedOnScore($topics, $algorithm, $asofdateTime, $page);
                if (isset($filter) && $filter != '' && $filter != null) {
                    $topics = TopicServiceFacade::filterTopicCollection($topics, $filter);
                }
            }

            $topicViews = TopicView::getTopicViewCounts(collect($topics)->pluck('topic_id')->all())->mapWithKeys(function ($item) {
                return [$item['topic_num'] => $item['view_count']];
            })->all();

            if (is_array($topics)) {
                $topics = array_values($topics);
            }

            $topicIds = collect($topics)->pluck('topic_id')->unique()->toArray();
            $supportersByTopic = Support::getSupportersByTopicIds($topicIds);
            $supporterCountsByTopic = Support::getSupporterCountsByTopicIds($topicIds);
            $tagsByTopic = Tag::getTagsByTopicNums($topicIds);
            $statementsByTopic = ($page === 'browse') ? Statement::getLiveStatementsByTopics($topicIds) : [];

            foreach ($topics as $key => $value) {
                $isObj = is_object($value);
                $topicId = $isObj ? $value->topic_id : $value['topic_id'];
                $campViews = intval($topicViews[$topicId] ?? 0);
                
                $supporterData = isset($supportersByTopic[$topicId]) ? $supportersByTopic[$topicId]->take(5) : collect([]);
                $supporterData->each(function ($supporter) {
                    $supporter->first_name = $supporter->first_name[0] ?? '';
                    $supporter->middle_name = $supporter->middle_name[0] ?? '';
                    $supporter->last_name = $supporter->last_name[0] ?? '';
                });

                $totalSupporters = $supporterCountsByTopic[$topicId] ?? 0;
                $remSupporters = $totalSupporters < 5 ? 0 : $totalSupporters - 5;
                $tags = $tagsByTopic[$topicId] ?? [];
                $stmt = $statementsByTopic[$topicId] ?? '';

                if ($isObj) {
                    $topics[$key]->camp_views = $campViews;
                    $topics[$key]->supporterData = $supporterData;
                    $topics[$key]->total_supporters_count = $remSupporters;
                    $topics[$key]->tags = $tags;
                    if ($page === 'browse') $topics[$key]->statement = $stmt;
                } else {
                    $topics[$key]['camp_views'] = $campViews;
                    $topics[$key]['supporterData'] = $supporterData;
                    $topics[$key]['total_supporters_count'] = $remSupporters;
                    $topics[$key]['tags'] = $tags;
                    if ($page === 'browse') $topics[$key]['statement'] = $stmt;
                }

                $liveTopic = TopicServiceFacade::getLiveTopic($topicId, time());
                if ($liveTopic && $liveTopic->is_rank_hidden) {
                    if (!TopicSupport::checkIfAnySupportExists($topicId, $currentUserNickIds)) {
                        if ($isObj) {
                            unset($topics[$key]->topic_score, $topics[$key]->topic_full_score);
                        } else {
                            unset($topics[$key]["topic_score"], $topics[$key]["topic_full_score"]);
                        }
                    }
                }
            }

            return new TopicResource($topics, $totalCount);
        } catch (Throwable $th) {
            return response()->json(UtilHelperFacade::exceptionResponse($th, $request->input('tracing') ?? false), 500);
        }
    }

    public function removeCacheSpecificTopics(RemoveTopicsRequest $request)
    {
        try {
            $response = ['status_code' => 404, 'message' => 'Not found'];
            if ($request->has('topic_numbers')) {
                $removeTree = Tree::whereIn('topic_id', $request->topic_numbers)->delete();
                $removeTimeline = Timeline::whereIn('topic_id', $request->topic_numbers)->delete();
                if ($removeTree && $removeTimeline) {
                    $response = ['status_code' => 200, 'message' => 'Tree and Timeline cache removed for requested topics'];
                }
            }
        } catch (Throwable $th) {
            $response = ['status_code' => 500, 'message' => $th->getMessage()];
        }
        return response()->json($response, $response['status_code']);
    }
}

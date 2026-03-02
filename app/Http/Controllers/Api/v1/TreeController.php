<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TreeStoreRequest;
use App\Http\Resources\TreeResource;
use App\Services\Api\v1\TreeService;
use App\Services\Api\v1\CampService;
use App\Services\Api\v1\TopicService;
use App\Services\Api\v1\AlgorithmService;
use App\Helpers\UtilHelper;
use App\Helpers\Helpers;
use App\Models\Topic;
use App\Models\Camp;
use App\Models\Statement;
use App\Models\TopicSupport;
use App\Facades\Repositories\TreeRepositoryFacade as TreeRepository;
use App\Facades\Services\TopicServiceFacade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;
use Exception;

class TreeController extends Controller
{
    public function store(TreeStoreRequest $request)
    {
        $topicNumber = (int) $request->input('topic_num');
        $algorithm = $request->input('algorithm');
        $asOfTime = (int) $request->input('asofdate');
        $updateAll = (int) $request->input('update_all', 0);
        $model_id = $request->input('model_id') ?? NULL;
        $model_type = $request->input('model_type') ?? NULL;
        $job_type = $request->input('job_type') ?? NULL;
        $camp_num = (int) $request->input('camp_num');
        $event_type = $request->input('event_type') ?? NULL;
        $pre_LiveId = $request->input('pre_LiveId') ?? NULL;

        $currentTime = time();

        $topics = Topic::select('id', 'submit_time')->where('topic_num', $topicNumber)->where('grace_period', '1')->where('objector_nick_id', NULL)->orderBy('submit_time', 'asc')->get();
        if ($topics->count() > 0) {
            foreach ($topics as $topic) {
                if ($currentTime > ($topic->submit_time + env('COMMIT_TIME_DELAY_IN_SECONDS'))) {
                    if (!$this->commitTheChange($topic->id, 'topic')) {
                        throw new Exception('Authentication Issue!', 401);
                    }
                }
            }
        }

        $camps = Camp::select('id', 'submit_time')->where('topic_num', $topicNumber)->where('grace_period', '1')->where('objector_nick_id', NULL)->orderBy('submit_time', 'asc')->get();
        if ($camps->count() > 0) {
            foreach ($camps as $camp) {
                if ($currentTime > ($camp->submit_time + env('COMMIT_TIME_DELAY_IN_SECONDS'))) {
                    if (!$this->commitTheChange($camp->id, 'camp', $camp->old_parent_camp_num, $camp->parent_camp_num)) {
                        throw new Exception('Authentication Issue!', 401);
                    }
                }
            }
        }

        $statements = Statement::select('id', 'submit_time')->where('topic_num', $topicNumber)->where('grace_period', '1')->where('objector_nick_id', NULL)->orderBy('submit_time', 'asc')->get();
        if ($statements->count() > 0) {
            foreach ($statements as $statement) {
                if ($currentTime > ($statement->submit_time + env('COMMIT_TIME_DELAY_IN_SECONDS'))) {
                    if (!$this->commitTheChange($statement->id, 'statement')) {
                        throw new Exception('Authentication Issue!', 401);
                    }
                }
            }
        }

        $tree = (new TreeService())->upsertTree($topicNumber, $algorithm, $asOfTime, $updateAll, $request);

        if ($job_type == "live-time-job" && !empty($model_id) && !empty($model_type)) {
            $this->agreeToChange($model_id, $topicNumber, $camp_num, $event_type, $pre_LiveId, $model_type);
        }

        return new TreeResource(array($tree));
    }

    private function agreeToChange($changeId, $topic_num, $camp_num, $event_type, $pre_LiveId, $change_for = "")
    {
        $requestBody = [
            'record_id' => $changeId,
            'topic_num' => $topic_num,
            'camp_num'  => $camp_num,
            'change_for'=> $change_for,
            'event_type'=> $event_type,
            'pre_LiveId'=> $pre_LiveId,
            "called_from_service" => true
        ];

        $endpoint = env('API_APP_URL') . "/" . env('API_AGREE_CHANGE_FOR_LIVE');
        $headers = ['Content-Type:multipart/form-data', 'Authorization:Bearer: ' . env('API_TOKEN')];

        $response = (new UtilHelper())->curlExecute('POST', $endpoint, $headers, $requestBody);
        if (isset($response)) {
            $checkRes = json_decode($response, true);
            if (array_key_exists("status_code", $checkRes) && $checkRes["status_code"] == 401) {
                throw new Exception('Authentication Issue!', 401);
            }
        }
        return true;
    }

    private function commitTheChange($id, $type, $oldParentCampNum = null, $parentCampNum = null)
    {
        $requestBody = [
            "id" => $id,
            "type" => $type,
            "called_from_service" => true,
            "old_parent_camp_num" => $oldParentCampNum,
            "parent_camp_num" => $parentCampNum
        ];

        $endpoint = env('API_APP_URL') . "/" . env('API_COMMIT_CHANGE');
        $headers = ['Content-Type:multipart/form-data', 'Authorization:Bearer: ' . env('API_TOKEN')];

        $response = (new UtilHelper())->curlExecute('POST', $endpoint, $headers, $requestBody);
        if (isset($response)) {
            $checkRes = json_decode($response, true);
            if (array_key_exists("status_code", $checkRes) && $checkRes["status_code"] == 401) {
                throw new Exception('Authentication Issue!', 401);
            }
        }
        return true;
    }

    public function find(TreeStoreRequest $request)
    {
        try {
            $topicNumber = (int) $request->input('topic_num');
            $algorithm = $request->input('algorithm');
            $asOf = $request->input('asOf');
            $asOfTime = ($asOf == "default" || $asOf == "review") ? time() : ceil($request->input('asofdate'));
            $updateAll = (int) $request->input('update_all', 0);
            $fetchTopicHistory = $request->input('fetch_topic_history');
            $asOfDate = Helpers::getStartOfTheDay($asOfTime);
            $campNumber = (int) $request->input('camp_num', 1);
            $topicId = $topicNumber . '_' . $campNumber;
            $currentUserNickIds = $request->input('current_user') ? Helpers::getNickNamesByEmail($request->input('current_user')) : [];

            $commandStatement = "php artisan tree:all";
            $commandSignature = "tree:all";
            $algorithms = (new AlgorithmService())->getAlgorithmKeyList("tree");
            $commandStatus = (new UtilHelper())->getCommandRuningStatus($commandStatement, $commandSignature);

            if (in_array($algorithm, $algorithms) && !$fetchTopicHistory && !$commandStatus) {
                $conditions = (new TreeService())->getConditions($topicNumber, $algorithm, $asOfDate);
                $isLastJobPending = DB::table('jobs')->where('queue', env('QUEUE_NAME'))->where('model_id', $topicNumber)->orWhere('unique_id', $topicId)->first();
                $latestProcessedJobStatus = DB::table('processed_jobs')->where('topic_num', $topicNumber)->orderBy('id', 'desc')->first();

                if ($isLastJobPending || $asOf == "review") {
                    $tree = array((new TreeService())->getTopicTreeFromMysql($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
                } else {
                    if (($latestProcessedJobStatus && $latestProcessedJobStatus->status == 'Success') || !$latestProcessedJobStatus) {
                        $mongoTree = TreeRepository::findLatestTree($conditions);
                        if ($mongoTree && count($mongoTree)) {
                            if ($asOfDate < $mongoTree[0]->as_of_date) {
                                $mongoTree = TreeRepository::findTree($conditions);
                                if (!$mongoTree || !count($mongoTree)) {
                                    if (TopicService::checkTopicInMySql($topicNumber, $asOfTime)) {
                                        $mongoTree = array((new TreeService())->upsertTree($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
                                    }
                                }
                            }
                            if ($mongoTree && count($mongoTree)) {
                                $tree = collect([$mongoTree[0]['tree_structure']]);
                                if (!$tree[0][1]['title'] || ($request->asOf == "review" && !$tree[0][1]['review_title'])) {
                                    $tree = array((new TreeService())->getTopicTreeFromMysql($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
                                }
                            } else {
                                $tree = array((new TreeService())->getTopicTreeFromMysql($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
                            }
                        } else {
                            $tree = array((new TreeService())->getTopicTreeFromMysql($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
                        }
                    } else {
                        $tree = array((new TreeService())->getTopicTreeFromMysql($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
                    }
                }
            } else {
                $tree = array((new TreeService())->getTopicTreeFromMysql($topicNumber, $algorithm, $asOfTime, $updateAll, $request, $fetchTopicHistory));
            }

            $response = new TreeResource($tree);
            $responseArray = json_decode(json_encode($response), true);

            if (array_key_exists('data', $responseArray) && count($responseArray['data'])) {
                if ($asOf == 'bydate') {
                    $topicCreatedDate = TopicService::getTopicCreatedDate($topicNumber);
                    $campCreatedDate = CampService::getCampCreatedDate($campNumber, $topicNumber);
                    $responseArray['data'][0][1]['is_valid_as_of_time'] = $asOfTime >= $topicCreatedDate;
                    if ($campNumber != 1 && $asOfTime < $campCreatedDate) {
                        $responseArray['data'][] = ['camp_exist' => $asOfDate >= $campCreatedDate, 'created_at' => $campCreatedDate];
                    }
                }
                $responseArray['data'][0][1]['collapsedTreeCampIds'] = array_reverse(Helpers::renderParentsCampTree($topicNumber, $campNumber));
            }

            $responseArray['data'][0][1]['camp_views'] = intval(Helpers::getCampViewsByDate($topicNumber, $campNumber));
            $liveTopic = TopicServiceFacade::getLiveTopic($topicNumber, time());

            if ($liveTopic->is_rank_hidden) {
                if (!TopicSupport::checkIfAnySupportExists($topicNumber, $currentUserNickIds)) {
                    $responseArray['data'][0][1]['rank_hidden'] = true;
                    $this->removeSupportTree($responseArray['data'][0][1]);
                }
            }

            return $responseArray;
        } catch (Throwable $e) {
            return response()->json((new UtilHelper())->exceptionResponse($e, $request->input('tracing') ?? false), 500);
        }
    }

    function removeSupportTree(&$node)
    {
        if (isset($node['support_tree'])) unset($node['support_tree']);
        if (isset($node['children'])) {
            foreach ($node['children'] as &$child) $this->removeSupportTree($child);
        }
    }
}

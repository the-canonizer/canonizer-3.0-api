<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TimelineStoreRequest;
use App\Http\Resources\TimelineResource;
use App\Services\Api\v1\TimelineService;
use App\Services\Api\v1\TopicService;
use App\Helpers\UtilHelper;
use App\Models\Topic;
use App\Models\Camp;
use App\Models\Statement;
use App\Facades\Repositories\TimelineRepositoryFacade as TimelineRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class TimelineController extends Controller
{
    public function store(TimelineStoreRequest $request)
    {
        try{
            $topicNumber = (int) $request->input('topic_num');
            $asOfTime = (int) $request->input('asofdate');
            $updateAll = (int) $request->input('update_all', 0);
            $message = $request->input('message');
            $type = $request->input('type');
            $id = $request->input('id');
            $old_parent_id = $request->input('old_parent_id');
            $new_parent_id = $request->input('new_parent_id');
            $url = $request->input('url');

            $currentTime = time();

            $topics = Topic::select('id', 'submit_time')->where('topic_num', $topicNumber)->where('grace_period', '1')->where('objector_nick_id', NULL)->get();
            if($topics->count() > 0) {
                foreach($topics as $topic) {
                    if($currentTime > ($topic->submit_time + (60*60))) {
                        $topic->grace_period = 0;
                        $topic->update();
                    }
                }
            }

            $camps = Camp::select('id', 'submit_time')->where('topic_num', $topicNumber)->where('grace_period', '1')->where('objector_nick_id', NULL)->get();
            if($camps->count() > 0) {
                foreach($camps as $camp) {
                    if($currentTime > ($camp->submit_time + (60*60))) {
                        $camp->grace_period = 0;
                        $camp->update();
                    }
                }
            }

            $statements = Statement::select('id', 'submit_time')->where('topic_num', $topicNumber)->where('grace_period', '1')->where('objector_nick_id', NULL)->get();
            if($statements->count() > 0) {
                foreach($statements as $statement) {
                    if($currentTime > ($statement->submit_time + (60*60))) {
                        $statement->grace_period = 0;
                        $statement->update();
                    }
                }
            }

            $timeline = (new TimelineService())->upsertTimeline($topicNumber, '', $asOfTime, $updateAll, $request, $message, $type, $id, $old_parent_id, $new_parent_id, "", "", null, "", 0, $url);

            return new TimelineResource(array($timeline));
        } catch (Throwable $e) {
            return response()->json((new UtilHelper())->exceptionResponse($e, $request->input('tracing') ?? false), 500);
        }
    }

    public function find(TimelineStoreRequest $request)
    {
        try{
            $topicNumber = (int) $request->input('topic_num');
            $algorithm = $request->input('algorithm');
            $asOfTime = time();

            $conditions = (new TimelineService())->getConditions($topicNumber, $algorithm);
            $mongoTree = TimelineRepository::findTimeline($conditions);
            $topicExistInMySql = TopicService::checkTopicInMySql($topicNumber, $asOfTime);

            if ((!$mongoTree || !count($mongoTree)) && $topicExistInMySql) {
                if(Artisan::call('timeline:all '.$topicNumber.' '.$algorithm)){
                    $mongoTree = TimelineRepository::findTimeline($conditions);
                }             
            }
       
            $tree = ($mongoTree && count($mongoTree)) ? collect([$mongoTree[0]]) : [];
 
            $response = new TimelineResource($tree);
            $responseArray = json_decode(json_encode($response), true);

            if(array_key_exists('data', $responseArray) && count($responseArray['data'])) {
                foreach($responseArray['data'] as $key => $item){
                    unset($item["_id"], $item["topic_id"], $item["algorithm_id"], $item["updated_at"], $item["created_at"]);
                    $responseArray['data'] = $item;
                }
                $response = $responseArray;
            }
        
            return $response;
        } catch (Throwable $e) {
            return response()->json((new UtilHelper())->exceptionResponse($e, $request->input('tracing') ?? false), 500);
        }
    }
}

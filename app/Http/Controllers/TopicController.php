<?php

namespace App\Http\Controllers;

use Exception;
use Throwable;
use App\Models\Camp;
use App\Models\Topic;
use App\Models\Support;
use App\Models\Namespaces;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ErrorResource;
use Illuminate\Support\Facades\Event;
use App\Events\ThankToSubmitterMailEvent;
use App\Facades\Util;
use App\Helpers\ResponseInterface;
use App\Helpers\ResourceInterface;
use App\Http\Request\ValidationRules;
use App\Http\Request\ValidationMessages;

class TopicController extends Controller
{

    public function __construct(ResponseInterface $respProvider, ResourceInterface $resProvider, ValidationRules $rules, ValidationMessages $validationMessages)
    {
        $this->rules = $rules;
        $this->validationMessages = $validationMessages;
        $this->resourceProvider  = $resProvider;
        $this->resProvider = $respProvider;
    }


    public function store(Request $request, Validate $validate)
    {

        $validationErrors = $validate->validate($request, $this->rules->getTopicStoreValidationRules(), $this->validationMessages->getTopicStoreValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        try {
            $current_time = time();
            $input = [
                "topic_name" => $request->topic_name,
                "namespace_id" => $request->namespace,
                "submit_time" => $current_time,
                "submitter_nick_id" => $request->nick_name,
                "go_live_time" =>  $current_time,
                "language" => 'English',
                "note" => isset($request->note) ? $request->note : "",
                "grace_period" => 1
            ];
            DB::beginTransaction();
            $topic = Topic::create($input);
            if ($topic) {
                $topicInput = [
                    "topic_num" => $topic->topic_num,
                    "nick_name_id" => $request->nick_name,
                    "delegate_nick_name_id" => 0,
                    "start" =>  $current_time,
                    "camp_num" => 1,
                    "support_order" => 1,
                ];
                ## If topic is created then add default support to that topic ##
                $support = Support::create($topicInput);

                if (isset($request->namespace) && $request->namespace == 'other') {

                    ## Create new namespace request ##
                    $othernamespace = trim($request->create_namespace, '/');
                    $namespace = new Namespaces();
                    $namespace->parent_id = 0;
                    $namespace->name = '/' . $othernamespace . '/';
                    $namespace->save();

                    ## update namespace id ##
                    $topic->namespace_id = $namespace->id;
                    $topic->update();
                }
                DB::commit();
                try {
                    $topicLive = Topic::getLiveTopic($topic->topic_num, $request->asof);
                    $filter['topicNum'] = $request->topic_num;
                    $filter['asOf'] = $request->asof;
                    $filter['campNum'] = 1;
                    $camp = Camp::getLiveCamp($filter);
                    $historylink = Util::getTopicCampUrl($topic->topic_num, 1, $topicLive, $camp, time());
                    $dataEmail = (object) [
                        "type" => "topic",
                        "link" => $historylink,
                        "historylink" => env('APP_URL_FRONT_END') . '/topic/history/' . $topic->topic_num,
                        "object" => $topic->topic_name . " / " . $camp->camp_name,
                    ];
                    Event::dispatch(new ThankToSubmitterMailEvent($request->user(), $dataEmail));
                } catch (Throwable $e) {
                    $status = 403;
                    $message = $e->getMessage();
                }
                $status = 200;
                $message = trans('message.success.topic_created');
            } else {
                $status = 400;
                $message = trans('message.error.topic_failed');
            }
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        } catch (Throwable $e) {
            DB::rollback();
            $status = 400;
            $message = $e->getMessage();
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }

   /**
    * @OA\Post(path="/get-topic-record",
     *   tags={"getTopicRecord"},
     *   summary="get topic record",
     *   description="Used to get topic record.",
     *   operationId="getTopicRecord",
     *   @OA\RequestBody(
     *       required=true,
     *       description="Get topic records",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               @OA\Property(
     *                   property="topic_num",
     *                   description="topic number is required",
     *                   required=true,
     *                   type="integer",
     *                   format="int32"
     *               ),
     *               @OA\Property(
     *                   property="camp_num",
     *                   description="Camp number is required",
     *                   required=true,
     *                   type="integer",
     *                   format="int32"
     *               ),
     *               @OA\Property(
     *                   property="as_of",
     *                   description="As of filter type",
     *                   required=false,
     *                   type="string",
     *               ),
     *               @OA\Property(
     *                   property="as_of_date",
     *                   description="As of filter date",
     *                   required=false,
     *                   type="string",
     *               )
     *        )
     *   )
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */

    public function getTopicRecord(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getTopicRecordValidationRules(), $this->validationMessages->getTopicRecordValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $filter['topicNum'] = $request->topic_num;
        $filter['asOf'] = $request->as_of;
        $filter['asOfDate'] = $request->as_of_date;
        $filter['campNum'] = $request->camp_num;

        try {  
            $topic[] = Camp::getAgreementTopic($filter);
            if ($topic) {
                $topic = $this->resourceProvider->jsonResponse('topic-record', $topic);
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $topic, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), $e->getMessage(), '');
        }
    }

}

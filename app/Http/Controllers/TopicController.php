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
use App\Models\CampSubscription;
use App\Jobs\ActivityLoggerJob;
use App\Models\Nickname;

class TopicController extends Controller
{

    public function __construct(ResponseInterface $respProvider, ResourceInterface $resProvider, ValidationRules $rules, ValidationMessages $validationMessages)
    {
        $this->rules = $rules;
        $this->validationMessages = $validationMessages;
        $this->resourceProvider  = $resProvider;
        $this->resProvider = $respProvider;
    }

    /**
     * @OA\POST(path="/topic/save",
     *   tags={"Topic"},
     *   summary="save topic",
     *   description="This is use for save topic",
     *   operationId="topicSave",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *    @OA\RequestBody(
     *     required=true,
     *     description="Request Body Json Parameter",
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *               @OA\Property(
     *                  property="topic_name",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="namespace",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="nick_name",
     *                  type="string"
     *              ),
     *               @OA\Property(
     *                  property="note",
     *                  type="string"
     *              )
     *          )
     *     ),
     *   ),
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="object",
     *                                 @OA\Property(
     *                                         property="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Property(
     *                                         property="data",
     *                                         type="object",
     *                                             @OA\Property(
     *                                              property="topic_num",
     *                                              type="integer"
     *                                          )
     *                                    )
     *                                 )
     *                            ),
     *
     *    @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     *
     * )
     */

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
                Util::dispatchJob($topic, 1, 1);
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
                    $link = Util::getTopicCampUrl($topic->topic_num, 1, $topicLive, $camp, time());
                    $historylink = Util::topicHistoryLink($topic->topic_num, 1, $topic->topic_name, 'Aggreement', 'topic');
                    $dataEmail = (object) [
                        "type" => "topic",
                        "link" => $link,
                        "historylink" => $historylink,
                        "object" => $topic->topic_name . " / " . $topic->camp_name,
                    ];
                    Event::dispatch(new ThankToSubmitterMailEvent($request->user(), $dataEmail));
                    $activitLogData = [
                        'log_type' =>  "topic/camps",
                        'activity' => 'Topic created',
                        'url' => $link,
                        'model' => $topic,
                        'topic_num' => $topic->topic_num,
                        'camp_num' =>  1,
                        'user' => $request->user(),
                        'nick_name' => Nickname::getNickName($request->nick_name)->nick_name
                    ];
                    dispatch(new ActivityLoggerJob($activitLogData))->onQueue(env('QUEUE_SERVICE_NAME'));
                } catch (Throwable $e) {
                    $data = null;
                    $status = 403;
                    $message = $e->getMessage();
                    return $this->resProvider->apiJsonResponse($status, $message, $data, null);
                }
                $data = $topic;
                $status = 200;
                $message = trans('message.success.topic_created');
            } else {
                $data = null;
                $status = 400;
                $message = trans('message.error.topic_failed');
            }
            return $this->resProvider->apiJsonResponse($status, $message, $data, null);
        } catch (Throwable $e) {
            DB::rollback();
            $status = 400;
            $message = $e->getMessage();
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }

    /**
     * @OA\Post(path="/get-topic-record",
     *   tags={"Topic"},
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
     *               ),
     *               @OA\Property(
     *                   property="camp_num",
     *                   description="Camp number is required",
     *                   required=true,
     *                   type="integer",
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
     *         )
     *      )
     *   ),
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
            $topic = Camp::getAgreementTopic($filter);
            $topic->topicSubscriptionId = "";
            if ($request->user()) {
                $topicSubscriptionData = CampSubscription::where('user_id', '=', $request->user()->id)->where('camp_num', '=', 0)->where('topic_num', '=', $filter['topicNum'])->where('subscription_start', '<=', strtotime(date('Y-m-d H:i:s')))->where('subscription_end', '=', null)->orWhere('subscription_end', '>=', strtotime(date('Y-m-d H:i:s')))->first();
                $topic->topicSubscriptionId = isset($topicSubscriptionData->id) ? $topicSubscriptionData->id : "";
            }
            if ($topic) {
                $topicRecord[] = $topic;
                $indexs = ['topic_num', 'camp_num', 'topic_name', 'namespace_name', 'topicSubscriptionId'];
                $topicRecord = $this->resourceProvider->jsonResponse($indexs, $topicRecord);
                $topicRecord = $topicRecord[0];
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $topicRecord, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}

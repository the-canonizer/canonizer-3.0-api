<?php

namespace App\Http\Controllers;


use DB;
use App\Models\Camp;
use App\Facades\Util;
use App\Models\Topic;
use App\Models\Reasons;
use App\Models\Support;
use App\Models\Nickname;
use Illuminate\Http\Request;
use App\Helpers\TopicSupport;
use App\Http\Request\Validate;
use App\Facades\PushNotification;
use App\Helpers\ResponseInterface;
use Illuminate\Support\Facades\Gate;
use App\Helpers\SupportAndScoreCount;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Http\Request\ValidationMessages;
use App\Models\ActivityLog;
use App\Models\ChangeAgreeLog;
use App\Models\Statement;
use Exception;

class SupportController extends Controller
{
    private const PROPERTIES_TOPIC_NUM = 'properties->topic_num';
    private const PROPERTIES_CAMP_NUM = 'properties->camp_num';

    private ValidationRules $rules;

    private ValidationMessages $validationMessages;

    public function __construct(ResponseInterface $resProvider)
    {
        $this->rules = new ValidationRules;
        $this->validationMessages = new ValidationMessages;
        $this->resProvider = $resProvider;
    }
    

    /**
     * @OA\Post(
     *     path="/support/add",
     *     summary="Add Direct Support",
     *     description="Adds direct support to a topic.",
     *     tags={"Support"},
     *     operationId="support/add",
     *     security={{"loginAuthToken":{}}},
     *     @OA\RequestBody( 
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="topic_num", type="integer", example=6474, description="Topic number"),
     *             @OA\Property(
     *                 property="add_camp",
     *                 type="object",
     *                 description="Camps to add support to",
     *                 @OA\Property(property="camp_num", type="integer", example=1, description="Camp number"),
     *                 @OA\Property(property="support_order", type="integer", example=1, description="Support order")
     *             ),
     *             @OA\Property(
     *                 property="remove_camps",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example ={},
     *                 description="Camps to remove support from"
     *             ),
     *             @OA\Property(property="type", type="string", example="direct", description="Type of support"),
     *             @OA\Property(property="action", type="string", example="add", description="Action to perform"),
     *             @OA\Property(property="nick_name_id", type="integer", example=995, description="Nickname ID"),
     *             @OA\Property(
     *                 property="order_update",
     *                 type="array",
     *                 description="Order update for camps",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="camp_num", type="integer", example=1, description="Camp number"),
     *                     @OA\Property(property="order", type="integer", example=1, description="New order")
     *                 )
     *             ),
     *             @OA\Property(property="reason", type="string", example="New information/evidence", description="Reason for support"),
     *             @OA\Property(property="reason_summary", type="string", example="Summary of the reason", description="Summary of the reason"),
     *             @OA\Property(property="citation_link", type="string", example="https://www.example.com?post=1234", description="Citation link")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Support added successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Support added successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or exception",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Validation error or exception")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=403),
     *             @OA\Property(property="message", type="string", example="Invalid data")
     *         )
     *     )
     * )
     */


    public function addDirectSupport(Request $request, Validate $validate)
    {        
        $validationErrors = $validate->validate($request, $this->rules->getAddDirectSupportRule(), $this->validationMessages->getAddDirectSupportMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        if (! Gate::allows('nickname-check', $request->nick_name_id)) {
            return $this->resProvider->apiJsonResponse(403, trans('message.error.invalid_data'), '', '');
        }

        $all = $request->all();
        $user = $request->user();
        $topicNum = $all['topic_num'];
        $nickNameId = $all['nick_name_id'];
        $addCamp = $all['add_camp'];
        $removedCamps = $all['remove_camps'];
        $orderUpdate = $all['order_update']; 
        $reason = $all['reason'] ?? null; 
        $reason_summary = $all['reason_summary'] ?? null; 
        $citation_link = $all['citation_link'] ?? null; 
        // dd($all);
        try{            
            TopicSupport::addDirectSupport($topicNum, $nickNameId, $addCamp, $user, $removedCamps, $orderUpdate,$reason,$reason_summary,$citation_link);
            $message =TopicSupport::getMessageBasedOnAction($addCamp, $removedCamps, $orderUpdate, $topicNum);            
            return $this->resProvider->apiJsonResponse(200, $message, '', '');
    
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/support/add-delegate",
     *     summary="Add Delegate Support",
     *     description="Adds delegate support to a topic.",
     *     tags={"Support"},
     *     operationId="support/add-delegate",
     *     security={{"loginAuthToken":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="topic_num", type="integer", description="Topic number"),
     *             @OA\Property(property="nick_name_id", type="integer", description="Nickname ID"),
     *             @OA\Property(property="camp_num", type="integer", description="Camp number"),
     *             @OA\Property(property="delegated_nick_name_id", type="integer", description="Delegated Nickname ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Delegate support added successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Delegate support added successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or exception",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Validation error or exception")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=403),
     *             @OA\Property(property="message", type="string", example="Invalid data")
     *         )
     *     )
     * )
     */
    public function addDelegateSupport(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getAddDelegateSupportRule(), $this->validationMessages->getAddDelegateSupportMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        if (!Gate::allows('nickname-check', $request->nick_name_id)) {
            return $this->resProvider->apiJsonResponse(403, trans('message.error.invalid_data'), '', '');
        }
        
        $all = $request->all();  
        $user = $request->user();

        try{
            $topicNum   = $all['topic_num'];
            $nickNameId = $all['nick_name_id'];
            $campNum    = isset($all['camp_num']) ? $all['camp_num'] : '';
            $delegatedNickId = $all['delegated_nick_name_id'];

            // add delegation support
            $result = TopicSupport::addDelegateSupport($request->user(),$topicNum, $campNum, $nickNameId, $delegatedNickId);

            $camp = Camp::where('topic_num', $topicNum)
                ->where('camp_num', $campNum)
                ->where('grace_period', 0)
                ->orderByDesc('go_live_time')
                ->first();

            // Update the camp_leader_nick_id
            if ($camp) {
                $camp->camp_leader_nick_id = $delegatedNickId;
                $camp->save();
            }
            
            $message = ['add' => trans('message.support.add_delegation_support')];
            return $this->resProvider->apiJsonResponse(200, $message, '','');

        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/support/update",
     *     summary="Remove Support",
     *     description="Removes support from a topic.",
     *     tags={"Support"},
     *     operationId="support/update",
     *     security={{"loginAuthToken":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="topic_num", type="integer", description="Topic number"),
     *             @OA\Property(property="camp_num", type="integer", description="Camp number"),
     *             @OA\Property(property="remove_camps", type="array", @OA\Items(type="integer"), description="Camps to remove support from"),
     *             @OA\Property(property="action", type="string", description="Action type (all or partial)"),
     *             @OA\Property(property="type", type="string", description="Type of support"),
     *             @OA\Property(property="nick_name_id", type="integer", description="Nickname ID"),
     *             @OA\Property(property="reason", type="string", description="Reason for removing support"),
     *             @OA\Property(property="reason_summary", type="string", description="Summary of the reason"),
     *             @OA\Property(property="citation_link", type="string", description="Citation link"),
     *             @OA\Property(property="order_update", type="array", @OA\Items(type="integer"), description="Order update for camps")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Support removed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Support removed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or exception",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Validation error or exception")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=403),
     *             @OA\Property(property="message", type="string", example="Invalid data")
     *         )
     *     )
     * )
     */
    public function removeSupport(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getRemoveSupportValidationRules(), $this->validationMessages->getRemoveSupportValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        $all = $request->all();
        // return json_encode($all);
        $user = $request->user();
        $userId = $user->id;
        $topicNum =$all['topic_num'];
        $campNum = isset($all['camp_num']) && $all['camp_num'] ? $all['camp_num'] : '';
        $removeCamps = isset($all['remove_camps']) && $all['remove_camps'] ? $all['remove_camps'] : [];
        $action = $all['action']; // all OR partial
        $type = isset($all['type']) ? $all['type'] : '';
        $nickNameId = $all['nick_name_id'] ?? '';
        $reason = isset($request->reason) ? $request->reason : null;
        $citation_link =isset($request->citation_link) ?$request->citation_link : null;
        $reason_summary = isset($request->reason_summary) ? $request->reason_summary : null;
        $orderUpdate = isset($all['order_update']) ? $all['order_update'] : [];

        try{
            TopicSupport::removeDirectSupport($topicNum, $removeCamps, $nickNameId, $action, $type, $orderUpdate, $request->user(),$reason,$reason_summary,$citation_link);     
            $message =TopicSupport::getMessageBasedOnAction([], $removeCamps, $orderUpdate, $topicNum);
            if(isset($action) && $action == 'all' && empty($message)){
                $message = "Support removed successfully.";
            }
            return $this->resProvider->apiJsonResponse(200, $message, '','');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
    
    /**
     * @OA\Post(
     *     path="/support/remove-delegate",
     *     summary="Remove Delegate Support",
     *     description="Removes delegate support from a topic.",
     *     tags={"Support"},
     *     operationId="support/remove-delegate",
     *     security={{"loginAuthToken":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="topic_num", type="integer", description="Topic number"),
     *             @OA\Property(property="nick_name_id", type="integer", description="Nickname ID"),
     *             @OA\Property(property="delegated_nick_name_id", type="integer", description="Delegated Nickname ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Delegate support removed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Delegate support removed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or exception",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Validation error or exception")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=403),
     *             @OA\Property(property="message", type="string", example="Invalid data")
     *         )
     *     )
     * )
     */
    public function removeDelegateSupport(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getRemoveDelegateSupportValidationRules(), $this->validationMessages->getRemoveDelegateSupportValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        $all = $request->all();
        $topicNum =$all['topic_num'];
        $nickNameId = $all['nick_name_id'];
        $delegatedNickNameId = $all['delegated_nick_name_id'];
        try{
            TopicSupport::removeDelegateSupport($topicNum, $nickNameId, $delegatedNickNameId);   
            $message = ['remove' => [ trans('message.support.delegate_support_removed') ]];            
            return $this->resProvider->apiJsonResponse(200, $message, '','');
        
        } catch (\Throwable $e) {

            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="support-order/update",
     *     summary="Update Support Order",
     *     description="Updates the order of support for a topic.",
     *     tags={"Support"},
     *     operationId="support-order/update",
     *     security={{"loginAuthToken":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="topic_num", type="integer", description="Topic number"),
     *             @OA\Property(property="camp_num", type="integer", description="Camp number"),
     *             @OA\Property(property="nick_name_id", type="integer", description="Nickname ID"),
     *             @OA\Property(property="order_update", type="array", @OA\Items(type="integer"), description="Order update for camps"),
     *             @OA\Property(property="reason", type="string", description="Reason for updating support order"),
     *             @OA\Property(property="reason_summary", type="string", description="Summary of the reason"),
     *             @OA\Property(property="citation_link", type="string", description="Citation link")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Support order updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Support order updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or exception",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Validation error or exception")
     *         )
     *     )
     * )
     */
    public function updateSupportOrder(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getUpdateSupportOrderValidationRules(), $this->validationMessages->getUpdateSupportOrderValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        $all = $request->all();
        $topicNum =$all['topic_num'];
        $campNum = isset($all['camp_num']) && $all['camp_num'] ? $all['camp_num'] : '';
        $nickNameId = $all['nick_name_id'];
        $orderUpdate = isset($all['order_update']) ? $all['order_update'] : [];
        $reason = isset($request->reason) ? $request->reason : null;
        $citation_link =isset($request->citation_link) ?$request->citation_link : null;
        $reason_summary = isset($request->reason_summary) ? $request->reason_summary : null;
        try{
            $allNickNames = Nickname::getAllNicknamesByNickId($nickNameId);
            TopicSupport::reorderSupport($orderUpdate, $topicNum, $allNickNames,$reason,$reason_summary,$citation_link);
            return $this->resProvider->apiJsonResponse(200, trans('message.support.order_update'), '','');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/topic-support-list",
     *     summary="Get support in topic",
     *     description="Retrieve the list of camps supported by the user in a specific topic.",
     *     tags={"Support"},
     *     operationId="getSupportInTopic",
     *     security={{"loginAuthToken":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Retrieve support information for a given topic",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="topic_num",
     *                 type="integer",
     *                 description="The topic number to retrieve support information for.",
     *                 example=123
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of support information",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="link", type="string", description="Link to the camp"),
     *                 @OA\Property(property="title", type="string", description="Title of the topic"),
     *                 @OA\Property(property="camp_leader", type="boolean", description="Indicates if the user is the camp leader")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request or exception occurred",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", description="Error message")
     *         )
     *     )
     * )
     */

    public function getSupportInTopic(Request $request)
    {
        $all = $request->all();
        $topicNum = $all['topic_num'];
        $userId = $request->user()->id;
        try{
            $topic = Topic::getLiveTopic($topicNum);
            $data = Support::getSupportedCampsList($topicNum, $userId); 
            foreach($data as $key => $support){
                $liveCamp = Camp::getLiveCamp(['topicNum' => $topicNum, 'campNum' => $support['camp_num']]);
                $link = Camp::campLink($support['topic_num'], $support['camp_num'], $support['title'], $support['camp_name']);
                $data[$key]['link'] = $link;
                $data[$key]['title'] = $topic->topic_name;
                $data[$key]['camp_leader'] = $liveCamp->camp_leader_nick_id > 0 && $liveCamp->camp_leader_nick_id === $support['nick_name_id'];
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $data,'');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
    
    /**
     * @OA\Post(
     *     path="/support-and-score-count",
     *     summary="Get Camp Support and Count",
     *     description="Retrieves the support and count for a specific camp.",
     *     tags={"Support"},
     *     operationId="support-and-score-count",
     *     security={{"clientAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="algorithm", type="string", description="Algorithm to use"),
     *             @OA\Property(property="topic_num", type="integer", description="Topic number"),
     *             @OA\Property(property="camp_num", type="integer", description="Camp number"),
     *             @OA\Property(property="as_of_date", type="integer", description="As of date timestamp", example=1633024800)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getCampSupportAndCount(Request $request) 
    {
        $all = $request->all();
        $algorithm = $all['algorithm'];
        $topicNum = $all['topic_num'];
        $campNum = $all['camp_num'];
        $asOfDate = (isset($all['as_of_date']) && $all['as_of_date']) ? $all['as_of_date'] : time();
        try{            
            $supportCount = new SupportAndScoreCount();
            $data = $supportCount->getSupporterWithScore($algorithm, $topicNum, $campNum, $asOfDate);
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $data,'');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }  
    }

    /**
     * @OA\POST(
     *     path="/camp-total-support-score",
     *     summary="Get Camp Total Support Score",
     *     tags={"Support"},
     *     operationId="camp-total-support-score",
     *     security={{"clientAuth":{}}},
     *     @OA\Parameter(
     *         name="algorithm",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="The algorithm to be used for calculating the support score"
     *     ),
     *     @OA\Parameter(
     *         name="topic_num",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The topic number"
     *     ),
     *     @OA\Parameter(
     *         name="camp_num",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The camp number"
     *     ),
     *     @OA\Parameter(
     *         name="as_of_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer"),
     *         description="The date as of which the support score is calculated (timestamp)"
     *     ),
     *     @OA\Parameter(
     *         name="as_of",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         description="Additional parameter for specifying the date"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Exception"),
     *             @OA\Property(property="error", type="string", example="Error message")
     *         )
     *     )
     * )
     */

    public function getCampTotalSupportScore(Request $request)
    {
        $all = $request->all();
        $algorithm = $all['algorithm'];
        $topicNum = $all['topic_num'];
        $campNum = $all['camp_num'];
        $asOfDate = (isset($all['as_of_date']) && $all['as_of_date']) ? $all['as_of_date'] : time();
        $asOf = (isset($all['as_of']) && $all['as_of']) ? $all['as_of'] : ""; 

        try{
            $supportCount = new SupportAndScoreCount();
            $data = $supportCount->getCampTotalSupportScore($algorithm, $topicNum, $asOfDate, $campNum, $asOf);
        
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $data,'');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }  
    }

    /**
     * @OA\Get(
     *     path="/support-reason-list",
     *     summary="Get Support Reasons",
     *     description="Retrieves a list of support reasons.",
     *     operationId="support-reason-list",
     *     security={{"clientAuth":{}}},
     *     tags={"Support"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getSupportReason(Request $request)
    {
        try{
            $supportReasonList = Reasons::get();
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $supportReasonList,'');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }  
    }

    /**
     * @OA\Post(
     *   path="get-change-supporters",
     *   tags={"Support"},
     *   summary="Get supporter count",
     *   description="Used to get supporter count for camp, topic, and statement.",
     *   operationId="getChangeSupporters",
     *   security={{"clientAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="Discard change",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               required={"topic_num", "camp_num", "change_id", "type"},
     *               @OA\Property(
     *                   property="topic_num",
     *                   description="Topic number is required",
     *                   type="integer"
     *               ),
     *               @OA\Property(
     *                   property="camp_num",
     *                   description="Camp number is required",
     *                   type="integer"
     *               ),
     *               @OA\Property(
     *                   property="change_id",
     *                   description="Change ID is required",
     *                   type="integer"
     *               ),
     *               @OA\Property(
     *                   property="type",
     *                   description="Type (topic, camp, statement)",
     *                   type="string"
     *               ),
     *           )
     *       )
     *   ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */

    public function getChangeSupporters(Request $request, Validate $validate)
    {
        try {
            $validationErrors = $validate->validate($request, $this->rules->getChangeSupportersValidationRules(), $this->validationMessages->getChangeSupportersValidationMessages());
            if ($validationErrors) {
                return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
            }
            $inputs = $request->input();

            $where = [
                'topic_num' => $inputs['topic_num'],
                'camp_num' => $inputs['camp_num'],
                'id' => $inputs['change_id'],
            ];

            switch ($inputs['type']) {
                case 'statement':
                    $model = Statement::where($where)->first();
                    break;
                case 'camp':
                    $model = Camp::where($where)->first();
                    break;
                case 'topic':
                    unset($where['camp_num']);
                    $model = Topic::where($where)->first();
                    break;

                default:
                    $model = null;
                    break;
            }

            if (!$model) {
                return $this->resProvider->apiJsonResponse(400, trans('message.error.record_not_found'), '', '');
            }

            $isChangeLive = $model->grace_period == 0 && $model->go_live_time < time() && is_null($model->objector_nick_id);

            $response = [];
            [$response['total_supporters'], $response['total_supporters_count']] = Support::getTotalSupporterByTimestamp($inputs['type'], (int)$inputs['topic_num'], (int)$inputs['camp_num'], $model->submitter_nick_id, $model->submit_time, ['topicNum' => $inputs['topic_num'], 'campNum' => $inputs['camp_num'], 'change_id' => $inputs['change_id']], $inputs['type'] === 'camp' ? false : true);
            
            if ($inputs['type'] == 'camp') {
                $filter['topicNum'] = (int)$inputs['topic_num'];
                $filter['campNum'] = (int)$inputs['camp_num'];
                $preLiveCamp = Camp::getLiveCamp($filter);
                if ($model->is_archive != $preLiveCamp->is_archive) { 
                    $revokableSupporters = Support::getSupportersNickNameOfArchivedCamps($filter['topicNum'], [$filter['campNum']], $model->is_archive, 1)->pluck('nick_name_id')->toArray();
                    $explicitSupporters = Support::ifIamArchiveExplicitSupporters($filter,$model->is_archive, 'supporters')->pluck('nick_name_id')->toArray();
                    $archiveSupporters = Support::filterArchivedSupporters($revokableSupporters, $explicitSupporters, $model->submitter_nick_id, $response['total_supporters']);
                    $archiveDirectSupporters = $archiveSupporters['direct_supporters'];
                    $archiveExplicitSupporters = $archiveSupporters['explicit_supporters'];

                    $archiveDirectSupporters = Nickname::select('id', 'nick_name')->whereIn('id', $archiveDirectSupporters)->get()->toArray();
                    
                    $archiveExplicitSupporters = Nickname::select('id', 'nick_name')->whereIn('id', $archiveExplicitSupporters)->get()->toArray();

                    $response['total_supporters'] = array_merge($response['total_supporters'], $archiveDirectSupporters, $archiveExplicitSupporters);
                    $response['total_supporters_count'] = $response['total_supporters_count'] + count($archiveDirectSupporters) + count($archiveExplicitSupporters);

                } else {
                    $revokableSupporters = Support::getAllRevokedSupporters((int)$inputs['topic_num'])->pluck('nick_name_id')->toArray();
                    if ($revokableSupporters) {
                        $revokableSupporters = Nickname::select('id', 'nick_name')->whereIn('id', $revokableSupporters)->get()->toArray();
                        $response['total_supporters'] = array_merge($response['total_supporters'], $revokableSupporters);
                        $response['total_supporters'] = array_map("unserialize", array_unique(array_map("serialize", $response['total_supporters'])));
                        $response['total_supporters_count'] = count($response['total_supporters']);
                    }
                }
            }

            [$response['agreed_supporters'], $response['agreed_supporters_count']] = ChangeAgreeLog::getAgreedSupporter((int)$inputs['topic_num'], (int)$inputs['camp_num'], $model->id, $inputs['type'], $model->submitter_nick_id, $model->submit_time);

            $response = $this->checkAgreedSupporters($response, $isChangeLive);

            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $response, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse($e->getCode() > 0 ? $e->getCode() : 500, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/camp/sign/check",
     *     summary="Check if User Already Signed Camp",
     *     description="Checks if the user has already signed the camp.",
     *     tags={"Support"},
     *     operationId="campSignCheck",
     *     security={{"clientAuth":{}}},
     *     @OA\Parameter(
     *         name="topic_num",
     *         in="query",
     *         required=true,
     *         description="Topic number",
     *         @OA\Schema(type="integer", example=6474)
     *     ),
     *     @OA\Parameter(
     *         name="camp_num",
     *         in="query",
     *         required=true,
     *         description="Camp number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="User has signed the camp"),
     *             @OA\Property(property="data", type="object", example={"signed": true})
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Invalid parameters"),
     *             @OA\Property(property="error", type="string", example="Topic number is required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Camp not found"),
     *             @OA\Property(property="error", type="string", example="The specified camp does not exist")
     *         )
     *     )
     * )
     */

    public function checkIfUserAlreadySignCamp(Request $request)
    {
        $data = $request->all();
        $userId = $request->user()->id;

        try {
            $topicNum = isset($data['topic_num']) ? $data['topic_num'] : '';
            $campNum =  isset($data['camp_num']) ? $data['camp_num'] : '';
            $nickNames = Nickname::getNicknamesIdsByUserId($userId);
            
            if (!$topicNum || !$campNum) {
                return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', '');
            }

            if (!Topic::getLiveTopic($topicNum) || !Camp::getLiveCamp(['topicNum' => $topicNum, 'campNum' => $campNum])) {
                return $this->resProvider->apiJsonResponse(404, '', null, trans('message.error.record_not_found'));
            }
            
            $support = Support::checkIfSupportExists($topicNum, $nickNames, [$campNum]);
            $data = TopicSupport::checkSignValidaionAndWarning($topicNum, $campNum, $nickNames);

            if($data === 'cannot_delegate_itslef') {
                $data = null;
            }

            if ($support) {
                $data['support_flag'] = 1;
                $message = trans('message.support.support_exist');
            } else {
                $message = trans('message.support.support_not_exist');
                $data['support_flag'] = 0;
            }

            return $this->resProvider->apiJsonResponse(200, $message ?? '', $data, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    private function checkAgreedSupporters($response, $isChangeLive = false)
    {
        $response['supporters'] = collect($response['total_supporters'])->map(function ($supporter) use ($response, $isChangeLive) {
            $supporter['agreed'] = $isChangeLive ? true : in_array($supporter['id'], $response['agreed_supporters']);
            return $supporter;
        })->toArray();
        unset($response['total_supporters'], $response['agreed_supporters']);
        return $response;
    }

    /**
     * @OA\Get(
     *     path="/support/check",
     *     summary="Check if support exists",
     *     description="Checks whether a user has support for a specific topic and camp.",
     *     tags={"Support"},
     *     operationId="checkIfSupportExist",
     *     security={{"clientAuth":{}}},
     *     @OA\Parameter(
     *         name="topic_num",
     *         in="query",
     *         required=true,
     *         description="Topic number to check support for",
     *         @OA\Schema(type="integer", example=6474)
     *     ),
     *     @OA\Parameter(
     *         name="camp_num",
     *         in="query",
     *         required=true,
     *         description="Camp number to check support for",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="delegated_nick_name_id",
     *         in="query",
     *         required=false,
     *         description="Delegated Nickname ID (optional)",
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Support check result",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Support exists"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="support_flag", type="integer", example=1, description="1 if support exists, 0 if not"),
     *                 @OA\Property(property="remove_camps", type="array", @OA\Items(type="integer"), description="List of camps to be removed"),
     *                 @OA\Property(property="warning", type="string", example="Warning message if applicable")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request or validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Validation error")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Record not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Record not found")
     *         )
     *     )
     * )
     */

    public function checkIfSupportExist(Request $request)
    {
        $data = $request->all();
        $user = $request->user();
        $userId = $user->id;
        try{
            $topicNum = isset($data['topic_num']) ? $data['topic_num'] : '';
            $campNum =  isset($data['camp_num']) ? $data['camp_num'] : '';
            $delegataedNickNameId = isset($data['delegated_nick_name_id']) ? $data['delegated_nick_name_id'] : 0;
            $nickNames = Nickname::getNicknamesIdsByUserId($userId);
            if(!$topicNum || !$campNum)
            {
                return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '','');
            }

            if (!Topic::getLiveTopic($topicNum) || !Camp::getLiveCamp(['topicNum' => $topicNum, 'campNum' => $campNum])) {
                return $this->resProvider->apiJsonResponse(404, '', null, trans('message.error.record_not_found'));
            }
            $support = Support::checkIfSupportExists($topicNum, $nickNames,[$campNum]);
            $data = TopicSupport::checkSupportValidaionAndWarning($topicNum, $campNum, $nickNames, $delegataedNickNameId);
            if (isset($data['remove_camps'])) {
                $data['remove_camps'] = collect($data['remove_camps'])->sortBy('support_order')->values()->all();
            }

            if($support){
                $data['support_flag'] = 1;
                $message = trans('message.support.support_exist');

            }else{
                $message = trans('message.support.support_not_exist');
                $data['support_flag'] = 0;
            }
            return $this->resProvider->apiJsonResponse(200, $message, $data,'');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }  
    }

    // Common function to get the supported camps
    private function getSupportedCampsData(Request $request, $supportType)
    {
        $user = $request->user();
        $userId = $user->id;
        $per_page = $request->get('per_page', 10); // Default to 10 if not provided
        $searchTopicName = $request->get('search', '');
        
        // Get current page from URL, default to page 1 if not set
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page <= 0) {
            $page = 1;
        }
        $page = ($page - 1);

        try {
        $sql = "CALL user_support(?, ?, ?, ?, ?)";
        $params = [$supportType, $userId, $page, $per_page, $searchTopicName];
        $connection = \DB::connection()->getPdo();  // Get the raw PDO connection
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
    
        // Fetch the first result set (paginated data
        $paginatedData = $stmt->fetchAll(\PDO::FETCH_OBJ);
    
        $stmt->nextRowset();  // Move to the second result set
        // Fetch the second result set (total count)
        $totalRecordsResult = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $totalRecords = $totalRecordsResult[0]->total_records ?? 0;
            // dd($totalRecords);
        $supports = [];
        foreach ($paginatedData as $k => $support) 
        {
            $jsonString = '[' . $support->details . ']';
            $result = json_decode($jsonString, true);
            $camps = []; 
            foreach ($result as $item) 
            {
                $campData = [
                    'id' => $item['camp_num'],
                    'camp_num' => $item['camp_num'],
                    'camp_name' => $item['camp_name'],
                    'support_order' => $item['support_order'],
                    'camp_link' => Camp::campLink($item['topic_num'], $item['camp_num'], $item['title'], $item['camp_name']),
                ];
                $camps[] = $campData;
            }
            $supports[$support->topic_num] = [
                'topic_num' => $support->topic_num,
                'title' => $support->title,
                'nick_name_id' => $support->nick_name_id,
                'title_link' => Topic::topicLink($support->topic_num, $support->title, 1),
                'camps' => $camps,
            ];

                // If the support type is delegate, add delegate-specific data
            if ($supportType == 'delegate') {
                $supports[$support->topic_num]['delegated_nick_name_id'] = $support->delegate_nick_name_id;
                $supports[$support->topic_num]['my_nick_name'] = $support->my_nick_name;
                $supports[$support->topic_num]['my_nick_name_link'] = Nickname::getNickNameLink(
                    $support->nick_name_id, $support->namespace_id, $support->topic_num, $support->camp_num
                );
                $supports[$support->topic_num]['delegated_to_nick_name'] = $support->delegated_to_nick_name;
                $supports[$support->topic_num]['delegated_to_nick_name_link'] = Nickname::getNickNameLink(
                    $support->delegate_nick_name_id, $support->namespace_id, $support->topic_num, $support->camp_num
                );
            }
        }
        return [
                'items' => $supports,
                'total' => $totalRecords
            ];

        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
         * @OA\Get(
        *     path="/get-direct-supported-camps",
        *     tags={"Support"},
        *     summary="Fetch direct supported camps",
        *     description="Retrieves a list of camps directly supported by the user.",
        *     operationId="getDirectSupportedCamps",
        *     security={{"clientAuth":{}}},
        *     @OA\Parameter(
        *         name="page",
        *         in="query",
        *         required=true,
        *         description="Page number to fetch direct supported camps",
        *         @OA\Schema(type="integer", default=1)
        *     ),
        *     @OA\Parameter(
        *         name="per_page",
        *         in="query",
        *         required=true,
        *         description="Per page records to fetch direct supported camps",
        *       @OA\Schema(type="integer", default=10)
        *     ),
        *     @OA\Parameter(
        *         name="search",
        *         in="query",
        *         required=false,
        *         description="Search Param to fetch direct supported camps",
        *         @OA\Schema(type="string")
        *     ),
        *     @OA\Response(
        *         response=200,
        *         description="Successful operation",
        *         @OA\JsonContent(
        *             type="object",
        *             @OA\Property(property="status_code", type="integer"),
        *             @OA\Property(property="message", type="string"),
        *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
        *         )
        *     ),
        *     @OA\Response(
        *         response=400,
        *         description="Bad request",
        *         @OA\JsonContent(
        *             @OA\Property(property="status_code", type="integer"),
        *             @OA\Property(property="message", type="string"),
        *             @OA\Property(property="error", type="string")
        *         )
        *     )
        * )
        */

    public function getDirectSupportedCamps(Request $request)
    {
        try{
            $supportType = 'direct';  // Direct support type
            $finalData = $this->getSupportedCampsData($request, $supportType); // Call the common function
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $finalData, '');
        }catch(\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
    
    /**
     * @OA\Get(
     *     path="/get-delegated-supported-camps",
     *     tags={"Support"},
     *     summary="Fetch delegated supported camps",
     *     description="Retrieves a list of camps that the user has delegated support for.",
     *     operationId="getDelegatedSupportedCamps",
     *     security={{"clientAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number to fetch delegated supported camps",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=true,
     *         description="Number of records per page",
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search term to filter delegated supported camps",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Invalid input parameters",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Missing or invalid token",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */

    public function getDelegatedSupportedCamps(Request $request)
    {  
        try{
            $supportType = 'delegate';  // Delegate support type
            $finalData = $this->getSupportedCampsData($request, $supportType); // Call the common function
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $finalData, '');
        }
        catch(\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        } 
    }
}

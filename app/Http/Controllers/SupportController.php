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
use App\Models\ChangeAgreeLog;
use App\Models\Statement;
use Exception;

class SupportController extends Controller
{

    private ValidationRules $rules;

    private ValidationMessages $validationMessages;

    public function __construct(ResponseInterface $resProvider)
    {
        $this->rules = new ValidationRules;
        $this->validationMessages = new ValidationMessages;
        $this->resProvider = $resProvider;
    }
    

    /**
     * @OA\Get(path="/get-direct-supported-camps",
     *   tags={"support"},
     *   summary="Get list of all the direct supported camps",
     *   description="Get list of all the direct supported camps",
     *   operationId="directSupport",
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Something went wrong")
     * )
     */
    public function getDirectSupportedCamps(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;
        try {
            
            $response = DB::select("CALL user_support('direct', $userId)");
            $directSupports = [];
            foreach($response as $k => $support){

                if(isset($directSupports[$support->topic_num])){
                    $tempCamp = [
                        'id' => $support->camp_num,
                        'camp_num' => $support->camp_num,
                        'camp_name' => $support->camp_name,
                        'support_order'=> $support->support_order,
                        'camp_link' => Camp::campLink($support->topic_num,$support->camp_num,$support->title,$support->camp_name),
                    ];
                    array_push($directSupports[$support->topic_num]['camps'],$tempCamp);

                }else{
                    $directSupports[$support->topic_num] = array(
                        'topic_num' => $support->topic_num,
                        'title' => $support->title,
                        'nick_name_id' => $support->nick_name_id,
                        'title_link' => Topic::topicLink($support->topic_num,1,$support->title),
                        'camps' => array(
                                [
                                    'id' => $support->camp_num,
                                    'camp_num' => $support->camp_num,
                                    'camp_name' => $support->camp_name,
                                    'support_order' => $support->support_order,
                                    'camp_link' =>  Camp::campLink($support->topic_num,$support->camp_num,$support->title,$support->camp_name),

                                ]
                        ),
                    );
                }
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $directSupports, '');

        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }

    }


    /**
     * @OA\Get(path="/get-delegate-supported-camps",
     *   tags={"support"},
     *   summary="Get list of all the direct supported camps",
     *   description="Get list of all the direct supported camps",
     *   operationId="delegateSupport",
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Something went wrong")
     * )
     */
    public function getDelegatedSupportedCamps(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;
        try {
            
            $response = DB::select("CALL user_support('delegate', $userId)");
            $directSupports = [];
            foreach($response as $k => $support){

                if(isset($directSupports[$support->topic_num])){
                    $tempCamp = [
                        'camp_num' => $support->camp_num,
                        'camp_name' => $support->camp_name,
                        'support_order'=> $support->support_order,
                        'camp_link' => Camp::campLink($support->topic_num,$support->camp_num,$support->title,$support->camp_name),                        
                        'support_added' => date('Y-m-d',$support->start)
                    ];
                    array_push($directSupports[$support->topic_num]['camps'],$tempCamp);

                }else{
                    $directSupports[$support->topic_num] = array(
                        'topic_num' => $support->topic_num,
                        'title' => $support->title,
                        'title_link' => Topic::topicLink($support->topic_num,1,$support->title),
                        'nick_name_id' => $support->nick_name_id,
                        'delegated_nick_name_id' => $support->delegate_nick_name_id,
                        'my_nick_name' => $support->my_nick_name,
                        'my_nick_name_link' => Nickname::getNickNameLink($support->nick_name_id, $support->namespace_id, $support->topic_num, $support->camp_num),
                        'delegated_to_nick_name' => $support->delegated_to_nick_name,
                        'delegated_to_nick_name_link' => Nickname::getNickNameLink($support->delegate_nick_name_id, $support->namespace_id, $support->topic_num,  $support->camp_num),
                        'camps' => array(
                                [
                                    'camp_num' => $support->camp_num,
                                    'camp_name' => $support->camp_name,
                                    'support_order' => $support->support_order,
                                    'camp_link' =>  Camp::campLink($support->topic_num,$support->camp_num,$support->title,$support->camp_name),                                   
                                    'support_added' => date('Y-m-d',$support->start)

                                ]
                        ),
                    );
                }
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $directSupports, '');

        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }

    }


    /** @OA\Get(path="/add-direct-support",
     *   tags={"addSupport"},
     * )
     * 
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
            $message =TopicSupport::getMessageBasedOnAction($addCamp, $removedCamps, $orderUpdate);            
            return $this->resProvider->apiJsonResponse(200, $message, '', '');
    
         } catch (\Throwable $e) {
           return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }


    /** @OA\Get(path="support/add-delegate",
     *   tags={"addSupport"},
     * )
     * 
     */
    public function addDelegateSupport(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getAddDelegateSupportRule(), $this->validationMessages->getAddDelegateSupportMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        if (! Gate::allows('nickname-check', $request->nick_name_id)) {
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
            return $this->resProvider->apiJsonResponse(200, trans('message.support.add_delegation_support'), '','');

        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(path="/support/update",
     *  tags = "{updateSupport}"
     *  description = "This action handle remove / re-order  the support for both direct supporter"
     * ) 
     * 
     */

    public function removeSupport(Request $request)
    {
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
            $message =TopicSupport::getMessageBasedOnAction([], $removeCamps, $orderUpdate);
            return $this->resProvider->apiJsonResponse(200, $message, '','');
               
        } catch (\Throwable $e) {

            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
    
    /**
     * @OA\Post(path="/support-order/update",
     * 
     * 
     */
    public function updateSupportOrder(Request $request)
    {
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
     * @OA\Post(path="/support/update",
     *  tags = "{updateSupport}"
     *  description = "This action handle remove / re-order  the support for both direct and delegate supporter"
     * ) 
     * 
     */

    public function removeDelegateSupport(Request $request)
    {
        $all = $request->all();
        $topicNum =$all['topic_num'];
        $nickNameId = $all['nick_name_id'];
        $delegatedNickNameId = $all['delegated_nick_name_id'];

        if(!$delegatedNickNameId || !$topicNum || !$nickNameId){
            return $this->resProvider->apiJsonResponse(400, trans('message.support.delegate_invalid_request'), '', $e->getMessage());
        }

        try{

            TopicSupport::removeDelegateSupport($topicNum, $nickNameId, $delegatedNickNameId);               
            return $this->resProvider->apiJsonResponse(200, trans('message.support.delegate_support_removed'), '','');
        
        } catch (\Throwable $e) {

            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Get(path="/support/check",
     * tags = "{support}",
     * description = "This will check if user has support in this camp or not and send warning messages accordingly."
     * )
     * 
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


    /* @OA\Post(path="topic-support-list",
     *  tags = "{topicSupport}"
     *  description = "This will return support added in topic."
     * ) 
     * 
     */

    public function getSupportInTopic(Request $request)
    {
        $all = $request->all();
        $topicNum = $all['topic_num'];
        $user = $request->user();
        $userId = $user->id;
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

     /* @OA\Post(path="support-and-score-count",
     *  tags = "{topicSupport}"
     *  description = "This will return support tree with score for camp."
     * ) 
     * 
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
     *  @OA\Post(path="camp-total-support-score",
     *  tags = "{campSupport}"
     *  description = "This will return camp's total support score count"
     * )
     * 
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
           $data = $supportCount->getCampTotalSupportScore($algorithm, $topicNum, $campNum, $asOfDate, $asOf);
       
           return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $data,'');
       } catch (\Throwable $e) {

          return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
       }  
    }


      /**
     *  @OA\GET(path="support-reason-list",
     *  tags = "{campSupport}"
     *  description = "This will return support reason list"
     * )
     * 
     */

     public function getSupportReason(Request $request)
     {
 
        try{
            // $per_page = !empty($request->per_page) ? $request->per_page : config('global.per_page');
            $supportReasonList = Reasons::get();
            // $supportReasonList = Reasons::paginate($per_page);
            // $supportReasonList = Util::getPaginatorResponse($supportReasonList);
        
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $supportReasonList,'');
        } catch (\Throwable $e) {
 
           return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }  
     }

     /**
     * @OA\Post(path="get-change-supporters",
     *   tags={"Topic"},
     *   summary="get supporter with count",
     *   description="Used to get supporter with count for camp, topic and statement.",
     *   operationId="get-change-supporters",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *   @OA\RequestBody(
     *       required=true,
     *       description="Discard change",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               @OA\Property(
     *                   property="topic_num",
     *                   description="topic_num id is required",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="camp_num",
     *                   description="camp_num id is required",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="change_id",
     *                   description="change_id id is required",
     *                   required=true,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="type",
     *                   description="Type (topic, camp, statement)",
     *                   required=true,
     *                   type="string",
     *               ),
     *         )
     *      )
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

    private function checkAgreedSupporters($response, $isChangeLive = false)
    {
        $response['supporters'] = collect($response['total_supporters'])->map(function ($supporter) use ($response, $isChangeLive) {
            $supporter['agreed'] = $isChangeLive ? true : in_array($supporter['id'], $response['agreed_supporters']);
            return $supporter;
        })->toArray();
        unset($response['total_supporters'], $response['agreed_supporters']);

        return $response;
    }

    public function checkIfUserAlreadySignCamp(Request $request)
    {

        $data = $request->all();
        $user = $request->user();
        $userId = $user->id;

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
}

<?php

namespace App\Http\Controllers;


use DB;
use App\Models\Camp;
use App\Models\Topic;
use App\Models\Support;
use App\Models\Nickname;
use Illuminate\Http\Request;
use App\Helpers\TopicSupport;
use App\Http\Request\Validate;
use App\Facades\PushNotification;
use App\Helpers\ResponseInterface;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Http\Request\ValidationMessages;


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

        $all = $request->all();
        $user = $request->user();
        $topicNum = $all['topic_num'];
        $nickNameId = $all['nick_name_id'];
        $addCamp = $all['add_camp'];
        $removedCamps = $all['remove_camps'];
        $orderUpdate = $all['order_update'];        

        try{
            
            TopicSupport::addDirectSupport($topicNum, $nickNameId, $addCamp, $user, $removedCamps, $orderUpdate);
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
        
        $all = $request->all();  

        try{
            $topicNum = $all['topic_num'];
            $nickNameId = $all['nick_name_id'];
            $campNum = isset($all['camp_num']) ? $all['camp_num'] : '';
            $delegatedNickId = $all['delegated_nick_name_id'];
            $fcmToken = $all['fcm_token'];

            // add delegation support
            $result = TopicSupport::addDelegateSupport($request->user(),$topicNum, $campNum, $nickNameId, $delegatedNickId, $fcmToken);
           
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
        $user = $request->user();
        $userId = $user->id;
        $topicNum =$all['topic_num'];
        $campNum = isset($all['camp_num']) && $all['camp_num'] ? $all['camp_num'] : '';
        $removeCamps = isset($all['remove_camps']) && $all['remove_camps'] ? $all['remove_camps'] : [];
        $action = $all['action']; // all OR partial
        $type = isset($all['type']) ? $all['type'] : '';
        $nickNameId = $all['nick_name_id'];
        $fcm_token = $all['fcm_token'];
        $orderUpdate = isset($all['order_update']) ? $all['order_update'] : [];

        try{
           
            TopicSupport::removeDirectSupport($topicNum, $removeCamps, $nickNameId, $action, $type, $orderUpdate);                
            //PushNotification::pushNotificationToSupporter($topicNum, $campNum, $fcm_token, 'remove');
            
            //case 1 removing direct support
            if($type == 'direct'){  
                TopicSupport::removeDirectSupport($topicNum, $removeCamps, $nickNameId, $action, $type, $orderUpdate, $fcm_token);                
                PushNotification::pushNotificationToSupporter($request->user(),$topicNum, $campNum, 'remove');
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.support.complete_support_removed'), '','');
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

        try{
           
            $allNickNames = Nickname::getAllNicknamesByNickId($nickNameId);
            TopicSupport::reorderSupport($orderUpdate, $topicNum, $allNickNames);

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
        $fcmToken = $all['fcm_token'];

        if(!$delegatedNickNameId || !$topicNum || !$nickNameId){
            return $this->resProvider->apiJsonResponse(400, trans('message.support.delegate_invalid_request'), '', $e->getMessage());
        }

        try{
            
            TopicSupport::removeDelegateSupport($topicNum, $nickNameId, $delegatedNickNameId, $fcmToken);               
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
            $nickNames = Nickname::getNicknamesIdsByUserId($userId);


            if(!$topicNum || !$campNum)
            {
                return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '','');
            }

            $support = Support::checkIfSupportExists($topicNum, $nickNames,[$campNum]);
            if($support){

                $data = TopicSupport::checkSupportValidaionAndWarning($topicNum, $campNum, $nickNames);
                $data['support_flag'] = 1;
                $message = trans('message.support.support_exist');

            }else{
                $data = TopicSupport::checkSupportValidaionAndWarning($topicNum, $campNum, $nickNames);

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
            
            $data = Support::getSupportedCampsList($topicNum, $userId);              
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $data,'');
            
        } catch (\Throwable $e) {

            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }

    }

}

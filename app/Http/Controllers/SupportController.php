<?php

namespace App\Http\Controllers;


use App\Models\Nickname;
use Illuminate\Http\Request;
use App\Models\Support;
use App\Models\Camp;
use App\Models\Topic;
use DB;
use App\Http\Request\ValidationRules;
use App\Http\Request\ValidationMessages;
use App\Helpers\ResponseInterface;
use App\Http\Request\Validate;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Helpers\TopicSupport;


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
                        'my_nick_name' => $support->my_nick_name,
                        'my_nick_name_link' => Nickname::getNickNameLink($userId, $support->namespace_id, $support->topic_num, $support->camp_num),
                        'delegated_to_nick_name' => $support->delegated_to_nick_name,
                        'delegated_to_nick_name_link' => Nickname::getNickNameLink($support->delegate_user_id, $support->namespace_id, $support->topic_num,  $support->camp_num),
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

        try{
            $all = $request->all();
            $supports = [];
            if(isset($all['camps'])){
                foreach($all['camps'] as $camp){
                    $data = [
                    'topic_num' => $all['topic_num'],
                    'nick_name_id' => $all['nick_name_id'],
                    'camp_num' => $camp['camp_num'],
                    'support_order' => $camp['support_order'],
                    'start' => time()
                    ];
                    array_push($supports,$data);
                }
                Support::insert($supports);
                
                return $this->resProvider->apiJsonResponse(200, trans('message.support.add_direct_support'), '', '');

            }
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }


    /**
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
            $nicknameId = $all['nick_name_id'];
            $delegatedNickId = $all['delegate_to_user_id'];

            // get all camps being supported by delegatedToUser
            $support = Support::getActiveSupporInTopic($topicNum,$delegatedNickId);

            // add delegation support
            $result = Support::addDelegationSupport($support,$topicNum,$nicknameId,$delegatedNickId);
           
            return $this->resProvider->apiJsonResponse(200, trans('message.support.add_delegation_support'), '','');

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

    public function removeSupport(Request $request)
    {
        $all = $request->all();
        $user = $request->user();
        $userId = $user->id;
        $topicNum =$all['topic_num'];
        //$campNum = isset($all['camp_num']) && $all['camp_num'] ? $all['camp_num'] : '';
        $removeCamps = isset($all['remove_camps']) && $all['remove_camps'] ? $all['remove_camps'] : [];
        $action = $all['action']; // all OR partial
        $type = isset($all['type']) ? $all['type'] : '';
        $nickNameId = $all['nick_name_id'];
        $orderUpdate = isset($all['order_update']) ? $all['order_update'] : [];

        try{
            //case 1 removing direct support
            if($type == 'direct'){  

                TopicSupport::removeDirectSupport($topicNum, $removeCamps, $nickNameId, $action, $type, $orderUpdate);                
                
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
        $delegateNickNameId = $all['delegate_nick_name_id'];

        if(!$delegateNickNameId || !$topicNum || !$nickNameId){
            return $this->resProvider->apiJsonResponse(400, trans('message.support.delegate_invalid_request'), '', $e->getMessage());
        }

        try{
            
            TopicSupport::removeDelegateSupport($topicNum, $nickNameId, $delegateNickNameId);                
            
            return $this->resProvider->apiJsonResponse(200, trans('message.support.delegate_support_removed'), '','');
        } catch (\Throwable $e) {

            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(path="topic-support-list",
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

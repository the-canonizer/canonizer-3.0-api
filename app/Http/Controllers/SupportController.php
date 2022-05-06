<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddNickNameRequest;
use App\Http\Requests\UpdateNickNameRequest;
use App\Models\Nickname;
use Illuminate\Http\Request;
use App\Models\Support;
use App\Models\Camp;
use App\Models\Topic;
use DB;


class SupportController extends Controller
{
    

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


    /**
     * @OA\Post(path="/support/remove",
     * 
     * 
     */

     public function removeSupport(Request $request)
     {
         echo "<pre>"; print_r($request->all()); exit;
     }
}

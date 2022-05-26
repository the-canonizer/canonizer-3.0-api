<?php

namespace App\Helpers;

use Throwable;
use App\Models\Camp;
use App\Models\User;
use App\Models\Topic;
use App\Models\Support;
use App\Models\Nickname;
use Illuminate\Support\Facades\Event;
use App\Events\PromotedDelegatesMailEvent;
use DB;


class TopicSupport
{

    /**
     * Remove Direct Support
     */
    public static function removeDirectSupport($topicNum, $campNum = '', $nickNameId, $action = 'all', $type = 'direct')
    { 
        if((isset($action) && $action == 'all') || $campNum == '')  //abandon entire topic and promote deleagte
        {
            $allNickNames = self::getAllNickNamesOfNickID($nickNameId);

            $getAllActiveSupport = Support::getActiveSupporInTopicWithAllNicknames($topicNum, $allNickNames);
            $campNum = $getAllActiveSupport[0]->camp_num;  // First choice camp number  of topic

            $allDirectDelegates = Support::getActiveDelegators($topicNum, $allNickNames);

            Support::removeSupportWithAllNicknames($topicNum, $campNum, $allNickNames);

            Support::promoteDelegatesToDirect($topicNum, $allNickNames);

            $promotedDelegatesIds = TopicSupport::sendEmailToPromotedDelegates($topicNum, $campNum, $nickNameId, $allDirectDelegates);

            //TopicSupport::sendEmailToSupportersAndSubscribers($topicNum, $campNum, $nickNameId, $promotedDelegates);
            return;
            
        }


    }


    /**
     * Send email to promoted delegates as a direct supporter of topic and camps 
     * @param integer $topicNum
     * @param integer $camNum
     * @param integer $nickNameId [Nick id of user removing support]
     * @return array $promotedDelegates [array of promoted delegates Ids]
     */
    public static function sendEmailToPromotedDelegates($topicNum, $campNum, $nickNameId, $allDirectDelegates)
    {
        $promotedDelegatesIds = [];
        $to = [];
        $topicFilter = ['topicNum' => $topicNum];
        $campFilter = ['topicNum' => $topicNum, 'campNum' => $campNum];

        $topic = Camp::getAgreementTopic($topicFilter);
        $camp  = self::getLiveCamp($campFilter);
        $promotedFrom = Nickname::getNickName($nickNameId);
        $topicLink =  self::getTopicLink($topic);
        $campLink = self::getCampLink($topic,$camp);
        $seoUrlPortion = Util::getSeoBasedUrlPortion($topicNum,$campNum,$topic,$camp);
        $subject = "You have been promoted as direct supporter";

        $data['topic_num'] = $topicNum;
        $data['camp_num'] = $campNum;
        $data['promotedFrom'] = $promotedFrom;
        $data['topic'] = $topic;
        $data['camp'] = $camp;
        $data['subject'] = $subject;
        $data['topic_link'] = $topicLink;
        $data['camp_link'] = $campLink;   
        $data['url_portion'] =  $seoUrlPortion;

        foreach($allDirectDelegates as $promoted)
        {
            $promotedUser = Nickname::getUserByNickName($promoted->nick_name_id);
            $user_id = $promotedUser->id ?? null;
            $promotedDelegates[] = $promotedUser->user_id;
            $to[] = $promotedUser->email;

        }

        try 
        {
            Event::dispatch(new PromotedDelegatesMailEvent($to, $promotedFrom, $data));
            //return $promotedDelegates;

        } catch (Throwable $e) 
        {
            $data = null;
            $status = 403;
            echo  $message = $e->getMessage();
        }


    }


    /**
    * [Get All nickanems of a user]
    * @param integer $nickId
    * @return array $nicknames  
    */
    public static function getAllNickNamesOfNickID($nickId)
    {
        return Nickname::getAllNicknamesByNickId($nickId);
    }

    /**
     * [get topic link]
     * @param object $topic is live  topic - agreement camp 
     * @return string $link is link of topic
     */
    public static function getTopicLink($topic)
    {
        return  Topic::topicLink($topic->topic_num, 1, $topic->title);
    }


    /** 
    * [get camp link]
    * @param object $camp is live camp object
    * @return string $link is link of live camp
    */
    public static function getCampLink($topic, $camp)
    {
        return  Topic::topicLink($topic->topic_num, $camp->camp_num, $topic->title, $camp->camp_name);
    }  
    
    /**
     * [getLiveCamp description]
     * @param array $filter [contains topicNum, campNum]
     * @return object  [camp]
     */
    public static function getLiveCamp($filter)
    {
        return Camp::getLiveCamp($filter);
    }

    /**
     * [getAllSupportedCampsByUser] 
     *  This function will return all supported camps group by nickname ID
     * @param $id is user id
     * @return array of all supporte camps group by nickname id
     *   
     */
    public static function getAllSupportedCampsByUserId($userId)
    {
        $response = DB::select("CALL user_support('delegate', $userId)");
        $supportedCamps = [];
        $supportedCamps = self::groupCampsByNickId($response, $userId, $supportedCamps);
       

        $response = DB::select("CALL user_support('direct', $userId)");
        $supportedCamps = self::groupCampsByNickId($response, $userId, $supportedCamps);

       return $supportedCamps;
        
    }


    public static function groupCampsByNickId($response, $userId, $directSupports = [])
    {
       
        foreach($response as $k => $support){
            if(isset($directSupports[$support->nick_name_id])){

                if(isset($directSupports[$support->nick_name_id]['topic'][$support->topic_num])){
                    $tempCamp = [
                        'camp_num' => $support->camp_num,
                        'camp_name' => $support->camp_name,
                        'support_order'=> $support->support_order,
                        'camp_link' => Camp::campLink($support->topic_num,$support->camp_num,$support->title,$support->camp_name),                        
                        'support_added' => date('Y-m-d',$support->start)
                    ];
                    array_push($directSupports[$support->nick_name_id]['topic'][$support->topic_num]['camps'],$tempCamp);

                }else{
                    $directSupports[$support->nick_name_id]['topic'][$support->topic_num] = array(
                        'topic_num' => $support->topic_num,
                        'title' => $support->title,
                        'title_link' => Topic::topicLink($support->topic_num,1,$support->title),
                        'my_nick_name' => isset($support->my_nick_name) ? $support->my_nick_name : '',
                        'my_nick_name_link' => Nickname::getNickNameLink($userId, $support->namespace_id, $support->topic_num, $support->camp_num),
                        'delegated_to_nick_name' => isset($support->delegated_to_nick_name) ? $support->delegated_to_nick_name : '' ,
                        'delegated_to_nick_name_link' => isset($support->delegate_user_id) ? Nickname::getNickNameLink($support->delegate_user_id, $support->namespace_id, $support->topic_num,  $support->camp_num) : '',
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
            }else{
                $directSupports[$support->nick_name_id] = array(
                    'nick_name_id' => $support->nick_name_id,
                    'nick_name' => isset($support->my_nick_name) ? $support->my_nick_name : '', 
                    'topic' =>array(
                        $support->topic_num => array(
                            'topic_num' => $support->topic_num,
                            'title' => $support->title,
                            'title_link' => Topic::topicLink($support->topic_num,1,$support->title),
                            'my_nick_name' => isset($support->my_nick_name) ? $support->my_nick_name : '' ,
                            'my_nick_name_link' => Nickname::getNickNameLink($userId, $support->namespace_id, $support->topic_num, $support->camp_num),
                            'delegated_to_nick_name' => isset($support->delegated_to_nick_name) ? $support->delegated_to_nick_name : '',
                            'delegated_to_nick_name_link' => isset($support->delegate_user_id) ? Nickname::getNickNameLink($support->delegate_user_id, $support->namespace_id, $support->topic_num,  $support->camp_num) : '',
                            'camps' => array(
                                    [
                                        'camp_num' => $support->camp_num,
                                        'camp_name' => $support->camp_name,
                                        'support_order' => $support->support_order,
                                        'camp_link' =>  Camp::campLink($support->topic_num,$support->camp_num,$support->title,$support->camp_name),                                   
                                        'support_added' => date('Y-m-d',$support->start)

                                    ]
                            ),
                        )
                    )
                );

            }            
        }
        return $directSupports;
    }


    public static function groupCampsForNickId($results, $nickname)
    {
        $supports[$nickname->id] = [];
        $supports[$nickname->id]['nick_name_id'] = $nickname->id;
        $supports[$nickname->id]['nick_name'] = $nickname->nick_name;

        
        foreach ($results as $rs) {
            $topic_num = $rs->topic_num;
            $camp_num = $rs->camp_num;
            $filter['topicNum'] = $topic_num;
            $filter['asOf'] = '';
            $filter['campNum'] =  $camp_num;
            $livecamp = Camp::getLiveCamp($filter);
            $topicLive = Topic::getLiveTopic($topic_num,['nofilter'=>true]);
            $title = preg_replace('/[^A-Za-z0-9\-]/', '-', ($livecamp->title != '') ? $livecamp->title : $livecamp->camp_name);
            $topic_id = $topic_num . "-" . $title;
            $url = Util::getTopicCampUrl($topicLive->topic_num, 1, $topicLive, $livecamp, time());
            
            if ($rs->delegate_nick_name_id && $camp_num != 1 ) {

                $tempCamp = [
                            'camp_name' => $livecamp->camp_name, 
                            'camp_num' => $camp_num, 
                            'support_order' => $rs->support_order,
                            'camp_link' =>  Camp::campLink($rs->topic_num,$rs->camp_num,$rs->title,$rs->camp_name),
                            'delegate_nick_name_id' => $rs->delegate_nick_name_id
                        ];
                
                if(isset($supports[$nickname->id]['topic'][$topic_num]['camps'])){
                    array_push($supports[$nickname->id]['topic'][$topic_num]['camps'],$tempCamp);
                }else{
                    $supports[$nickname->id]['topic'][$topic_num]['camps'][] = $tempCamp;
                }

            } else if ($camp_num == 1) {

                if($rs->title ==''){
                    $topicData = Topic::where('topic_num','=',$topic_num)->where('go_live_time', '<=', time())->latest('submit_time')->get();
                    $liveTopic = Topic::getLiveTopic($topic_num,['nofilter'=>true]);
                    $title = preg_replace('/[^A-Za-z0-9\-]/', '-', $liveTopic->topic_name);
                    $topic_id = $topic_num . "-" . $title;
                }

                //$supports[$nickname->id]['topic'][$topic_num]['camp_name'] = ($rs->camp_name != "") ? $livecamp->camp_name : $livecamp->title;
                $supports[$nickname->id]['topic'][$topic_num]['topic_num'] = $topic_num;
                $supports[$nickname->id]['topic'][$topic_num]['title_link'] = Topic::topicLink($topic_num, 1, $title);
                $supports[$nickname->id]['topic'][$topic_num]['title'] = $title;
                $supports[$nickname->id]['topic'][$topic_num]['camp_name'] = ($rs->camp_name != "") ? $livecamp->camp_name : $livecamp->title;

                if($rs->delegate_nick_name_id){
                    $supports[$nickname->id]['topic'][$topic_num]['delegate_nick_name_id'] = $rs->delegate_nick_name_id;
                }
                
            } else {

                $tempCamp = [
                    'camp_name' => $livecamp->camp_name, 
                    'camp_num' => $camp_num, 
                    'support_order' => $rs->support_order,
                    'camp_link' =>  Camp::campLink($rs->topic_num,$rs->camp_num,$rs->title,$rs->camp_name),
                    'delegate_nick_name_id'=>$rs->delegate_nick_name_id
                ];

                if(isset($supports[$nickname->id]['topic'][$topic_num]['camps'])){
                    array_push($supports[$nickname->id]['topic'][$topic_num]['camps'],$tempCamp);
                }else{
                    $supports[$nickname->id]['topic'][$topic_num]['camps'][] = $tempCamp;
                }
            }
        }

        return $supports;
    }

    
}

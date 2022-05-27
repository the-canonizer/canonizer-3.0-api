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


    public  static function removeDirectSupport($topicNum, $removeCamps = array(), $nickNameId, $action = 'all', $type = 'direct', $orderUpdate = array())
    {
        if(isset($action) && $action == 'all')
        {
            return self::removeCompleteSupport($topicNum, $removeCamps , $nickNameId, $action , $type);
        
        }else if(isset($action) && $action == 'partial')
        {
            return self::removePartialSupport($topicNum, $removeCamps , $nickNameId, $action,  $type, $orderUpdate);
        }
    }

    /**
     * Remove Direct Support
     */
    public static function removeCompleteSupport($topicNum, $removeCamps = array(), $nickNameId, $action = 'all', $type = 'direct')
    { 

        if((isset($action) && $action == 'all') || $campNum == '')  //abandon entire topic and promote deleagte
        {
            $allNickNames = self::getAllNickNamesOfNickID($nickNameId);

            $getAllActiveSupport = Support::getActiveSupporInTopicWithAllNicknames($topicNum, $allNickNames);
            
            if($getAllActiveSupport->count() == 0){ 
                return;
            }

            $campNum = isset($getAllActiveSupport[0]->camp_num) ?  $getAllActiveSupport[0]->camp_num : '';  // First choice camp number  of topic

            $allDirectDelegates = Support::getActiveDelegators($topicNum, $allNickNames);
           
            Support::removeSupportWithAllNicknames($topicNum, '', $allNickNames);
            
            Support::promoteDelegatesToDirect($topicNum, $allNickNames);

            $promotedDelegatesIds = TopicSupport::sendEmailToPromotedDelegates($topicNum, $campNum, $nickNameId, $allDirectDelegates);

            //TopicSupport::sendEmailToSupportersAndSubscribers($topicNum, $campNum, $nickNameId, $promotedDelegates);
            return;
            
        }
    }

    /**
     * [Remomve Partial support that is from one or more camps]
     * remove support from camps listed in @param $camNum
     * Also remove support from those camps for delegated supporter
     * 
     */
    public static function removePartialSupport($topicNum, $removeCamps = array(), $nickNameId, $action = 'all', $type = 'direct', $orderUpdate = array())
    {
        $allNickNames = self::getAllNickNamesOfNickID($nickNameId);
       // $campArray = explode(',', trim($campNum));

        if(!empty($removeCamps)){

            self::removeSupport($topicNum,$removeCamps,$allNickNames);

            $nicknameModel = Nickname::getNickName($nickNameId);
            $topicFilter = ['topicNum' => $topicNum];
            $topicModel = Camp::getAgreementTopic($topicFilter);
    
            foreach($removeCamps as $camp)
            {
    
                $campFilter = ['topicNum' => $topicNum, 'campNum' => $camp];
                $campModel  = self::getLiveCamp($campFilter);
                //self::supportRemovalEmail($topicModel, $campModel, $nicknameModel);
            }
        }

        if(isset($orderUpdate) && !empty($orderUpdate)){
            self::reorderSupport($orderUpdate, $topicNum, $allNickNames);
        }

        return;
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

    

    /**
     * [Recursive function ro remove delegate support]
     * @param $topicNum is topic_num
     * @param $campNum is array of camp_num 
     * @param $allNicknames is array all delegated__nick_id (s) of user to whome support is delagted  
     */
    public static function removeSupport($topicNum, $campNum = array(), $allNickNames = array())
    {
        $delegators = Support::getActiveDelegators($topicNum, $allNickNames);
        Support::removeSupportWithAllNicknames($topicNum, $campNum, $allNickNames);

        if(isset($delegators) && count($delegators) > 0)
        {
            foreach($delegators as $delegator)
            {
                $delegatorsNickArray = self::getAllNickNamesOfNickID($delegator->nick_name_id);
                return self::removeSupport($topicNum, $campNum, $delegatorsNickArray);
            }
        }

        return;
    }

    /**
     * [Re-order support]
     */
    public static function reorderSupport($orders, $topicNum, $allNickNames)
    {

        try{
            DB::beginTransaction();
                // do all your updates here
                foreach($orders as $order)
                {
                    
                    DB::table('support')
                    ->where('topic_num', '=', $topicNum)
                    ->where('camp_num', '=', $order['camp_num'])
                    ->whereIn('nick_name_id', $allNickNames)
                    ->update(['support_order' => $order['order']  // update your field(s) here
                        ]);
                }
            DB::commit();

            //get delegates and re-order
            $delegators = Support::getActiveDelegators($topicNum, $allNickNames);
            if(isset($delegators) && count($delegators) > 0)
            {
                foreach($delegators as $delegator)
                {
                    $delegatorsNickArray = self::getAllNickNamesOfNickID($delegator->nick_name_id);
                    return self::reorderSupport($orders, $topicNum, $delegatorsNickArray);
                }
            }

        }catch(Exception $e){
            DB::rollback();
        }

        return;
    }

    /**
     * @param $topic is object of topic model
     * @param $camp is object of camp model
     * @param $nnickname is object of nickname model
     */
    public static function supportRemovalEmail($topic, $camp, $nickname)
    {

        $object = $topic->topic_name ." / ".$camp->camp_name;
        $topicLink =  self::getTopicLink($topic);
        $campLink = self::getCampLink($topic,$camp);
        $seoUrlPortion = Util::getSeoBasedUrlPortion($topic->topic_num, $camp->camp_num, $topic, $camp);

        $mailData['object'] = $object;
        $mailData['subject'] = $nickname->nick_name . " has removed their support from ".$object. ".";
        $mailData['topic'] = $topic;
        $mailData['camp'] = $camp;
        $mailDta['camp_name'] = $camp->camp_name;
        $mailData['topic_name'] = $topic->topic_name;
        $mailData['topic_num'] = $topic->topic_num;
        $mailData['camp_num'] = $camp->camp_num;
        $mailData['topic_link'] = $topicLink;
        $mailData['camp_link'] = $campLink;   
        $mailData['url_portion'] =  $seoUrlPortion;
        $mailData['nick_name_id'] = $nickname->id;
        $mailData['nick_name'] = $nickname->nick_name;
        $mailData['support_action'] = "deleted"; //default will be 'added'


        self::SendEmailToSubscribersAndSupporters($mailData);
        return;
    }


    /**
     * Send email to promoted delegates as a direct supporter of topic and camps 
     * @param array $data [is mail data]
     * @return void
     */
    public static function SendEmailToSubscribersAndSupporters($data)
    {
        
        $bcc_email = [];
        $subscriber_bcc_email = [];
        $bcc_user = [];
        $sub_bcc_user = [];
        $userExist = [];
        $topicNum = $data['topic_num'];
        $campNum = $data['camp_num'] ; 
        $topic = $data['topic'];  
        $data['namespace_id'] = isset($topic->namespace_id) ? $topic->namespace_id : 1;
         
        
        $directSupporter = Support::getDirectSupporter($topicNum, $campNum);
        $subscribers = Camp::getCampSubscribers($campNum, $campNum);
        $i = 0;
        foreach ($directSupporter as $supporter) {
            $user = Nickname::getUserByNickName($supporter->nick_name_id);
            $user_id = $user->id ?? null;
            $nickName = Nickname::find($supporter->nick_name_id);
            $supported_camp = $nickName->getSupportCampList($topic_name_space_id, ['nofilter' => true]);
            $supported_camp_list = $nickName->getSupportCampListNamesEmail($supported_camp, $topicNum, $campNum);
            $support_list[$user_id] = $supported_camp_list;
            $ifalsoSubscriber = Camp::checkifSubscriber($subscribers, $user);
            if ($ifalsoSubscriber) {
                $support_list_data = Camp::getSubscriptionList($user_id, $topicNum, $campNum);
                $supporter_and_subscriber[$user_id] = ['also_subscriber' => 1, 'sub_support_list' => $support_list_data];
            }
            $bcc_user[] = $user;
            $userExist[] = $user_id;
        }
        if ($subscribers && count($subscribers) > 0) {
            foreach ($subscribers as $sub) {
                if (!in_array($sub, $userExist, true)) {
                    $userSub = User::find($sub);
                    $subscriptions_list = Camp::getSubscriptionList($userSub->id, $topicNum, $campNum);
                    $subscribe_list[$userSub->id] = $subscriptions_list;
                    $sub_bcc_user[] = $userSub;
                }
            }
        }
        $filtered_bcc_user = array_unique($bcc_user);
        $filtered_sub_user = array_unique(array_filter($sub_bcc_user, function ($e) use ($userExist) {
            return !in_array($e->id, $userExist);
        }));

        if (isset($filtered_bcc_user) && count($filtered_bcc_user) > 0) {

            foreach ($filtered_bcc_user as $user) {
                $data['support_list'] = $support_list[$user_id];
                if (isset($supporter_and_subscriber[$user_id]) && isset($supporter_and_subscriber[$user_id]['also_subscriber']) && $supporter_and_subscriber[$user_id]['also_subscriber']) {
                    $data['also_subscriber'] = $supporter_and_subscriber[$user_id]['also_subscriber'];
                    $data['sub_support_list'] = $supporter_and_subscriber[$user_id]['sub_support_list'];
                }
                try {
                    if($data['support_action'] == 'deleted'){
                        Event::dispatch(new SupportRemovedMailEvent($user->email ?? null, $user, $data));
                    }
                    
                } catch (Throwable $e) {
                    $data = null;
                    $status = 403;
                    echo  $message = $e->getMessage();
                }
            }
        }

        if (isset($filtered_sub_user) && count($filtered_sub_user) > 0) {
            $data['subscriber'] = 1;
            foreach ($filtered_sub_user as $userSub) {
                $data['support_list'] = $subscribe_list[$userSub->id];
                try {
                    if($data['support_action'] == 'deleted'){
                        Event::dispatch(new SupportRemovedMailEvent($userSub->email ?? null, $userSub, $data));
                    }
                } catch (Throwable $e) {
                    $data = null;
                    $status = 403;
                    echo $message = $e->getMessage();
                }
            }
        }
        return;
    }

    
}

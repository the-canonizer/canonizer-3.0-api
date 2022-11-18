<?php

namespace App\Helpers;

use DB;
use Throwable;
use App\Models\Camp;
use App\Models\User;
use App\Models\Topic;
use App\Models\Support;
use App\Models\Nickname;
use App\Jobs\ActivityLoggerJob;
use App\Events\SupportAddedMailEvent;
use Illuminate\Support\Facades\Event;
use App\Events\SupportRemovedMailEvent;
use App\Events\PromotedDelegatesMailEvent;
use App\Facades\GetPushNotificationToSupporter;
use App\Events\NotifyDelegatedAndDelegatorMailEvent;
use App\Facades\Util;


class TopicSupport
{

   

    public static $model = 'App\Models\Support';


    public  static function removeDirectSupport($topicNum, $removeCamps = array(), $nickNameId, $action = 'all', $type = 'direct', $orderUpdate = array(), $user)
    {
        if(isset($action) && $action == 'all')
        {
            return self::removeCompleteSupport($topicNum, $removeCamps , $nickNameId, $action , $type);
        
        }else if(isset($action) && $action == 'partial')
        {
            return self::removePartialSupport($topicNum, $removeCamps , $nickNameId, $action,  $type, $orderUpdate, $user);
        }
    }

    /**
     * [remove delegate support]
     * @param integer $topicNum is topic number
     * @param integer $nickId is nick_name_id of user removeing delegation support
     * @param integer $delegateNickId is nick_name_id of user from whome delegation is removed
     * 
     */
    public static function removeDelegateSupport($topicNum, $nickNameId, $delegateNickNameId)
    {
        
        self::removeCompleteSupport($topicNum,'',$nickNameId, 'all', 'delegate', $delegateNickNameId); 
        return;
    }


    /**
     * [Remove Direct Support completely]
     * [And promote direct delegates to direct supporter with notification]
     * @param integer $topicNum is topic number
     * @param array $removeCamps are list of camps to be removed
     * @param integer $nickNameId is nick_name_id of removing user
     * @param string $action defines remove status [all|partial]
     * @param string $type defines support type [direct|delegate]
     */
    public static function removeCompleteSupport($topicNum, $removeCamps = array(), $nickNameId, $action = 'all', $type = 'direct', $delegateNickNameId = '')
    { 
        
        if(isset($action) && $action == 'all')  //abandon entire topic and promote deleagte
        {
            $allNickNames = self::getAllNickNamesOfNickID($nickNameId);

            $getAllActiveSupport = Support::getActiveSupporInTopicWithAllNicknames($topicNum, $allNickNames);
            
            if($getAllActiveSupport->count() == 0){ 
                return;
            }

            $campNum = isset($getAllActiveSupport[0]->camp_num) ?  $getAllActiveSupport[0]->camp_num : '';  // First choice camp number  of topic
            $allDirectDelegates = Support::getActiveDelegators($topicNum, $allNickNames);
            
            Support::removeSupportWithAllNicknames($topicNum, '', $allNickNames);

            //semd Email
            $nicknameModel = Nickname::getNickName($nickNameId);
            $topicFilter = ['topicNum' => $topicNum];
            $topicModel = Camp::getAgreementTopic($topicFilter);
            $campFilter = ['topicNum' => $topicNum, 'campNum' => $campNum];
            $campModel  = self::getLiveCamp($campFilter);
            self::supportRemovalEmail($topicModel, $campModel, $nicknameModel,$delegateNickNameId);

            if(isset($allDirectDelegates) && count($allDirectDelegates) > 0)
            {
                Support::promoteUpDelegates($topicNum, $allNickNames, $delegateNickNameId);
                $promotedDelegatesIds = TopicSupport::sendEmailToPromotedDelegates($topicNum, $campNum, $nickNameId, $allDirectDelegates, $delegateNickNameId);

                //push notification to promoted delegates
                self::sendNotification($topicNum, $campNum, $nickNameId, $allDirectDelegates, $delegateNickNameId);

            }

            /* To update the Mongo Tree while deleting delegate support */
            $topic = Topic::where('topic_num', $topicNum)->orderBy('id','DESC')->first();
            if(!empty($campNum)) {
                Util::dispatchJob($topic, $campNum, 1);
            } else {
                Util::dispatchJob($topic, 1, 1);
            }

            //log remove support activity
            self::logActivityForRemoveCamps($removeCamps, $topicNum, $nickNameId, $delegateNickNameId);
            return;
            
        }
    }

    /**
     * [Remomve Partial support that is from one or more camps]
     * remove support from camps listed in @param $camNum
     * Also remove support from those camps for delegated supporter
     * 
     */
    public static function removePartialSupport($topicNum, $removeCamps = array(), $nickNameId, $action = 'all', $type = 'direct', $orderUpdate = array(), $user)
    {
        $allNickNames = self::getAllNickNamesOfNickID($nickNameId);

        if(!empty($removeCamps)){

            self::removeSupport($topicNum,$removeCamps,$allNickNames);

            $nicknameModel = Nickname::getNickName($nickNameId);
            $nickName = '';
            if (!empty($nicknameModel)) {
                $nickName = $nicknameModel->nick_name;
            }
            $topicFilter = ['topicNum' => $topicNum];
            $topicModel = Camp::getAgreementTopic($topicFilter);
    
            foreach($removeCamps as $camp)
            {
                $campFilter = ['topicNum' => $topicNum, 'campNum' => $camp];
                $campModel  = self::getLiveCamp($campFilter);
                self::supportRemovalEmail($topicModel, $campModel, $nicknameModel); 

                /* To update the Mongo Tree while removing support */
                /* Execute job here only when this is topicnumber == 81 (because we using dynamic camp_num for 81) */
                $topic = Topic::where('topic_num', $topicNum)->orderBy('id','DESC')->first();
                if($topicNum == config('global.mind_expert_topic_num')) {
                    Util::dispatchJob($topic, $camp, 1);
                }
                
                GetPushNotificationToSupporter::pushNotificationToSupporter($user, $topicNum, $camp, 'remove', null, $nickName);
            }

             //log activity
             self::logActivityForRemoveCamps($removeCamps, $topicNum, $nickNameId);
        }

        if(isset($orderUpdate) && !empty($orderUpdate)){
            self::reorderSupport($orderUpdate, $topicNum, $allNickNames);
        }

        return;
    }

    /**
     * [Add direct Support]
     * @param array $camps list of associative camps with order to which support is to be added
     * @param string $topicNum topic number of topic
     * @param integer $nickNameId nick name id of user
     * @param array $removedCamps  list of camps to be removed if any
     * @param array $orderUpdate is associative array of camps with order numbr to be updated, if any
     */
    public static function addDirectSupport($topicNum, $nickNameId, $addCamp, $user, $removeCamps = array(), $orderUpdate = array())
    {
        $allNickNames = self::getAllNickNamesOfNickID($nickNameId);
        // $campArray = explode(',', trim($campNum));

        /* To update the Mongo Tree while adding support */
        $topic = Topic::where('topic_num', $topicNum)->orderBy('id','DESC')->first();
        $allDelegates =  self::getAllDelegates($topicNum, $nickNameId);

        $nicknameModel = Nickname::getNickName($nickNameId);

        $nickName = '';
        if (!empty($nicknameModel)) {
            $nickName = $nicknameModel->nick_name;
        }

         if(!empty($removeCamps)){

             // before removing get delegation support
             self::removeSupport($topicNum,$removeCamps,$allNickNames);
             Support::reOrderSupport($topicNum, $allNickNames); //after removal reorder support

             $topicFilter = ['topicNum' => $topicNum];
             $topicModel = Camp::getAgreementTopic($topicFilter);
     
             foreach($removeCamps as $camp) {     
                 $campFilter = ['topicNum' => $topicNum, 'campNum' => $camp];
                 $campModel  = self::getLiveCamp($campFilter);

                /* To update the Mongo Tree while removing at add support */
                /* Execute job here only when this is topicnumber == 81 (because we using dynamic camp_num for 81) */
                if($topicNum == config('global.mind_expert_topic_num')) {
                    Util::dispatchJob($topic, $camp, 1);
                }

                 self::supportRemovalEmail($topicModel, $campModel, $nicknameModel);
                 GetPushNotificationToSupporter::pushNotificationToSupporter($user,$topicNum, $camp, 'remove', null, $nickName);
             }

             //log activity
             self::logActivityForRemoveCamps($removeCamps, $topicNum, $nickNameId);


         }
 
        if(isset($orderUpdate) && !empty($orderUpdate)){
             self::reorderSupport($orderUpdate, $topicNum, $allNickNames);
         }

         $supportToAdd = [];
         if(isset($addCamp) && !empty($addCamp)){
            $campNum = $addCamp['camp_num'];
            $supportOrder = $addCamp['support_order'];

            $campFilter = ['topicNum' => $topicNum, 'campNum' => $campNum];
            $campModel  = self::getLiveCamp($campFilter);

            $support = self::addSupport($topicNum, $campNum, $supportOrder, $nickNameId);
            array_push($supportToAdd, $support);
            if(count($allDelegates)) { 
                self::insertDelegateSupport($allDelegates, $supportToAdd);
            }
            
             
           $subjectStatement = "has added their support to"; 
           self::SendEmailToSubscribersAndSupporters($topicNum, $campNum, $nickNameId, $subjectStatement, 'add');
           GetPushNotificationToSupporter::pushNotificationToSupporter($user,$topicNum, $campNum, 'add', null, $nickName);
           //log activity
           self::logActivityForAddSupport($topicNum, $campNum, $nickNameId);
           
           /* To update the Mongo Tree while adding support */
           Util::dispatchJob($topic, $campNum, 1);
         }

        /* To update the Mongo Tree while adding support */
        /* Execute job here only when topicnumber != 81 (because there are multiple camps here) */
        if($topicNum != config('global.mind_expert_topic_num')) {
            Util::dispatchJob($topic, 1, 1);
        }
    }


    /**
     * [Add deleagte support]
     * 
     */
    public static function addDelegateSupport($user,$topicNum, $campNum, $nickNameId, $delegateNickNameId)
    { 
        $delegatToNickNames = self::getAllNickNamesOfNickID($delegateNickNameId);
        $allNickNames = self::getAllNickNamesOfNickID($nickNameId);
        $supportToAdd = Support::getActiveSupporInTopicWithAllNicknames($topicNum, $delegatToNickNames);
        $delegatorPrevSupport = Support::getActiveSupporInTopicWithAllNicknames($topicNum, $allNickNames);
        $campNum = $supportToAdd[0]->camp_num;   // first choice
        $notifyDelegatedUser = false;        

        if(count($delegatorPrevSupport)){
            $allDelegates =  self::getAllDelegates($topicNum, $nickNameId);
            self::removeSupport($topicNum, [], $allNickNames);  
        }

        $delegateSupporters = array(
                ['nick_name_id' => $nickNameId, 'delegate_nick_name_id' => $delegateNickNameId]
            );
        if(isset($allDelegates) && $allDelegates){
            $delegateSupporters = array_merge($delegateSupporters, $allDelegates);
        } 
        
        $nickName = '';
        $nicknameModel = Nickname::getNickName($nickNameId);
        if (!empty($nicknameModel)) {
            $nickName = $nicknameModel->nick_name;
        }

        self::insertDelegateSupport($delegateSupporters, $supportToAdd);  
       
        $subjectStatement = "has just delegated their support to";
        self::SendEmailToSubscribersAndSupporters($topicNum, $campNum, $nickNameId, $subjectStatement, 'add', $delegateNickNameId);
        GetPushNotificationToSupporter::pushNotificationToSupporter($user,$topicNum, $campNum, 'add-delegate', null, $nickName);

       if($supportToAdd[0]->delegate_nick_name_id)  // if  delegated user is a delegated supporter itself, then notify
        {
            $notifyDelegatedUser = true;
        }

        self::notifyDelegatorAndDelegateduser($topicNum, $campNum, $nickNameId, 'add', $delegateNickNameId, $notifyDelegatedUser);

        // log activity
        self::logActivityForAddSupport($topicNum, $campNum, $nickNameId, $delegateNickNameId);       
        

        /* To update the Mongo Tree while delegating at add support*/
        $topic = Topic::where('topic_num', $topicNum)->orderBy('id','DESC')->first();
        if(!empty($campNum)) {
            Util::dispatchJob($topic, $campNum, 1);
        } else {
            Util::dispatchJob($topic, 1, 1);
        }
        
        
    }


    /** 
     * 
     * @return void
     */
    public static function insertDelegateSupport($delegates = [], $supportToAdd)
    {
        $insert = [];

        foreach($delegates as $supporter)
        {
            $temp = [];
            foreach($supportToAdd as $sp)
            {
                $temp = [
                    'topic_num' => $sp->topic_num,
                    'camp_num' => $sp->camp_num,
                    'support_order' => $sp->support_order,
                    'nick_name_id' => $supporter['nick_name_id'],
                    'delegate_nick_name_id' => $supporter['delegate_nick_name_id'],
                    'start' => time()
                ];

                array_push($insert, $temp);
            }
        }
        Support::insert($insert);
        return true;
    }


    /** 
     * Send email to promoted delegates as a direct supporter of topic and camps 
     * @param integer $topicNum
     * @param integer $camNum
     * @param integer $nickNameId [Nick id of user removing support]
     * @return array $promotedDelegates [array of promoted delegates Ids]
     */
    public static function sendEmailToPromotedDelegates($topicNum, $campNum, $nickNameId, $allDirectDelegates, $delegateNickNameId='')
    {
        $promotedDelegatesIds = [];
        $topicFilter = ['topicNum' => $topicNum];
        $campFilter = ['topicNum' => $topicNum, 'campNum' => $campNum];

        $topic = Camp::getAgreementTopic($topicFilter);
        $camp  = self::getLiveCamp($campFilter);
        $promotedFrom = Nickname::getNickName($nickNameId);       
        $topicLink =  self::getTopicLink($topic);
        $campLink = self::getCampLink($topic,$camp);
        $seoUrlPortion = Util::getSeoBasedUrlPortion($topicNum,$campNum,$topic,$camp);
        if(isset($delegateNickNameId) && $delegateNickNameId != ''){

            $promotedTo = Nickname::getNickName($delegateNickNameId);        
            $subject = "Your delegation support has been promoted up.";

        }else{

            $subject = "You have been promoted as direct supporter.";
        }
        
        $object = $topic->topic_name ." / ".$camp->camp_name;
        $data['namespace_id'] = isset($topic->namespace_id) ? $topic->namespace_id : 1;
        $data['topic_num'] = $topicNum;
        $data['camp_num'] = $campNum;
        $data['promotedFrom'] = $promotedFrom;
        $data['topic'] = $topic;
        $data['camp'] = $camp;
        $data['subject'] = $subject;
        $data['topic_link'] = $topicLink;
        $data['camp_link'] = $campLink;   
        $data['url_portion'] =  $seoUrlPortion;
        $data['delegate_nick_name_id'] =  $delegateNickNameId;
        $data['delegated_nick_name_link'] = Nickname::getNickNameLink($data['delegate_nick_name_id'], $data['namespace_id'], $data['topic_num'], $data['camp_num']);
        $data['promotedTo'] = isset($promotedTo) ? $promotedTo : [];
        $data['topic_name'] = $topic->topic_name;
        $data['camp_name'] = $camp->camp_name;
        $data['nick_name_id'] = $promotedFrom->id;
        $data['nick_name'] = $promotedFrom->nick_name;
        $data['support_action'] = "deleted"; //default will be 'added'        
        $data['object'] = $object;       
        $data['nick_name_link'] = Nickname::getNickNameLink($data['nick_name_id'], $data['namespace_id'], $data['topic_num'], $data['camp_num']);
        

        foreach($allDirectDelegates as $promoted)
        {
            $promotedUser = Nickname::getUserByNickName($promoted->nick_name_id);
            $user_id = $promotedUser->id ?? null;
            $promotedDelegates[] = $promotedUser->user_id;
            $to = $promotedUser->email;

            try 
            {
                Event::dispatch(new PromotedDelegatesMailEvent($to, $promotedFrom, $data)); 

                //$data['subject'] = $promotedFrom->nick_name . " has removed their support from ".$object. ".";           
                
                //$subjectStatement = "has removed their support from";
                //self::SendEmailToSubscribersAndSupporters($topicNum, $campNum, $nickNameId, $subjectStatement, 'remove');

            } catch (Throwable $e) 
            {
                $data = null;
                $status = 403;
                echo  $message = $e->getMessage();
            }
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

    /*
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


    public static function groupCampsForNickId($results, $nickname,$namespaceId = 1)
    {
        $supports[$nickname->id] = [];
        $supports[$nickname->id]['nick_name_id'] = $nickname->id;
        $supports[$nickname->id]['nick_name'] = $nickname->nick_name;
        $supports[$nickname->id]['private_status'] = $nickname->private;
        
        foreach ($results as $rs) {
            $topic_num = $rs->topic_num;
            $camp_num = $rs->camp_num;
            $filter['topicNum'] = $topic_num;
            $filter['asOf'] = '';
            $filter['campNum'] =  $camp_num;
            $livecamp = Camp::getLiveCamp($filter);
            $liveTopic = Topic::getLiveTopic($topic_num,['nofilter'=>true]);
            $delegatedToNickname = '';
            if(isset($namespaceId) && $namespaceId != $liveTopic->namespace_id){
                continue;
            }
            //$topicLive = Topic::getLiveTopic($topic_num,['nofilter'=>true]);
           // $title = preg_replace('/[^A-Za-z0-9\-]/', '-', ($topicLive->title != '') ? $livecamp->title : $livecamp->camp_name);
            $title = preg_replace('/[^A-Za-z0-9\-]/', '-', $liveTopic->topic_name); 
            $topic_id = $topic_num . "-" . $title;
            $url = Util::getTopicCampUrl($liveTopic->topic_num, 1, $liveTopic, $livecamp, time());
            
            if(isset($rs->delegate_nick_name_id) && $rs->delegate_nick_name_id){
                $dnModel = Nickname::getNickName($rs->delegate_nick_name_id);
                $delegatedToNickname = $dnModel->nick_name;
            }

            if ($rs->delegate_nick_name_id && $camp_num != 1 ) {

                $tempCamp = [
                            'camp_name' => $livecamp->camp_name, 
                            'camp_num' => $camp_num, 
                            'support_order' => $rs->support_order,
                            'camp_link' =>  Camp::campLink($rs->topic_num,$rs->camp_num,$rs->title,$rs->camp_name),
                            'delegate_nick_name_id' => $rs->delegate_nick_name_id,
                            'delegate_nick_name' => $delegatedToNickname
                        ];
                
                if(isset($supports[$nickname->id]['topic'][$topic_num]['camps'])){
                    array_push($supports[$nickname->id]['topic'][$topic_num]['camps'],$tempCamp);
                }else{
                    $supports[$nickname->id]['topic'][$topic_num]['camps'][] = $tempCamp;
                }

            } else if ($camp_num == 1) { 

                //$supports[$nickname->id]['topic'][$topic_num]['camp_name'] = ($rs->camp_name != "") ? $livecamp->camp_name : $livecamp->title;
                $supports[$nickname->id]['topic'][$topic_num]['topic_num'] = $topic_num;
                $supports[$nickname->id]['topic'][$topic_num]['title_link'] = Topic::topicLink($topic_num, 1, $title);
                $supports[$nickname->id]['topic'][$topic_num]['title'] = $liveTopic->topic_name;
                $supports[$nickname->id]['topic'][$topic_num]['camp_name'] = ($rs->camp_name != "") ? $livecamp->camp_name : $livecamp->title;
                $supports[$nickname->id]['topic'][$topic_num]['namespace_id'] = $namespaceId;
                if($rs->delegate_nick_name_id){
                    $supports[$nickname->id]['topic'][$topic_num]['delegate_nick_name_id'] = $rs->delegate_nick_name_id;
                    $supports[$nickname->id]['topic'][$topic_num]['delegate_nick_name'] = $delegatedToNickname;
                }
                
            } else {

                $tempCamp = [
                    'camp_name' => $livecamp->camp_name, 
                    'camp_num' => $camp_num, 
                    'support_order' => $rs->support_order,
                    'camp_link' =>  Camp::campLink($rs->topic_num,$rs->camp_num,$liveTopic->topic_name,$rs->camp_name),
                    'delegate_nick_name_id'=>$rs->delegate_nick_name_id,
                    'delegate_nick_name' => $delegatedToNickname
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

        if(count($delegators))
        { 
            foreach($delegators as $delegator)
            {
                $delegatorsNickArray = self::getAllNickNamesOfNickID($delegator->nick_name_id);
                self::removeSupport($topicNum, $campNum, $delegatorsNickArray);
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
            /* To update the Mongo Tree while adding support */
            $topic = Topic::where('topic_num', $topicNum)->orderBy('id','DESC')->first();
            
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
                    
                    /* To update the Mongo Tree while adding support */
                    /* Execute job here only when this is topicnumber == 81 (because we using dynamic camp_num for 81) */
                    if($topicNum == config('global.mind_expert_topic_num')) {
                        Util::dispatchJob($topic, $order['camp_num'], 1);
                    }
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
    public static function supportRemovalEmail($topic, $camp, $nickname, $delegateNickNameId='')
    {

        $object = $topic->topic_name ." / ".$camp->camp_name;
        $topicLink =  self::getTopicLink($topic);
        $campLink = self::getCampLink($topic,$camp);
        $seoUrlPortion = Util::getSeoBasedUrlPortion($topic->topic_num, $camp->camp_num, $topic, $camp);

        $mailData['object'] = $object;
        if(isset($delegateNickNameId) && !empty($delegateNickNameId)){
            $mailData['subject'] = $nickname->nick_name . " has removed their delegated support from ".$object. ".";
        }else{
            $mailData['subject'] = $nickname->nick_name . " has removed their support from ".$object. ".";
        }
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
        $mailData['support_action'] = "remove"; //default will be 'added'

        if(isset($delegateNickNameId) && !empty($delegateNickNameId)){
            $subjectStatement = "has removed their delegated support from";
        }else{
            $subjectStatement = "has removed their support from";
        }
        
        self::SendEmailToSubscribersAndSupporters($topic->topic_num, $camp->camp_num, $nickname->id, $subjectStatement, 'remove');
        return;
    }


    /**
     * Send email to promoted delegates as a direct supporter of topic and camps 
     * @param array $data [is mail data]
     * @return void
     */
    public static function SendEmailToSubscribersAndSupporters($topicNum, $campNum, $nickNameId, $subjectStatement, $action = "add", $delegatedNickNameId ='')
    {
        $bcc_email = [];
        $subscriber_bcc_email = [];
        $bcc_user = [];
        $sub_bcc_user = [];
        $userExist = [];

        $topicFilter = ['topicNum' => $topicNum];
        $campFilter = ['topicNum' => $topicNum, 'campNum' => $campNum];

        $topic = Camp::getAgreementTopic($topicFilter);
        $camp  = self::getLiveCamp($campFilter);
        $nickname =  Nickname::getNickName($nickNameId);

        $object = (isset($delegatedNickNameId) && $delegatedNickNameId) ? $topic->topic_name : $topic->topic_name ." / ".$camp->camp_name;
        $topicLink =  self::getTopicLink($topic);
        $campLink = self::getCampLink($topic,$camp);
        $seoUrlPortion = Util::getSeoBasedUrlPortion($topicNum, $campNum, $topic, $camp);

        $data['object']     = $object;
        $data['subject']    = $nickname->nick_name . " ". $subjectStatement . " " . $object. ".";
        $data['topic']      = $topic;
        $data['camp']       = $camp;
        $data['camp_name']  = $camp->camp_name;
        $data['topic_name'] = $topic->topic_name;
        $data['topic_num']  = $topic->topic_num;
        $data['camp_num']   = $camp->camp_num;
        $data['topic_link'] = $topicLink;
        $data['camp_link']  = $campLink;   
        $data['url_portion'] =  $seoUrlPortion;
        $data['nick_name_id'] = $nickname->id;
        $data['nick_name'] = $nickname->nick_name;
        $data['namespace_id'] = isset($topic->namespace_id) ? $topic->namespace_id : 1;
        $data['nick_name_link'] = Nickname::getNickNameLink($data['nick_name_id'], $data['namespace_id'], $data['topic_num'], $data['camp_num']);;
        $data['support_action'] = $action; //default will be 'added'       
        $topic_name_space_id = $data['namespace_id'];
        
        /** If delegate support */
        if(isset($delegatedNickNameId) && $delegatedNickNameId){
            $delegatedToNickname =  Nickname::getNickName($delegatedNickNameId);
            $data['delegated_nick_name'] = $delegatedToNickname->nick_name;
            $data['delegated_nick_name_id'] = $delegatedToNickname->id;
            $data['delegated_nick_name_link'] = Nickname::getNickNameLink($data['delegated_nick_name_id'], $data['namespace_id'], $data['topic_num'], $data['camp_num']);
            $data['subject']  = $nickname->nick_name . " ". $subjectStatement . " " . $delegatedToNickname->nick_name. ".";
        }        
        
        $directSupporter = Support::getAllDirectSupporters($topicNum, $campNum);
        $subscribers = Camp::getCampSubscribers($topicNum, $campNum);

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
                    if($action == 'add'){
                        Event::dispatch(new SupportAddedMailEvent($user->email ?? null, $user, $data));
                    }else{
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
                    if($action == 'add'){
                        Event::dispatch(new SupportAddedMailEvent($userSub->email ?? null, $userSub, $data));
                    }else{
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

    /** 
     * 
     */
    public static function checkSupportValidaionAndWarning($topicNum, $campNum, $nickNames, $delegataedNickNameId = 0)
    {
        $returnData = [];

        if($delegataedNickNameId){
            $returnData = self::checkIfSupportSwitchToDirectToDelegate($topicNum, $campNum, $nickNames);
            if(!empty($returnData)){
                return $returnData;
            }
        }else{
            $returnData = self::checkIfDelegatorSupporter($topicNum, $campNum, $nickNames);
            if(!empty($returnData)){
                return $returnData;
            }
            
            $returnData = self::checkIfSupportswitchToChild($topicNum, $campNum, $nickNames);
            if(!empty($returnData)){
                return $returnData;
            }

            $returnData = self::checkIfSupportSwitchToParent($topicNum, $campNum, $nickNames);
            if(!empty($returnData)){
                return $returnData;
            }
        }

       

        return $returnData;

    }

    /**
     *  check is supported is a deleagtor supporter
     *  @return $returnData with warning messages
     */
    public static function checkIfDelegatorSupporter($topicNum, $campNum, $nickNames)
    {
        $returnData = [];
        $supportedCamps = [];
        $delegatedSupport = Support::getDelgatedSupportInTopic($topicNum,$nickNames); 
        $liveTopic = Topic::getLiveTopic($topicNum,['nofilter'=>true]);
        $campsToemoved = [];

        if ($delegatedSupport->count()) {
            $nickName = Nickname::getNickName($delegatedSupport[0]->delegate_nick_name_id);

            foreach($delegatedSupport as $support){

                $filter['topicNum'] = $topicNum;
                $filter['asOf'] = '';
                $filter['campNum'] =  $support->camp_num;
                $livecamp = Camp::getLiveCamp($filter);
                $temp = [
                    'camp_num' => $support->camp_num,
                    'support_order' => $support->support_order,
                    'camp_name' => $livecamp->camp_name,
                    'link' => Camp::campLink($topicNum, $support->camp_num, $liveTopic->topic_name, $livecamp->camp_name)
                ];
                array_push($campsToemoved, $temp);
                array_push($supportedCamps, $support->camp_num);
            }

            if(in_array($campNum, $supportedCamps)){
                $returnData['warning'] = "You have delegated your support to user ".$nickName->nick_name." in this camp. If you continue your delegated support will be removed.";
            }else{
                $returnData['warning'] = "You have delegated your support to user ".$nickName->nick_name." under this topic. If you continue your delegated support will be removed.";
            }  

            $returnData['is_delegator'] = 1;
            $returnData['topic_num'] = $topicNum;
            $returnData['camp_num'] = $campNum;
            $returnData['delegated_nick_name_id'] = $nickName->id;
            $returnData['is_confirm'] = 1;
            $returnData['remove_camps'] = $campsToemoved;
        }

        return $returnData;
    }

    /**
     *  [This will check & return warning if support switched from child to parent]
     */
    public static function checkIfSupportswitchToChild($topicNum, $campNum, $nickNames)
    { 
        $returnData = [];
        $as_of_time = time();
        $parentSupport = Camp::validateParentsupport($topicNum, $campNum, $nickNames);

        if(empty($parentSupport))
          return $returnData;

        $filter = Camp::getLiveCampFilter($topicNum, $campNum);
        $onecamp = self::getLiveCamp($filter);  
        $returnData['topic_num'] = $topicNum;
        $returnData['camp_num'] = $campNum;

        if($parentSupport === "notfound"){

            $returnData['warning'] =  trans('message.support_warning.not_found');

        }else if ($parentSupport === "notlive") { 
                            
            $returnData['warning'] =  trans('message.support_warning.not_live');

        } else { 
            $liveTopic = Topic::getLiveTopic($topicNum,['nofilter'=>true]);
            $campsToemoved = [];   
            
            foreach($parentSupport as $support){
                $filter['topicNum'] = $topicNum;
                $filter['asOf'] = '';
                $filter['campNum'] =  $support->camp_num;
                $livecamp = Camp::getLiveCamp($filter);
                $temp = [
                    'camp_num' => $support->camp_num,
                    'support_order' => $support->support_order,
                    'camp_name' => $livecamp->camp_name,
                    'link' => Camp::campLink($topicNum, $support->camp_num, $liveTopic->topic_name, $livecamp->camp_name)
                ];
                array_push($campsToemoved, $temp);
            }
            $res = self::ValidateAndCheckWarning($parentSupport, $onecamp, $campNum, $as_of_time);
            $returnData = array_merge($returnData,$res);
            if(isset($returnData['is_confirm']) && $returnData['is_confirm']){
                $returnData['remove_camps'] = $campsToemoved;
            }

             
            
        }
        
        return $returnData;
    }

    /**
     * [This will check & return warning if support switched from parent to child]
     */
    public static function checkIfSupportSwitchToParent($topicNum, $campNum, $nickNames)
    {
        $returnData = [];
        $as_of_time = time();
        $childSupport = Camp::validateChildsupport($topicNum, $campNum, $nickNames);

        $filter = Camp::getLiveCampFilter($topicNum, $campNum);
        $onecamp = self::getLiveCamp($filter);
        $liveTopic = Topic::getLiveTopic($topicNum,['nofilter'=>true]);
        $campsToemoved = [];

        if($childSupport && !empty($childSupport)){
            foreach ($childSupport as $child)
            {
                $filter['topicNum'] = $topicNum;
                $filter['asOf'] = '';
                $filter['campNum'] =  $child->camp_num;
                $livecamp = Camp::getLiveCamp($filter);
                $temp = [
                    'camp_num' => $child->camp_num,
                    'support_order' => $child->support_order,
                    'camp_name' => $livecamp->camp_name,
                    'link' => Camp::campLink($topicNum, $child->camp_num, $liveTopic->topic_name, $livecamp->camp_name)
                ];
                array_push($campsToemoved, $temp);
            }
            if (count($childSupport) == 1) {
                $child = $childSupport[0];
                $childCampName = Camp::getCampNameByTopicIdCampId($topicNum, $child->camp_num, $as_of_time);
                if ($child->camp_num == $campNum && $child->delegate_nick_name_id == 0) {                        
                    $returnData['is_confirm'] = 0;  

                }else{
                    $returnData['is_confirm'] = 1;  
                    $returnData['warning'] =  '"'.$onecamp->camp_name .'" is a parent camp to "'. $childCampName. '", so if you commit support to "'.$onecamp->camp_name .'", the support of the child camp "'. $childCampName. '" will be removed.';

                }
            } else {
                $returnData['is_confirm'] = 1;    
                $returnData['warning'] = '"'.$onecamp->camp_name .'" is a parent camp to this list of child camps. If you commit support to "'.$onecamp->camp_name .'", the support of the camps in this list will be removed.';
                
            }

            $returnData['topic_num'] = $topicNum;
            $returnData['camp_num'] = $campNum;
            if(isset($returnData['is_confirm']) && $returnData['is_confirm']){
                $returnData['remove_camps'] = $campsToemoved;
            }
            
        }

        return $returnData;
    }

    public static function ValidateAndCheckWarning($parentSupport, $onecamp, $campNum, $as_of_time)
    {
        $returnData['is_confirm'] = 0;

        if (count($parentSupport) == 1) {

            foreach ($parentSupport as $parent){                        
                $parentCampName = Camp::getCampNameByTopicIdCampId($onecamp->topic_num, $parent->camp_num, $as_of_time);
                
                if ($parent->camp_num != $campNum) {

                    $returnData['is_confirm'] = 1;  
                    $returnData['warning'] = '"' . $onecamp->camp_name .'" is a child camp to "' .$parentCampName .'", so if you commit support to "'.$onecamp->camp_name .'", the support of the parent camp "' .$parentCampName .'" will be removed.';
                }
            }
        } else {

            $returnData['is_confirm'] = 1; 
            $returnData['warning'] = 'The following  camps are parent camps to "' . $onecamp->camp_name . '" and will be removed if you commit this support.';
        
        }
        return $returnData;
    }

    /**
     *  [This will add support ]
     */
    public static function addSupport($topicNum, $campNum, $supportOrder, $nickNameId, $delegatedNickNameId = 0)
    {
       
        $support = new Support();
        $support->topic_num = $topicNum;
        $support->nick_name_id = $nickNameId;
        $support->delegate_nick_name_id = $delegatedNickNameId;
        $support->camp_num = $campNum;
        $support->support_order = $supportOrder;
        $support->start = time();
        $support->save();

        return $support;        
    }
    
    /**
     *  [function to write log]
     * @param string $logType is string specfying  type
     * @param string $activity event performed 
     * @param string $link is link on which event is performed
     * @param object $model is model object on which event is performed
     * @param integer $topicNum is topic number
     * @param integer $campNum is camp number
     * @param object $user is user object performing action
     * @param string $nickName is nick name is nickname of user
     * @param string $description is description
     */
    public static function logActivity($logType, $activity, $link, $model, $topicNum, $campNum, $user, $nickName, $description)
    {
        
        $activitLogData = [
            'log_type' =>  $logType,
            'activity' => $activity,
            'url' => $link,
            'model' => new Support(),
            'topic_num' => $topicNum,
            'camp_num' =>  $campNum,
            'user' => $user,
            'nick_name' => $nickName,
            'description' => $description
        ];

        dispatch(new ActivityLoggerJob($activitLogData))->onQueue(env('QUEUE_SERVICE_NAME'));
    }

    /**
     * [activity logger on remove support]
     * @param array $removeCamps are list of camps to be removed
     * @param integer $topicNum is topic number
     * @param integer $nickNameId is nick name id of user removing support
     * 
     * @return void
     */
    public static function logActivityForRemoveCamps($removeCamps, $topicNum, $nickNameId, $delegateNickNameId = '')
    {
        if(!empty($removeCamps))
        {         
            $nicknameModel = Nickname::getNickName($nickNameId);
            $topicFilter = ['topicNum' => $topicNum];
            $topicModel = Camp::getAgreementTopic($topicFilter); 
            $user = Nickname::getUserByNickName($nickNameId);

            $logType = "support";
            $activity =  trans('message.general.support_removed');
            $model = new Support();
            $description = trans('message.general.support_removed');

            if(!empty($delegateNickNameId)){
                $delegatedTo = Nickname::getNickName($delegateNickNameId);
                $activity = "Delegated support removed from " . $delegatedTo->nick_name; 
            }

            foreach($removeCamps as $camp)
            {
                $campFilter = ['topicNum' => $topicNum, 'campNum' => $camp];
                $campModel  = self::getLiveCamp($campFilter); 
                $link = Util::getTopicCampUrl($topicNum, $camp, $topicModel, $campModel);

                self::logActivity($logType, $activity, $link, $model, $topicNum, $camp, $user, $nicknameModel->nick_name, $description);
            }
        }
        return;
    }

    /**
     * [activity logger on add support]
     * @param integer $campNum is camp number to which support is added
     * @param integer $topicNum is topic number
     * @param integer $nickNameId is nick name id of user adding support
     * 
     * @return void
     */
    public static function logActivityForAddSupport($topicNum, $campNum, $nickNameId, $delegateNickNameId = '')
    {
        if($campNum){ 
            $nicknameModel = Nickname::getNickName($nickNameId);
            $topicFilter = ['topicNum' => $topicNum];
            $topicModel = Camp::getAgreementTopic($topicFilter);
            $user = Nickname::getUserByNickName($nickNameId);

            $campFilter = ['topicNum' => $topicNum, 'campNum' => $campNum];
            $campModel  = self::getLiveCamp($campFilter);

            $logType = "support";
            $activity = trans('message.general.support_added');
            $link = Util::getTopicCampUrl($topicNum, $campNum, $topicModel, $campModel);
            $model = new Support();
            $description = trans('message.general.support_added');

            if($delegateNickNameId){
                $delegatedTo = Nickname::getNickName($delegateNickNameId);
                $activity = $nicknameModel->name  . " delegated their support to " . $delegatedTo->nick_name; 
                $description = trans('message.general.support_delegated');
            }
            
            return self::logActivity($logType, $activity, $link, $model, $topicNum, $campNum, $user, $nicknameModel->nick_name, $description);
        }
        
        return;
    }

    /** 
     * Return Add support API message
     */
    public static function getMessageBasedOnAction($add, $remove, $reOrder)
    {
        $message = trans('message.support.update_support');

        if($add && !$remove)
        {
            $message = trans('message.support.add_direct_support');
        }else if(!$add && $remove)
        {
            $message = trans('message.support.remove_direct_support');
        }

        return $message;
    }

    /**
     *  [Get All Delegates of a supporter in the topic]
     *  @param integer $nickNameId is nick name id user for which all deleagtes needs to be fetched
     * 
     */
    public static function getAllDelegates($topicNum, $nickNameId, $delegates = [])
    {
        $delegateSupporters =  Support::getActiveDelegators($topicNum, [$nickNameId]);
        
        if(!empty($delegateSupporters))
        {
            foreach($delegateSupporters as $ds){
                $temp = [
                    'nick_name_id' => $ds->nick_name_id,
                    'delegate_nick_name_id' => $ds->delegate_nick_name_id
                ];
                array_push($delegates, $temp);

                $subDelegates =  Support::getActiveDelegators($topicNum, [$ds->nick_name_id]);
               
                if(count($subDelegates)){
                    return self::getAllDelegates($topicNum, $ds->nick_name_id, $delegates);
                }
            }
        }

        return $delegates;
    }

    /** 
     *  [notify delegator and  delegated user]
     */
    public static function notifyDelegatorAndDelegateduser($topicNum, $campNum, $nickNameId, $action = 'add', $delegatedNickNameId, $notifyDelegatedUser = false)
    {
        $bcc_email = [];
        $subscriber_bcc_email = [];
        $bcc_user = [];
        $sub_bcc_user = [];
        $userExist = [];

        $topicFilter = ['topicNum' => $topicNum];
        $campFilter = ['topicNum' => $topicNum, 'campNum' => $campNum];

        $topic = Camp::getAgreementTopic($topicFilter);
        $camp  = self::getLiveCamp($campFilter);
        $nickname =  Nickname::getNickName($nickNameId);
        $delegatedToNickname =  Nickname::getNickName($delegatedNickNameId);

        $object = $topic->topic_name ." / ".$camp->camp_name;
        $topicLink =  self::getTopicLink($topic);
        $campLink = self::getCampLink($topic,$camp);
        $seoUrlPortion = Util::getSeoBasedUrlPortion($topicNum, $campNum, $topic, $camp);

        $data['object']     = $object;
        $data['subject']    = "You have just delegated your support to " . $delegatedToNickname->nick_name . ".";
        $data['topic']      = $topic;
        $data['camp']       = $camp;
        $data['camp_name']  = $camp->camp_name;
        $data['topic_name'] = $topic->topic_name;
        $data['topic_num']  = $topic->topic_num;
        $data['camp_num']   = $camp->camp_num;
        $data['topic_link'] = $topicLink;
        $data['camp_link']  = $campLink;   
        $data['url_portion'] =  $seoUrlPortion;
        $data['nick_name_id'] = $nickname->id;
        $data['nick_name'] = $nickname->nick_name;
        $data['namespace_id'] = isset($topic->namespace_id) ? $topic->namespace_id : 1;
        $data['nick_name_link'] = Nickname::getNickNameLink($nickname->id, $data['namespace_id'], $data['topic_num'], $data['camp_num']);
        $data['support_action'] = $action; //default will be 'added'       
        $data['delegated_nick_name'] = $delegatedToNickname->nick_name;
        $data['delegated_nick_name_id'] = $delegatedToNickname->id;
        $data['delegated_nick_name_link'] = Nickname::getNickNameLink($data['delegated_nick_name_id'], $data['namespace_id'], $data['topic_num'], $data['camp_num']);
        $data['action'] = $action;
        $topic_name_space_id = $data['namespace_id'];

        try {
                    
            if($action == 'add'){
                $user = Nickname::getUserByNickName($nickNameId);
                Event::dispatch(new NotifyDelegatedAndDelegatorMailEvent($user->email ?? null, $user, $data));

                if(isset($notifyDelegatedUser) && $notifyDelegatedUser){
                    $data['notify_delegated_user'] = $notifyDelegatedUser;
                    $data['subject']    = $nickname->nick_name . " has just delegated their support to you.";                    
                    $delegatedUser = Nickname::getUserByNickName($delegatedNickNameId);                    
                    Event::dispatch(new NotifyDelegatedAndDelegatorMailEvent($delegatedUser->email ?? null, $delegatedUser, $data));
                }
            }
            
        } catch (Throwable $e) {
            $data = null;
            $status = 403;
            echo  $message = $e->getMessage();
        }

        return;
    }

    /**
     * [send push notfication]
     * 
     */
    public static function sendNotification($topicNum, $campNum, $nickNameId, $allDirectDelegates, $delegateNickNameId)
    {
        
        $promotedTo = [];
        $promoteLevel = "direct"; 
        $topicFilter = ['topicNum' => $topicNum];
        $campFilter = ['topicNum' => $topicNum, 'campNum' => $campNum];

        $topic = Camp::getAgreementTopic($topicFilter);
        $camp  = self::getLiveCamp($campFilter);
        $promotedFrom = Nickname::getNickName($nickNameId);       
        $topicLink =  self::getTopicLink($topic);
        $campLink = self::getCampLink($topic,$camp);

        if(isset($delegateNickNameId) && $delegateNickNameId != ''){            
            $promotedTo = Nickname::getNickName($delegateNickNameId);  
            $promoteLevel = "delegate";  
        }
        foreach($allDirectDelegates as $supporter)
        {
            $user = Nickname::getUserByNickName($supporter->nick_name_id);
           // PushNotification::pushNotificationToPromotedDelegates($fcmToken, $topic, $camp, $topicLink, $campLink, $user, $promoteLevel, $promotedFrom, $promotedTo);
           GetPushNotificationToSupporter::pushNotificationToPromotedDelegates($topic, $camp, $topicLink, $campLink, $user, $promoteLevel, $promotedFrom, $promotedTo);    
        }        
    }

    /**
     *  check is supported is a direct supporter and switching to delegate
     *  @return $returnData with warning messages
     */
    public static function checkIfSupportSwitchToDirectToDelegate($topicNum, $campNum, $nickNames)
    {
        $returnData = [];
        $supportedCamps = [];
        $directSupport = Support::getActiveSupporInTopicWithAllNicknames($topicNum, $nickNames, true);
        $liveTopic = Topic::getLiveTopic($topicNum,['nofilter'=>true]);
        $campsToemoved = [];

        if (count($directSupport)) {
            foreach($directSupport as $support){

                $filter['topicNum'] = $topicNum;
                $filter['asOf'] = '';
                $filter['campNum'] =  $support->camp_num;
                $livecamp = Camp::getLiveCamp($filter);
                $temp = [
                    'camp_num' => $support->camp_num,
                    'support_order' => $support->support_order,
                    'camp_name' => $livecamp->camp_name,
                    'link' => Camp::campLink($topicNum, $support->camp_num, $liveTopic->topic_name, $livecamp->camp_name)
                ];
                array_push($campsToemoved, $temp);
            }

            $returnData['warning'] = "You are directly supporting one or more camps under this topic. If you continue, your direct support will be removed.";

            $returnData['is_delegator'] = 0;
            $returnData['topic_num'] = $topicNum;
            $returnData['camp_num'] = $campNum;
            $returnData['is_confirm'] = 1;
            $returnData['remove_camps'] = $campsToemoved;
        }

        return $returnData;
    }
}

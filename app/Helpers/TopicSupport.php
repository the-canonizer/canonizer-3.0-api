<?php

namespace App\Helpers;

use Throwable;
use App\Models\Camp;
use App\Models\User;
use App\Facades\Util;
use App\Models\Topic;
use App\Models\Support;
use App\Models\Nickname;
use App\Jobs\ActivityLoggerJob;
use Illuminate\Support\Facades\Auth;
use App\Events\NotifySupportersEvent;
use App\Events\SupportAddedMailEvent;
use Illuminate\Support\Facades\Event;
use App\Events\SupportRemovedMailEvent;
use App\Events\PromotedDelegatesMailEvent;
use App\Facades\GetPushNotificationToSupporter;
use App\Events\NotifyDelegatedAndDelegatorMailEvent;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class TopicSupport
{

   

    public static $model = 'App\Models\Support';


    public  static function removeDirectSupport($topicNum, $removeCamps = array(), $nickNameId, $action = 'all', $type = 'direct', $orderUpdate = array(), $user, $reason = null,$reason_summary = null,$citation_link = null)
    {
        if(isset($action) && $action == 'all')
        {
            return self::removeCompleteSupport($topicNum, $removeCamps , $nickNameId, $action , $type,'', $reason,$reason_summary, $citation_link);
        
        }else if(isset($action) && $action == 'partial')
        {
            return self::removePartialSupport($topicNum, $removeCamps , $nickNameId, $action,  $type, $orderUpdate, $user ,$reason,$reason_summary, $citation_link);
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
        $removeCamps = [];
        self::removeCompleteSupport($topicNum,$removeCamps,$nickNameId, 'all', 'delegate', $delegateNickNameId); 
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
    public static function removeCompleteSupport($topicNum, $removeCamps = array(), $nickNameId, $action = 'all', $type = 'direct', $delegateNickNameId = '', $reason = null,$reason_summary=null,$citation_link = null)
    { 
        
        if(isset($action) && $action == 'all')  //abandon entire topic and promote deleagte
        {
            $allNickNames = self::getAllNickNamesOfNickID($nickNameId);
            
            //dd($allNickNames);
            $getAllActiveSupport = Support::getActiveSupporInTopicWithAllNicknames($topicNum, $allNickNames);
            if($getAllActiveSupport->count() == 0){ 
                return;
            }

            $campNum = isset($getAllActiveSupport[0]->camp_num) ?  $getAllActiveSupport[0]->camp_num : '';  // First choice camp number  of topic
            $allDirectDelegates = Support::getActiveDelegators($topicNum, $allNickNames);
            
            Support::removeSupportWithAllNicknames($topicNum, '', $allNickNames, $reason, $reason_summary, $citation_link, ['route' => 'support/update', 'action' => $action, 'type' => $type]);

            //semd Email
            $nicknameModel = Nickname::getNickName($nickNameId);
            $topicFilter = ['topicNum' => $topicNum];
            $topicModel = Camp::getAgreementTopic($topicFilter);
            $campFilter = ['topicNum' => $topicNum, 'campNum' => $campNum];
            $campModel  = self::getLiveCamp($campFilter);

            // Get Nominated Camp Leader In review Changes
            $inReviewChanges = Camp::getNominatedCampLeaderInReviewChanges($topicNum, $campNum, $nickNameId);
            foreach ($inReviewChanges as $camp) {
                $camp->objector_nick_id = $nickNameId;
                $camp->object_reason = trans('message.camp_leader.error.system_generated.nominated_user_removes_support');
                $camp->object_time = time();
                $camp->save();
            }

            $previousCampLeaderNickId = $campModel->camp_leader_nick_id;

            $supportRemovedFrom = Support::getActiveSupporInTopicWithAllNicknames($topicNum, [$delegateNickNameId]);
            $notifyDelegatedUser = false;  
            
            if(count($supportRemovedFrom) && empty($removeCamps)){
                array_push($removeCamps, $supportRemovedFrom[0]->camp_num);
            }
            
            if(isset($supportRemovedFrom[0]->delegate_nick_name_id) && $supportRemovedFrom[0]->delegate_nick_name_id)  // if  user is a delegated supporter itself, then notify
            {
                $notifyDelegatedUser = true;
            }

            $delegate_nick_name = '';
            $delegateNickNameIdModel = Nickname::getNickName($delegateNickNameId);
            if (!empty($delegateNickNameIdModel)) {
                $delegate_nick_name = $delegateNickNameIdModel->nick_name;
            }

            if(isset($allDirectDelegates) && count($allDirectDelegates) > 0)
            {
                Support::promoteUpDelegates($topicNum, $allNickNames, $delegateNickNameId);
                $promotedDelegatesIds = TopicSupport::sendEmailToPromotedDelegates($topicNum, $campNum, $nickNameId, $allDirectDelegates, $delegateNickNameId);

                //push notification to promoted delegates
                self::sendNotification($topicNum, $campNum, $nickNameId, $allDirectDelegates, $delegateNickNameId);

            }

            /* To update the Mongo Tree while remove all support */
            $topic = Topic::where('topic_num', $topicNum)->orderBy('id','DESC')->first();
            if(count($removeCamps)) {
                Util::dispatchJob($topic, $removeCamps[0], 1);
            } else {
                Util::dispatchJob($topic, 1, 1);
            }

            //timeline start
            $timeline_url = Util::getTimelineUrlgetTimelineUrl($topic->topic_num, $topic->topic_name, $campModel->camp_num, $campModel->camp_name, $topic->topic_name, $type . "_support_removed", null, $topic->namespace_id, $topic->submitter_nick_id);
            if($type=="direct"){
                $removed_msg = $nicknameModel->nick_name . " has removed "  . $type . " support in camp - " . $campModel->camp_name;
            }
            else{
                $removed_msg = $nicknameModel->nick_name . " has removed their delegated support from " . $delegate_nick_name;
            }
            
            Util::dispatchTimelineJob($topic->topic_num, $campModel->camp_num, 1, $removed_msg, $type . "_support_removed", $campModel->camp_num, null, null, null, time(), $timeline_url);
            //timeline end

            self::supportRemovalEmail($topicModel, $campModel, $nicknameModel,$delegateNickNameId, $notifyDelegatedUser, $previousCampLeaderNickId);

            //log remove support activity
            self::logActivityForRemoveCamps($removeCamps, $topicNum, $nickNameId, $delegateNickNameId, $reason, $reason_summary, $citation_link);
            return;
            
        }
    }

    /**
     * [Remomve Partial support that is from one or more camps]
     * remove support from camps listed in @param $camNum
     * Also remove support from those camps for delegated supporter
     * 
     */
    public static function removePartialSupport($topicNum, $removeCamps = array(), $nickNameId, $action = 'all', $type = 'direct', $orderUpdate = array(), $user, $reason = null,$reason_summary =null,$citation_link = null)
    {
        $allNickNames = self::getAllNickNamesOfNickID($nickNameId);
        $nicknameModel = Nickname::getNickName($nickNameId);
        $nickName = '';
        if (!empty($nicknameModel)) {
            $nickName = $nicknameModel->nick_name;
        }
        if(!empty($removeCamps)){

            try
            {
                DB::beginTransaction();
                self::removeSupport($topicNum,$removeCamps,$allNickNames,$reason, $reason_summary, $citation_link);
                DB::commit();

            }catch (Throwable $e) 
            {
                DB::rollback();
                throw new Exception($e->getMessage());
            }

            $topicFilter = ['topicNum' => $topicNum];
            $topicModel = Camp::getAgreementTopic($topicFilter);
            $removeArrayCount = count($removeCamps);

            foreach($removeCamps as $key => $camp)
            {
                $campFilter = ['topicNum' => $topicNum, 'campNum' => $camp];
                $campModel  = self::getLiveCamp($campFilter); 

                /* To update the Mongo Tree while removing support */
                /* Execute job to queue the updated tree */
                $topic = Topic::where('topic_num', $topicNum)->orderBy('id','DESC')->first();
                Util::dispatchJob($topic, $camp, 1);

                self::supportRemovalEmail($topicModel, $campModel, $nicknameModel);
                // GetPushNotificationToSupporter::pushNotificationToSupporter($user, $topicNum, $camp, 'remove', null, $nickName);
                
                //timeline start
                $timeline_url = Util::getTimelineUrlgetTimelineUrl($topic->topic_num, $topic->topic_name, $campModel->camp_num, $campModel->camp_name, $topic->topic_name, $type . "_support_removed", null, $topic->namespace_id, $topic->submitter_nick_id);
                
                Util::dispatchTimelineJob($topic->topic_num, $campModel->camp_num, 1, $nicknameModel->nick_name . " has removed "  . $type . " support in camp - " . $campModel->camp_name, $type . "_support_removed", $campModel->camp_num, null, null, null, time(), $timeline_url);
                //timeline end
            }

             //log activity
             self::logActivityForRemoveCamps($removeCamps, $topicNum, $nickNameId, '' , $reason, $reason_summary, $citation_link);
        }

        if(isset($orderUpdate) && !empty($orderUpdate)){
            try
            {
                DB::beginTransaction();
                $isOrderUpdated = false;
                $previousSupports = self::getTopicSupportByNickName($topicNum, $allNickNames);
                foreach ($previousSupports as $singleSupport) {
                    foreach ($orderUpdate as $item) {
                        if ($item['camp_num'] == $singleSupport->camp_num) {
                            if ($item['order'] != $singleSupport->support_order) {
                                $isOrderUpdated = true;
                            }
                            break;
                        }
                    }
                    if ($isOrderUpdated) {
                        break;
                    }
                }
                self::reorderSupport($orderUpdate, $topicNum, $allNickNames,$reason, $reason_summary, $citation_link);
                DB::commit();
                $topic = Topic::where('topic_num', $topicNum)->orderBy('id','DESC')->first();
                foreach($orderUpdate as $order) {
                    if ($isOrderUpdated) {
                        self::logActivityForUpdateSupport($topicNum, $order['camp_num'], $nickNameId, $reason, $reason_summary, $citation_link);
                    } elseif (!empty($reason) || !empty($reason_summary) || !empty($citation_link)) {
                        self::logActivityForUpdateSupportReason($topicNum, $order['camp_num'], $nickNameId, $reason, $reason_summary, $citation_link);
                    }
                    // Execute job here only when this is topicnumber == 81 (because we using dynamic camp_num for 81) 
                    if($topicNum == config('global.mind_expert_topic_num')) {
                        Util::dispatchJob($topic, $order['camp_num'], 1);
                    }
                }
                Util::dispatchJob($topic, 1, 1);
            }catch (Throwable $e) 
            {
                DB::rollback();
                throw new Exception($e->getMessage());
            }
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
    public static function addDirectSupport($topicNum, $nickNameId, $addCamp, $user, $removeCamps = array(), $orderUpdate = array(),$reason = null,$reason_summary = null,$citation_link = null)
    {
        $allNickNames = self::getAllNickNamesOfNickID($nickNameId);
        // $campArray = explode(',', trim($campNum));

        /* To update the Mongo Tree while adding support */
        $topic = Topic::where('topic_num', $topicNum)->orderBy('id','DESC')->first();
        $allDelegates =  self::getAllDelegates($topicNum, $nickNameId);

        $nicknameModel = Nickname::getNickName($nickNameId);

        $nickName = '';
        $asOfDefaultDate = time();
        if (!empty($nicknameModel)) {
            $nickName = $nicknameModel->nick_name;
        }

        if(!empty($removeCamps)){

            try
            {
                DB::beginTransaction();

                self::removeSupport($topicNum,$removeCamps,$allNickNames,$reason,$reason_summary,$citation_link);
                Support::reOrderSupport($topicNum, $allNickNames,$reason,$reason_summary,$citation_link); //after removal reorder support
                
                DB::commit();

                $topicFilter = ['topicNum' => $topicNum];
                $topicModel = Camp::getAgreementTopic($topicFilter);
                $removeArrayCount = count($removeCamps);

                foreach($removeCamps as $key => $camp) {     
                    $campFilter = ['topicNum' => $topicNum, 'campNum' => $camp];
                    $campModel  = self::getLiveCamp($campFilter);
                    
                    // Get Nominated Camp Leader In review Changes
                    $inReviewChanges = Camp::getNominatedCampLeaderInReviewChanges($topicNum, $camp, $nickNameId);
                    foreach ($inReviewChanges as $campChange) {
                        $campChange->objector_nick_id = $nickNameId;
                        $campChange->object_reason = trans('message.camp_leader.error.system_generated.nominated_user_removes_support');
                        $campChange->object_time = time();
                        $campChange->save();
                    }
                    
                    // Check camp leader remove his support
                    $camp_leader = Camp::getCampLeaderNickId($topicNum, $camp);
                    if (!is_null($camp_leader) && $camp_leader == $nickNameId) {
                        $oldest_direct_supporter = self::findOldestDirectSupporter($topicNum, $camp, $nickNameId);
                        Camp::updateCampLeaderFromLiveCamp($topicNum, $camp, $oldest_direct_supporter->nick_name_id ?? null);
                    }

                    /* To update the Mongo Tree while removing at add support */
                    /* Execute job here only when this is topicnumber == 81 (because we using dynamic camp_num for 81) */
                    Util::dispatchJob($topic, $camp, 1);

                    //timeline start
                    $timeline_url = Util::getTimelineUrlgetTimelineUrl($topic->topic_num, $topic->topic_name, $campModel->camp_num, $campModel->camp_name, $topic->topic_name, "direct_support_removed", null, $topic->namespace_id, $topic->submitter_nick_id);

                    Util::dispatchTimelineJob($topic->topic_num, $campModel->camp_num, 1, $nickName . " removed direct support from camp - ". $campModel->camp_name, "direct_support_removed", $campModel->camp_num, null, null, null, $asOfDefaultDate + 1, $timeline_url);
                    //timeline end

                    $parentcamps = Camp::getAllParent($campModel);
                    $existParentSupports = Support::where('topic_num', $topicNum)->whereIn('camp_num', $parentcamps)->whereIn('nick_name_id', $allNickNames)->where('end', '=', 0)->orderBy('support_order', 'ASC')->get();
                    $sendRemoveEmail = (count($existParentSupports)) ? $existParentSupports : false;
                    if($sendRemoveEmail){
                        self::supportRemovalEmail($topicModel, $campModel, $nicknameModel);
                    }
                    // GetPushNotificationToSupporter::pushNotificationToSupporter($user,$topicNum, $camp, 'remove', null, $nickName);
                }
                //log activity
                self::logActivityForRemoveCamps($removeCamps, $topicNum, $nickNameId, '', $reason, $reason_summary, $citation_link);
                
            }catch (Throwable $e) 
            {
                DB::rollback();
                throw new Exception($e->getMessage());
            }
        }

         $supportToAdd = [];
         if(isset($addCamp) && !empty($addCamp)){
            try
            {
                DB::beginTransaction();
                $campNum = $addCamp['camp_num'];
                $supportOrder = $addCamp['support_order'];

                $campFilter = ['topicNum' => $topicNum, 'campNum' => $campNum];
                $campModel  = self::getLiveCamp($campFilter);
                $delegatedNickNameId=0;
                $support = self::addSupport($topicNum, $campNum, $supportOrder, $nickNameId,$delegatedNickNameId, $reason,$reason_summary,$citation_link);
                array_push($supportToAdd, $support);
                if(count($allDelegates)) { 
                    self::insertDelegateSupport($allDelegates, $supportToAdd);
                }

                DB::commit();
                /* To update the Mongo Tree while adding support */
                Util::dispatchJob($topic, $campNum, 1);

                //timeline start
                $timeline_url = Util::getTimelineUrlgetTimelineUrl($topic->topic_num, $topic->topic_name, $campModel->camp_num, $campModel->camp_name, $topic->topic_name, "direct_support_added", null, $topic->namespace_id, $topic->submitter_nick_id);

                Util::dispatchTimelineJob($topic->topic_num, $campModel->camp_num, 1, $nickName . " added direct support in camp - " . $campModel->camp_name, "direct_support_added", $campModel->camp_num, null, null, null, $asOfDefaultDate + 1, $timeline_url);
                //timeline start

                $subjectStatement = "has added their support to "; 
                self::SendEmailToSubscribersAndSupporters($topicNum, $campNum, $nickNameId, $subjectStatement, 'add');
                //GetPushNotificationToSupporter::pushNotificationToSupporter($user,$topicNum, $campNum, 'add', null, $nickName);
                //log activity
                self::logActivityForAddSupport($topicNum, $campNum, $nickNameId, null, $reason, $reason_summary, $citation_link);

            }catch (Throwable $e) 
            {
                DB::rollback();
                throw new Exception($e->getMessage());
            }            

        }


 
        if(isset($orderUpdate) && !empty($orderUpdate)){
            try
            {
                DB::beginTransaction();
                $isOrderUpdated = false;
                $previousSupports = self::getTopicSupportByNickName($topicNum, $allNickNames);
                foreach ($previousSupports as $singleSupport) {
                    foreach ($orderUpdate as $item) {
                        if ($item['camp_num'] == $singleSupport->camp_num) {
                            if ($item['order'] != $singleSupport->support_order) {
                                $isOrderUpdated = true;
                            }
                            break;
                        }
                    }
                    if ($isOrderUpdated) {
                        break;
                    }
                }
                self::reorderSupport($orderUpdate, $topicNum, $allNickNames,$reason,$reason_summary,$citation_link);

                DB::commit();

                $topic = Topic::where('topic_num', $topicNum)->orderBy('id','DESC')->first();
                foreach($orderUpdate as $key => $order) {
                    // Execute job to queue the updated tree
                    Util::dispatchJob($topic, $order['camp_num'], 1);
                    //adding code for topic timeline reorder process
                    $campFilter = ['topicNum' => $topicNum, 'campNum' => $order['camp_num']];
                    $campModel  = self::getLiveCamp($campFilter); 

                    //timeline start
                    //$timeline_url = Util::getTimelineUrlgetTimelineUrl($topic->topic_num, $topic->topic_name, $campModel->camp_num, $campModel->camp_name, $topic->topic_name, "delegated_support_added", null, $topic->namespace_id, $topic->submitter_nick_id);

                    //Util::dispatchTimelineJob($topic->topic_num, $campModel->camp_num, 1, $nickName . " has changed the order preference of camp - " . $campModel->camp_name, "reorder_support", $campModel->id, null, null, null, $asOfDefaultDate + 1, $timeline_url);
                    //timeline end
                    // #917 : When adding support remove activity log for support order update
                    if (empty($addCamp)) {
                        if ($isOrderUpdated) {
                            self::logActivityForUpdateSupport($topicNum, $order['camp_num'], $nickNameId, $reason, $reason_summary, $citation_link);
                        } elseif (!empty($reason) || !empty($reason_summary) || !empty($citation_link)) {
                            self::logActivityForUpdateSupportReason($topicNum, $order['camp_num'], $nickNameId, $reason, $reason_summary, $citation_link);
                        }
                    }
                }
            }catch (Throwable $e) 
            {
                DB::rollback();
                throw new Exception($e->getMessage());
            }
        }
    }

    public static function getTopicSupportByNickName($topicNum, $allNickNames)
    {
        return Support::where('topic_num', $topicNum)
            ->whereIn('nick_name_id', $allNickNames)
            ->where('end', 0)
            ->orderBy('support_order', 'asc')->get();
    }


    /**
     * [Add deleagte support]
     * 
     */
    public static function addDelegateSupport($user,$topicNum, $campNum, $nickNameId, $delegateNickNameId)
    { 

        try{
            DB::beginTransaction();
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

            $inReviewChanges = Camp::getNominatedCampLeaderInReviewChanges($topicNum, $campNum, $nickNameId);
            foreach ($inReviewChanges as $camp) {
                $camp->objector_nick_id = 0;
                $camp->object_reason = trans('message.camp_leader.error.system_generated.nominated_user_removes_support');
                $camp->object_time = time();
                $camp->save();
            }

            // Remove user as camp leader if delegate support to someone
            $camp_leader = Camp::getCampLeaderNickId($topicNum, $campNum);
            if (!is_null($camp_leader) && $camp_leader == $nickNameId) {
                $oldest_direct_supporter = self::findOldestDirectSupporter($topicNum, $campNum, $nickNameId);
                Camp::updateCampLeaderFromLiveCamp($topicNum, $campNum, $oldest_direct_supporter->nick_name_id ?? null);
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
            $delegate_nick_name = '';
            $delegateNickNameIdModel = Nickname::getNickName($delegateNickNameId);
            if (!empty($delegateNickNameIdModel)) {
                $delegate_nick_name = $delegateNickNameIdModel->nick_name;
            }

            self::insertDelegateSupport($delegateSupporters, $supportToAdd);  

            DB::commit();
            
            /* To update the Mongo Tree while delegating at add support*/
            $topic = Topic::where('topic_num', $topicNum)->orderBy('id','DESC')->first();
            if(!empty($campNum)) {
                Util::dispatchJob($topic, $campNum, 1);
            } else {
                Util::dispatchJob($topic, 1, 1);
            }

            //timeline start
            $campFilter = ['topicNum' => $topicNum, 'campNum' => $campNum];
            $camp  = self::getLiveCamp($campFilter);

            $timeline_url = Util::getTimelineUrlgetTimelineUrl($topic->topic_num, $topic->topic_name, $camp->camp_num, $camp->camp_name, $topic->topic_name, "delegated_support_added", null, $topic->namespace_id, $topic->submitter_nick_id);

            Util::dispatchTimelineJob($topic->topic_num, $camp->camp_num, 1, $nickName . " has just delegated their support to " . $delegate_nick_name, "delegated_support_added", $camp->camp_num, null, null, null, time(), $timeline_url);
            //timeline start
        
            $subjectStatement = "has just delegated their support to";
            self::SendEmailToSubscribersAndSupporters($topicNum, $campNum, $nickNameId, $subjectStatement, config('global.notification_type.addDelegate'), $delegateNickNameId);

            GetPushNotificationToSupporter::pushNotificationToSupporter($user,$topicNum, $campNum, 'add-delegate', null, $nickName,$delegateNickNameId);
            GetPushNotificationToSupporter::pushNotificationToDelegater($topicNum, $campNum, $nickNameId, $delegateNickNameId);

            if($supportToAdd[0]->delegate_nick_name_id)  // if  delegated user is a delegated supporter itself, then notify
            {
                $notifyDelegatedUser = true;
            }

            self::notifyDelegatorAndDelegateduser($topicNum, $campNum, $nickNameId, 'add', $delegateNickNameId, $notifyDelegatedUser);

            // log activity
            self::logActivityForAddSupport($topicNum, $campNum, $nickNameId, $delegateNickNameId);       
            

            
        }catch (Throwable $e) 
        {
            DB::rollback();
            throw new Exception($e->getMessage());
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
        
        $object = $topic->topic_name ." >> ".$camp->camp_name;
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
        $data['support_link'] = util::getSupportLink($seoUrlPortion);

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
                throw new Exception($e->getMessage());
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
    public static function removeSupport($topicNum, $campNum = array(), $allNickNames = array(), $reason = null,$reason_summary = null,$citation_link = null)
    {
        $delegators = Support::getActiveDelegators($topicNum, $allNickNames);
        Support::removeSupportWithAllNicknames($topicNum, $campNum, $allNickNames,$reason, $reason_summary, $citation_link);

        if(count($delegators))
        { 
            foreach($delegators as $delegator)
            {
                $delegatorsNickArray = self::getAllNickNamesOfNickID($delegator->nick_name_id);
                self::removeSupport($topicNum, $campNum, $delegatorsNickArray, $reason, $reason_summary, $citation_link);
            }
        }

        return; 
    }

    /** 
     * [Re-order support]
     */
    public static function reorderSupport($orders, $topicNum, $allNickNames,$reason,$reason_summary,$citation_link)
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
                ->update([
                    'support_order' => $order['order'],  // update your field(s) here
                    'reason'=> $reason,
                    'reason_summary'=> $reason_summary,
                    'citation_link'=> $citation_link
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
                    return self::reorderSupport($orders, $topicNum, $delegatorsNickArray,$reason,$reason_summary,$citation_link);
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
    public static function supportRemovalEmail($topic, $camp, $nickname, $delegateNickNameId='', $notifyDelegatedUser = false, $previousCampLeaderNickId = null)
    {
        if(isset($delegateNickNameId) && !empty($delegateNickNameId))
        {
            $subjectStatement = "has removed their delegated support from";
             /** Notify removing supporter through email and notification */
            self::notifyDelegatorAndDelegateduser($topic->topic_num, $camp->camp_num, $nickname->id, 'remove', $delegateNickNameId, $notifyDelegatedUser);
        
        }else{
            $subjectStatement = "has removed their support from";
        }

        self::SendEmailToSubscribersAndSupporters($topic->topic_num, $camp->camp_num, $nickname->id, $subjectStatement, config('global.notification_type.removeSupport'), $delegateNickNameId, $previousCampLeaderNickId);
        return;
    }


    /**
     * Send email to promoted delegates as a direct supporter of topic and camps 
     * @param array $data [is mail data]
     * @return void
     */
    public static function SendEmailToSubscribersAndSupporters($topicNum, $campNum, $nickNameId, $subjectStatement, $action = "add", $delegatedNickNameId ='', $previousCampLeaderNickId = null)
    {
        $topicFilter = ['topicNum' => $topicNum];
        $campFilter = ['topicNum' => $topicNum, 'campNum' => $campNum];
        $topic = Camp::getAgreementTopic($topicFilter);
        $camp  = self::getLiveCamp($campFilter);
        $nickname =  Nickname::getNickName($nickNameId);
        $subject = (isset($delegatedNickNameId) && $delegatedNickNameId) ? Nickname::getNickName($delegatedNickNameId)->nick_name : $topic->topic_name ." >> ".$camp->camp_name;
        $object = (isset($delegatedNickNameId) && $delegatedNickNameId) ? $topic->topic_name : Helpers::renderParentCampLinks($topic->topic_num, $camp->camp_num, $topic->topic_name, true, 'camp');
        $topicLink =  self::getTopicLink($topic);
        $campLink = self::getCampLink($topic,$camp);
        $seoUrlPortion = Util::getSeoBasedUrlPortion($topicNum, $campNum, $topic, $camp);
        $data['object']     = $object;
        $data['subject']    = $nickname->nick_name . " ". $subjectStatement . " " . $subject. ".";
        $data['topic']      = $topic;
        $data['camp']       = $camp;
        $data['camp_name']  = $camp->camp_name;
        $data['topic_name'] = $topic->topic_name;
        $data['topic_num']  = $topic->topic_num;
        $data['camp_num']   = $camp->camp_num;
        $data['topic_link'] = $topicLink;
        $data['camp_link']  = $campLink;   
        $data['camp_url']   = $campLink;
        $data['url_portion'] =  $seoUrlPortion;
        $data['nick_name_id'] = $nickname->id;
        $data['nick_name'] = $nickname->nick_name;
        $data['previous_camp_leader_nick_id'] = $previousCampLeaderNickId;
        $data['namespace_id'] = isset($topic->namespace_id) ? $topic->namespace_id : 1;
        $data['nick_name_link'] = Nickname::getNickNameLink($data['nick_name_id'], $data['namespace_id'], $data['topic_num'], $data['camp_num']);;
        $data['support_action'] = $action; //default will be 'added'     
        
        if(isset($delegatedNickNameId) && $delegatedNickNameId) {
            $data['delegated_nick_name_id'] = $delegatedNickNameId;
            $data['delegated_nick_name'] = Nickname::getNickName($delegatedNickNameId)->nick_name;
            $data['delegated_nick_name_link'] = Nickname::getNickNameLink($data['delegated_nick_name_id'], $data['namespace_id'], $data['topic_num'], $data['camp_num']);
        } 

        $notificationData = [
            "email" => [],
            "push_notification" => []
        ];

        $liveThread = null;
        $threadId = null;
        $link = null;
        $getMessageData = GetPushNotificationToSupporter::getMessageData(Auth::user(), $topic, $camp, $liveThread, $threadId, $action, $nickname->nick_name, null);

        $notificationData['email'] = $data;

        if (!empty($getMessageData)) {
            $notificationData['push_notification'] = [
                "topic_num" => $camp->topic_num,
                "camp_num" => $camp->camp_num,
                "notification_type" => $getMessageData->notification_type,
                "title" => $getMessageData->title,
                "message_body" => $getMessageData->message_body,
                "link" => $getMessageData->link,
                "thread_id" => !empty($threadId) ? $threadId : null,
            ];
        }
        $channel = config('global.notify.both');
        Event::dispatch(new NotifySupportersEvent($camp, $notificationData, $action, $link, $channel));
        return true;
    }

    /** 
     * 
     */
    public static function checkSupportValidaionAndWarning($topicNum, $campNum, $nickNames, $delegataedNickNameId = 0)
    {
        $returnData = [];

        if($delegataedNickNameId)
        {
            $nickName = Nickname::getNickName($delegataedNickNameId);

            /**  case I - if try to delgate support to its own delegator supporter  */
            $DelegateSupport = Support::checkIfDelegateSupportExists($topicNum, $nickNames, $delegataedNickNameId);
            if($DelegateSupport)
            {    
                $warning =  $nickName->nick_name . " is already delegating support to you, you cannot delegate your support to this user";
                $returnData = self::getWarningToDisableSupport($topicNum, $campNum, $nickName, $warning, $delegataedNickNameId);           
                return $returnData;
            }

            /**  Case II - If try to delgate support to In-Active supporter, this may happen when user tru to make action at same time. */
            $support = Support::checkIfSupportExists($topicNum, [$delegataedNickNameId],[$campNum]); 
            if(empty($support))
            {
                $warning =  "You cannot delegate your support to the ". $nickName->nick_name." as the selected user is not an active supporter of this camp.";
                $returnData = self::getWarningToDisableSupport($topicNum, $campNum, $nickName, $warning, $delegataedNickNameId);  
                return $returnData;
            }

            /** Case III - switch support that can be submitted with warning messages*/
            $returnData = self::checkIfSupportSwitchToDirectToDelegate($topicNum, $campNum, $nickNames);
            if(!empty($returnData)){
                return $returnData;
            }

            /** Case IV - switch support from one delegate to another*/
            $returnData = self::checkIfSupportSwitchToAnotherDelegate($topicNum, $campNum, $nickNames);
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

        // check archive camp warning message
        $returnData = self::checkIfSupportSwitchToParent($topicNum, $campNum, $nickNames, true);
        if(!empty($returnData)){ 
            return $returnData;
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
    public static function checkIfSupportSwitchToParent($topicNum, $campNum, $nickNames, $checkArchive =  false)
    {
        $returnData = [];
        $as_of_time = time();
        $childSupport = Camp::validateChildsupport($topicNum, $campNum, $nickNames, $checkArchive);

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
                    if($checkArchive){
                        $returnData['archive_support_end'] = 1;
                        $returnData['warning'] =  '"'.$onecamp->camp_name .'" is a parent camp to the archived child camp. So if you commit your support to "'.$onecamp->camp_name .'", the support of archived child camp will be removed permanently.';
                    }else
                        $returnData['warning'] =  '"'.$onecamp->camp_name .'" is a parent camp to "'. $childCampName. '", so if you commit support to "'.$onecamp->camp_name .'", the support of the child camp "'. $childCampName. '" will be removed.';

                }
            } else {
                $returnData['is_confirm'] = 1;    
                if($checkArchive){
                    $returnData['archive_support_end'] = 1;
                    $returnData['warning'] =  '"'.$onecamp->camp_name .'" is a parent camp to this list of archived child camps. So if you commit your support to "'.$onecamp->camp_name .'", the support of archived camps will be removed permanently.';
                }else
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
    public static function addSupport($topicNum, $campNum, $supportOrder, $nickNameId, $delegatedNickNameId = 0,$reason,$reason_summary,$citation_link)
    {
        $support = new Support();
        $support->topic_num = $topicNum;
        $support->nick_name_id = $nickNameId;
        $support->delegate_nick_name_id = $delegatedNickNameId;
        $support->camp_num = $campNum;
        $support->support_order = $supportOrder;
        $support->start = time();
        $support->reason = $reason;
        $support->reason_summary = $reason_summary;
        $support->citation_link = $citation_link;
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
    public static function logActivity($logType, $activity, $link, $model, $topicNum, $campNum, $user, $nickName, $description, $reason = null ,$reason_summary = null,$citation_link = null)
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
            'description' => $description,
            'reason' => $reason,
            'reason_summary' => $reason_summary,
            'citation_link' => $citation_link
        ];

        dispatch(new ActivityLoggerJob($activitLogData))->onQueue(env('ACTIVITY_LOG_QUEUE'));
    }

    /**
     * [activity logger on remove support]
     * @param array $removeCamps are list of camps to be removed
     * @param integer $topicNum is topic number
     * @param integer $nickNameId is nick name id of user removing support
     * 
     * @return void
     */
    public static function logActivityForRemoveCamps($removeCamps, $topicNum, $nickNameId, $delegateNickNameId = '', $reason = null,$reason_summary = null,$citation_link = null)
    {
        if(!empty($removeCamps) || !empty($delegateNickNameId))
        {         
            $nicknameModel = Nickname::getNickName($nickNameId);
            $topicFilter = ['topicNum' => $topicNum];
            $topicModel = Camp::getAgreementTopic($topicFilter); 
            $user = Nickname::getUserByNickName($nickNameId);

            $logType = "support";
            $activity =  trans('message.activity_log_message.support_removed', ['nick_name' => $nicknameModel->nick_name]);
            $model = new Support();
            $description = trans('message.general.support_removed');
           

            foreach($removeCamps as $camp)
            {
                $campFilter = ['topicNum' => $topicNum, 'campNum' => $camp];
                $campModel  = self::getLiveCamp($campFilter); 
                $link = Util::getTopicCampUrl($topicNum, $camp, $topicModel, $campModel);

                if(!empty($delegateNickNameId)){
                    $delegatedTo = Nickname::getNickName($delegateNickNameId);
                    $topic = $topicModel->title . "/" . $campModel->camp_name;
                    $activity = trans('message.activity_log_message.remove_delegated_support', ['nick_name' => $nicknameModel->nick_name, 'delegate_to' => $delegatedTo->nick_name, 'topic_name' => $topic]);
                    //$activity = "Delegated support removed from " . $delegatedTo->nick_name . " under topic - " . $topicModel->title . "/" . $campModel->camp_name; 
                }

                self::logActivity($logType, $activity, $link, $model, $topicNum, $camp, $user, $nicknameModel->nick_name, $description, $reason,$reason_summary,$citation_link);
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
    public static function logActivityForAddSupport($topicNum, $campNum, $nickNameId, $delegateNickNameId = '', $reason = null, $reason_summary = null, $citation_link = null)
    {
        if($campNum){ 
            $nicknameModel = Nickname::getNickName($nickNameId);
            $topicFilter = ['topicNum' => $topicNum];
            $topicModel = Camp::getAgreementTopic($topicFilter);
            $user = Nickname::getUserByNickName($nickNameId);

            $campFilter = ['topicNum' => $topicNum, 'campNum' => $campNum];
            $campModel  = self::getLiveCamp($campFilter);

            $logType = "support";
            $activity = trans('message.activity_log_message.support_added', ['nick_name' => $nicknameModel->nick_name]);
            $link = Util::getTopicCampUrl($topicNum, $campNum, $topicModel, $campModel);
            $model = new Support();
            $description = trans('message.general.support_added');

            if(!empty($delegateNickNameId)){
                $delegatedTo = Nickname::getNickName($delegateNickNameId);
                $activity = trans('message.activity_log_message.delegate_support', ['nick_name' => $nicknameModel->nick_name, 'delegate_to' => $delegatedTo->nick_name]);
                $description = trans('message.general.support_delegated');
            }

            return self::logActivity($logType, $activity, $link, $model, $topicNum, $campNum, $user, $nicknameModel->nick_name, $description, $reason, $reason_summary, $citation_link);
        }
        
        return;
    }

    /**
     * [activity logger on update support order]
     * @param integer $campNum is camp number to which support is added
     * @param integer $topicNum is topic number
     * @param integer $nickNameId is nick name id of user adding support
     * 
     * @return void
     */
    public static function logActivityForUpdateSupport($topicNum, $campNum, $nickNameId, $reason = null, $reason_summary = null, $citation_link = null)
    {
        if($campNum){ 
            $nicknameModel = Nickname::getNickName($nickNameId);
            $topicFilter = ['topicNum' => $topicNum];
            $topicModel = Camp::getAgreementTopic($topicFilter);
            $user = Nickname::getUserByNickName($nickNameId);

            $campFilter = ['topicNum' => $topicNum, 'campNum' => $campNum];
            $campModel  = self::getLiveCamp($campFilter);

            $logType = "support";
            $activity = trans('message.activity_log_message.support_order_updated', ['nick_name' => $nicknameModel->nick_name]);
            $link = Util::getTopicCampUrl($topicNum, $campNum, $topicModel, $campModel);
            $model = new Support();
            $description = trans('message.general.support_order_updated');

            return self::logActivity($logType, $activity, $link, $model, $topicNum, $campNum, $user, $nicknameModel->nick_name, $description, $reason, $reason_summary, $citation_link);
        }
        
        return;
    }

    /**
     * [activity logger on update support reason]
     * @param integer $campNum is camp number to which support is added
     * @param integer $topicNum is topic number
     * @param integer $nickNameId is nick name id of user adding support
     * 
     * @return void
     */
    public static function logActivityForUpdateSupportReason($topicNum, $campNum, $nickNameId, $reason = null, $reason_summary = null, $citation_link = null)
    {
        if($campNum){ 
            $nicknameModel = Nickname::getNickName($nickNameId);
            $topicFilter = ['topicNum' => $topicNum];
            $topicModel = Camp::getAgreementTopic($topicFilter);
            $user = Nickname::getUserByNickName($nickNameId);

            $campFilter = ['topicNum' => $topicNum, 'campNum' => $campNum];
            $campModel  = self::getLiveCamp($campFilter);

            $logType = "support";
            $activity = trans('message.activity_log_message.support_reason_updated', ['nick_name' => $nicknameModel->nick_name]);
            $link = Util::getTopicCampUrl($topicNum, $campNum, $topicModel, $campModel);
            $model = new Support();
            $description = trans('message.general.support_reason_updated');

            return self::logActivity($logType, $activity, $link, $model, $topicNum, $campNum, $user, $nicknameModel->nick_name, $description, $reason, $reason_summary, $citation_link);
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

        $object = $topic->topic_name ." >> ".$camp->camp_name;
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
            $user = Nickname::getUserByNickName($nickNameId);
            if($action == 'add'){
                Event::dispatch(new NotifyDelegatedAndDelegatorMailEvent($user->email ?? null, $user, $data));

                if(isset($notifyDelegatedUser) && $notifyDelegatedUser){
                    $data['notify_delegated_user'] = $notifyDelegatedUser;
                    $data['subject']    = $nickname->nick_name . " has just delegated their support to you.";                    
                    $delegatedUser = Nickname::getUserByNickName($delegatedNickNameId);                    
                    Event::dispatch(new NotifyDelegatedAndDelegatorMailEvent($delegatedUser->email ?? null, $delegatedUser, $data));
                }
            }else{               
                $data['subject']    = "You have removed your delegated support from " . $delegatedToNickname->nick_name . " in " . $data['object'];
                Event::dispatch(new NotifyDelegatedAndDelegatorMailEvent($user->email ?? null, $user, $data));

                if(isset($notifyDelegatedUser) && $notifyDelegatedUser){
                    $data['notify_delegated_user'] = $notifyDelegatedUser;
                    $data['subject']    = $nickname->nick_name . " has removed their delegated support from you in " . $data['object'];                    
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

    /**
     * 
     */
    public static function getWarningToDisableSupport($topicNum, $campNum, $nickName, $warning, $delegataedNickNameId, $isDelegator = 0, $disableSubmit = 1, $isConfirm = 1, $removeCamps = [])
    {
        $returnData['warning'] =  $warning;
        $returnData['is_delegator'] = $isDelegator;
        $returnData['topic_num'] = $topicNum;
        $returnData['camp_num'] = $campNum;
        $returnData['delegated_nick_name_id'] = $nickName->id;
        $returnData['is_confirm'] = $isConfirm;
        $returnData['disable_submit'] = $disableSubmit;
        $returnData['nick_name_link'] = Nickname::getNickNameLink($delegataedNickNameId, '1', $topicNum, $campNum);
        $returnData['remove_camps'] = $removeCamps;

        return $returnData;
    }

    /**
     *  check is supported is a delegate supporter and switching to another delegate
     *  @return $returnData with warning messages
     */
    public static function checkIfSupportSwitchToAnotherDelegate($topicNum, $campNum, $nickNames)
    {
        $returnData = [];
        $supportedCamps = [];
        $supports = Support::getActiveSupporInTopicWithAllNicknames($topicNum, $nickNames);
        $liveTopic = Topic::getLiveTopic($topicNum,['nofilter'=>true]);
        $campsToemoved = [];

        if (count($supports) && $supports[0]->delegate_nick_name_id) {
            $delegataedNickNameId = $supports[0]->delegate_nick_name_id;
            $alreadyDelegatedTo = NickName::getNickName($delegataedNickNameId);
            foreach($supports as $support){
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

            $returnData['warning'] = "You have already delegated your support for this camp to user " . $alreadyDelegatedTo->nick_name . ". If you continue your delegated support will be removed.";
            $returnData['topic_num'] = $topicNum;
            $returnData['camp_num'] = $campNum;
            $returnData['is_confirm'] = 1;
            $returnData['is_delegator'] = 1;
            $returnData['remove_camps'] = $campsToemoved;
            $returnData['delegated_nick_name_id'] = $delegataedNickNameId;
            $returnData['nick_name_link'] = Nickname::getNickNameLink($delegataedNickNameId, '1', $topicNum, $campNum);
        }

        return $returnData;
    }

    public static function checkSignValidaionAndWarning($topic_num, $camp_num, $nickNames)
    {
        $livecamp = Camp::getLiveCamp(['topicNum' => $topic_num, 'campNum' => $camp_num]);
        $directSupporters = collect(Support::getDirectSupporter($topic_num, $camp_num));

        /**
         * Case 1: If there not any direct supporter.
         * Tooltip messages from: https://github.com/the-canonizer/Canonizer-Beta--Issue-Tracking/issues/1020
         */
        if ($directSupporters->count() === 0) {
            $warning = "There is no direct supporter for camp <b>" . $livecamp->camp_name . "</b>. You will be appointed as Camp Leader and your support will also be added to the camp, if you continue.";
            return self::checkSignCampInfo($topic_num, $camp_num, $warning, 'info', null);
        }

        /**
         * Case 2: If there is only one direct supporter and also camp leader.
         */
        if ($directSupporters->count() === 1 && in_array($livecamp->camp_leader_nick_id, $nickNames)) {
            $warning = "As you are the current Camp Leader, hence you cannot sign the petition.";
            return self::checkSignCampInfo($topic_num, $camp_num, $warning, 'warning', $livecamp->camp_leader_nick_id);
        }

        $userSignedCamp = self::getUserSignedCamp($topic_num, $camp_num, $nickNames);
        /**
         * Case 3: If there are one or more than one direct supporters. And camp leader exists
         */
        if ($directSupporters->count() > 0 && !in_array($livecamp->camp_leader_nick_id, $nickNames) && !is_null($livecamp->camp_leader_nick_id) && count($userSignedCamp) === 0) {
            $nickName = Nickname::getNickName($livecamp->camp_leader_nick_id)->nick_name;
            $warning = "<b>" . $nickName . "</b> is the Leader of this camp. By continuing, your support will be delegated to this user.";
            return self::checkSignCampInfo($topic_num, $camp_num, $warning, 'info', null);
        }

        /**
         * Case 4: If there are one or more than one direct supporters, but no camp leader.
         */
        if ($directSupporters->count() > 0 && !in_array($livecamp->camp_leader_nick_id, $nickNames) && is_null($livecamp->camp_leader_nick_id)) {
            $oldest_direct_supporter = self::findOldestDirectSupporter($topic_num, $camp_num);
            if ($oldest_direct_supporter) {
                $nickName = Nickname::getNickName($oldest_direct_supporter->nick_name_id)->nick_name;
                $oldestDirectSupportMessage = in_array($oldest_direct_supporter->nick_name_id, $nickNames) ? 'you will be added as direct supporter and assigned as camp leader.'
                : "the oldest direct supporter (\"" . $nickName . "\") will be assigned as new camp leader and your support will be delegated to the camp leader.";
                $warning = "There is no camp leader of this camp <b>" . $livecamp->camp_name . "</b>. If you continue, " . $oldestDirectSupportMessage;
                $warning = "<span><b>Info!</b> " . $warning . "</span>";
                return self::checkSignCampInfo($topic_num, $camp_num, $warning, 'info', null);
            }
        }

        /**
         * Case 5: If User is already signed another camp
         */
        return $userSignedCamp;
    }

    public static function getUserSignedCamp($topic_num, $camp_num, $nickNames)
    {
        $returnData = [];
        $supports = Support::getDelgatedSupportInTopic($topic_num, $nickNames);
        $liveTopic = Topic::getLiveTopic($topic_num, ['nofilter' => true]);
        $camp_leader = Camp::getCampLeaderNickId($topic_num, $camp_num);
        $campsToRemove = [];
        if (count($supports) && $supports[0]->delegate_nick_name_id) {
            foreach ($supports as $support) {
                $support_camp_leader = Camp::getCampLeaderNickId($topic_num, $support->camp_num);
                if ($support_camp_leader === $support->delegate_nick_name_id) {
                    $filter['topicNum'] = $topic_num;
                    $filter['asOf'] = '';
                    $filter['campNum'] =  $support->camp_num;
                    $livecamp = Camp::getLiveCamp($filter);
                    $temp = [
                        'camp_num' => $support->camp_num,
                        'support_order' => $support->support_order,
                        'camp_name' => $livecamp->camp_name,
                        'link' => Camp::campLink($topic_num, $support->camp_num, $liveTopic->topic_name, $livecamp->camp_name)
                    ];
                    array_push($campsToRemove, $temp);
                }
            }

            if (empty($campsToRemove)) {
                return [];
            }
            $returnData['warning'] = "You have already signed following camps. If you continue, your support will be removed from following camps and delegated to the camp leader of this camp.";
            $returnData['warning_type'] = "warning";
            $returnData['topic_num'] = $topic_num;
            $returnData['camp_num'] = $camp_num;
            $returnData['remove_camps'] = $campsToRemove;
            $returnData['delegated_nick_name_id'] = $camp_leader;
            $returnData['nick_name_link'] = Nickname::getNickNameLink($camp_leader, '1', $topic_num, $camp_num);
        }

        return $returnData;
    }

    public static function checkSignCampInfo($topic_num, $camp_num, $warning, $warning_type = 'info', $delegated_nick_name_id = null, $remove_camps = []) 
    {
        
        $warning = "<span><b>" . ucfirst($warning_type) . "!</b> " . $warning . "</span>";

        return [
            'warning' => $warning,
            'topic_num' => $topic_num,
            'camp_num' => $camp_num,
            'delegated_nick_name_id' => $delegated_nick_name_id,
            'nick_name_link' => is_null($delegated_nick_name_id) ? Nickname::getNickNameLink($delegated_nick_name_id, '1', $topic_num, $camp_num) : null,
            'remove_camps' => $remove_camps,
            'warning_type' => $warning_type,
        ];
    }

    public static function signPetition(User $user, int $topic_num, int $camp_num, int $nick_name_id)
    {
        // Ticket: https://github.com/the-canonizer/canonizer-3.0-api/issues/862
        $direct_supporters = Support::getDirectSupporter($topic_num, $camp_num, ['start', 'end']);

        /**
         * Case 1: If camp leader exists then delegate support to camp leader.
         */
        $camp_leader_nick_id = Camp::getCampLeaderNickId($topic_num, $camp_num);
        if (!is_null($camp_leader_nick_id) && count($direct_supporters) > 0) {
            if ($camp_leader_nick_id !== $nick_name_id) {
                // Checking if user already signed the camp
                $support =  Support::checkIfDelegateSupportExists($topic_num, [$camp_leader_nick_id], $nick_name_id);
                if(!$support) {
                    TopicSupport::addDelegateSupport($user, $topic_num, $camp_num, $nick_name_id, $camp_leader_nick_id);
                } else {
                    throw new Exception(trans('message.camp_leader.error.already_signed_camp'));
                }
            } else {
                throw new Exception(trans('message.camp_leader.error.cannot_delegate_itslef'));
            }
            return;
        }

        /**
         * Case 2: If there are one or more direct supports but there is no camp leader then delegate support to the oldest direct supporter and set as camp leader.
         */
        $oldest_direct_supporter = TopicSupport::findOldestSupporterAndMakeCampLeader($topic_num, $camp_num, null);
        if ($oldest_direct_supporter) {
            // Delegate user support to oldest direct supporter
            if ($oldest_direct_supporter->nick_name_id != $nick_name_id) { // Cannot delegate support to itself
                TopicSupport::addDelegateSupport($user, $topic_num, $camp_num, $nick_name_id, $oldest_direct_supporter->nick_name_id);
            }
            return;
        }
        
        /**
         * Case 3: If there are no supporters of the camp then add user as a direct supporter and make it a camp leader.
         */
        $nickNames = Nickname::getNicknamesIdsByUserId($user->id);
        // Getting camps to remove support if User sign child camp of any parent, so the support will be transfered from parent to child. 
        $removeCamps = TopicSupport::checkSupportValidaionAndWarning($topic_num, $camp_num, $nickNames, 0);
        if (count($removeCamps) > 0) {
            $removeCamps = collect($removeCamps['remove_camps'])->pluck('camp_num')->toArray();
        }

        // Getting & calculating User's support order
        $supportList = collect(Support::getSupportedCampsList($topic_num, $user->id))->transform(function ($item) use ($removeCamps) {
            if (!in_array($item['camp_num'], $removeCamps)) {
                return [
                    "camp_num" => $item['camp_num'],
                    "order" => $item['support_order']
                ];
            }
        });
        $maxSupportOrder = ($supportList->max('order') ?? 0) + 1;
        $supportList[] = ['camp_num' => (int)$camp_num, "order" => (int)$maxSupportOrder];
        $supportList = array_values(array_filter($supportList->all(), function ($value) {
            return !is_null($value) && $value !== '';
        }));

        TopicSupport::addDirectSupport($topic_num, $nick_name_id, ['camp_num' => $camp_num, "support_order" => $maxSupportOrder], $user, $removeCamps, $supportList);

        // Submits a new change from live camp to assign User as camp leader. Also, Log it
        Camp::updateCampLeaderFromLiveCamp($topic_num, $camp_num, $nick_name_id);
    }

    public static function findOldestSupporterAndMakeCampLeader(int $topic_num, int $camp_num, ?int $nick_name_id)
    {
        // Submits a new change from live camp to assign User as camp leader. Also, Log it
        $oldest_direct_supporter = self::findOldestDirectSupporter($topic_num, $camp_num, $nick_name_id, true);
        if ($oldest_direct_supporter) {
            Camp::updateCampLeaderFromLiveCamp($topic_num, $camp_num, $oldest_direct_supporter->nick_name_id);
        } else {
            Camp::updateCampLeaderFromLiveCamp($topic_num, $camp_num, null);
        }
        return $oldest_direct_supporter;
    }

    public static function findOldestDirectSupporter(int $topic_num, int $camp_num, ?int $nick_name_id = null, bool $includeThisNickName = false, bool $checkSupportOrder = false)
    {
        $support = Support::where([
            ['topic_num', '=', $topic_num],
            ['camp_num', '=', $camp_num],
            ['nick_name_id', '=', $nick_name_id],
        ])->orderBy('support_id', 'desc')->first();

        /**
         * Case - If nickname is a supporter at the time of sign, get all the direct supporters before user and make oldest one as camp leader
         * Case - If nickname is not a supporter at the time of sign or null, get all the direct supporters and make oldest one as camp leader
         */
        return Support::where([
            ['topic_num', '=', $topic_num],
            ['camp_num', '=', $camp_num],
            ['end', '=', 0],
        ])
        ->when($checkSupportOrder, fn ($query) => $query->where('support_order', 1))
        ->when(
            $nick_name_id && $support,
            fn ($query) => $query->where('start', $includeThisNickName ? '<=' : '<', fn ($query) => $query->select('start')
                ->from((new Support())->getTable())
                ->where('support_id', $support->support_id)->first()),
            fn ($query) => $query->where('start', $includeThisNickName ? '<=' : '<', time())
        )->orderBy('start')->first();
    }
}

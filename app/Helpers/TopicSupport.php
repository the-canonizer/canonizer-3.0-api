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

    
}

<?php

namespace App\Helpers;

use Throwable;
use App\Models\Camp;
use App\Models\User;
use App\Models\Topic;
use App\Models\Thread;
use App\Models\Support;
use App\Models\Nickname;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Events\NotifySupportersEvent;
use Illuminate\Support\Facades\Event;
use App\Events\CampForumPostMailEvent;
use App\Events\CampForumThreadMailEvent;
use App\Facades\GetPushNotificationToSupporter;

class CampForum
{

    /**
     * [sendEmailToSupporters description]
     * @param  [type] $topicid [description]
     * @param  [type] $campnum [description]
     * @return [type]          [description]
     */
    public static function sendEmailToSupportersForumThread($topicid, $campnum, $link, $thread_title, $nick_id, $topic_name_encoded)
    {
        $user='';
        $bcc_user = [];
        $sub_bcc_user = [];
        $userExist = [];
        $filter = [];
        $filter['topicNum'] = $topicid;
        $filter['asOf'] = '';
        $filter['campNum'] = $campnum;
        $camp = CampForum::getForumLiveCamp($filter);
        $topic = Topic::getLiveTopic($topicid, "");
        $topic_name = $topic->topic_name;
        $camp_name = $camp->camp_name;
        $subCampIds = CampForum::getForumAllChildCamps($camp);
        $data['camp_name'] = $camp_name;
        $data['nick_name'] = CampForum::getForumNickName($nick_id);
        $data['subject'] = $topic_name . " >> " . $data['camp_name'] . " >> " . $thread_title .
            " created";
        $data['namespace_id'] = $topic->namespace_id;
        $data['nick_name_id'] = $nick_id;
        $data['camp_url'] = Camp::campLink($topicid, $campnum, $topic_name, $data['camp_name']);
        $data['nickname_url'] = Nickname::getNickNameLink($data['nick_name_id'], $data['namespace_id'], $topicid, $campnum);
        $data['thread_title'] = $thread_title;
        $topic_name_space_id = isset($topic) ? $topic->namespace_id : 1;

        foreach ($subCampIds as $camp_id) {
            $directSupporter = CampForum::getDirectCampSupporter($topicid, $camp_id);
            $subscribers = Camp::getCampSubscribers($topicid, $camp_id);
            $i = 0;
            foreach ($directSupporter as $supporter) {
                $user = CampForum::getUserFromNickId($supporter->nick_name_id);
                $user_id = $user->id ?? null;
                $nickName = Nickname::find($supporter->nick_name_id);
                $supported_camp = $nickName->getSupportCampList($topic_name_space_id, ['nofilter' => true]);
                $supported_camp_list = $nickName->getSupportCampListNamesEmail($supported_camp, $topicid, $campnum);
                $support_list[$user_id] = $supported_camp_list;
                $ifalsoSubscriber = Camp::checkifSubscriber($subscribers, $user);
                if ($ifalsoSubscriber) {
                    $support_list_data = Camp::getSubscriptionList($user_id, $topicid, $campnum);
                    $supporter_and_subscriber[$user_id] = ['also_subscriber' => 1, 'sub_support_list' => $support_list_data];
                }
                $bcc_user[] = $user;
                $userExist[] = $user_id;
            }
            if ($subscribers && count($subscribers) > 0) {
                foreach ($subscribers as $sub) {
                    if (!in_array($sub, $userExist, true)) {
                        $userSub = User::find($sub);
                        $subscriptions_list = Camp::getSubscriptionList($userSub->id, $topicid, $campnum);
                        $subscribe_list[$userSub->id] = $subscriptions_list;
                        $sub_bcc_user[] = $userSub;
                    }
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
                    Event::dispatch(new CampForumThreadMailEvent($user->email ?? null, $user, $link, $data));
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
                    Event::dispatch(new CampForumThreadMailEvent($userSub->email ?? null, $userSub, $link, $data));
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
     * [notifySupportersForumThread description]
     * @param  [type] $topicid [description]
     * @param  [type] $campnum [description]
     * @return [type]          [description]
     */
    public static function notifySupportersForumThread($topicid, $campnum, $link, $thread_title, $nick_id, $topic_name_encoded, $threadId = null, $nickName,  $channel = 0)
    {
        $filter = [];
        $filter['topicNum'] = $topicid;
        $filter['asOf'] = '';
        $filter['campNum'] = $campnum;
        $camp = CampForum::getForumLiveCamp($filter);
        $topic = Topic::getLiveTopic($topicid, "");
        $topic_name = $topic->topic_name;
        $camp_name = $camp->camp_name;
        $notificationData = [
            "email" => [],
            "push_notification" => []
        ];

        $liveThread = !empty($threadId) ? Thread::find($threadId) : null;
        $getMessageData = GetPushNotificationToSupporter::getMessageData(Auth::user(), $topic, $camp, $liveThread, $threadId, config('global.notification_type.Thread'), $nickName, null);


        $notificationData['email'] = [
            "camp_name" => $camp_name,
            "nick_name" => CampForum::getForumNickName($nick_id),
            "subject"   => $topic_name . " >> " . $camp_name . " >> " . $thread_title . " created",
            "namespace_id" => $topic->namespace_id,
            "nick_name_id" => $nick_id,
            "camp_url" => Camp::campLink($topicid, $campnum, $topic_name, $camp_name),
            "nickname_url" => Nickname::getNickNameLink($nick_id, $topic->namespace_id, $topicid, $campnum),
            "thread_title" => $thread_title
        ];

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
        
        Event::dispatch(new NotifySupportersEvent($camp, $notificationData, config('global.notification_type.Thread'), $link, $channel));
        return true;
    }

    /**
     * [getForumLiveCamp description]
     * @param  [type] $topic_id [description]
     * @param  [type] $camp_num [description]
     * @return [type]           [description]
     */
    public static function getForumLiveCamp($filter)
    {
        return Camp::getLiveCamp($filter);
    }

    /**
     * [getForumAllChildCamps description]
     * @param  [type] $camp [description]
     * @return [type]       [description]
     */
    public static function getForumAllChildCamps($camp)
    {
        return array_unique(Camp::getAllChildCamps($camp));
    }

    /**
     * [getForumNickName description]
     * @param  [type] $nick_id [description]
     * @return [type]          [description]
     */
    public static function getForumNickName($nick_id)
    {
        return Nickname::getNickName($nick_id);
    }

    /**
     * [getDirectCampSupporter description]
     * @param  [type] $topicid [description]
     * @param  [type] $campnum [description]
     * @return [type]          [description]
     */
    public static function getDirectCampSupporter($topicid, $campnum)
    {
        return Support::getAllDirectSupporters($topicid, $campnum);
    }

    /**
     * [getUserNickName description]
     * @param  [type] $nick_id [description]
     * @return [type]          [description]
     */
    public static function getUserFromNickId($nick_id)
    {
        return Nickname::getUserByNickName($nick_id);
    }

    /**
     * [getReceiver description]
     * @param  [type] $user_email [description]
     * @return [type]             [description]
     */
    public static function getReceiver($user_email)
    {
        return (config('app.env') == "production" || config('app.env') == "staging") ? $user_email : config('app.admin_email');
    }

    /**
     * [getTopicName description]
     * @param  [type] $topicid [description]
     * @return [type]          [description]
     */
    public static function getTopicName($topicid)
    {
        return Topic::where('topic_num', $topicid)->where('objector_nick_id', '=', null)
            ->where('go_live_time', '<=', time())
            ->latest('submit_time')->first()->topic_name;
    }

    /**
     * [getCampName description]
     * @param  [type] $topicid [description]
     * @param  [type] $campnum [description]
     * @return [type]          [description]
     */
    public static function getCampName($topicid, $campnum, $asOf= "default")
    {
        if($asOf == 'review') {
            return Camp::where('camp_num', $campnum)->where('objector_nick_id', '=', null)->where('topic_num', $topicid)
                ->where('grace_period', 0)
                ->latest('go_live_time')->first()->camp_name;
        } else {
            return Camp::where('camp_num', $campnum)->where('objector_nick_id', '=', null)->where('topic_num', $topicid)
                ->where('go_live_time', '<=', time())
                ->latest('submit_time')->first()->camp_name;
        }
    }

    /**
     * [getNamespaceId description]
     * @param  [type] $topicid [description]
     * @return [type]          [description]
     */
    public static function getNamespaceId($topicid)
    {
        return Topic::where('topic_num', $topicid)->where('objector_nick_id', '=', null)
            ->where('go_live_time', '<=', time())
            ->latest('submit_time')->first()->namespace_id;
    }

    /**
     * [sendEmailToSupporters description]
     * @param  [type] $topicid  [description]
     * @param  [type] $campnum  [description]
     * @param  [type] $link     [description]
     * @param  [type] $post     [description]
     * @param  [type] $threadId [description]
     * @param  [type] $nick_id  [description]
     * @return [type]           [description]
     */

    public static function sendEmailToSupportersForumPost($topicid, $campnum, $link, $post, $threadId, $nick_id, $topic_name_encoded, $reply_id)
    {
        $user='';
        $userExist = [];
        $bcc_user = [];
        $supporter_and_subscriber = [];
        $sub_bcc_user = [];
        $support_list = [];
        $subscribe_list = [];

        $filter = [];
        $filter['topicNum'] = $topicid;
        $filter['asOf'] = '';
        $filter['campNum'] = $campnum;
        $camp = CampForum::getForumLiveCamp($filter);
        $topic = Topic::getLiveTopic($topicid, "");
        $subCampIds = CampForum::getForumAllChildCamps($camp);
        $topic_name = $topic->topic_name;
        $camp_name = $camp->camp_name;
        $post_msg = " submitted.";
        $data['post_type'] = " has made";
        if ($reply_id != "") {
            $post_msg = " updated.";
            $data['post_type'] = " has updated";
        }
        $data['post'] = $post;
        $data['camp_name'] = $camp_name;
        $data['thread'] = Thread::where('id', $threadId)->latest()->get();
        $data['subject'] = $topic_name . " >> " . $camp_name . " >> " . $data['thread'][0]->title . " post " . $post_msg;
        $data['namespace_id'] = $topic->namespace_id;
        $data['nick_name_id'] = $nick_id;
        $data['nickname_url'] = Nickname::getNickNameLink($data['nick_name_id'], $data['namespace_id'], $topicid, $campnum);

        $data['camp_url'] = Camp::campLink($topicid, $campnum, $topic_name, $camp_name);
        $data['nick_name'] = CampForum::getForumNickName($nick_id);
        $topic_name_space_id = isset($topic) ? $topic->namespace_id : 1;
        foreach ($subCampIds as $camp_id) {

            $directSupporter = CampForum::getDirectCampSupporter($topicid, $camp_id);
            $subscribers = Camp::getCampSubscribers($topicid, $camp_id);
            foreach ($directSupporter as $supporter) {
                $user = CampForum::getUserFromNickId($supporter->nick_name_id);
                $user_id = $user->id ?? null;
                $nickName = Nickname::find($supporter->nick_name_id);
                $supported_camp = $nickName->getSupportCampList($topic_name_space_id, ['nofilter' => true]);
                $supported_camp_list = $nickName->getSupportCampListNamesEmail($supported_camp, $topicid, $campnum);
                $support_list[$user_id] = $supported_camp_list;
                $ifalsoSubscriber = Camp::checkifSubscriber($subscribers, $user);
                if ($ifalsoSubscriber) {
                    $support_list_data = Camp::getSubscriptionList($user_id, $topicid, $campnum);
                    $supporter_and_subscriber[$user_id] = ['also_subscriber' => 1, 'sub_support_list' => $support_list_data];
                }
                $userExist[] = $user_id;
                $bcc_user[] = $user;
            }
            if ($subscribers && count($subscribers) > 0) {
                foreach ($subscribers as $sub) {
                    if (!in_array($sub, $userExist, true)) {
                        $userSub = User::find($sub);
                        $subscriptions_list = Camp::getSubscriptionList($userSub->id, $topicid, $campnum);
                        $subscribe_list[$userSub->id] = $subscriptions_list;
                        $sub_bcc_user[] = $userSub;
                    }
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
                    Event::dispatch(new CampForumPostMailEvent($user->email ?? null, $user, $link, $data));
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
                    Event::dispatch(new CampForumPostMailEvent($userSub->email, $user, $link, $data));
                } catch (Throwable $e) {
                    $data = null;
                    $status = 403;
                    echo  $message = $e->getMessage();
                }
            }
        }
    }

    /**
     * [notifySupportersForumPost description]
     * @param  [type] $topicid  [description]
     * @param  [type] $campnum  [description]
     * @param  [type] $link     [description]
     * @param  [type] $post     [description]
     * @param  [type] $threadId [description]
     * @param  [type] $nick_id  [description]
     * @return [type]           [description]
     */

    public static function notifySupportersForumPost($topicid, $campnum, $link, $post, $threadId, $nick_id, $topic_name_encoded, $reply_id, $nickName, $notificationType,  $channel = 0)
    {
        $filter = [];
        $filter['topicNum'] = $topicid;
        $filter['asOf'] = '';
        $filter['campNum'] = $campnum;
        $camp = CampForum::getForumLiveCamp($filter);
        $topic = Topic::getLiveTopic($topicid, "");
        $topic_name = $topic->topic_name;
        $camp_name = $camp->camp_name;
        $post_msg = " submitted.";

        $post_type = " has made";
        if ($reply_id != "") {
            $post_msg = " updated.";
            $post_type = " has updated";
        }
        $notificationData = [
            "email" => [],
            "push_notification" => []
        ];

        $liveThread = !empty($threadId) ? Thread::find($threadId) : null;
        $getMessageData = GetPushNotificationToSupporter::getMessageData(Auth::user(), $topic, $camp, $liveThread, $threadId, $notificationType, $nickName, null);
        
        $notificationData['email'] = [
            "post_type" => $post_type,
            "post" => $post,
            "camp_name"   =>  $camp_name,
            "thread" => Thread::where('id', $threadId)->latest()->get(),
            "subject" => $topic_name . " >> " . $camp_name . " >> " . $liveThread->title . " post " . $post_msg,
            "namespace_id" => $topic->namespace_id,
            "nick_name_id" => $nick_id,
            "nickname_url" => Nickname::getNickNameLink($nick_id, $topic->namespace_id, $topicid, $campnum),
            "camp_url" => Camp::campLink($topicid, $campnum, $topic_name, $camp_name),
            "nick_name" => CampForum::getForumNickName($nick_id)
        ];

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
        
        Event::dispatch(new NotifySupportersEvent($camp, $notificationData, config('global.notification_type.Post'), $link, $channel));
        return true;
    }

}

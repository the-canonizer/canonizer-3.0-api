<?php

namespace App\Helpers;

use Throwable;
use App\Models\Camp;
use App\Models\User;
use App\Models\Topic;
use App\Models\Thread;
use App\Models\Support;
use App\Models\Nickname;
use Illuminate\Support\Facades\Event;
use App\Events\CampForumPostMailEvent;
use App\Events\CampForumThreadMailEvent;

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
        $bcc_email = [];
        $subscriber_bcc_email = [];
        $bcc_user = [];
        $sub_bcc_user = [];
        $userExist = [];
        $filter = [];
        $filter['topicNum'] = $topicid;
        $filter['asOf'] = '';
        $filter['campNum'] = $campnum;
        $camp = CampForum::getForumLiveCamp($filter);
        $subCampIds = CampForum::getForumAllChildCamps($camp);
        $topic_name = CampForum::getTopicName($topicid);

        $data['camp_name'] = CampForum::getCampName($topicid, $campnum);

        $data['nick_name'] = CampForum::getForumNickName($nick_id);

        $data['subject'] = $topic_name . " / " . $data['camp_name'] . " / " . $thread_title .
            " created";
        $data['namespace_id'] = CampForum::getNamespaceId($topicid);
        $data['nick_name_id'] = $nick_id;

        $data['camp_url'] = Camp::campLink($topicid, $campnum, $topic_name, $data['camp_name']);
        $data['nickname_url'] = Nickname::getNickNameLink($data['nick_name_id'], $data['namespace_id'], $topicid, $campnum);

        $data['thread_title'] = $thread_title;

        foreach ($subCampIds as $camp_id) {
            $directSupporter = CampForum::getDirectCampSupporter($topicid, $camp_id);
            $subscribers = Camp::getCampSubscribers($topicid, $camp_id);
            $i = 0;
            foreach ($directSupporter as $supporter) {
                $user = CampForum::getUserFromNickId($supporter->nick_name_id);
                $user_id = $user->id ?? null;
                $topic = Topic::where('topic_num', '=', $topicid)->latest('submit_time')->get();
                $topic_name_space_id = isset($topic[0]) ? $topic[0]->namespace_id : 1;
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
        return Support::getDirectSupporter($topicid, $campnum);
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
    public static function getCampName($topicid, $campnum)
    {
        return Camp::where('camp_num', $campnum)->where('objector_nick_id', '=', null)->where('topic_num', $topicid)
            ->where('go_live_time', '<=', time())
            ->latest('submit_time')->first()->camp_name;
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
        $bcc_email = [];
        $subscriber_bcc_email = [];
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
        $subCampIds = CampForum::getForumAllChildCamps($camp);

        $topic_name = CampForum::getTopicName($topicid);
        $camp_name = CampForum::getCampName($topicid, $campnum);
        $post_msg = " submitted.";
        $data['post_type'] = " has made";
        if ($reply_id != "") {
            $post_msg = " updated.";
            $data['post_type'] = " has updated";
        }
        $data['post'] = $post;
        $data['camp_name'] = $camp_name;
        $data['thread'] = Thread::where('id', $threadId)->latest()->get();
        $data['subject'] = $topic_name . " / " . $camp_name . " / " . $data['thread'][0]->title . " post " . $post_msg;
        $data['namespace_id'] = CampForum::getNamespaceId($topicid);
        $data['nick_name_id'] = $nick_id;
        $data['nickname_url'] = Nickname::getNickNameLink($data['nick_name_id'], $data['namespace_id'], $topicid, $campnum);

        $data['camp_url'] = Camp::campLink($topicid, $campnum, $topic_name, $camp_name);
        $data['nick_name'] = CampForum::getForumNickName($nick_id);
        foreach ($subCampIds as $camp_id) {
            $directSupporter = CampForum::getDirectCampSupporter($topicid, $camp_id);
            $subscribers = Camp::getCampSubscribers($topicid, $camp_id);
            foreach ($directSupporter as $supporter) {
                $user = CampForum::getUserFromNickId($supporter->nick_name_id);
                $user_id = $user->id ?? null;
                $user_id = $user->id ?? null;
                $topic = Topic::where('topic_num', '=', $topicid)->latest('submit_time')->get();
                $topic_name_space_id = isset($topic[0]) ? $topic[0]->namespace_id : 1;
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

}

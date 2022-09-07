<?php

namespace App\Helpers;

use stdClass;
use Throwable;
use App\Models\Camp;
use App\Models\User;
use App\Facades\Util;
use App\Models\Topic;
use App\Models\Thread;
use App\Models\Support;
use App\Models\Nickname;

class GetPushNotificationToSupporter
{

    public function pushNotificationToSupporter($request ,$topicNum, $campNum, $action = 'add', $threadId = null)
    {
        $directSupporter = Support::getDirectSupporter($topicNum, $campNum);
        $subscribers = Camp::getCampSubscribers($topicNum, $campNum);
        $directSupporterUser = [];
        $directSupporterUserId = [];
        $subscribers_user = [];
        $subscribers_user_id = [];
        foreach ($directSupporter as $supporter) {
            $user = Nickname::getUserByNickName($supporter->nick_name_id);
            $user_id = $user->id ?? null;
            $directSupporterUser[] = $user;
            $directSupporterUserId[] = $user_id;
        }
        if ($subscribers && count($subscribers) > 0) {
            foreach ($subscribers as $sub) {
                if (!in_array($sub, $directSupporterUserId, true)) {
                    $userSub = User::find($sub);
                    $subscribers_user[] = $userSub;
                    $subscribers_user_id[] = $userSub->id;
                }
            }
        }
        $filtered_direct_supporter_user = array_unique($directSupporterUser);
        $filtered_subscribers_user = array_unique($subscribers_user);
        $topic = Topic::getLiveTopic($topicNum, "");
        $filter['topicNum'] = $topicNum;
        $filter['asOf'] = "";
        $filter['campNum'] = $campNum;
        $camp = Camp::getLiveCamp($filter);
        $liveThread = Thread::find($threadId);
        $PushNotificationData =  new stdClass();
        $PushNotificationData->topic_num = $topic->topic_num;
        $PushNotificationData->camp_num = $camp->camp_num;
        if (isset($filtered_direct_supporter_user) && count($filtered_direct_supporter_user) > 0) {

            foreach ($filtered_direct_supporter_user as $user) {
                try {
                    $PushNotificationData->user_id = $user->id;
                    if ($action == 'add') {
                        $PushNotificationData->notification_type = config('global.notification_type.Support');
                        $PushNotificationData->title = trans('message.notification_title.addSupport', ['camp_name' => $camp->camp_name]);
                        $PushNotificationData->message_body = trans('message.notification_message.addSupport', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/support/' . $topic->topic_num . '-' . urlencode($topic->topic_name)   . '/' . $camp->camp_num . '-' . urlencode($camp->camp_name);
                    } else if ($action == config('global.notification_type.Thread')) {
                        $PushNotificationData->notification_type = config('global.notification_type.Thread');
                        $PushNotificationData->title = trans('message.notification_title.createThread');
                        $PushNotificationData->message_body = trans('message.notification_message.createThread', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'thread_name' => $liveThread->title, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/forum/' . $request->topic_num . '-' . urlencode($request->topic_name)  . '/' . $request->camp_num . '/threads';
                    } else if ($action == config('global.notification_type.Post')) {
                        $PushNotificationData->notification_type = config('global.notification_type.Post');
                        $PushNotificationData->title = trans('message.notification_title.createPost');
                        $PushNotificationData->message_body = trans('message.notification_message.createPost', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'thread_name' => $liveThread->title]);
                        $PushNotificationData->link =  config('global.APP_URL_FRONT_END') . '/forum/' . $topic->topic_num . '-' . urlencode($topic->topic_name) . '/' . $camp->camp_num . '/threads/' . $threadId;
                    } else if ($action == 'updatePost') {
                        $PushNotificationData->notification_type = config('global.notification_type.Post');
                        $PushNotificationData->title = trans('message.notification_title.updatePost');
                        $PushNotificationData->message_body = trans('message.notification_message.updatePost', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'thread_name' => $liveThread->title]);
                        $PushNotificationData->link =  config('global.APP_URL_FRONT_END') . '/forum/' . $topic->topic_num . '-' . urlencode($topic->topic_name) . '/' . $camp->camp_num . '/threads/' . $threadId;
                    } else if ($action == config('global.notification_type.Statement')) {
                        $PushNotificationData->notification_type = config('global.notification_type.Statement');
                        $PushNotificationData->title = trans('message.notification_title.manageStatement', ['camp_name' => $camp->camp_name]);
                        $PushNotificationData->message_body = trans('message.notification_message.manageStatement', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/statement/history/' . $topic->topic_num . '-' . urlencode($topic->topic_name) . '/' . $camp->camp_num . '-' . urlencode($camp->camp_name);
                    } else if ($action == config('global.notification_type.Camp')) {
                        $PushNotificationData->notification_type = config('global.notification_type.Camp');
                        $PushNotificationData->title = trans('message.notification_title.createCamp');
                        $PushNotificationData->message_body = trans('message.notification_message.createCamp',['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name'=> $camp->camp_name]);
                        $PushNotificationData->link = Camp::campLink($topic->topic_num,$camp->camp_num,$topic->topic_name,$camp->camp_name);
                    } else if ($action == 'add-delegate') {
                        $PushNotificationData->notification_type = config('global.notification_type.Support');
                        $PushNotificationData->title = trans('message.notification_title.addDelegateSupport', ['camp_name' => $camp->camp_name]);
                        $PushNotificationData->message_body = trans('message.notification_message.addDelegateSupport', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/support/' . $topic->topic_num . '-' . urlencode($topic->topic_name) . '/' . $camp->camp_num . '-' . urlencode($camp->camp_name);
                    } else if ($action == 'statement-commit') {
                        $PushNotificationData->notification_type = config('global.notification_type.Statement');
                        $PushNotificationData->title = trans('message.notification_title.commitStatementChange', ['topic_name' => $topic->topic_name, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->message_body = trans('message.notification_message.commitStatementChange', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/statement/history/' . $topic->topic_num . '-' . urlencode($topic->topic_name) . '/' . $camp->camp_num . '-' . urlencode($camp->camp_name);
                    }else if ($action == 'camp-commit') {
                        $PushNotificationData->notification_type = config('global.notification_type.Camp');
                        $PushNotificationData->title = trans('message.notification_title.commitCampChange', ['camp_name' => $camp->camp_name]);
                        $PushNotificationData->message_body = trans('message.notification_message.commitCampChange', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/camp/history/' . $topic->topic_num . '-' .urlencode($topic->topic_name) . '/' . $camp->camp_num . '-' . urlencode($camp->camp_name);
                    }else if ($action == 'topic-commit') {
                        $PushNotificationData->notification_type = config('global.notification_type.Topic');
                        $PushNotificationData->title = trans('message.notification_title.commitTopicChange', ['topic_name' => $topic->topic_name]);
                        $PushNotificationData->message_body = trans('message.notification_message.commitTopicChange', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'topic_name' => $topic->topic_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/topic/history/' . $topic->topic_num . '-' . urlencode($topic->topic_name);
                    } else {
                        $PushNotificationData->notification_type = config('global.notification_type.Support');
                        $PushNotificationData->title = trans('message.notification_title.removeSupport', ['camp_name' => $camp->camp_name]);
                        $PushNotificationData->message_body = trans('message.notification_message.removeSupport', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/support/' . $topic->topic_num . '-' . urlencode($topic->topic_name) . '/' . $camp->camp_num . '-' . urlencode($camp->camp_name);
                    }
                    $PushNotificationData->fcm_token = $user->fcm_token;
                    if($user->id != $request->id && !empty($user->fcm_token)){
                        Util::sendPushNotification($PushNotificationData);
                    }
                } catch (Throwable $e) {
                    echo  $message = $e->getMessage();
                }
            }
        }

        if (isset($filtered_subscribers_user) && count($filtered_subscribers_user) > 0) {
            foreach ($filtered_subscribers_user as $userSub) {
                try {
                    $PushNotificationData->user_id = $userSub->id;
                    if ($action == 'add') {
                        $PushNotificationData->notification_type = config('global.notification_type.Support');
                        $PushNotificationData->title = trans('message.notification_title.addSupport', ['camp_name' => $camp->camp_name]);
                        $PushNotificationData->message_body = trans('message.notification_message.addSupport', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/support/' . $topic->topic_num . '-' . urlencode($topic->topic_name) . '/' . $camp->camp_num . '-' . urlencode($camp->camp_name);
                    } else if ($action == config('global.notification_type.Thread')) {
                        $PushNotificationData->notification_type = config('global.notification_type.Thread');
                        $PushNotificationData->title = trans('message.notification_title.createThread');
                        $PushNotificationData->message_body = trans('message.notification_message.createThread', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'thread_name' => $liveThread->title, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/forum/' . $request->topic_num . '-' . urlencode($request->topic_name) . '/' . $request->camp_num . '/threads';
                    }else if ($action == config('global.notification_type.Post')) {
                        $PushNotificationData->notification_type = config('global.notification_type.Post');
                        $PushNotificationData->title = trans('message.notification_title.createPost');
                        $PushNotificationData->message_body = trans('message.notification_message.createPost', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'thread_name' => $liveThread->title]);
                        $PushNotificationData->link =  config('global.APP_URL_FRONT_END') . '/forum/' . $topic->topic_num . '-' . urlencode($topic->topic_name) . '/' . $camp->camp_num . '/threads/' . $threadId;
                    } else if ($action == 'updatePost') {
                        $PushNotificationData->notification_type = config('global.notification_type.Post');
                        $PushNotificationData->title = trans('message.notification_title.updatePost');
                        $PushNotificationData->message_body = trans('message.notification_message.updatePost', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'thread_name' => $liveThread->title]);
                        $PushNotificationData->link =  config('global.APP_URL_FRONT_END') . '/forum/' . $topic->topic_num . '-' . urlencode($topic->topic_name) . '/' . $camp->camp_num . '/threads/' . $threadId;
                    } else if ($action == config('global.notification_type.Statement')) {
                        $PushNotificationData->notification_type = config('global.notification_type.Statement');
                        $PushNotificationData->title = trans('message.notification_title.manageStatement', ['camp_name' => $camp->camp_name]);
                        $PushNotificationData->message_body = trans('message.notification_message.manageStatement', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/statement/history/' . $topic->topic_num . '-' . urlencode($topic->topic_name) . '/' . $camp->camp_num . '-' . urlencode($camp->camp_name);
                    } else if ($action == config('global.notification_type.Camp')) {
                        $PushNotificationData->notification_type = config('global.notification_type.Camp');
                        $PushNotificationData->title = trans('message.notification_title.createCamp');
                        $PushNotificationData->message_body = trans('message.notification_message.createCamp',['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name'=> $camp->camp_name]);
                        $PushNotificationData->link = Camp::campLink($topic->topic_num,$camp->camp_num,$topic->topic_name,$camp->camp_name);
                    } else if ($action == 'add-delegate') {
                        $PushNotificationData->notification_type = config('global.notification_type.Support');
                        $PushNotificationData->title = trans('message.notification_title.addDelegateSupport', ['camp_name' => $camp->camp_name]);
                        $PushNotificationData->message_body = trans('message.notification_message.addDelegateSupport', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/support/' . $topic->topic_num . '-' . urlencode($topic->topic_name) . '/' . $camp->camp_num . '-' . urlencode($camp->camp_name);
                    }else {
                        $PushNotificationData->notification_type = config('global.notification_type.Support');
                        $PushNotificationData->title = trans('message.notification_title.removeSupport', ['camp_name' => $camp->camp_name]);
                        $PushNotificationData->message_body = trans('message.notification_message.removeSupport', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/support/' . $topic->topic_num . '-' . urlencode($topic->topic_name) . '/' . $camp->camp_num . '-' . urlencode($camp->camp_name);
                    }
                    $PushNotificationData->fcm_token = $userSub->fcm_token;
                    if($userSub->id != $request->id && !empty($userSub->fcm_token)){
                        Util::sendPushNotification($PushNotificationData);
                    }
                } catch (Throwable $e) {
                    echo $message = $e->getMessage();
                }
            }
        }
    }

    public function pushNotificationToPromotedDelegates($topic, $camp, $topicLink, $campLink, $user, $promoteLevel, $promotedFrom, $promotedTo = [])
    {

        try {

            $PushNotificationData =  new stdClass();
            $PushNotificationData->topic_num = $topic->topic_num;
            $PushNotificationData->camp_num = $camp->camp_num;
            $PushNotificationData->user_id = $user->id;
            $PushNotificationData->notification_type = config('global.notification_type.Support');

            if (isset($promotedTo) && !empty($promotedTo)) {
                $PushNotificationData->title = trans('message.notification_title.promotedDelegate', ['camp_name' => $camp->camp_name, 'topic_name' =>  $topic->title]);
                $PushNotificationData->message_body = trans('message.notification_message.promotedDelegate', ['nick_name' => $promotedFrom->nick_name, 'delegated_nick_name' => $promotedTo->nick_name, 'camp_name' => $camp->camp_name, 'topic_name' => $topic->title]);
            } else {
                $PushNotificationData->title = trans('message.notification_title.promotedDirect', ['camp_name' => $camp->camp_name, 'topic_name' => $topic->title]);
                $PushNotificationData->message_body = trans('message.notification_message.promotedDirect', ['nick_name' => $promotedFrom->nick_name, 'camp_name' => $camp->camp_name, 'topic_name' => $topic->title]);
            }

            $PushNotificationData->fcm_token = $user->fcm_token;
            $PushNotificationData->link = $campLink;
            
            if(!empty($user->fcm_token)){
                Util::sendPushNotification($PushNotificationData);
            }
        } catch (Throwable $e) {
            echo $message = $e->getMessage();
        }
    }
}

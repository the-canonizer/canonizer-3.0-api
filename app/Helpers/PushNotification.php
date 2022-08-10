<?php

namespace App\Helpers;

use stdClass;
use Throwable;
use App\Models\Camp;
use App\Models\User;
use App\Models\Topic;
use App\Models\Thread;
use App\Models\Support;
use App\Models\Nickname;
use App\Models\PushNotification as ModelPushNotification;

class PushNotification
{


    public static function sendPushNotification($request)
    {

        $saveNotificationData = self::savePushNotification($request);
        $url = env('FCM_URL');
        $serverKey = env('FCM_SERVER_KEY');
        $fcmToken[] = $request->fcm_token;
        $data = [
            "registration_ids" => $fcmToken,
            "notification" => [
                "title" => $request->title,
                "body" => $request->message_body,
                "url" => $request->link,
            ]
        ];
        $encodedData = json_encode($data);

        $headers = [
            'Authorization:key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);

        // Execute post
        $result = curl_exec($ch);

        if ($result === FALSE) {
            return null;
        }

        // Close connection
        curl_close($ch);
        ModelPushNotification::where('id', $saveNotificationData->id)->update(['message_response' => $result]);
        return $result;
    }


    public static function savePushNotification($request)
    {

        $pushNotification = ModelPushNotification::create([
            "user_id" => $request->user_id,
            "topic_num" => $request->topic_num,
            "camp_num" => $request->camp_num,
            "notification_type" => $request->notification_type,
            "message_title" => $request->title,
            "message_body" => $request->message_body,
            "fcm_token" => $request->fcm_token,
            "thread_id" => $request->thread_id ?? NULL,
        ]);

        return $pushNotification;
    }

    public static function pushNotificationToSupporter($request ,$topicNum, $campNum, $action = 'add', $threadId = null)
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
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/support/' . $topic->topic_num . '-' . $topic->topic_name . '/' . $camp->camp_num . '-' . $camp->camp_name;
                    } else if ($action == config('global.notification_type.Thread')) {
                        $PushNotificationData->notification_type = config('global.notification_type.Thread');
                        $PushNotificationData->title = trans('message.notification_title.createThread');
                        $PushNotificationData->message_body = trans('message.notification_message.createThread', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'thread_name' => $liveThread->title, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/forum/' . $request->topic_num . '-' . $request->topic_name . '/' . $request->camp_num . '/threads';
                    } else if ($action == config('global.notification_type.Post')) {
                        $PushNotificationData->notification_type = config('global.notification_type.Post');
                        $PushNotificationData->title = trans('message.notification_title.createPost');
                        $PushNotificationData->message_body = trans('message.notification_message.createPost', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'thread_name' => $liveThread->title]);
                        $PushNotificationData->link =  config('global.APP_URL_FRONT_END') . '/forum/' . $topic->topic_num . '-' . $topic->topic_name . '/' . $camp->camp_num . '/threads/' . $threadId;
                    } else if ($action == 'updatePost') {
                        $PushNotificationData->notification_type = config('global.notification_type.Post');
                        $PushNotificationData->title = trans('message.notification_title.updatePost');
                        $PushNotificationData->message_body = trans('message.notification_message.updatePost', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'thread_name' => $liveThread->title]);
                        $PushNotificationData->link =  config('global.APP_URL_FRONT_END') . '/forum/' . $topic->topic_num . '-' . $topic->topic_name . '/' . $camp->camp_num . '/threads/' . $threadId;
                    } else if ($action == config('global.notification_type.Statement')) {
                        $PushNotificationData->notification_type = config('global.notification_type.Statement');
                        $PushNotificationData->title = trans('message.notification_title.manageStatement', ['camp_name' => $camp->camp_name]);
                        $PushNotificationData->message_body = trans('message.notification_message.manageStatement', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/statement/history/' . $topic->topic_num . '-' . $topic->topic_name . '/' . $camp->camp_num . '-' . $camp->camp_name;
                    } else if ($action == config('global.notification_type.Camp')) {
                        $PushNotificationData->notification_type = config('global.notification_type.Camp');
                        $PushNotificationData->title = trans('message.notification_title.createCamp');
                        $PushNotificationData->message_body = trans('message.notification_message.createCamp',['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name'=> $camp->camp_name]);
                        $PushNotificationData->link = Camp::campLink($topic->topic_num,$camp->camp_num,$topic->topic_name,$camp->camp_name);
                    } else if ($action == 'add-delegate') {
                        $PushNotificationData->notification_type = config('global.notification_type.Support');
                        $PushNotificationData->title = trans('message.notification_title.addDelegateSupport', ['camp_name' => $camp->camp_name]);
                        $PushNotificationData->message_body = trans('message.notification_message.addDelegateSupport', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/support/' . $topic->topic_num . '-' . $topic->topic_name . '/' . $camp->camp_num . '-' . $camp->camp_name;
                    } else {
                        $PushNotificationData->notification_type = config('global.notification_type.Support');
                        $PushNotificationData->title = trans('message.notification_title.removeSupport', ['camp_name' => $camp->camp_name]);
                        $PushNotificationData->message_body = trans('message.notification_message.removeSupport', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/support/' . $topic->topic_num . '-' . $topic->topic_name . '/' . $camp->camp_num . '-' . $camp->camp_name;
                    }
                    $PushNotificationData->fcm_token = $user->fcm_token;
                    if($user->id != $request->id && !empty($user->fcm_token)){
                        self::sendPushNotification($PushNotificationData);
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
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/support/' . $topic->topic_num . '-' . $topic->topic_name . '/' . $camp->camp_num . '-' . $camp->camp_name;
                    } else if ($action == config('global.notification_type.Thread')) {
                        $PushNotificationData->notification_type = config('global.notification_type.Thread');
                        $PushNotificationData->title = trans('message.notification_title.createThread');
                        $PushNotificationData->message_body = trans('message.notification_message.createThread', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'thread_name' => $liveThread->title, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/forum/' . $request->topic_num . '-' . $request->topic_name . '/' . $request->camp_num . '/threads';
                    }else if ($action == config('global.notification_type.Post')) {
                        $PushNotificationData->notification_type = config('global.notification_type.Post');
                        $PushNotificationData->title = trans('message.notification_title.createPost');
                        $PushNotificationData->message_body = trans('message.notification_message.createPost', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'thread_name' => $liveThread->title]);
                        $PushNotificationData->link =  config('global.APP_URL_FRONT_END') . '/forum/' . $topic->topic_num . '-' . $topic->topic_name . '/' . $camp->camp_num . '/threads/' . $threadId;
                    } else if ($action == 'updatePost') {
                        $PushNotificationData->notification_type = config('global.notification_type.Post');
                        $PushNotificationData->title = trans('message.notification_title.updatePost');
                        $PushNotificationData->message_body = trans('message.notification_message.updatePost', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'thread_name' => $liveThread->title]);
                        $PushNotificationData->link =  config('global.APP_URL_FRONT_END') . '/forum/' . $topic->topic_num . '-' . $topic->topic_name . '/' . $camp->camp_num . '/threads/' . $threadId;
                    } else if ($action == config('global.notification_type.Statement')) {
                        $PushNotificationData->notification_type = config('global.notification_type.Statement');
                        $PushNotificationData->title = trans('message.notification_title.manageStatement', ['camp_name' => $camp->camp_name]);
                        $PushNotificationData->message_body = trans('message.notification_message.manageStatement', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/statement/history/' . $topic->topic_num . '-' . $topic->topic_name . '/' . $camp->camp_num . '-' . $camp->camp_name;
                    } else if ($action == config('global.notification_type.Camp')) {
                        $PushNotificationData->notification_type = config('global.notification_type.Camp');
                        $PushNotificationData->title = trans('message.notification_title.createCamp');
                        $PushNotificationData->message_body = trans('message.notification_message.createCamp',['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name'=> $camp->camp_name]);
                        $PushNotificationData->link = Camp::campLink($topic->topic_num,$camp->camp_num,$topic->topic_name,$camp->camp_name);
                    } else if ($action == 'add-delegate') {
                        $PushNotificationData->notification_type = config('global.notification_type.Support');
                        $PushNotificationData->title = trans('message.notification_title.addDelegateSupport', ['camp_name' => $camp->camp_name]);
                        $PushNotificationData->message_body = trans('message.notification_message.addDelegateSupport', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/support/' . $topic->topic_num . '-' . $topic->topic_name . '/' . $camp->camp_num . '-' . $camp->camp_name;
                    }else {
                        $PushNotificationData->notification_type = config('global.notification_type.Support');
                        $PushNotificationData->title = trans('message.notification_title.removeSupport', ['camp_name' => $camp->camp_name]);
                        $PushNotificationData->message_body = trans('message.notification_message.removeSupport', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'camp_name' => $camp->camp_name]);
                        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/support/' . $topic->topic_num . '-' . $topic->topic_name . '/' . $camp->camp_num . '-' . $camp->camp_name;
                    }
                    $PushNotificationData->fcm_token = $userSub->fcm_token;
                    if($userSub->id != $request->id && !empty($userSub->fcm_token)){
                        self::sendPushNotification($PushNotificationData);
                    }
                } catch (Throwable $e) {
                    echo $message = $e->getMessage();
                }
            }
        }
    }

    public static function pushNotificationToPromotedDelegates($topic, $camp, $topicLink, $campLink, $user, $promoteLevel, $promotedFrom, $promotedTo = [])
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
                self::sendPushNotification($PushNotificationData);
            }
        } catch (Throwable $e) {
            echo $message = $e->getMessage();
        }
    }
}

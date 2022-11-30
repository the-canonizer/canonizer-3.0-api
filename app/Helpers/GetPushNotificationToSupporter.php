<?php

namespace App\Helpers;

use App\Facades\PushNotification;
use stdClass;
use Throwable;
use App\Models\Camp;
use App\Models\User;
use App\Models\Topic;
use App\Models\Thread;
use App\Models\Support;
use App\Models\Nickname;

class GetPushNotificationToSupporter
{

    public function pushNotificationToSupporter($request, $topicNum, $campNum, $action = 'add', $threadId = null, $nickName = '')
    {
        $directSupporter = Support::getAllDirectSupporters($topicNum, $campNum);
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
        if($threadId){
            $PushNotificationData->thread_id = $threadId;
        }
        if (isset($filtered_direct_supporter_user) && count($filtered_direct_supporter_user) > 0) {

            foreach ($filtered_direct_supporter_user as $user) {
                try {
                    $PushNotificationData->user_id = $user->id;

                    $getMessageData = $this->getMessageData($request, $topic, $camp, $liveThread, $threadId, $action, $nickName);

                    $PushNotificationData->notification_type = $getMessageData->notification_type;
                    $PushNotificationData->title = $getMessageData->title;
                    $PushNotificationData->message_body = $getMessageData->message_body;
                    $PushNotificationData->link = $getMessageData->link;
                    $PushNotificationData->fcm_token = $user->fcm_token;
                    if ($user->id != $request->id && !empty($user->fcm_token) && !empty($getMessageData)) {
                        PushNotification::sendPushNotification($PushNotificationData);
                    }
                } catch (Throwable $e) {
                    echo $e->getMessage();
                }
            }
        }

        if (isset($filtered_subscribers_user) && count($filtered_subscribers_user) > 0) {
            foreach ($filtered_subscribers_user as $userSub) {
                try {
                    $PushNotificationData->user_id = $userSub->id;

                    $getMessageData = $this->getMessageData($request, $topic, $camp, $liveThread, $threadId, $action, $nickName);

                    $PushNotificationData->notification_type = $getMessageData->notification_type;
                    $PushNotificationData->title = $getMessageData->title;
                    $PushNotificationData->message_body = $getMessageData->message_body;
                    $PushNotificationData->link = $getMessageData->link;

                    $PushNotificationData->fcm_token = $userSub->fcm_token;
                    if ($userSub->id != $request->id && !empty($userSub->fcm_token) && !empty($getMessageData)) {
                        PushNotification::sendPushNotification($PushNotificationData);
                    }
                } catch (Throwable $e) {
                    echo $e->getMessage();
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

            if (!empty($user->fcm_token)) {
                PushNotification::sendPushNotification($PushNotificationData);
            }
        } catch (Throwable $e) {
            echo $message = $e->getMessage();
        }
    }

    public function getMessageData($request, $topic, $camp, $liveThread, $threadId, $action, $nickName)
    {
        $PushNotificationData =  new stdClass();

        switch ($action) {
            case config('global.notification_type.addSupport'):
                $PushNotificationData->notification_type = config('global.notification_type.Support');
                $PushNotificationData->title = trans('message.notification_title.addSupport', ['camp_name' => $camp->camp_name]);
                $PushNotificationData->message_body = trans('message.notification_message.addSupport', ['nick_name' => $nickName, 'camp_name' => $camp->camp_name]);
                $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/support/' . $topic->topic_num . '-' . Util::replaceSpecialCharacters($topic->topic_name)   . '/' . $camp->camp_num . '-' . Util::replaceSpecialCharacters($camp->camp_name);
                break;
            case config('global.notification_type.Thread'):
                $PushNotificationData->notification_type = config('global.notification_type.Thread');
                $PushNotificationData->title = trans('message.notification_title.createThread');
                $PushNotificationData->message_body = trans('message.notification_message.createThread', ['nick_name' => $nickName, 'thread_name' => $liveThread->title, 'camp_name' => $camp->camp_name]);
                $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/forum/' . $topic->topic_num . '-' . Util::replaceSpecialCharacters($topic->topic_name)  . '/' . $camp->camp_num . '-' . Util::replaceSpecialCharacters($camp->camp_name) . '/threads/'. $threadId;;
                break;
            case config('global.notification_type.Post'):
                $PushNotificationData->notification_type = config('global.notification_type.Post');
                $PushNotificationData->title = trans('message.notification_title.createPost');
                $PushNotificationData->message_body = trans('message.notification_message.createPost', ['nick_name' => $nickName, 'thread_name' => $liveThread->title]);
                $PushNotificationData->link =  config('global.APP_URL_FRONT_END') . '/forum/' . $topic->topic_num . '-' . Util::replaceSpecialCharacters($topic->topic_name) . '/' . $camp->camp_num . '-' . Util::replaceSpecialCharacters($camp->camp_name) . '/threads/' . $threadId;
                break;
            case "updatePost":
                $PushNotificationData->notification_type = config('global.notification_type.Post');
                $PushNotificationData->title = trans('message.notification_title.updatePost');
                $PushNotificationData->message_body = trans('message.notification_message.updatePost', ['nick_name' => $nickName, 'thread_name' => $liveThread->title]);
                $PushNotificationData->link =  config('global.APP_URL_FRONT_END') . '/forum/' . $topic->topic_num . '-' . Util::replaceSpecialCharacters($topic->topic_name) . '/' . $camp->camp_num . '-' . Util::replaceSpecialCharacters($camp->camp_name) . '/threads/' . $threadId;
                break;
            case config('global.notification_type.Statement'):
                $PushNotificationData->notification_type = config('global.notification_type.Statement');
                $PushNotificationData->title = trans('message.notification_title.manageStatement', ['camp_name' => $camp->camp_name]);
                $PushNotificationData->message_body = trans('message.notification_message.manageStatement', ['nick_name' => $nickName, 'camp_name' => $camp->camp_name]);
                $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/statement/history/' . $topic->topic_num . '-' . Util::replaceSpecialCharacters($topic->topic_name) . '/' . $camp->camp_num . '-' . Util::replaceSpecialCharacters($camp->camp_name);
                break;
            case config('global.notification_type.Camp'):
                $PushNotificationData->notification_type = config('global.notification_type.Camp');
                $PushNotificationData->title = trans('message.notification_title.createCamp');
                $PushNotificationData->message_body = trans('message.notification_message.createCamp', ['nick_name' => $nickName, 'camp_name' => $camp->camp_name]);
                $PushNotificationData->link = Camp::campLink($topic->topic_num, $camp->camp_num, $topic->topic_name, $camp->camp_name);
                break;
            case config('global.notification_type.addDelegate'):
                $PushNotificationData->notification_type = config('global.notification_type.Support');
                $PushNotificationData->title = trans('message.notification_title.addDelegateSupport', ['camp_name' => $camp->camp_name]);
                $PushNotificationData->message_body = trans('message.notification_message.addDelegateSupport', ['nick_name' => $nickName, 'camp_name' => $camp->camp_name]);
                $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/support/' . $topic->topic_num . '-' . Util::replaceSpecialCharacters($topic->topic_name) . '/' . $camp->camp_num . '-' . Util::replaceSpecialCharacters($camp->camp_name);
                break;
            case config('global.notification_type.statementCommit'):
                $PushNotificationData->notification_type = config('global.notification_type.Statement');
                $PushNotificationData->title = trans('message.notification_title.commitStatementChange', ['topic_name' => $topic->topic_name, 'camp_name' => $camp->camp_name]);
                $PushNotificationData->message_body = trans('message.notification_message.commitStatementChange', ['nick_name' => $nickName, 'camp_name' => $camp->camp_name]);
                $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/statement/history/' . $topic->topic_num . '-' . Util::replaceSpecialCharacters($topic->topic_name) . '/' . $camp->camp_num . '-' . Util::replaceSpecialCharacters($camp->camp_name);
                break;
            case config('global.notification_type.campCommit'):
                $PushNotificationData->notification_type = config('global.notification_type.Camp');
                $PushNotificationData->title = trans('message.notification_title.commitCampChange', ['camp_name' => $camp->camp_name]);
                $PushNotificationData->message_body = trans('message.notification_message.commitCampChange', ['nick_name' => $nickName, 'camp_name' => $camp->camp_name]);
                $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/camp/history/' . $topic->topic_num . '-' . Util::replaceSpecialCharacters($topic->topic_name) . '/' . $camp->camp_num . '-' . Util::replaceSpecialCharacters($camp->camp_name);
                break;
            case config('global.notification_type.topicCommit'):
                $PushNotificationData->notification_type = config('global.notification_type.Topic');
                $PushNotificationData->title = trans('message.notification_title.commitTopicChange', ['topic_name' => $topic->topic_name]);
                $PushNotificationData->message_body = trans('message.notification_message.commitTopicChange', ['nick_name' => $nickName, 'topic_name' => $topic->topic_name]);
                $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/topic/history/' . $topic->topic_num . '-' . Util::replaceSpecialCharacters($topic->topic_name);
                break;
            default:
                $PushNotificationData->notification_type = config('global.notification_type.Support');
                $PushNotificationData->title = trans('message.notification_title.removeSupport', ['camp_name' => $camp->camp_name]);
                $PushNotificationData->message_body = trans('message.notification_message.removeSupport', ['nick_name' => $nickName, 'camp_name' => $camp->camp_name]);
                $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/support/' . $topic->topic_num . '-' . Util::replaceSpecialCharacters($topic->topic_name) . '/' . $camp->camp_num . '-' . Util::replaceSpecialCharacters($camp->camp_name);
        }
        return $PushNotificationData;
    }

     /** If delegate support */
    public function pushNotificationToDelegatesSupporter($topicNum, $campNum, $nickNameId, $delegateNickNameId){
        $topicFilter = ['topicNum' => $topicNum];
        $campFilter = ['topicNum' => $topicNum, 'campNum' => $campNum];

        $topic = Camp::getAgreementTopic($topicFilter);
        $camp  = Camp::getLiveCamp($campFilter);
        $nicknameModel = Nickname::getNickName($nickNameId);
        if (!empty($nicknameModel)) {
            $nickName = $nicknameModel->nick_name;
        }

         if(isset($delegateNickNameId) && $delegateNickNameId){
            $delegatedToNickname =  Nickname::getNickName($delegateNickNameId);
            $delegatedNickname  = $delegatedToNickname->nick_name;
            $delegatedNicknameId  = $delegatedToNickname->id;
        }

        $user = Nickname::getUserByNickName($nickNameId);

        $PushNotificationData =  new stdClass();
        $PushNotificationData->topic_num = $topic->topic_num;
        $PushNotificationData->camp_num = $camp->camp_num;
        $PushNotificationData->notification_type = config('global.notification_type.Support');
        $PushNotificationData->title = trans('message.notification_title.addDelegateSupportUser', ['topic_name' => $topic->topic_name]);
        $PushNotificationData->message_body = trans('message.notification_message.addDelegateSupportUser', ['nick_name' => $nickName,'delegate_nick_name' => $delegatedNickname, 'topic_name' => $topic->topic_name]);
        $PushNotificationData->link = config('global.APP_URL_FRONT_END') . '/support/' . $topic->topic_num . '-' . Util::replaceSpecialCharacters($topic->topic_name) . '/' . $camp->camp_num . '-' . Util::replaceSpecialCharacters($camp->camp_name);
        $PushNotificationData->fcm_token = $user->fcm_token;
        $PushNotificationData->user_id = $user->id;
        if (!empty($user->fcm_token) && !empty($PushNotificationData)) {
            PushNotification::sendPushNotification($PushNotificationData);
        }
    }
}

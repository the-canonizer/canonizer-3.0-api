<?php

namespace App\Listeners;

use stdClass;
use Throwable;
use App\Models\Camp;
use App\Models\User;
use App\Models\Topic;
use App\Models\Support;
use App\Models\Nickname;
use App\Facades\CampForum;
use App\Facades\PushNotification;
use App\Events\NotifySupportersEvent;
use Illuminate\Support\Facades\Event;
use App\Events\CampForumPostMailEvent;
use App\Events\CampForumThreadMailEvent;
use App\Events\SendPushNotificationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifySupportersListner implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function viaQueue()
    {
        return env('NOTIFY_SUPPORTER_QUEUE');
    }

    /**
     * Handle the event.
     *
     * @param  NotifySupportersEvent  $event
     * @return void
     */
    public function handle(NotifySupportersEvent $event)
    {
        //
        $camp = $event->camp;
        $type = $event->type;
        $data = $event->data;
        $link = $event->link;
        $channel = $event->channel;


        $user = '';
        $userExist = [];
        $bcc_user = [];
        $supporter_and_subscriber = [];
        $sub_bcc_user = [];
        $support_list = [];
        $subscribe_list = [];

        $implicitSupporters = Support::getAllDirectSupporters($camp->topic_num, $camp->camp_num);
        $subscribers = Camp::getCampSubscribers($camp->topic_num, $camp->camp_num);
        $topic = Topic::getLiveTopic($camp->topic_num, "");
        $topic_name_space_id = isset($topic) ? $topic->namespace_id : 1;

        foreach ($implicitSupporters as $supporter) {
            $user = CampForum::getUserFromNickId($supporter->nick_name_id);
            $user_id = $user->id ?? null;
            $nickName = Nickname::find($supporter->nick_name_id);
            $supported_camp = $nickName->getSupportCampList($topic_name_space_id, ['nofilter' => true]);
            $supported_camp_list = $nickName->getSupportCampListNamesEmail($supported_camp, $camp->topic_num, $camp->camp_num);
            $support_list[$user_id] = $supported_camp_list;
            $ifalsoSubscriber = Camp::checkifSubscriber($subscribers, $user);
            if ($ifalsoSubscriber) {
                $support_list_data = Camp::getSubscriptionList($user_id, $camp->topic_num, $camp->camp_num);
                $supporter_and_subscriber[$user_id] = ['also_subscriber' => 1, 'sub_support_list' => $support_list_data];
            }
            $bcc_user[] = $user;
            $userExist[] = $user_id;
        }
        if ($subscribers && count($subscribers) > 0) {
            foreach ($subscribers as $sub) {
                if (!in_array($sub, $userExist, true)) {
                    $userSub = User::find($sub);
                    $subscriptions_list = Camp::getSubscriptionList($userSub->id, $camp->topic_num, $camp->camp_num);
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
                $data['email']['support_list'] = $support_list[$user_id];
                if (isset($supporter_and_subscriber[$user_id]) && isset($supporter_and_subscriber[$user_id]['also_subscriber']) && $supporter_and_subscriber[$user_id]['also_subscriber']) {
                    $data['email']['also_subscriber'] = $supporter_and_subscriber[$user_id]['also_subscriber'];
                    $data['email']['sub_support_list'] = $supporter_and_subscriber[$user_id]['sub_support_list'];
                }
                switch ($channel) {
                    case config('global.notify.email'):
                        $this->dispatchEmail($user->email ?? null, $user, $data['email'], $type, $link);
                        break;
                    case config('global.notify.push_notification'):
                        $this->sendPushNotification($user, $data['push_notification']);
                        break;
                    case config('global.notify.both'):
                        $this->dispatchEmail($user->email ?? null, $user, $data['email'], $type, $link);
                        $this->sendPushNotification($user, $data['push_notification']);
                        break;
                }
            }
        }

        if (isset($filtered_sub_user) && count($filtered_sub_user) > 0) {
            $data['email']['subscriber'] = 1;
            foreach ($filtered_sub_user as $userSub) {
                $data['email']['support_list'] = $subscribe_list[$userSub->id];
                switch ($channel) {
                    case config('global.notify.email'):
                        $this->dispatchEmail($userSub->email ?? null, $user, $data['email'], $type, $link);
                        break;
                }
            }
        }
    }

    private function dispatchEmail($email, $user, $data, $type, $link)
    {
        switch ($type) {
            case config('global.notification_type.Thread'):
                Event::dispatch(new CampForumThreadMailEvent($email, $user, $link, $data));
                break;
            case config('global.notification_type.Post'):
                Event::dispatch(new CampForumPostMailEvent($email, $user, $link, $data));
                break;
        }
        return;
    }

    private function sendPushNotification($user, $data)
    {
        Event::dispatch(new SendPushNotificationEvent($user, $data));
    }
}

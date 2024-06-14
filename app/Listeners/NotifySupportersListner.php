<?php

namespace App\Listeners;

use stdClass;
use Exception;
use Throwable;
use App\Models\Camp;
use App\Models\User;
use App\Facades\Util;
use App\Models\Topic;
use App\Models\Support;
use App\Models\Nickname;
use App\Facades\CampForum;
use App\Facades\PushNotification;
use App\Events\NotifySupportersEvent;
use App\Events\SupportAddedMailEvent;
use Illuminate\Support\Facades\Event;
use App\Events\CampForumPostMailEvent;
use App\Events\SupportRemovedMailEvent;
use App\Events\CampForumThreadMailEvent;
use App\Events\SendPushNotificationEvent;
use App\Helpers\TopicSupport;
use App\Jobs\PurposedToSupportersMailJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifySupportersListner implements ShouldQueue
{
    public $timeout = 300;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        
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
        Util::logMessage('-------------------- Notify Listner Started --------------------------');
        Util::logMessage('start time ==> '. date("Y-m-d h:i:s", time()));
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
    
        try {
            $implicitSupporters = Support::getAllDirectSupporters($camp->topic_num, $camp->camp_num);
            $subscribers = Camp::getCampSubscribers($camp->topic_num, $camp->camp_num);
            $topic = Topic::getLiveTopic($camp->topic_num, "");
            $topic_name_space_id = isset($topic) ? $topic->namespace_id : 1;

            Util::logMessage("Total Direct Supporters: ".count($implicitSupporters));
            Util::logMessage("Total Subscribers: ".count($subscribers));

            $count = 0;
            foreach ($implicitSupporters as $supporter) {
                $user = CampForum::getUserFromNickId($supporter->nick_name_id);
                $user_id = $user->id ?? null;
                $nickName = Nickname::find($supporter->nick_name_id);

                Util::logMessage("Count: " . $count++ . " User ID" . $user_id . " Nick Name: " . $nickName->nick_name);

                $supported_camp = $nickName->getSupportCampList($topic_name_space_id, ['nofilter' => true]);
                Util::logMessage("Total supported camps: ". count($supported_camp));

                $supported_camp_list = $nickName->getSupportCampListNamesEmail($supported_camp, $camp->topic_num, $camp->camp_num);
                Util::logMessage("Total supported camps name and email: ". count($supported_camp));

                $support_list[$user_id] = $supported_camp_list;
                $ifalsoSubscriber = Camp::checkifSubscriber($subscribers, $user);
                if ($ifalsoSubscriber) {
                    $support_list_data = Camp::getSubscriptionList($user_id, $camp->topic_num, $camp->camp_num);
                    $supporter_and_subscriber[$user_id] = ['also_subscriber' => 1, 'sub_support_list' => $support_list_data];
                }

                $user->nick_ids = TopicSupport::getAllNickNamesOfNickID($supporter->nick_name_id) ?? [];
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
            Util::logMessage("Filtered Direct Supporters: ".count($filtered_bcc_user));
            Util::logMessage("Filtered Subscribers: ".count($filtered_sub_user));

            if (isset($filtered_bcc_user) && count($filtered_bcc_user) > 0) {
                foreach ($filtered_bcc_user as $user) {
                    $user_id = $user->id;
                    $data['email']['support_list'] = $support_list[$user_id];
                    $data['email']['also_subscriber'] = 0;
                    $data['email']['sub_support_list'] = [];
                    if (isset($supporter_and_subscriber[$user_id]) && isset($supporter_and_subscriber[$user_id]['also_subscriber']) && $supporter_and_subscriber[$user_id]['also_subscriber']) {
                        $data['email']['also_subscriber'] = $supporter_and_subscriber[$user_id]['also_subscriber'];
                        $data['email']['sub_support_list'] = $supporter_and_subscriber[$user_id]['sub_support_list'];
                    }

                    if (!is_null($camp->camp_leader_nick_id) && isset($data['email']['previous_camp_leader_nick_id']) && $camp->camp_leader_nick_id !== $data['email']['previous_camp_leader_nick_id']) {
                        $newCampLeaderNickNames = Nickname::getNicknamesIdsByUserId($user->id);
                        $nickname = Nickname::getNickName($camp->camp_leader_nick_id);
                        $nicknameLink = Nickname::getNickNameLink($camp->camp_leader_nick_id, $data['email']['namespace_id'], $data['email']['topic_num'], $data['email']['camp_num']);
                        $data['email']['new_camp_leader_statement'] = (in_array($camp->camp_leader_nick_id, $newCampLeaderNickNames) ? 'You are' : '<a href="' . $nicknameLink . '">' . $nickname->nick_name . '</a> is') . ' assigned as the new camp leader.';
                    }

                    switch ($channel) {
                        case config('global.notify.email'):
                            $this->dispatchEmail($user->email ?? null, $user, $data['email'], $type, $link);
                            break;
                        case config('global.notify.push_notification'):
                            $this->dispatchPushNotification($user, $data['push_notification']);
                            break;
                        case config('global.notify.both'):
                            $this->dispatchEmail($user->email ?? null, $user, $data['email'], $type, $link);
                            $this->dispatchPushNotification($user, $data['push_notification']);
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
                            $this->dispatchEmail($userSub->email ?? null, $userSub, $data['email'], $type, $link);
                            break;
                        case config('global.notify.push_notification'):
                            $this->dispatchPushNotification($userSub, $data['push_notification']);
                            break;
                        case config('global.notify.both'):
                            $this->dispatchEmail($userSub->email ?? null, $userSub, $data['email'], $type, $link);
                            $this->dispatchPushNotification($userSub, $data['push_notification']);
                            break;
                    }
                }
            }
        } catch(Exception $e) {
            Util::logMessage($e->getMessage(), 'error');
        }

        Util::logMessage('end time ==> '. date("Y-m-d h:i:s", time()));
        Util::logMessage('-------------------- Notify Listner Ended --------------------------');
        
    }

    private function dispatchEmail($email, $user, $data, $type, $link)
    {
        Util::logMessage('dispatching email ==> '. $email. ' using type =>'. $type);
        switch ($type) {
            case config('global.notification_type.Thread'):
                Event::dispatch(new CampForumThreadMailEvent($email, $user, $link, $data));
                break;
            case config('global.notification_type.Post'):
                Event::dispatch(new CampForumPostMailEvent($email, $user, $link, $data));
                break;
            case config('global.notification_type.manageCamp'):
            case config('global.notification_type.Statement'):
            case config('global.notification_type.statementCommit'):
            case config('global.notification_type.campCommit'):
            case config('global.notification_type.topicCommit'):
                dispatch(new PurposedToSupportersMailJob($user, $link, $data, $user->email ?? null))->onQueue(env('NOTIFY_SUPPORTER_QUEUE'));
                break;
            case config('global.notification_type.addSupport'):
                /**
                 * This switch case is dispatching email to each user in case of add support.
                 * We need to check the supporters of this camp and compare it with user that is adding support in camp.
                 * If the supporter nick ids match with the action user then on base of this we are handling the subject and 
                 * message body text... 
                 */

                $sendingMailToActionUser = in_array($data['nick_name_id'], $user->nick_ids ?? []);
                if($sendingMailToActionUser) {
                    $extractCampFromSubject = preg_match('/\bto\b\s*(.*)/', $data['subject'], $matches);
                    $data['subject']    =  ($extractCampFromSubject) ? 'Thank you for adding your support for this camp : '. $matches[1] : 'Thank you for adding your support.';
                    $data['sending_mail_to_action_user'] = true;
                }
                
                Event::dispatch(new SupportAddedMailEvent($user->email ?? null, $user, $data));
                break;
            case config('global.notification_type.addDelegate'):
                Event::dispatch(new SupportAddedMailEvent($user->email ?? null, $user, $data));
                break;
            case config('global.notification_type.removeSupport'):
                Event::dispatch(new SupportRemovedMailEvent($user->email ?? null, $user, $data));
                break;
        }
        Util::logMessage('dispatched');
        return;
    }

    private function dispatchPushNotification($user, $data)
    {
        Util::logMessage('dispatching notification ==> '. $user->id);
        Event::dispatch(new SendPushNotificationEvent($user, $data));
        Util::logMessage('dispatched');
        return;
    }
}

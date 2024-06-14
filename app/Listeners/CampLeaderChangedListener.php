<?php

namespace App\Listeners;

use App\Events\{CampLeaderAssignedEvent, CampLeaderRemovedEvent, NotifySupportersEvent};
use App\Facades\GetPushNotificationToSupporter;
use App\Models\{Camp, Nickname, Topic};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use App\Facades\CampForum;
use Illuminate\Support\Facades\Event;

class CampLeaderChangedListener implements ShouldQueue
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
     * @return void
     */
    public function handle($event)
    {
        Log::info("======================= Camp Leader Changed Event Start =======================");
        Log::debug("Camp Leader Changed Event: " . json_encode($event));

        $liveTopic = Topic::getLiveTopic($event->topic_num);

        $liveCamp = Camp::getLiveCamp(['topicNum' => $event->topic_num, 'campNum' => $event->camp_num]);

        $liveThread = null;
        $threadId = null;

        $nickName = Nickname::getNickName($event->nick_name_id);

        if($event->push_notification) { 
            if ($event instanceof CampLeaderAssignedEvent) {
                $notification_type = config('global.notification_type.CampLeaderAssigned');
            }
            if ($event instanceof CampLeaderRemovedEvent) {
                $notification_type = config('global.notification_type.CampLeaderRemoved');
            }
            
            $user = CampForum::getUserFromNickId($event->nick_name_id);
    
            $getMessageData = GetPushNotificationToSupporter::getMessageData($user, $liveTopic, $liveCamp, $liveThread, $threadId, $notification_type, $nickName->nick_name, null);
    
            if (!empty($getMessageData)) {
                $notificationData['push_notification'] = [
                    "topic_num" => $liveTopic->topic_num,
                    "camp_num" => $liveCamp->camp_num,
                    "notification_type" => $getMessageData->notification_type,
                    "title" => $getMessageData->title,
                    "message_body" => $getMessageData->message_body,
                    "link" => $getMessageData->link,
                    "thread_id" => !empty($threadId) ? $threadId : null,
                ];
            }
    
            Event::dispatch(new NotifySupportersEvent($liveCamp, $notificationData, $notification_type, $getMessageData->link, config('global.notify.push_notification')));
    
        }
        Log::info("======================= Camp Leader Changed Event End =======================");
    }
}

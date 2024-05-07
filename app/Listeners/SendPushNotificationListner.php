<?php

namespace App\Listeners;

use App\Facades\PushNotification;
use App\Events\SendPushNotificationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use stdClass;

class SendPushNotificationListner implements ShouldQueue
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
     * @param  SendPushNotificationEvent  $event
     * @return void
     */
    public function handle(SendPushNotificationEvent $event)
    {
        $user = $event->user;
        $data = $event->data;

        $PushNotificationData =  new stdClass();
        $PushNotificationData->topic_num = $data['topic_num'];
        $PushNotificationData->camp_num = $data['camp_num'];
        if(!empty($data['thread_id'])){
            $PushNotificationData->thread_id = $data['thread_id'];
        }
        $PushNotificationData->user_id = $user->id;
        $PushNotificationData->notification_type = $data['notification_type'];
        $PushNotificationData->title = $data['title'];
        $PushNotificationData->message_body = $data['message_body'];
        $PushNotificationData->link = $data['link'];
        $PushNotificationData->fcm_token = $user->fcm_token;
        if (!empty($user) && !empty($data)) {
            PushNotification::sendPushNotification($PushNotificationData);
        }
    }
}

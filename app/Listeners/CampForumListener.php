<?php

namespace App\Listeners;

use Throwable;
use Illuminate\Bus\Queueable;
use App\Facades\CampForumPost;
use App\Facades\CampForumThread;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CampForumListener implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;


    public $timeout = 0;

    public function viaQueue()
    {
        return env('QUEUE_SERVICE_NAME');
    }
    /**
     * Handle the event.
     *
     * @param  \App\Events\ExampleEvent  $event
     * @return void
     */
    public function handle($event)
    {

        $topic_num = $event->topic_num;
        $camp_num = $event->camp_num;
        $return_url = $event->return_url;
        $title = $event->title;
        $nick_name = $event->nick_name;
        $topic_name = $event->topic_name;
        $body = $event->body;
        $thread_id = $event->thread_id;
        $action = $event->action;

        try {
            // Log::info("Call CampForumListener");
            switch ($action) {
                case config('global.notification_type.Thread'):
                    CampForumThread::sendEmailToSupportersForumThread($topic_num, $camp_num, $return_url, $title, $nick_name, $topic_name);
                    break;
                case config('global.notification_type.Post'):
                    CampForumPost::sendEmailToSupportersForumPost($topic_num, $camp_num, $return_url, $body, $thread_id, $nick_name, $topic_name, "");
                    break;
            }
        } catch (Throwable $e) {
            Log::error("Catch error in CampForumListener: " . $e->getMessage());
        }
    }
}

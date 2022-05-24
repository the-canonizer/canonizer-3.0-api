<?php

namespace App\Listeners;

use App\Events\LogActivityEvent;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use App\Helpers\ActivityLogger;
class LogActivityListener implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  LogActivityEvent  $event
     * @return void
     */
    public function handle(LogActivityEvent $event)
    {
        ActivityLogger::logActivity($event->log_type,$event->url, $event->activity, $event->model,$event->topic_num, $event->camp_num, $event->user);
    }
}

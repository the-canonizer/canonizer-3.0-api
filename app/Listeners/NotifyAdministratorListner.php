<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Mail;
use App\Mail\NotifyAdministratorMail;
use App\Events\NotifyAdministratorEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyAdministratorListner implements ShouldQueue
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
     * @param  NotifyAdministratorEvent  $event
     * @return void
     */
    public function handle(NotifyAdministratorEvent $event)
    {
        $url = $event->url;
        $refererURL = $event->refererURL;
        $emails = explode(',', env('EMAILS_FOR_NOTIFY_ADMINISTRATOR'));
        foreach($emails as $email){
            Mail::to($email)->send(new NotifyAdministratorMail($url, $refererURL));
        }
    }
}

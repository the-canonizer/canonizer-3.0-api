<?php

namespace App\Listeners;

use App\Events\EmailChnageEvent;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailChange;

class EmailChangeListener
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

    /**
     * Handle the event.
     *
     * @param  EmailChnageEvent  $event
     * @return void
     */
    public function handle(EmailChnageEvent $event)
    {
        $user = $event->user;
        //$settingFlag = $event->settingFlag;

        Mail::to($user->email)->send(new SendOtp($user,$settingFlag));
    }
}

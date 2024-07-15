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
     * @param  EmailChangeEvent  $event
     * @return void
     */
    public function handle($event)
    {
        $user = $event->user;
        $requestChange = $event->requestChange;
        $newEmail = $event->newEmail;

        $email = ($newEmail) ? $newEmail : $user->email;
        Mail::to($email)->send(new EmailChange($user,$requestChange));
    }
}

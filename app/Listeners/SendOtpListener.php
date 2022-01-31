<?php

namespace App\Listeners;

use App\Mail\SendOtpMail;
use App\Events\SendOtpEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendOtpListener
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
    * Send Otp mail event
    */
    public function handle($event)
    {
        $user = $event->user;
       // echo "<pre>"; print_r($user); exit;
        Mail::to('reenanalwa@gmail.com')->send(new sendOtpMail($user));
    }
}

<?php

namespace App\Listeners;

use App\Mail\ForgotPasswordSendOtp;
use App\Events\ForgotPasswordSendOtpEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordSendOtpListener
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
     * @param  \App\Events\ExampleEvent  $event
     * @return void
     */
    public function handle($event)
    {
        $user = $event->user;
        $settingFlag = $event->settingFlag;

        Mail::to($user->email)->send(new ForgotPasswordSendOtp($user,$settingFlag));
    }
}

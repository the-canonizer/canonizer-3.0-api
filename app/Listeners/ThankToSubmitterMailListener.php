<?php

namespace App\Listeners;

use Illuminate\Bus\Queueable;
use App\Mail\ThankToSubmitterMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\ThankToSubmitterMailEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class ThankToSubmitterMailListener implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;
    /**
     * Create the event listener.
     *
     * @return void
     */

    public function __construct()
    {

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
        $data = $event->data;
        Mail::to($user->email)->send(new ThankToSubmitterMail($user,$data));
    }
}

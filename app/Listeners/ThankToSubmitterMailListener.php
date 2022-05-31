<?php

namespace App\Listeners;

use Illuminate\Bus\Queueable;
use App\Mail\ThankToSubmitterMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ThankToSubmitterMailListener implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;
    
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
        $user = $event->user;
        $data = $event->data;
        Mail::to($user->email)->send(new ThankToSubmitterMail($user,$data));
    }
}

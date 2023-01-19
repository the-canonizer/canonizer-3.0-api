<?php

namespace App\Listeners;

use Illuminate\Bus\Queueable;
use App\Mail\SupportRemovedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SupportRemovedMailListener implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function viaQueue()
    {
        return env('NOTIFY_SUPPORTER_QUEUE');
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
        $to = $event->to;
        $data = $event->data;
        Mail::to($to)->send(new SupportRemovedMail($user,$data));
    }
}

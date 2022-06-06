<?php

namespace App\Listeners;

use Illuminate\Bus\Queueable;
use App\Mail\CampForumPostMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class PromotedDelegatesMailListener implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;
 
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

        echo "<pre>"; print_r($data); exit;
        Mail::to($to)->send(new PromotedDelegatesMail($user,$data));
    }
}

<?php

namespace App\Listeners;

use Illuminate\Bus\Queueable;
use App\Mail\CampForumThreadMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CampForumThreadMailListener implements ShouldQueue
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
        $email = $event->email;
        $link = $event->link;
        $data = $event->data;
        Mail::to($email)->send(new CampForumThreadMail($user,$link,$data));
    }
}

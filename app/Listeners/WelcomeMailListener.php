<?php

namespace App\Listeners;

use App\Mail\welcomeEmail;
use App\Events\WelcomeMailEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class WelcomeMailListener implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
      //  $this->onQueue('mail');
    }

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
        $link_index_page = $event->link_index_page;
        Mail::to($user->email)->send(new welcomeEmail($user,$link_index_page));
    }
}

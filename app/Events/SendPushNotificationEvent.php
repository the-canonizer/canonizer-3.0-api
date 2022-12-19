<?php

namespace App\Events;

class SendPushNotificationEvent extends Event
{
    public $user;
    public $data;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user, $data)
    {
        //
        $this->user = $user;
        $this->data = $data;
    }
}

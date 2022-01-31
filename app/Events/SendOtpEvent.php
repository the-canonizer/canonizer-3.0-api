<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use App\Models\User;

class SendOtpEvent extends Event
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $user;
    
    public function __construct($user)
    {
        $this->user = $user;
    }


    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}

<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use App\Models\User;

class EmailChangeEvent extends Event
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $user;
    public $requestChange;

    
    public function __construct($user, $requestChange = false)
    {
        $this->user = $user;
        $this->requestChange = $requestChange;
    }


    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}

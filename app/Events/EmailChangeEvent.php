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
    public $newEmail;

    
    public function __construct($user, $requestChange = false, $newEmail='')
    {
        $this->user = $user;
        $this->requestChange = $requestChange;
        $this->newEmail = $newEmail;
    }


    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}

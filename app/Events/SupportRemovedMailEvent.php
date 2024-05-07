<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;

class SupportRemovedMailEvent extends Event
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $user;
    public $to;
    public $data;

    public function __construct ($to, $user, $data)
    {
        $this->user = $user;
        $this->to = $to;
        $this->data = $data;
    }


    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}

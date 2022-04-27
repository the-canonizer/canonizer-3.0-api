<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;

class CampForumPostMailEvent extends Event
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $user;
    public $email;
    public $link;
    public $data;

    public function __construct ($email, $user, $link, $data)
    {
        $this->user = $user;
        $this->email = $email;
        $this->link = $link;
        $this->data = $data;
    }


    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}

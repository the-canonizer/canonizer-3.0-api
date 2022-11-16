<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use App\Models\User;

class WelcomeMailEvent extends Event
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $user;
    public $link_index_page;
    
    public function __construct($user,$link_index_page)
    {
       // echo "<pre>"; print_r($user->first_name); die;
        $this->user = $user;
        $this->link_index_page = $link_index_page;
    }


    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}

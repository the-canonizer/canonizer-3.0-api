<?php

namespace App\Events;
use Illuminate\Support\Facades\Log;

use Illuminate\Broadcasting\PrivateChannel;
use App\Models\User;

class ThankToSubmitterMailEvent extends Event
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $user;
    public $data;
    
    public function __construct(object $user,$data)
    {
        $this->user = $user;
        $this->data = $data;
    }


    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}

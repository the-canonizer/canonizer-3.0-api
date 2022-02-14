<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use App\Models\User;

class ForgotPasswordSendOtpEvent extends Event
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $user;
    
    public function __construct($user)
    {
       // echo "<pre>"; print_r($user->first_name); die;
        $this->user = $user;
    }


    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}

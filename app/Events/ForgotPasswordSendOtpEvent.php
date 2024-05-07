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
    public $settingFlag;

    
    public function __construct($user, $settingFlag = true)
    {
        $this->user = $user;
        $this->settingFlag = $settingFlag;
    }


    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}

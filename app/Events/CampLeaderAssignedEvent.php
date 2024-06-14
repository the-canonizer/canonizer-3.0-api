<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;

class CampLeaderAssignedEvent extends Event
{
    public int $topic_num;
    public int $camp_num;
    public ?int $nick_name_id;
    public bool $push_notification;
    
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(int $topic_num, int $camp_num, ?int $nick_name_id, bool $push_notification = false)
    {
        $this->topic_num = $topic_num;
        $this->camp_num = $camp_num;
        $this->nick_name_id = $nick_name_id;
        $this->push_notification = $push_notification;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}

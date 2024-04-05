<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;

class CampForumEvent extends Event
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $topic_num;
    public $camp_num;
    public $return_url;
    public $title;
    public $nick_name;
    public $topic_name;
    public $body;
    public $thread_id;
    public $action;

    public function __construct ($topic_num, $camp_num, $return_url, $title, $nick_name, $topic_name, $body, $thread_id,$action)
    {
        $this->topic_num = $topic_num;
        $this->camp_num = $camp_num;
        $this->return_url = $return_url;
        $this->title = $title;
        $this->nick_name = $nick_name;
        $this->topic_name = $topic_name;
        $this->body = $body;
        $this->thread_id = $thread_id;
        $this->action = $action;
    }


    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}

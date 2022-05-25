<?php

namespace App\Events;

//use Illuminate\Broadcasting\PrivateChannel;
class LogActivityEvent extends Event
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $log_type;
    public $url;
    public $activity;
    public $topic_num;
    public $camp_num;
    public $user;
    public $model;
   
    public function __construct( $log_type, $url,$activity, $model, $topic_num, $camp_num, $user)
    {
        $this->log_type = $log_type;
        $this->url = $url;
        $this->activity = $activity;
        $this->topic_num = $topic_num;
        $this->camp_num = $camp_num;
        $this->user = $user;
        $this->model = $model;
    }

}

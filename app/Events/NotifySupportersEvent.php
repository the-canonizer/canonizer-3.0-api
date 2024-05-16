<?php

namespace App\Events;

class NotifySupportersEvent extends Event
{
    public $camp;
    public $type;
    public $data;
    public $link;
    public $channel;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($camp, $data, $type, $link = null, $channel = 0)
    {
        //
        $this->camp = $camp;
        $this->type = $type;
        $this->data = $data;
        $this->link = $link;
        $this->channel = $channel;
    }
}

<?php

namespace App\Events;

class NotifyAdministratorEvent extends Event
{
    public $url;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($url)
    {
        $this->url = $url;
    }
}

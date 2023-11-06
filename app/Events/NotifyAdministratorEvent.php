<?php

namespace App\Events;

class NotifyAdministratorEvent extends Event
{
    public $url;
    public $refererURL;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($url, $refererURL)
    {
        $this->url = $url;
        $this->refererURL = $refererURL;
    }
}

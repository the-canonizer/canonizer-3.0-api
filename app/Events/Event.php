<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

abstract class Event
{
    use InteractsWithSockets, SerializesModels;
}

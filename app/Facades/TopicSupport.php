<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class TopicSupport extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'topicSupport';
    }
}

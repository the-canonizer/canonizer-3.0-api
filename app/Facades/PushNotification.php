<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class PushNotification extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'PushNotification';
    }
}

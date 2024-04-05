<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class GetPushNotificationToSupporter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'GetPushNotificationToSupporter';
    }
}

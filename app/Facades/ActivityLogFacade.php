<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class ActivityLogFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ActivityLogHelper';
    }
}

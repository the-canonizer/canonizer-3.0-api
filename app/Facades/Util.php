<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Util extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'util';
    }
}

<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Aws extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'aws';
    }
}

<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class CampForum extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'campForum';
    }
}

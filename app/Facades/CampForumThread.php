<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class CampForumThread extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'CampForumThread';
    }
}

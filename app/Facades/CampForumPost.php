<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class CampForumPost extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'CampForumPost';
    }
}

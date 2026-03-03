<?php

namespace App\Facades\Services;

use Illuminate\Support\Facades\Facade;

class TreeServiceFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\Api\v1\TreeService::class;
    }
}

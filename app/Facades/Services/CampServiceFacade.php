<?php

namespace App\Facades\Services;

use Illuminate\Support\Facades\Facade;

class CampServiceFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\Api\v1\CampService::class;
    }
}

<?php

namespace App\Facades\Repositories;

use Illuminate\Support\Facades\Facade;

class TopicRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Repository\Topic\TopicInterface::class;
    }
}

<?php

namespace App\Providers;

use App\Helpers\ResponseBuilder;
use App\Helpers\ResponseInterface;
use Carbon\Laravel\ServiceProvider;

class ResponseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ResponseInterface::class, ResponseBuilder::class);
    }
}

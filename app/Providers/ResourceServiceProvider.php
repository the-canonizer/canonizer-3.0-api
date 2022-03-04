<?php

namespace App\Providers;

use App\Helpers\ResourceBuilder;
use App\Helpers\ResourceInterface;
use Illuminate\Support\ServiceProvider;

class ResourceServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ResourceInterface::class, ResourceBuilder::class);
    }
}

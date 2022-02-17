<?php

namespace App\Providers;

use App\Helpers\Util;
use App\Helpers\ActivityLogHelper;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->bind('util', Util::class);
        $this->app->bind('ActivityLogHelper', ActivityLogHelper::class);
    }
}

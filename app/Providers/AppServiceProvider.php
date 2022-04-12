<?php

namespace App\Providers;

use App\Helpers\Util;
use Illuminate\Support\ServiceProvider;
use App\Helpers\Aws;

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
        $this->app->bind('aws', AWS::class);
        $this->app->bind('campForum', CampForum::class);
    }
}

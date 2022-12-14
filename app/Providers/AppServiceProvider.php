<?php

namespace App\Providers;

use App\Helpers\Aws;
use App\Helpers\Util;
use App\Helpers\CampForum;
use App\Helpers\PushNotification;
use Illuminate\Support\ServiceProvider;
use App\Helpers\GetPushNotificationToSupporter;

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
        $this->app->bind('topicSupport', TopicSupport::class);
        $this->app->bind('PushNotification', PushNotification::class);
        $this->app->bind('GetPushNotificationToSupporter', GetPushNotificationToSupporter::class);
    }
}

<?php

namespace App\Providers;

use App\Helpers\Aws;
use App\Helpers\Util;
use App\Helpers\CampForumPost;
use App\Helpers\CampForumThread;
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
        $this->app->bind('CampForumPost', CampForumPost::class);
        $this->app->bind('topicSupport', TopicSupport::class);
        $this->app->bind('PushNotification', PushNotification::class);
        $this->app->bind('GetPushNotificationToSupporter', GetPushNotificationToSupporter::class);
        $this->app->bind('CampForumThread', CampForumThread::class);
    }
}

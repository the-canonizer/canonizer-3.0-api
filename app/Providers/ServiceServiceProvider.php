<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Api\v1\CampService;
use App\Services\Api\v1\TopicService;
use App\Services\Api\v1\TreeService;
use App\Services\Api\v1\TimelineService;
use App\Services\Api\v1\AlgorithmService;

class ServiceServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(CampService::class, function ($app) {
            return new CampService();
        });

        $this->app->bind(TopicService::class, function ($app) {
            return new TopicService();
        });

        $this->app->bind(TreeService::class, function ($app) {
            return new TreeService();
        });

        $this->app->bind(TimelineService::class, function ($app) {
            return new TimelineService();
        });

        $this->app->bind(AlgorithmService::class, function ($app) {
            return new AlgorithmService();
        });
    }
}

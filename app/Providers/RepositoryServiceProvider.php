<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repository\Tree\TreeInterface;
use App\Repository\Tree\TreeRepository;
use App\Repository\Timeline\TimelineInterface;
use App\Repository\Timeline\TimelineRepository;
use App\Repository\Topic\TopicInterface;
use App\Repository\Topic\TopicRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(TreeInterface::class, TreeRepository::class);
        $this->app->bind(TimelineInterface::class, TimelineRepository::class);
        $this->app->bind(TopicInterface::class, TopicRepository::class);
    }
}

<?php

namespace App\Providers;

use App\Events\ExampleEvent;
use App\Events\SendOtpEvent;
use App\Listeners\ExampleListener;
use App\Listeners\SendOtpListener;
use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \App\Events\ExampleEvent::class => [
            \App\Listeners\ExampleListener::class,
        ],
        SendOtpEvent::class => [
            SendOtpListener::class,
        ],
    ];
}

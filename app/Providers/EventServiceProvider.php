<?php

namespace App\Providers;

use App\Mail\welcomeEmail;
use App\Events\ExampleEvent;
use App\Events\SendOtpEvent;
use App\Events\WelcomeMailEvent;
use App\Listeners\ExampleListener;
use App\Listeners\SendOtpListener;
use App\Listeners\WelcomeMailListener;
use App\Events\ThankToSubmitterMailEvent;
use App\Events\ForgotPasswordSendOtpEvent;
use App\Listeners\ThankToSubmitterMailListener;
use App\Listeners\ForgotPasswordSendOtpListener;
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
        WelcomeMailEvent::class => [
            WelcomeMailListener::class,
        ],
        ForgotPasswordSendOtpEvent::class => [
            ForgotPasswordSendOtpListener::class,
        ],
        ThankToSubmitterMailEvent::class => [
            ThankToSubmitterMailListener::class,
        ],
    ];
}

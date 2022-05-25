<?php

namespace App\Providers;

use App\Mail\welcomeEmail;
use App\Events\ExampleEvent;
use App\Events\SendOtpEvent;
use App\Events\WelcomeMailEvent;
use App\Listeners\ExampleListener;
use App\Listeners\SendOtpListener;
use App\Events\CampForumPostMailEvent;
use App\Listeners\WelcomeMailListener;
use App\Events\CampForumThreadMailEvent;
use App\Events\ThankToSubmitterMailEvent;
use App\Events\ForgotPasswordSendOtpEvent;
use App\Listeners\CampForumPostMailListener;
use App\Listeners\CampForumThreadMailListener;
use App\Listeners\ThankToSubmitterMailListener;
use App\Listeners\ForgotPasswordSendOtpListener;
use App\Events\PromotedDelegatesMailEvent;
use App\Listeners\PromotedDelegatesMailListener;
use App\Events\SupportRemovedMailEvent;
use App\Listeners\SupportRemovedMailListener;
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
        CampForumThreadMailEvent::class => [
            CampForumThreadMailListener::class,
        ],
        CampForumPostMailEvent::class => [
            CampForumPostMailListener::class,
        ],
        PromotedDelegatesMailEvent::class => [
            PromotedDelegatesMailListener::class,
        ],
        SupportRemovedMailEvent::class => [
            SupportRemovedMailListener::class,
        ],
    ];
}

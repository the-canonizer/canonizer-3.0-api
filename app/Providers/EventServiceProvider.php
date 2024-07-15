<?php

namespace App\Providers;

use App\Mail\welcomeEmail;
use App\Events\ExampleEvent;
use App\Events\SendOtpEvent;
use App\Events\CampForumEvent;
use App\Events\LogActivityEvent;
use App\Events\WelcomeMailEvent;
use App\Listeners\ExampleListener;
use App\Listeners\SendOtpListener;
use App\Listeners\CampForumListener;
use App\Events\NotifySupportersEvent;
use App\Events\SupportAddedMailEvent;
use App\Events\CampForumPostMailEvent;
use App\Listeners\LogActivityListener;
use App\Listeners\WelcomeMailListener;
use App\Events\SupportRemovedMailEvent;
use App\Events\CampForumThreadMailEvent;
use App\Events\CampLeaderAssignedEvent;
use App\Events\CampLeaderRemovedEvent;
use App\Events\NotifyAdministratorEvent;
use App\Events\SendPushNotificationEvent;
use App\Events\ThankToSubmitterMailEvent;
use App\Events\ForgotPasswordSendOtpEvent;
use App\Events\PromotedDelegatesMailEvent;
use App\Listeners\NotifySupportersListner;
use App\Listeners\SupportAddedMailListener;
use App\Listeners\CampForumPostMailListener;
use App\Listeners\NotifyAdministratorListner;
use App\Listeners\SupportRemovedMailListener;
use App\Listeners\CampForumThreadMailListener;
use App\Listeners\SendPushNotificationListner;
use App\Listeners\ThankToSubmitterMailListener;
use App\Listeners\ForgotPasswordSendOtpListener;
use App\Listeners\PromotedDelegatesMailListener;
use App\Events\NotifyDelegatedAndDelegatorMailEvent;
use App\Listeners\NotifyDelegatedAndDelegatorMailListener;
use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;
use App\Events\UnarchiveCampMailEvent;
use App\Listeners\UnarchiveCampMailListener;
use App\Events\EmailChangeEvent;
use App\Listeners\CampLeaderChangedListener;
use App\Listeners\EmailChangeListener;

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
        LogActivityEvent::class => [
            LogActivityListener::class,
        ],
        SupportAddedMailEvent::class => [
            SupportAddedMailListener::class,
        ],
        NotifyDelegatedAndDelegatorMailEvent::class => [
            NotifyDelegatedAndDelegatorMailListener::class,
        ],
        CampForumEvent::class => [
            CampForumListener::class,
        ],
        NotifySupportersEvent::class => [
            NotifySupportersListner::class
        ],
        SendPushNotificationEvent::class => [
            SendPushNotificationListner::class
        ],
        NotifyAdministratorEvent::class => [
            NotifyAdministratorListner::class
        ],
        UnarchiveCampMailEvent::class => [
            UnarchiveCampMailListener::class
        ],
        EmailChangeEvent::class => [
            EmailChangeListener::class
        ],
        CampLeaderAssignedEvent::class => [
            CampLeaderChangedListener::class
        ],
        CampLeaderRemovedEvent::class => [
            CampLeaderChangedListener::class
        ],
    ];
}

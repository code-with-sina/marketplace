<?php

namespace App\Providers;



use App\Events\TradeRequestEvent;
use App\Events\RejectRequestEvent;
use App\Events\PaymentReleasedEvent;
use Illuminate\Auth\Events\Registered;
use App\Events\CancelTradeRequestEvent;
use App\Events\AcceptedTradeRequestEvent;
use App\Listeners\PaymentReleasedListener;
use App\Listeners\TradeRequestNotification;
use App\Listeners\RejectRequestNotification;
use App\Listeners\AcceptedTradeRequestNotification;
use App\Listeners\SendCancelTradeRequestNotification;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        RejectRequestEvent::class => [
            RejectRequestNotification::class,
        ],

        TradeRequestEvent::class => [
            TradeRequestNotification::class,
        ],

        AcceptedTradeRequestEvent::class => [
            AcceptedTradeRequestNotification::class,
        ],

        CancelTradeRequestEvent::class => [
            SendCancelTradeRequestNotification::class,
        ],

        PaymentReleasedEvent::class => [
            PaymentReleasedListener::class,
        ],

    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

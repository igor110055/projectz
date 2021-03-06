<?php

namespace App\Providers;

use App\Models\OrderLogger;
use App\Events\OpenPosition;
use App\Observers\OrderLoggerRecord;
use App\Observers\OrderLoggerObserver;
use Illuminate\Auth\Events\Registered;
use App\Listeners\TrackSymbolWithWebsocket;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // Registered::class => [
        //     SendEmailVerificationNotification::class,
        // ],
        //
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
    ];


    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        OrderLogger::observe(OrderLoggerObserver::class);
    }


    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return true;
    }
}

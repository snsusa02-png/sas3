<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        'App\Events\Auth\UserRegistered'=>[
            'App\Listeners\Auth\SendRegisteredNotification'
        ],
        'Illuminate\Auth\Events\Login' => [
            'App\Listeners\UserLoggedIn',
        ],
        'App\Events\OrderChangeEvent' => [
            'App\Listeners\OrderChangeListener',
        ],
        'App\Events\docs1CLoadedEvent' => [
            'App\Listeners\docs1CLoadedListener',
        ],
        'App\Events\notifyEvent' => [
            'App\Listeners\notifyEventListener',
        ],
        'Illuminate\Auth\Events\Logout' => [
            'App\Listeners\UserLoggedOut',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}

<?php

namespace App\Providers;

//use App\Listeners\SendEmailVerificationNotification;
use App\Events\RegisterEvent;
use App\Listeners\SendEmailVerificationNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(RegisterEvent::class, SendEmailVerificationNotification::class);
    }
}

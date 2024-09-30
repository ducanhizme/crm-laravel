<?php

namespace App\Providers;

use App\Events\InvitationSentEvent;
use App\Listeners\SendEmailInvitation;
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
        \Event::listen(InvitationSentEvent::class,SendEmailInvitation::class);
    }
}

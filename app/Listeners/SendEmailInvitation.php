<?php

namespace App\Listeners;

use App\Events\InvitationSentEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendEmailInvitation implements ShouldQueue
{
    use InteractsWithQueue, Queueable;
    /**
     * Create the event listener.
     */
    public function __construct(InvitationSentEvent $event)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $invitation = $event->invitation;
        \Mail::to($invitation->email)->send(new \App\Mail\InvitationEmail($invitation));
    }
}

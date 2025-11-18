<?php

namespace App\Listeners;

use App\Events\CompteCreated;
use App\Services\EmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendClientNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected EmailService $emailService;

    /**
     * Create the event listener.
     */
    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Handle the event.
     */
    public function handle(CompteCreated $event): void
    {
        $code = $event->user->verification_code;
        $this->emailService->sendVerificationEmail($event->user->email, $code);
    }
}

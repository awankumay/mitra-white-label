<?php

namespace App\Listeners\Security;

use Core\Security\Enums\SecurityEventType;
use Core\Security\Events\SecurityEventOccurred;
use Illuminate\Auth\Events\Login;

class RecordLoginSucceeded
{
    public function handle(Login $event): void
    {
        SecurityEventOccurred::dispatch(
            SecurityEventType::LoginSucceeded,
            method_exists($event->user, 'getKey') ? $event->user->getKey() : null,
        );
    }
}

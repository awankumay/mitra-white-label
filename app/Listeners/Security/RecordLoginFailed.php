<?php

namespace App\Listeners\Security;

use Core\Security\Enums\SecurityEventType;
use Core\Security\Events\SecurityEventOccurred;
use Illuminate\Auth\Events\Failed;

class RecordLoginFailed
{
    public function handle(Failed $event): void
    {
        SecurityEventOccurred::dispatch(
            SecurityEventType::LoginFailed,
            $event->user?->getKey(),
        );
    }
}

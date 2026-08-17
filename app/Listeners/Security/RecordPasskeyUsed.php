<?php

namespace App\Listeners\Security;

use Core\Security\Enums\SecurityEventType;
use Core\Security\Events\SecurityEventOccurred;
use Jeffgreco13\FilamentBreezy\Events\PasskeyUsedToAuthenticate;

class RecordPasskeyUsed
{
    public function handle(PasskeyUsedToAuthenticate $event): void
    {
        SecurityEventOccurred::dispatch(
            SecurityEventType::LoginSucceeded,
            $event->passkey->authenticatable_id,
            ['method' => 'passkey', 'passkey_name' => $event->passkey->name],
        );
    }
}

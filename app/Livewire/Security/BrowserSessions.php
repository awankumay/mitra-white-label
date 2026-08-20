<?php

namespace App\Livewire\Security;

use Core\Security\Enums\SecurityEventType;
use Core\Security\Events\SecurityEventOccurred;
use Jeffgreco13\FilamentBreezy\Livewire\BrowserSessions as BreezyBrowserSessions;

class BrowserSessions extends BreezyBrowserSessions
{
    public static function logoutOtherBrowserSessions($password): void
    {
        parent::logoutOtherBrowserSessions($password);

        SecurityEventOccurred::dispatch(
            SecurityEventType::SessionRevoked,
            auth()->id(),
        );
    }
}

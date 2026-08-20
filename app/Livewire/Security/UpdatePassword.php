<?php

namespace App\Livewire\Security;

use Core\Security\Enums\SecurityEventType;
use Core\Security\Events\SecurityEventOccurred;
use Jeffgreco13\FilamentBreezy\Livewire\UpdatePassword as BreezyUpdatePassword;

class UpdatePassword extends BreezyUpdatePassword
{
    public function submit(): void
    {
        parent::submit();

        SecurityEventOccurred::dispatch(
            SecurityEventType::PasswordChanged,
            $this->user->getKey(),
        );
    }
}

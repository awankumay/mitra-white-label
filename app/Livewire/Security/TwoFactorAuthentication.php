<?php

namespace App\Livewire\Security;

use Core\Security\Enums\SecurityEventType;
use Core\Security\Events\SecurityEventOccurred;
use Jeffgreco13\FilamentBreezy\Livewire\TwoFactorAuthentication as BreezyTwoFactorAuthentication;

class TwoFactorAuthentication extends BreezyTwoFactorAuthentication
{
    public function enableAction(): \Filament\Actions\Action
    {
        $action = parent::enableAction();

        return $action->action(function () {
            $this->user->enableTwoFactorAuthentication();
            SecurityEventOccurred::dispatch(
                SecurityEventType::TwoFactorEnabled,
                $this->user->getKey(),
            );
        });
    }

    public function disableAction(): \Filament\Actions\Action
    {
        $action = parent::disableAction();

        return $action->action(function () {
            $this->user->disableTwoFactorAuthentication();
            SecurityEventOccurred::dispatch(
                SecurityEventType::TwoFactorDisabled,
                $this->user->getKey(),
            );
        });
    }
}

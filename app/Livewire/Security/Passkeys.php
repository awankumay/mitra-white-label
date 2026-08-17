<?php

namespace App\Livewire\Security;

use Core\Security\Enums\SecurityEventType;
use Core\Security\Events\SecurityEventOccurred;
use Filament\Actions\DeleteAction;
use Jeffgreco13\FilamentBreezy\Livewire\Passkeys as BreezyPasskeys;

class Passkeys extends BreezyPasskeys
{
    protected function getTableActions(): array
    {
        return [
            parent::getTableActions()[0],
            DeleteAction::make()
                ->iconButton()
                ->action(function ($record) {
                    SecurityEventOccurred::dispatch(
                        SecurityEventType::PasskeyRevoked,
                        $this->user->getKey(),
                        ['passkey_name' => $record->name],
                    );
                    $record->delete();
                }),
        ];
    }

    public function storePasskey(string $passkey): void
    {
        parent::storePasskey($passkey);

        SecurityEventOccurred::dispatch(
            SecurityEventType::PasskeyRegistered,
            $this->user->getKey(),
            ['passkey_name' => $this->name],
        );
    }
}

<?php

namespace App\Filament\Resources\OrganizationalUnits\Schemas;

use Core\Organization\Enums\OrganizationalUnitType;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrganizationalUnitInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('type')
                    ->badge()
                    ->formatStateUsing(fn (OrganizationalUnitType $state): string => $state->value),
                TextEntry::make('organization.name')
                    ->label('Organization'),
                TextEntry::make('parent.name')
                    ->label('Parent')
                    ->placeholder('—'),
            ]);
    }
}

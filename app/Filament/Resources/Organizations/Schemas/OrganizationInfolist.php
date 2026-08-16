<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrganizationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('organizational_units_count')
                    ->label('Organizational Units')
                    ->state(fn ($record) => $record->organizationalUnits()->count()),
            ]);
    }
}

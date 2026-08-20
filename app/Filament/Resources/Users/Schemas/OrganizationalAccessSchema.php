<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class OrganizationalAccessSchema
{
    public static function components(): array
    {
        return [
            Select::make('units')
                ->label('Organizational Units')
                ->relationship('units', 'name')
                ->multiple()
                ->searchable()
                ->preload(),
            Select::make('primary_organizational_unit_id')
                ->label('Primary Unit')
                ->relationship('primaryOrganizationalUnit', 'name')
                ->options(fn ($record) => $record === null ? [] : $record->units->pluck('name', 'id'))
                ->searchable()
                ->preload(),
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::components());
    }
}

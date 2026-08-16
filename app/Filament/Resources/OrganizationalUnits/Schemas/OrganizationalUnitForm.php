<?php

namespace App\Filament\Resources\OrganizationalUnits\Schemas;

use Core\Organization\Enums\OrganizationalUnitType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class OrganizationalUnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
                Select::make('parent_id')
                    ->relationship('parent', 'name', modifyQueryUsing: fn ($query, Get $get) => $query->where('organization_id', $get('organization_id')))
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->visible(fn (Get $get): bool => filled($get('organization_id'))),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->options(OrganizationalUnitType::class)
                    ->required(),
            ]);
    }
}

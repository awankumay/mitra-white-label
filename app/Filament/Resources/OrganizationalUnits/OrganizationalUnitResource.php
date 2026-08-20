<?php

namespace App\Filament\Resources\OrganizationalUnits;

use App\Filament\Resources\OrganizationalUnits\Pages\CreateOrganizationalUnit;
use App\Filament\Resources\OrganizationalUnits\Pages\EditOrganizationalUnit;
use App\Filament\Resources\OrganizationalUnits\Pages\ListOrganizationalUnits;
use App\Filament\Resources\OrganizationalUnits\Pages\ViewOrganizationalUnit;
use App\Filament\Resources\OrganizationalUnits\Schemas\OrganizationalUnitForm;
use App\Filament\Resources\OrganizationalUnits\Schemas\OrganizationalUnitInfolist;
use App\Filament\Resources\OrganizationalUnits\Tables\OrganizationalUnitsTable;
use BackedEnum;
use Core\Organization\Models\OrganizationalUnit;
use Core\Support\Scope;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationalUnitResource extends Resource
{
    protected static ?string $model = OrganizationalUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Square3Stack3d;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string
    {
        return trans('nav.administration');
    }

    public static function form(Schema $schema): Schema
    {
        return OrganizationalUnitForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrganizationalUnitInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrganizationalUnitsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrganizationalUnits::route('/'),
            'create' => CreateOrganizationalUnit::route('/create'),
            'edit' => EditOrganizationalUnit::route('/{record}/edit'),
            'view' => ViewOrganizationalUnit::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(
                ! Scope::isSuperAdmin(auth()->user()),
                fn (Builder $q) => $q->whereIn('id', auth()->user()->units()->pluck('organizational_units.id'))
            );
    }
}

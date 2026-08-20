<?php

namespace App\Filament\Pages\Settings;

use BackedEnum;
use Core\Contracts\SettingsRepository;
use Core\Settings\Enums\SettingScope;
use Core\Settings\SettingsRegistry;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * @property-read Schema $form
 */
class ApplicationSettings extends Page
{
    protected string $view = 'filament.pages.settings.application-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog;

    protected static ?int $navigationSort = 50;

    protected static ?string $title = 'Application Settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return trans('nav.administration');
    }

    public static function canAccess(): bool
    {
        return (bool) Auth::user()?->can('view:settings');
    }

    public function mount(): void
    {
        $repository = app(SettingsRepository::class);
        $registry = app(SettingsRegistry::class);

        foreach ($registry->keysInGroup('application') as $key) {
            $field = str_replace('.', '_', $key);
            $this->data[$field] = $repository->getForScope($key, SettingScope::System)
                ?? $registry->definition($key)['default'];
        }

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('app_name')
                    ->label('Nama Aplikasi')
                    ->required(),
                TextInput::make('app_locale')
                    ->label('Locale Default')
                    ->required(),
                TextInput::make('app_timezone')
                    ->label('Timezone Default')
                    ->required(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        abort_unless(Auth::user()?->can('update:settings'), 403);

        $repository = app(SettingsRepository::class);
        $registry = app(SettingsRegistry::class);
        $state = $this->form->getState();

        foreach ($registry->keysInGroup('application') as $key) {
            $field = str_replace('.', '_', $key);
            $repository->set($key, $state[$field], SettingScope::System);
        }

        Notification::make()
            ->success()
            ->title('Pengaturan disimpan')
            ->send();
    }
}

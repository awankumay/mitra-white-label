<?php

namespace App\Filament\Pages\Settings;

use BackedEnum;
use Core\Contracts\SettingsRepository;
use Core\Settings\Enums\SettingScope;
use Core\Settings\SettingsRegistry;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * @property-read Schema $form
 */
class SecuritySettings extends Page
{
    protected string $view = 'filament.pages.settings.security-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog;

    protected static ?int $navigationSort = 52;

    protected static ?string $title = 'Security Settings';

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

        foreach ($registry->keysInGroup('security') as $key) {
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
                Toggle::make('security_two_factor_required')
                    ->label('Wajibkan 2FA')
                    ->helperText('Super admin selalu wajib 2FA.'),
                TextInput::make('security_password_min_length')
                    ->label('Panjang Minimum Password')
                    ->numeric()
                    ->minValue(6)
                    ->maxValue(128),
                Toggle::make('security_password_require_complexity')
                    ->label('Wajib Password Kompleks')
                    ->helperText('Membutuhkan huruf besar/kecil, angka, dan simbol.'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        abort_unless(Auth::user()?->can('update:settings'), 403);

        $repository = app(SettingsRepository::class);
        $registry = app(SettingsRegistry::class);
        $state = $this->form->getState();

        foreach ($registry->keysInGroup('security') as $key) {
            $field = str_replace('.', '_', $key);
            $repository->set($key, $state[$field], SettingScope::System);
        }

        Notification::make()
            ->success()
            ->title('Pengaturan disimpan')
            ->send();
    }
}

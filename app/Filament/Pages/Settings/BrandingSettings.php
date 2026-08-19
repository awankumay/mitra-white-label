<?php

namespace App\Filament\Pages\Settings;

use Core\Contracts\OrganizationContext;
use Core\Contracts\SettingsRepository;
use Core\Settings\Enums\SettingScope;
use Core\Settings\SettingsRegistry;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read Schema $form
 */
class BrandingSettings extends Page
{
    protected string $view = 'filament.pages.settings.branding-settings';

    protected static ?int $navigationSort = 51;

    protected static ?string $title = 'Branding Settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return trans('nav.administration');
    }

    public static function canAccess(): bool
    {
        return (bool) Auth::user()?->can('view:branding');
    }

    public function mount(): void
    {
        if (! app(OrganizationContext::class)->has()) {
            Notification::make()
                ->warning()
                ->title('Belum ada organisasi aktif')
                ->send();
        }

        $repository = app(SettingsRepository::class);
        $registry = app(SettingsRegistry::class);
        $orgId = app(OrganizationContext::class)->organizationId();

        foreach ($registry->keysInGroup('branding') as $key) {
            $field = str_replace('.', '_', $key);
            $this->data[$field] = ($orgId !== null
                ? $repository->getForScope($key, SettingScope::Organization, $orgId)
                : null) ?? $registry->definition($key)['default'];
        }

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('branding_company_name')
                    ->label('Nama Perusahaan'),
                FileUpload::make('branding_logo')
                    ->label('Logo')
                    ->image()
                    ->disk(config('core.branding.disk'))
                    ->directory('brand'),
                FileUpload::make('branding_dark_logo')
                    ->label('Logo (Dark Mode)')
                    ->image()
                    ->disk(config('core.branding.disk'))
                    ->directory('brand'),
                FileUpload::make('branding_favicon')
                    ->label('Favicon')
                    ->image()
                    ->disk(config('core.branding.disk'))
                    ->directory('brand'),
                ColorPicker::make('branding_primary_color')
                    ->label('Warna Primer'),
                ColorPicker::make('branding_secondary_color')
                    ->label('Warna Sekunder'),
                TextInput::make('branding_footer_text')
                    ->label('Teks Footer'),
            ])
            ->statePath('data')
            ->disabled(fn (): bool => ! app(OrganizationContext::class)->has());
    }

    public function save(): void
    {
        $orgId = app(OrganizationContext::class)->organizationId();
        abort_unless(Auth::user()?->can('update:branding') && $orgId !== null, 403);

        $repository = app(SettingsRepository::class);
        $registry = app(SettingsRegistry::class);
        $disk = Storage::disk(config('core.branding.disk'));
        $state = $this->form->getState();

        foreach ($registry->keysInGroup('branding') as $key) {
            $field = str_replace('.', '_', $key);
            $newValue = $state[$field];

            if (in_array($key, ['branding.logo', 'branding.dark_logo', 'branding.favicon'], true)) {
                $old = $repository->getForScope($key, SettingScope::Organization, $orgId);
                if ($old !== null && $old !== $newValue && $disk->exists($old)) {
                    $disk->delete($old);
                }
            }

            $repository->set($key, $newValue, SettingScope::Organization, $orgId);
        }

        Notification::make()
            ->success()
            ->title('Branding disimpan')
            ->send();
    }
}

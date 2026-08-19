<?php

namespace Core\Branding;

use Core\Settings\Enums\SettingScope;
use Core\Settings\SettingsRegistry;
use Illuminate\Support\ServiceProvider;

class BrandingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BrandingResolver::class);
    }

    public function boot(): void
    {
        $this->app->make(SettingsRegistry::class)->register([
            'branding.company_name' => [
                'type' => 'string',
                'default' => null,
                'scopes' => [SettingScope::Organization, SettingScope::System],
                'group' => 'branding',
            ],
            'branding.logo' => [
                'type' => 'string',
                'default' => null,
                'scopes' => [SettingScope::Organization, SettingScope::System],
                'group' => 'branding',
            ],
            'branding.dark_logo' => [
                'type' => 'string',
                'default' => null,
                'scopes' => [SettingScope::Organization, SettingScope::System],
                'group' => 'branding',
            ],
            'branding.favicon' => [
                'type' => 'string',
                'default' => null,
                'scopes' => [SettingScope::Organization, SettingScope::System],
                'group' => 'branding',
            ],
            'branding.primary_color' => [
                'type' => 'string',
                'default' => null,
                'scopes' => [SettingScope::Organization, SettingScope::System],
                'group' => 'branding',
            ],
            'branding.secondary_color' => [
                'type' => 'string',
                'default' => null,
                'scopes' => [SettingScope::Organization, SettingScope::System],
                'group' => 'branding',
            ],
            'branding.footer_text' => [
                'type' => 'string',
                'default' => null,
                'scopes' => [SettingScope::Organization, SettingScope::System],
                'group' => 'branding',
            ],
        ]);
    }
}

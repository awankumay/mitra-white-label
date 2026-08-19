<?php

use Core\Branding\BrandingServiceProvider;
use Core\Context\ContextServiceProvider;
use Core\Settings\Enums\SettingScope;
use Core\Settings\SettingsServiceProvider;

return [
    'providers' => [
        ContextServiceProvider::class,
        SettingsServiceProvider::class,
        BrandingServiceProvider::class,
    ],
    'organization' => [
        'max_depth' => 10,
    ],
    'context' => [
        'session_key' => 'context.unit_id',
    ],
    'branding' => [
        'disk' => env('BRANDING_DISK', 'public'),
    ],
    'settings' => [
        'cache_ttl' => (int) env('SETTINGS_CACHE_TTL', 3600),
        'definitions' => [
            'app.name' => [
                'type' => 'string',
                'default' => env('APP_NAME', 'Mitra White Label'),
                'scopes' => [SettingScope::System],
                'group' => 'application',
            ],
            'app.locale' => [
                'type' => 'string',
                'default' => 'id',
                'scopes' => [SettingScope::System],
                'group' => 'application',
            ],
            'app.timezone' => [
                'type' => 'string',
                'default' => 'Asia/Jakarta',
                'scopes' => [SettingScope::System, SettingScope::Unit, SettingScope::User],
                'group' => 'application',
            ],
        ],
    ],
    'auth' => [
        'two_factor' => [
            'enabled' => true,
            'force' => (bool) env('AUTH_2FA_FORCE', false),
            'super_admin_forced' => true,
        ],
        'passkey' => [
            'enabled' => true,
            'relying_party_id' => env('AUTH_PASSKEY_RP_ID'),
            'relying_party_name' => env('APP_NAME', 'Mitra White Label'),
        ],
        'password' => [
            'rules' => ['min:8', 'mixedCase', 'numbers', 'symbols', 'uncompromised(3)'],
        ],
    ],
];

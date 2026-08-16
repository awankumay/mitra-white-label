<?php

return [
    'providers' => [
        Core\Context\ContextServiceProvider::class,
    ],
    'organization' => [
        'max_depth' => 10,
    ],
    'context' => [
        'session_key' => 'context.unit_id',
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

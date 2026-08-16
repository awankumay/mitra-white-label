<?php

return [
    'providers' => [
        // Sub-provider Core didaftarkan di sini saat domain dibuat,
        // contoh: Core\Organization\OrganizationServiceProvider::class.
    ],
    'organization' => [
        'max_depth' => 10,
    ],
    // Domain sub-sistem ditambahkan saat milestone yang membutuhkannya,
    // misal: 'context' => [...], 'settings' => [...]
];

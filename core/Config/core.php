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
];

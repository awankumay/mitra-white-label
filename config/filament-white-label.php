<?php

return [

    'enabled' => env('FILAMENT_WHITE_LABEL_ENABLED', true),

    'cache_ttl' => env('FILAMENT_WHITE_LABEL_CACHE_TTL', 300),

    'disk' => env('FILAMENT_WHITE_LABEL_DISK', 'public'),

    'storage_path_prefix' => 'brand',

    'defaults' => [
        'brand_name' => env('APP_NAME', 'Filament'),
        'logo' => null,
        'dark_mode_logo' => null,
        'favicon' => null,
        'brand_logo_height' => null,
        'font_family' => 'Inter',
        'colors' => [
            'primary' => null,
            'secondary' => null,
            'danger' => null,
            'warning' => null,
            'success' => null,
            'info' => null,
        ],
        'topbar' => true,
        'top_navigation' => false,
        'sidebar_collapsible_on_desktop' => false,
        'sidebar_fully_collapsible_on_desktop' => false,
        'collapsible_navigation_groups' => true,
        'breadcrumbs' => true,
        'unsaved_changes_alerts' => false,
        'spa_mode' => false,
        'database_notifications' => false,
        'database_notifications_polling' => '30s',

        'border_radius' => 'default',
        'input_border_radius' => null,
        'badge_shape' => 'default',
        'shadow_intensity' => 'default',

        'container_width' => null,
        'sidebar_width' => null,
        'heading_size' => 'default',
        'nav_item_spacing' => 'default',

        'font_scale' => null,
        'form_density' => 'default',
        'table_row_density' => 'default',
        'modal_size' => 'default',
        'transition_speed' => 'default',
        'footer_text' => null,
        'footer_links' => [],
    ],

    'security' => [
        'disable_custom_css' => env('FILAMENT_WHITE_LABEL_DISABLE_CSS', false),
        'max_css_length' => 50000,
    ],

    'ui' => [
        'show_preview' => env('FILAMENT_WHITE_LABEL_PREVIEW', true),
        'navigation_group' => 'nav.administration',
        'navigation_sort' => 88,
    ],

    'login' => [
        'enabled' => env('FILAMENT_WHITE_LABEL_LOGIN_ENABLED', true),
    ],

    'fonts' => [
        'enabled' => true,
        'api_key' => env('GOOGLE_FONTS_API_KEY'),
    ],

];

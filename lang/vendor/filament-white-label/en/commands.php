<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Filament White-Label — Command Translations
    |--------------------------------------------------------------------------
    |
    | Strings for the install and clear-cache artisan commands.
    | Copy this file to create translations for other languages.
    |
    | Usage: __('filament-white-label::commands.install.description')
    |
    */

    // ─── Commands: Install ─────
    'install.description' => 'Install Filament White-Label — publish config and migrations',
    'install.banner' => 'Filament White-Label Installer',
    'install.success' => 'Filament White-Label installed successfully.',
    'install.next_steps' => 'Next steps:',
    'install.step_1' => '1. Review the published migration in database/migrations/',
    'install.step_2' => '2. Run php artisan migrate',
    'install.step_3' => '3. Review the config at config/filament-white-label.php',
    'install.step_4' => '4. Add traits to your Tenant model and PanelProvider',
    'install.docs' => '📖 Documentation: https://github.com/ashrafic/filament-white-label',
    'install.config_skipped' => '⏭  Config already published — skipping.',
    'install.config_published' => '✅ Config published to config/filament-white-label.php',
    'install.migration_published' => '✅ Migration published to database/migrations/',

    // ─── Commands: Clear Cache ─────
    'clear_cache.description' => 'Clear the white-label brand settings cache',
    'clear_cache.option_tenant' => 'Clear cache for a specific tenant ID',
    'clear_cache.option_panel' => 'Clear cache for a specific panel ID',
    'clear_cache.success' => 'White-label cache cleared.',

];

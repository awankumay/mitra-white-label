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
    'install.description' => 'Pasang Filament White-Label — publikasikan config dan migrasi',
    'install.banner' => 'Pemasang Filament White-Label',
    'install.success' => 'Filament White-Label berhasil dipasang.',
    'install.next_steps' => 'Langkah berikutnya:',
    'install.step_1' => '1. Tinjau migrasi yang dipublikasikan di database/migrations/',
    'install.step_2' => '2. Jalankan php artisan migrate',
    'install.step_3' => '3. Tinjau config di config/filament-white-label.php',
    'install.step_4' => '4. Tambahkan trait ke model Tenant dan PanelProvider Anda',
    'install.docs' => '📖 Dokumentasi: https://github.com/ashrafic/filament-white-label',
    'install.config_skipped' => '⏭  Config sudah dipublikasikan — dilewati.',
    'install.config_published' => '✅ Config dipublikasikan ke config/filament-white-label.php',
    'install.migration_published' => '✅ Migrasi dipublikasikan ke database/migrations/',

    // ─── Commands: Clear Cache ─────
    'clear_cache.description' => 'Bersihkan cache pengaturan white-label brand',
    'clear_cache.option_tenant' => 'Bersihkan cache untuk ID tenant tertentu',
    'clear_cache.option_panel' => 'Bersihkan cache untuk ID panel tertentu',
    'clear_cache.success' => 'Cache white-label dibersihkan.',

];

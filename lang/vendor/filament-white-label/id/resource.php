<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Filament White-Label — Resource Translations
    |--------------------------------------------------------------------------
    |
    | Labels, sections, fields, and options for the White Label resource UI.
    | Copy this file to create translations for other languages.
    |
    | Usage: __('filament-white-label::resource.sections.brand_identity')
    |
    */

    // ─── Navigation & Resource Labels ─────
    'navigation.label' => 'White Label',
    'label.singular' => 'Pengaturan White Label',
    'label.plural' => 'Pengaturan White Label',
    'sub_navigation.brand' => 'Merek',
    'sub_navigation.layout' => 'Tata Letak',
    'sub_navigation.advanced' => 'Lanjutan',

    // ─── Page Titles ─────
    'page.brand.title' => 'Merek',
    'page.brand.nav_label' => 'Merek',
    'page.layout.title' => 'Tata Letak',
    'page.layout.nav_label' => 'Tata Letak',
    'page.advanced.title' => 'Lanjutan',
    'page.advanced.nav_label' => 'Lanjutan',

    // ─── Sections ─────
    'sections.brand_identity' => 'Identitas Merek',
    'sections.colors' => 'Warna',
    'sections.typography' => 'Tipografi',
    'sections.styling' => 'Penataan Gaya',
    'sections.custom_css' => 'CSS Kustom',
    'sections.navigation' => 'Navigasi',
    'sections.sidebar' => 'Bilah Sisi',
    'sections.display' => 'Tampilan',
    'sections.dimensions' => 'Dimensi',
    'sections.footer' => 'Footer',
    'sections.behavior' => 'Perilaku',
    'sections.notifications' => 'Notifikasi',

    // ─── Brand Fields ─────
    'fields.brand_name.label' => 'Nama Merek',
    'fields.logo_height.label' => 'Tinggi Logo',
    'fields.logo_height.placeholder' => '2.5rem',
    'fields.logo_height.helper_text' => 'Nilai tinggi CSS. Kosongkan untuk menggunakan default Filament.',
    'fields.logo_light.label' => 'Logo (Terang)',
    'fields.logo_dark.label' => 'Logo (Gelap)',
    'fields.logo_dark.helper_text' => 'Menggunakan logo terang jika tidak diatur.',
    'fields.favicon.label' => 'Favicon',

    // ─── Color Fields ─────
    'fields.colors.primary.label' => 'Utama',
    'fields.colors.secondary.label' => 'Sekunder',
    'fields.colors.danger.label' => 'Bahaya',
    'fields.colors.warning.label' => 'Peringatan',
    'fields.colors.success.label' => 'Sukses',
    'fields.colors.info.label' => 'Info',
    'fields.colors.palette.label' => 'Palet',
    'fields.colors.hex.label' => 'Hex',
    'fields.colors.custom_hex' => 'Hex kustom...',

    // ─── Typography Fields ─────
    'fields.font_family.label' => 'Keluarga Font',

    // ─── Styling Fields ─────
    'fields.border_radius.label' => 'Radius Sudut',
    'fields.border_radius.helper_text' => 'Sudut membulat pada tombol, kartu, input, modal, dan dropdown.',
    'fields.input_border_radius.label' => 'Radius Sudut Input',
    'fields.input_border_radius.helper_text' => 'Ganti radius sudut khusus untuk input teks dan select.',
    'fields.shadow_intensity.label' => 'Intensitas Bayangan',
    'fields.shadow_intensity.helper_text' => 'Bayangan kotak pada kartu dan panel dropdown.',
    'fields.badge_shape.label' => 'Bentuk Badge',
    'fields.badge_shape.helper_text' => 'Radius sudut dan padding untuk badge.',

    // ─── Custom CSS Fields ─────
    'fields.custom_css.label' => 'CSS Kustom',
    'fields.custom_css.helper_text' => 'CSS kustom akan disuntikkan ke dalam panel Anda. Tag <script> otomatis dihapus demi keamanan.',

    // ─── Layout Fields ─────
    'fields.topbar.label' => 'Bilah Atas',
    'fields.topbar.helper_text' => 'Tampilkan bilah atas dengan menu pengguna dan notifikasi.',
    'fields.top_navigation.label' => 'Navigasi Atas',
    'fields.top_navigation.helper_text' => 'Pindahkan navigasi dari bilah sisi ke bilah atas. Menonaktifkan bilah sisi.',
    'fields.sidebar_collapsible.label' => 'Bilah Sisi Dapat Dilipat',
    'fields.sidebar_collapsible.helper_text' => 'Memungkinkan bilah sisi dilipat menjadi ikon saja.',
    'fields.sidebar_fully_collapsible.label' => 'Bilah Sisi Sepenuhnya Dapat Dilipat',
    'fields.sidebar_fully_collapsible.helper_text' => 'Memungkinkan bilah sisi disembunyikan sepenuhnya.',
    'fields.collapsible_navigation_groups.label' => 'Grup Navigasi yang Dapat Dilipat',
    'fields.collapsible_navigation_groups.helper_text' => 'Izinkan grup navigasi untuk diperluas/dilipat.',
    'fields.breadcrumbs.label' => 'Breadcrumb',
    'fields.breadcrumbs.helper_text' => 'Tampilkan navigasi breadcrumb.',
    'fields.content_width.label' => 'Lebar Konten',
    'fields.content_width.helper_text' => 'Lebar maksimum area konten utama.',
    'fields.sidebar_width.label' => 'Lebar Bilah Sisi',
    'fields.sidebar_width.helper_text' => 'Lebar tetap bilah sisi navigasi.',
    'fields.page_heading_size.label' => 'Ukuran Judul Halaman',
    'fields.page_heading_size.helper_text' => 'Ukuran font judul halaman (h1).',
    'fields.nav_item_spacing.label' => 'Jarak Item Navigasi',
    'fields.nav_item_spacing.helper_text' => 'Padding vertikal antar item navigasi bilah sisi.',
    'fields.footer_text.label' => 'Teks Footer',
    'fields.footer_text.placeholder' => 'Portal Admin ACME',
    'fields.footer_text.helper_text' => 'Teks yang ditampilkan di footer panel. Kosongkan untuk menyembunyikan.',
    'fields.footer_links.label' => 'Tautan Footer',
    'fields.footer_links.helper_text' => 'Tautan opsional yang ditampilkan di bawah teks footer.',
    'fields.footer_links.link_label.label' => 'Label',
    'fields.footer_links.link_url.label' => 'URL',
    'fields.footer_links.add_link' => 'Tambahkan tautan',

    // ─── Advanced Fields ─────
    'fields.unsaved_changes.label' => 'Peringatan Perubahan Belum Tersimpan',
    'fields.unsaved_changes.helper_text' => 'Peringatkan sebelum meninggalkan halaman dengan perubahan yang belum disimpan.',
    'fields.spa_mode.label' => 'Mode SPA',
    'fields.spa_mode.helper_text' => 'Mode aplikasi satu halaman untuk navigasi yang lebih cepat.',
    'fields.database_notifications.label' => 'Notifikasi Database',
    'fields.database_notifications.helper_text' => 'Aktifkan notifikasi database di bilah atas/bilah sisi.',
    'fields.polling_interval.label' => 'Interval Polling',
    'fields.font_scale.label' => 'Skala Font',
    'fields.font_scale.helper_text' => 'Pengali ukuran font global untuk aksesibilitas atau kepadatan.',
    'fields.form_density.label' => 'Kepadatan Formulir',
    'fields.form_density.helper_text' => 'Padding dan jarak dalam bagian dan kolom formulir.',
    'fields.table_row_density.label' => 'Kepadatan Baris Tabel',
    'fields.table_row_density.helper_text' => 'Padding vertikal baris tabel.',
    'fields.modal_size.label' => 'Ukuran Modal Default',
    'fields.modal_size.helper_text' => 'Lebar maksimum default untuk dialog modal.',
    'fields.transition_speed.label' => 'Kecepatan Transisi',
    'fields.transition_speed.helper_text' => 'Durasi transisi CSS pada tombol, dropdown, modal, dan bilah sisi.',

    // ─── Shared Options ─────
    'options.default' => 'Default',
    'options.none' => 'Tidak Ada',
    'options.small' => 'Kecil',
    'options.medium' => 'Sedang',
    'options.large' => 'Besar',
    'options.pill' => 'Pil',
    'options.inherit' => 'Mewarisi',
    'options.sharp' => 'Tajam',
    'options.rounded' => 'Membulat',
    'options.subtle' => 'Halus',
    'options.pronounced' => 'Tegas',
    'options.compact' => 'Padat',
    'options.spacious' => 'Legap',
    'options.fast' => 'Cepat',
    'options.slow' => 'Lambat',

    // ─── Layout-Specific Options ─────
    'options.content_width.1024' => '1024px (Sempit)',
    'options.content_width.1280' => '1280px',
    'options.content_width.full' => 'Lebar Penuh',
    'options.sidebar_width.320' => 'Default (320px)',
    'options.sidebar_width.260' => '260px',
    'options.sidebar_width.300' => '300px',
    'options.sidebar_width.340' => '340px',
    'options.page_heading_size.small' => 'Kecil',
    'options.page_heading_size.large' => 'Besar',
    'options.nav_item_spacing.default' => 'Default',
    'options.nav_item_spacing.compact' => 'Padat',
    'options.nav_item_spacing.spacious' => 'Legap',

    // ─── Advanced-Specific Options ─────
    'options.polling_interval.30s' => 'Default (30 detik)',
    'options.polling_interval.10s' => '10 detik',
    'options.polling_interval.60s' => '1 menit',
    'options.polling_interval.2m' => '2 menit',
    'options.polling_interval.5m' => '5 menit',
    'options.font_scale.90' => '90% (Padat)',
    'options.font_scale.100' => '100% (Default)',
    'options.font_scale.110' => '110% (Besar)',
    'options.font_scale.120' => '120% (Sangat Besar)',
    'options.modal_size.small' => 'Kecil (480px)',
    'options.modal_size.medium' => 'Sedang (640px)',
    'options.modal_size.large' => 'Besar (800px)',
    'options.modal_size.xl' => 'Sangat Besar (1024px)',
    'options.transition_speed.none' => 'Tidak Ada',

    // ─── Table Columns ─────
    'table.columns.logo' => 'Logo',
    'table.columns.brand' => 'Merek',
    'table.columns.updated' => 'Terakhir Diperbarui',

];

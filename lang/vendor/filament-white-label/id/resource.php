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
    'label.singular' => 'White Label Settings',
    'label.plural' => 'White Label Settings',
    'sub_navigation.brand' => 'Brand',
    'sub_navigation.layout' => 'Layout',
    'sub_navigation.advanced' => 'Advanced',

    // ─── Page Titles ─────
    'page.brand.title' => 'Brand',
    'page.brand.nav_label' => 'Brand',
    'page.layout.title' => 'Layout',
    'page.layout.nav_label' => 'Layout',
    'page.advanced.title' => 'Advanced',
    'page.advanced.nav_label' => 'Advanced',

    // ─── Sections ─────
    'sections.brand_identity' => 'Brand Identity',
    'sections.colors' => 'Colors',
    'sections.typography' => 'Typography',
    'sections.styling' => 'Styling',
    'sections.custom_css' => 'Custom CSS',
    'sections.navigation' => 'Navigation',
    'sections.sidebar' => 'Sidebar',
    'sections.display' => 'Display',
    'sections.dimensions' => 'Dimensions',
    'sections.footer' => 'Footer',
    'sections.behavior' => 'Behavior',
    'sections.notifications' => 'Notifications',

    // ─── Brand Fields ─────
    'fields.brand_name.label' => 'Brand Name',
    'fields.logo_height.label' => 'Logo Height',
    'fields.logo_height.placeholder' => '2.5rem',
    'fields.logo_height.helper_text' => 'CSS height value. Leave empty for Filament default.',
    'fields.logo_light.label' => 'Logo (Light)',
    'fields.logo_dark.label' => 'Logo (Dark)',
    'fields.logo_dark.helper_text' => 'Falls back to light logo if not set.',
    'fields.favicon.label' => 'Favicon',

    // ─── Color Fields ─────
    'fields.colors.primary.label' => 'Primary',
    'fields.colors.secondary.label' => 'Secondary',
    'fields.colors.danger.label' => 'Danger',
    'fields.colors.warning.label' => 'Warning',
    'fields.colors.success.label' => 'Success',
    'fields.colors.info.label' => 'Info',
    'fields.colors.palette.label' => 'Palette',
    'fields.colors.hex.label' => 'Hex',
    'fields.colors.custom_hex' => 'Custom hex...',

    // ─── Typography Fields ─────
    'fields.font_family.label' => 'Font Family',

    // ─── Styling Fields ─────
    'fields.border_radius.label' => 'Border Radius',
    'fields.border_radius.helper_text' => 'Rounded corners on buttons, cards, inputs, modals, and dropdowns.',
    'fields.input_border_radius.label' => 'Input Border Radius',
    'fields.input_border_radius.helper_text' => 'Override border radius specifically for text inputs and selects.',
    'fields.shadow_intensity.label' => 'Shadow Intensity',
    'fields.shadow_intensity.helper_text' => 'Box shadow on cards and dropdown panels.',
    'fields.badge_shape.label' => 'Badge Shape',
    'fields.badge_shape.helper_text' => 'Border radius and padding for badges.',

    // ─── Custom CSS Fields ─────
    'fields.custom_css.label' => 'Custom CSS',
    'fields.custom_css.helper_text' => 'Custom CSS will be injected into your panel. <script> tags are automatically removed for security.',

    // ─── Layout Fields ─────
    'fields.topbar.label' => 'Top Bar',
    'fields.topbar.helper_text' => 'Show the top bar with user menu and notifications.',
    'fields.top_navigation.label' => 'Top Navigation',
    'fields.top_navigation.helper_text' => 'Move navigation from sidebar to top bar. Disables sidebar.',
    'fields.sidebar_collapsible.label' => 'Collapsible Sidebar',
    'fields.sidebar_collapsible.helper_text' => 'Allows sidebar to collapse to icons only.',
    'fields.sidebar_fully_collapsible.label' => 'Fully Collapsible Sidebar',
    'fields.sidebar_fully_collapsible.helper_text' => 'Allows sidebar to hide completely.',
    'fields.collapsible_navigation_groups.label' => 'Collapsible Navigation Groups',
    'fields.collapsible_navigation_groups.helper_text' => 'Allow navigation groups to be expanded/collapsed.',
    'fields.breadcrumbs.label' => 'Breadcrumbs',
    'fields.breadcrumbs.helper_text' => 'Show breadcrumb navigation.',
    'fields.content_width.label' => 'Content Width',
    'fields.content_width.helper_text' => 'Max-width of the main content area.',
    'fields.sidebar_width.label' => 'Sidebar Width',
    'fields.sidebar_width.helper_text' => 'Fixed width of the navigation sidebar.',
    'fields.page_heading_size.label' => 'Page Heading Size',
    'fields.page_heading_size.helper_text' => 'Font size of page headings (h1).',
    'fields.nav_item_spacing.label' => 'Navigation Item Spacing',
    'fields.nav_item_spacing.helper_text' => 'Vertical padding between sidebar navigation items.',
    'fields.footer_text.label' => 'Footer Text',
    'fields.footer_text.placeholder' => 'ACME Admin Portal',
    'fields.footer_text.helper_text' => 'Text displayed in the panel footer. Leave empty to hide.',
    'fields.footer_links.label' => 'Footer Links',
    'fields.footer_links.helper_text' => 'Optional links displayed below the footer text.',
    'fields.footer_links.link_label.label' => 'Label',
    'fields.footer_links.link_url.label' => 'URL',
    'fields.footer_links.add_link' => 'Add link',

    // ─── Advanced Fields ─────
    'fields.unsaved_changes.label' => 'Unsaved Changes Alerts',
    'fields.unsaved_changes.helper_text' => 'Warn before leaving pages with unsaved changes.',
    'fields.spa_mode.label' => 'SPA Mode',
    'fields.spa_mode.helper_text' => 'Single-page application mode for faster navigation.',
    'fields.database_notifications.label' => 'Database Notifications',
    'fields.database_notifications.helper_text' => 'Enable database notifications in the topbar/sidebar.',
    'fields.polling_interval.label' => 'Polling Interval',
    'fields.font_scale.label' => 'Font Scale',
    'fields.font_scale.helper_text' => 'Global font size multiplier for accessibility or density.',
    'fields.form_density.label' => 'Form Density',
    'fields.form_density.helper_text' => 'Padding and spacing within form sections and fields.',
    'fields.table_row_density.label' => 'Table Row Density',
    'fields.table_row_density.helper_text' => 'Vertical padding of table rows.',
    'fields.modal_size.label' => 'Default Modal Size',
    'fields.modal_size.helper_text' => 'Default max-width for modal dialogs.',
    'fields.transition_speed.label' => 'Transition Speed',
    'fields.transition_speed.helper_text' => 'Duration of CSS transitions on buttons, dropdowns, modals, and sidebar.',

    // ─── Shared Options ─────
    'options.default' => 'Default',
    'options.none' => 'None',
    'options.small' => 'Small',
    'options.medium' => 'Medium',
    'options.large' => 'Large',
    'options.pill' => 'Pill',
    'options.inherit' => 'Inherit',
    'options.sharp' => 'Sharp',
    'options.rounded' => 'Rounded',
    'options.subtle' => 'Subtle',
    'options.pronounced' => 'Pronounced',
    'options.compact' => 'Compact',
    'options.spacious' => 'Spacious',
    'options.fast' => 'Fast',
    'options.slow' => 'Slow',

    // ─── Layout-Specific Options ─────
    'options.content_width.1024' => '1024px (Narrow)',
    'options.content_width.1280' => '1280px',
    'options.content_width.full' => 'Full Width',
    'options.sidebar_width.320' => 'Default (320px)',
    'options.sidebar_width.260' => '260px',
    'options.sidebar_width.300' => '300px',
    'options.sidebar_width.340' => '340px',
    'options.page_heading_size.small' => 'Small',
    'options.page_heading_size.large' => 'Large',
    'options.nav_item_spacing.default' => 'Default',
    'options.nav_item_spacing.compact' => 'Compact',
    'options.nav_item_spacing.spacious' => 'Spacious',

    // ─── Advanced-Specific Options ─────
    'options.polling_interval.30s' => 'Default (30s)',
    'options.polling_interval.10s' => '10 seconds',
    'options.polling_interval.60s' => '1 minute',
    'options.polling_interval.2m' => '2 minutes',
    'options.polling_interval.5m' => '5 minutes',
    'options.font_scale.90' => '90% (Compact)',
    'options.font_scale.100' => '100% (Default)',
    'options.font_scale.110' => '110% (Large)',
    'options.font_scale.120' => '120% (Extra Large)',
    'options.modal_size.small' => 'Small (480px)',
    'options.modal_size.medium' => 'Medium (640px)',
    'options.modal_size.large' => 'Large (800px)',
    'options.modal_size.xl' => 'Extra Large (1024px)',
    'options.transition_speed.none' => 'None',

    // ─── Table Columns ─────
    'table.columns.logo' => 'Logo',
    'table.columns.brand' => 'Brand',
    'table.columns.updated' => 'Last Updated',

];

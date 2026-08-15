# Filament & Livewire Rules

- **Filament Resources & Language Switcher:**
    - Pastikan `FilamentLanguageSwitcherPlugin` terkonfigurasi dengan default locale `id` (Bahasa Indonesia) dan opsional `en` (Bahasa Inggris).
    - Selalu gunakan `trans('domain.field.key')` (contoh: `trans('location.field.code')`) untuk penerjemahan label dan teks UI pada Filament Resource / Form / Table.
    - Pastikan Form Schemas dan Table Columns rapi, modular, dan terbaca (_scannable_).
- **Livewire Components:**
    - Minimalisasi _payload size_ pada properti publik Livewire.
    - Gunakan atribut `#[Url]` atau `#[Validate]` secara eksplisit jika diperlukan.

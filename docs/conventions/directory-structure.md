# Struktur Direktori

**Status:** Accepted
**Tanggal:** 2026-08-15
**Referensi:** ADR-001, ADR-002, ADR-005, ADR-006

## Peta Direktori

```text
core/                     # Core System — namespace Core\
├── Context/
├── Contracts/
├── Enums/
├── Exceptions/
├── Features/             # Feature Registry
├── Modules/              # Module contract & discovery
├── Organization/         # Organization, OrganizationalUnit, Assignment
├── Settings/
├── Branding/
├── Audit/
├── Security/             # SecurityEvents, 2FA policy, passkey support
├── Support/
└── Actions/

app/                      # Application layer — namespace App\
├── Models/
├── Domain/
├── Actions/
├── Services/
├── Contracts/
├── Enums/
├── Policies/
├── Support/
├── Filament/             # AdminPanelProvider, Resources, Pages, Widgets
└── Http/

modules/                  # Business modules — namespace Modules\<Name>\
└── <ModuleName>/
    ├── Module.php
    ├── Config/
    ├── Database/
    ├── Domain/
    ├── Filament/
    ├── Models/
    └── Policies/
```

## Konfigurasi Core

- Sumber: `core/Config/core.php` (di-commit in-repo).
- Bootstrap: `Core\CoreServiceProvider` di `bootstrap/providers.php`.
- Sub-provider domain: daftar di `config('core.providers')`.
- Publishable: `php artisan vendor:publish --tag=core-config` → menyalin
  ke `config/core.php` untuk override aplikasi.
- Pola kunci: `core.{domain}.{key}`.

## Aturan

- `core/` tidak pernah mengimpor `app/` atau `modules/` (ADR-005).
- `app/Filament/` hanya berisi komponen UI; logika bisnis di
  Domain/Actions/Services.
- `modules/` opsional; struktur module mengikuti ADR-006.
- Folder fisik dibuat sesuai kebutuhan milestone — peta ini adalah
  target akhir; `core/` sudah aktif sejak M1.

## Nama Folder

- `PascalCase` untuk folder modul (`modules/Inventory/`).
- `snake_case` untuk folder umum (jarang; ikuti konvensi Laravel).

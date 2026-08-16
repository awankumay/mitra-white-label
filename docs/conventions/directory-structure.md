# Struktur Direktori

**Status:** Accepted
**Tanggal:** 2026-08-16
**Referensi:** ADR-001, ADR-002, ADR-005, ADR-006, ADR-008, ADR-009, ADR-010,
spec `docs/superpowers/specs/2026-08-16-directory-structure-design.md`

## Peta Direktori

```text
core/                     # Core System — namespace Core\
├── CoreServiceProvider.php        # entry point; daftar sub-provider dari config
├── Config/
│   └── core.php                   # publishable; pola kunci core.{domain}.{key}
├── Database/
│   ├── Migrations/                # dimuat via loadMigrationsFrom
│   ├── Factories/
│   └── Seeders/
├── Contracts/                     # API publik Core (terpusat)
├── Exceptions/
├── Support/                       # utilitas generik murni
├── Enums/                         # enum lintas-domain Core
├── Filament/                      # komponen UI milik Core (resource/panel Core)
├── Context/                       # OrganizationContext, dll. (M4)
├── Organization/                  # Organization, OrganizationalUnit, Assignment (M3)
├── Settings/                      # (M7)
├── Branding/                      # (M7)
├── Audit/                         # (M8)
├── Security/                      # SecurityEvents, 2FA, passkey (M5)
├── Features/                      # Feature Registry (M8)
└── Modules/                       # Module contract & discovery (M1+)

app/                      # Application layer — namespace App\
├── Models/
├── Domain/               # entity & value object (bukan perilaku)
├── Actions/              # operasi tunggal reusable
├── Services/             # orkestrasi multi-langkah
├── Contracts/
├── Enums/                # datar, nama singular
├── Policies/             # semua policy — termasuk untuk model milik Core
├── Support/              # utilitas generik murni (tanpa logika bisnis)
├── Filament/             # mengikuti default Filament v5
│   ├── Resources/<Plural>/
│   │   ├── <Model>Resource.php
│   │   ├── Pages/
│   │   └── Schemas/      # Form, Infolist, Table
│   ├── Pages/            # custom page (datar)
│   └── Widgets/          # datar
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/         # Form Request (Laravel default)
│   └── Resources/        # API Resource (Laravel default)
├── Console/
├── Jobs/
├── Events/
├── Listeners/
├── Notifications/
├── Mail/
├── Rules/
├── Observers/
└── Providers/            # termasuk Filament/AdminPanelProvider.php

modules/                  # Business modules — namespace Modules\<Name>\
└── <ModuleName>/         # PascalCase, mis. Inventory/
    ├── Module.php        # metadata + registrasi module (ADR-006)
    ├── Config/
    ├── Database/
    │   ├── Migrations/
    │   ├── Factories/
    │   └── Seeders/
    ├── Models/
    ├── Domain/           # entity & value object
    ├── Actions/          # operasi tunggal reusable
    ├── Services/         # orkestrasi multi-langkah
    ├── Contracts/
    ├── Enums/
    ├── Policies/
    ├── Support/          # utilitas generik murni
    └── Filament/         # Resource/Pages/Widgets milik module (default v5)
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
- `core/` non-UI tidak bergantung pada Filament; komponen UI Core hidup di
  `core/Filament/` (pengecualian arch test ADR-005).
- Model milik Core hidup di subfolder domain-nya (`core/Organization/Models/`),
  bukan di `app/Models/`; policy-nya tetap di `app/Policies/`.
- `app/Filament/` hanya berisi komponen UI; logika bisnis di
  Domain/Actions/Services.
- `modules/` opsional; struktur module mengikuti ADR-006 dan memakai pola
  penuh ala `app/`.
- Folder fisik dibuat sesuai kebutuhan milestone — peta ini adalah target
  akhir; `core/` sudah aktif sejak M1.

## Nama Folder

- `PascalCase` untuk folder modul (`modules/Inventory/`).
- `snake_case` untuk folder umum (jarang; ikuti konvensi Laravel).

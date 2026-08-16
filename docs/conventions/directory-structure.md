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

## Konvensi Folder

Berlaku konsisten di `core/`, `app/`, dan `modules/` (detail: spec
`docs/superpowers/specs/2026-08-16-directory-structure-design.md` §4).

### Domain

- Isi: entity & value object — class dengan state domain dan perilaku
  internalnya sendiri (mis. `Money`, `Email`, `OrganizationalUnit`).
- Bukan isi: operasi yang memanipulasi banyak entity (→ Actions),
  koordinasi multi-langkah (→ Services).

### Actions

- Isi: operasi tunggal reusable, invokable, satu tujuan
  (`CreateOrganizationAction`, `AssignUserToUnit`).
- Konvensi: nama `VerbNoun` (naming.md), class `final`, method `handle()`,
  dependency injection via constructor (bukan `app()`/`resolve()`).
- Bukan isi: orkestrasi multi-langkah (→ Services).

### Services

- Isi: orkestrasi/koordinasi multi-langkah yang menggabungkan beberapa
  action/repository (`OrganizationService`, `AuthService`).
- Konvensi: nama `NounService` (naming.md), constructor injection, `final`.
- Bukan isi: operasi tunggal (→ Actions).

### Contracts

- Isi: interface yang mendefinisikan kontrak/API (`OrganizationContext`,
  `SettingRepository`).
- `core/Contracts/` adalah API publik Core — satu folder terpusat;
  implementasi dibind di service provider (`config('core.providers')`).
- Code to interfaces di system boundaries (payment gateway, external API).

### Enums

- Isi: backed enum (`OrganizationalUnitType`, `RoleType`).
- Konvensi: nama singular (`UserType`, bukan `UserTypes`), case value
  `UPPER_SNAKE` (naming.md).
- Datar, tumbuh organik; subfolder hanya saat puluhan file dengan subyek jelas.

### Policies

- Policy **manual** (di luar resource Filament — mis. `OrganizationalAccessPolicy`,
  kebijakan khusus domain) di `app/Policies/`. Authorization adalah concern
  application layer.
- Policy resource untuk model Core **di-generate Shield** ke
  `core/<Domain>/Policies/` (Shield menurunkan path dari lokasi model —
  keputusan sesi 2026-08-16: ikuti default Shield v4.3.1); model
  `app/Models/` → `app/Policies/`.
- Policy milik module di `modules/<Name>/Policies/`.

### Support

- Isi: utilitas generik murni tanpa state domain — helper statis, mixin,
  macro (`Str`, `Money`, `Arr`-like).
- Bukan isi: logika bisnis apa pun — itu harus ke Domain/Actions/Services.

### Filament

- `core/Filament/`: komponen UI milik Core (resource/panel Core).
- `app/Filament/`: komponen UI aplikasi — default Filament v5
  (`Resources/<Plural>/` dengan Pages + Schemas, Pages datar, Widgets datar).
- `modules/<Name>/Filament/`: komponen UI milik module — pola yang sama.
- Panel provider di `app/Providers/Filament/`.

### Database

- Migration Core di `core/Database/Migrations/` (anonymous class, tanpa
  namespace), dimuat via `loadMigrationsFrom` di `CoreServiceProvider::boot()`
  (ADR-010: Core = package in-repo).
- Factories Core di `core/Database/Factories/` (namespace
  `Core\Database\Factories\` — tidak auto-discovered; model Core perlu
  `newFactory()` atau registrasi eksplisit).
- Seeders Core di `core/Database/Seeders/` (namespace
  `Core\Database\Seeders\` — dipanggil eksplisit, tidak auto-discover).
- Artefak database aplikasi tetap di `database/` root aplikasi (default Laravel).
- Uniqueness nama file migration dijaga lintas kedua folder.

## Nama Folder

- `PascalCase` untuk folder modul (`modules/Inventory/`).
- `snake_case` untuk folder umum (jarang; ikuti konvensi Laravel).

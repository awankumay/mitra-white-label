# Design — Directory Structure (TODO §1.2)

**Tanggal:** 2026-08-16
**Status:** Approved
**Sumber:** `docs/TODO.md` §1.2, `docs/PRD.md`, ADR-001 s.d. ADR-010, `docs/conventions/directory-structure.md`, `docs/conventions/coding.md`, `docs/conventions/naming.md`
**Metode:** Brainstorming (sesi 2026-08-16) — konsultasi `filament-pro`, `laravel-patterns`, `laravel-best-practices`

## 1. Ringkasan

Milestone "Directory Structure" (TODO.md §1.2) mendefinisikan struktur direktori final
dan konvensi per folder untuk tiga lapisan: `core/` (Core System), `app/`
(application layer), dan `modules/` (business modules). Sebagian besar keputusan
arsitektur sudah direkam di ADR-001 s.d. ADR-010 dan konvensi
`directory-structure.md`; sesi ini menetapkan detail yang belum terjawab dan
menjadikannya satu dokumen acuan.

Deliverable:

1. **Peta direktori final** untuk `core/`, `app/`, `modules/`.
2. **Konvensi per folder** — Domain, Actions, Services, Contracts, Enums, Support,
   Filament, Database, Policies — yang berlaku konsisten di tiga lapisan.
3. **Update** `docs/conventions/directory-structure.md` dan checklist TODO.md §1.2.

Tidak ada scaffolding fisik baru di milestone ini selain yang sudah ada
(`core/Config/`, `core/Exceptions/`, `CoreServiceProvider.php`) — folder dibuat
sesuai kebutuhan milestone (YAGNI, ADR-002).

## 2. Konteks

ADR yang sudah memutuskan kerangka:

- ADR-001: namespace `Core\` top-level (PSR-4 `Core\` => `core/`).
- ADR-002: struktur `app/` per-konsep (bukan per-teknikal layer).
- ADR-005: batas dependensi — `Core\` tidak mengimpor `App\`/`Modules\`;
  `Core\` non-UI tidak bergantung pada Filament.
- ADR-006: module-ready, bukan modular monolith penuh.
- ADR-008: extension points — contracts, config, events, actions.
- ADR-009: `Core\CoreServiceProvider` entry point tunggal; sub-provider dari config.
- ADR-010: Core in-repo, dibootstrapped seperti package; config publishable.

Konvensi yang sudah ada: `coding.md` (Actions vs Services, DI, CoreException),
`naming.md` (PascalCase, `VerbNoun` untuk Action, `NounService` untuk Service,
enum case `UPPER_SNAKE`).

Referensi standar Filament v5 (terpasang, diverifikasi dari source):
`make:filament-resource` menghasilkan `Resources/<Plural>/` dengan `Pages/` dan
`Schemas/` di dalam; `make:filament-page` menghasilkan `Pages/` datar;
`make:filament-widget` menghasilkan `Widgets/` datar.

Referensi `laravel-patterns` & `laravel-best-practices` (rules/architecture.md,
rules/style.md): Actions single-purpose invokable, Services untuk koordinasi,
constructor injection, code to interfaces di system boundaries, enum singular,
convention over configuration.

## 3. Peta Direktori Final

### 3.1 `core/` — Core System, namespace `Core\`

```text
core/
├── CoreServiceProvider.php        # entry point; daftar sub-provider dari config
├── Config/
│   └── core.php                   # publishable; pola kunci core.{domain}.{key}
├── Database/
│   ├── Migrations/                # dimuat via loadMigrationsFrom
│   ├── Factories/
│   └── Seeders/
├── Contracts/                     # API publik Core (terpusat)
├── Exceptions/
│   └── CoreException.php
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
```

Aturan:

- `core/` diorganisir **per-domain** untuk subsistem besar (`Organization/`,
  `Settings/`, `Audit/`, dst.); folder lintas-domain (`Contracts/`, `Support/`,
  `Enums/`, `Exceptions/`, `Database/`, `Filament/`) di top-level.
- Folder dibuat sesuai kebutuhan milestone — peta ini target akhir, bukan
  kewajiban membuat folder kosong sekarang.
- Subfolder domain boleh punya subfolder internal saat kebutuhan muncul:
  `Actions/`, `Contracts/`, `Services/`, `Models/` (model milik domain itu).
  Model milik Core hidup di subfolder domain-nya
  (`core/Organization/Models/Organization.php`), **bukan** di `app/Models/`.
- `core/Filament/` menampung komponen UI milik Core (resource/panel Core),
  konsisten dengan `app/Filament/`; batas ini memudahkan verifikasi arch test
  ADR-005 dan ekstraksi Core menjadi package.
- Policy untuk model Core tetap di `app/Policies/` (lihat §4.6).

### 3.2 `app/` — Application layer, namespace `App\`

```text
app/
├── Models/                        # model application
├── Domain/                        # entity & value object (bukan perilaku)
├── Actions/                       # operasi tunggal reusable (CreateOrganization)
├── Services/                      # orkestrasi multi-langkah
├── Contracts/                     # interface application
├── Enums/                         # enum application (datar, singular)
├── Policies/                      # semua policy — termasuk model milik Core
├── Support/                       # utilitas generik murni (tanpa logika bisnis)
├── Filament/                      # mengikuti default Filament v5
│   ├── Resources/<Plural>/
│   │   ├── <Model>Resource.php
│   │   ├── Pages/
│   │   └── Schemas/               # Form, Infolist, Table (default v5)
│   ├── Pages/                     # custom page (datar)
│   └── Widgets/                   # datar
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/                  # Form Request (Laravel default)
│   └── Resources/                 # API Resource (Laravel default)
├── Console/
├── Jobs/
├── Events/
├── Listeners/
├── Notifications/
├── Mail/
├── Rules/
├── Observers/
└── Providers/                     # termasuk Filament/AdminPanelProvider.php
```

Aturan:

- Kombinasi folder **per-konsep** (Domain/Actions/Services/Contracts/Enums/Support)
  dan **folder Laravel standar** apa adanya (Console, Jobs, Events, dst.) — folder
  standar tidak dipindah, mengikuti konvensi Laravel (convention over configuration).
- `app/Filament/` mengikuti default Filament v5: `Resources/<Plural>/` dengan
  `Pages/` + `Schemas/` di dalam (hasil `make:filament-resource`), `Pages/` datar
  (hasil `make:filament-page`), `Widgets/` datar (hasil `make:filament-widget`).
  Panel provider tetap di `app/Providers/Filament/`.
- Folder Laravel standar yang belum terisi tidak dibuat dulu — dibuat saat
  `php artisan make:` membutuhkannya (YAGNI).

### 3.3 `modules/` — Business modules, namespace `Modules\<Name>\`

```text
modules/
└── <ModuleName>/                  # PascalCase, mis. Inventory/
    ├── Module.php                 # metadata + registrasi module (ADR-006)
    ├── Config/                    # config module
    ├── Database/
    │   ├── Migrations/
    │   ├── Factories/
    │   └── Seeders/
    ├── Models/                    # model milik module
    ├── Domain/                    # entity & value object module
    ├── Actions/                   # operasi tunggal reusable module
    ├── Services/                  # orkestrasi multi-langkah module
    ├── Contracts/                 # interface publik module
    ├── Enums/                     # enum module
    ├── Policies/                  # policy milik model module
    ├── Support/                   # utilitas generik murni module
    └── Filament/                  # Resource/Pages/Widgets milik module (default v5)
```

Aturan:

- Module memakai **pola penuh ala `app/`** — struktur yang sama dengan application
  layer sehingga developer cukup mengenal satu pola (standar ke depan).
- Nama folder `PascalCase` (`modules/Inventory/`), mengikuti ADR-006.
- Skeleton inti (`Module.php`, `Config/`, `Database/`) dibuat oleh generator
  `mitra:make:module` (M10); folder lain tumbuh organik saat modul membutuhkannya.
- Dependensi: `Modules\<Name>\` boleh mengimpor `Core\` dan `App\` public
  (ADR-005); Core tidak boleh mengimpor module.

## 4. Konvensi Per Folder

Konvensi berikut berlaku konsisten di `core/`, `app/`, dan `modules/`.

### 4.1 Domain

- Isi: **entity & value object** — class yang mewakili konsep bisnis dengan state
  dan perilaku internalnya sendiri (mis. `Money`, `Email`, `OrganizationalUnit`).
- Bukan isi: operasi yang memanipulasi banyak entity (→ Actions), koordinasi
  multi-langkah (→ Services).
- Aturan praktis: class dengan state domain + perilaku yang melekat → Domain;
  operasi sekali pakai → Action; orkestrasi → Service.

### 4.2 Actions

- Isi: operasi tunggal reusable, invokable, satu tujuan —
  `CreateOrganizationAction`, `AssignUserToUnit`.
- Konvensi: nama `VerbNoun` (naming.md), class `final`, method `handle()`,
  dependency injection via constructor (bukan `app()`/`resolve()`).
- Bukan isi: orkestrasi multi-langkah (→ Services).

### 4.3 Services

- Isi: orkestrasi/koordinasi multi-langkah yang menggabungkan beberapa
  action/repository — `OrganizationService`, `AuthService`.
- Konvensi: nama `NounService` (naming.md), constructor injection, `final`.
- Bukan isi: operasi tunggal (→ Actions).

### 4.4 Contracts

- Isi: interface yang mendefinisikan kontrak/API — `OrganizationContext`,
  `SettingRepository`.
- Aturan: `core/Contracts/` adalah **API publik Core** — satu folder terpusat;
  implementasi boleh di mana saja, dibind di service provider
  (`config('core.providers')` / `AppServiceProvider`).
- **Code to interfaces** di system boundaries (payment gateway, external API,
  dll.) untuk testability dan swappability (laravel-best-practices).

### 4.5 Enums

- Isi: backed enum — `OrganizationalUnitType`, `RoleType`.
- Konvensi: nama **singular** (`UserType`, bukan `UserTypes`), case value
  `UPPER_SNAKE` (naming.md).
- **Datar**, tumbuh organik; subfolder hanya saat sudah puluhan file dengan
  subyek jelas.

### 4.6 Policies

- Semua policy — termasuk untuk model milik Core — diletakkan di `app/Policies/`
  (`app/Policies/OrganizationPolicy.php`).
- Authorization adalah concern application layer; Core tetap bebas dari konsep
  policy/authorization (selaras dengan semangat ADR-005).
- Policy milik module diletakkan di `modules/<Name>/Policies/`.

### 4.7 Support

- Isi: utilitas generik murni tanpa state domain — helper statis, mixin, macro
  (`Str`, `Money`, `Arr`-like).
- Bukan isi: **logika bisnis apa pun** — itu harus ke Domain/Actions/Services.
  Batasan tegas ini mencegah `Support/` menjadi dumpster.

### 4.8 Filament (Core vs Application)

- `core/Filament/`: komponen UI milik Core — resource/panel Core. Struktur
  internal mengikuti default Filament v5 (Resources/<Plural>/ dengan Pages +
  Schemas, Pages datar, Widgets datar).
- `app/Filament/`: komponen UI aplikasi — pola default Filament v5 (§3.2).
- `modules/<Name>/Filament/`: komponen UI milik module — pola yang sama.

### 4.9 Database (Core vs Application)

- Artefak database milik Core di `core/Database/` (Migrations/, Factories/,
  Seeders/), dimuat via `loadMigrationsFrom` di `CoreServiceProvider` — gaya
  package, konsisten dengan ADR-010.
- Artefak database aplikasi tetap di `database/` root aplikasi (default Laravel).

## 5. Aturan Dependensi (Ringkasan)

1. `Core\` **tidak boleh** mengimpor `App\` atau `Modules\` (ADR-005).
2. `Core\` non-UI **tidak bergantung** pada Filament; komponen UI Core
   (`core/Filament/`) dikecualikan.
3. `App\` **boleh** mengimpor `Core\`.
4. `Modules\<Name>\` **boleh** mengimpor `Core\` dan `App\` public.
5. Verifikasi otomatis (Pest arch test) diterapkan — `tests/Arch/CoreArchTest.php`
   (dibuat di M1).

## 6. Dampak pada Dokumen

### 6.1 `docs/conventions/directory-structure.md`

Update peta direktori:

- `core/`: tambah `Database/` (Migrations/, Factories/, Seeders/), `Filament/`,
  `Enums/`; tandai struktur sebagai final, bukan target.
- `app/`: tambah folder Laravel standar (Console, Jobs, Events, Listeners,
  Notifications, Mail, Rules, Observers, Providers, Http/Requests, Http/Resources);
  dokumentasikan struktur `app/Filament/` default v5.
- `modules/`: tambah `Actions/`, `Services/`, `Contracts/`, `Enums/`, `Support/`.
- Tambah ringkasan konvensi per folder (§4) sebagai bagian "Konvensi Folder".

### 6.2 TODO.md §1.2

Checklist yang terjawab:

- Define final `app/` structure → §3.2
- Define `Core` structure → §3.1
- Define `Domain` conventions → §4.1
- Define `Actions` conventions → §4.2
- Define `Services` conventions → §4.3
- Define `Contracts` conventions → §4.4
- Define `Enums` conventions → §4.5
- Define `Support` conventions → §4.7
- Define `Filament` conventions → §3.2, §4.8
- Define `modules/` conventions → §3.3

## 7. Non-Goals

- Tidak membuat folder fisik kosong di luar yang sudah ada (YAGNI, ADR-002).
- Tidak memindahkan model Core ke `app/Models/` — model Core hidup di subfolder
  domain `core/` (keputusan §3.1).
- Tidak mengubah aturan dependensi ADR-005.
- Tidak membuat kontrak/interface/action spekulatif — semua lahir per-kebutuhan
  milestone (ADR-008).
- Tidak mengubah konvensi penamaan yang sudah ada (naming.md).
- Tidak mengekstrak Core ke package terpisah (ADR-010).

## 8. Verifikasi / Acceptance

- Peta direktori §3 dan konvensi §4 konsisten dengan ADR-001 s.d. ADR-010 dan
  `directory-structure.md` yang di-update.
- `core/Filament/` konsisten dengan pengecualian arch test ADR-005.
- Struktur `app/Filament/` sesuai hasil generator `make:filament-*` v5
  (Resources/<Plural>/ + Pages + Schemas, Pages datar, Widgets datar).
- Arch test (`tests/Arch/CoreArchTest.php`) tetap lolos: `Core\` tidak mengimpor
  `App\`/`Modules\`; `Core\` non-UI tidak mengimpor Filament.

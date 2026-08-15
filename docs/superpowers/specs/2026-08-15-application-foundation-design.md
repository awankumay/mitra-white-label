# Design — Application Foundation (TODO 1.1)

**Tanggal:** 2026-08-15
**Status:** Approved
**Sumber:** `docs/TODO.md` §1.1, `docs/PRD.md`, ADR-001 s.d. ADR-007
**Metode:** Brainstorming (sesi 2026-08-15)

## 1. Ringkasan

Milestone "Application Foundation" (TODO.md §1.1) menetapkan fondasi arsitektur
Core System: keputusan arsitektur direkam sebagai ADR baru, struktur fisik
`core/` di-scaffold, dan aturan dependensi Core dijamin oleh verifikasi otomatis.

Deliverable sesuai keputusan sesi brainstorming:

1. **Dokumen** — 3 ADR baru (ADR-008 s.d. ADR-010), update konvensi
   `directory-structure.md`, update TODO.md (checklist §1.1).
2. **Scaffolding inti** — PSR-4 `Core\` => `core/`, `CoreServiceProvider`,
   `config/core.php` publishable, `CoreException`, verifikasi arsitektur.

Tidak ada kontrak/interface/event/action inti yang dibuat di M1 — semuanya
lahir per-kebutuhan di milestone yang membutuhkannya (YAGNI).

## 2. Konteks

PRD §7 menetapkan Core System sebagai lapisan di atas Laravel/Filament,
independent dari business modules. ADR-001 s.d. ADR-007 sudah memutuskan:

- ADR-001: namespace `Core\` top-level (PSR-4 `Core\` => `core/`).
- ADR-002: struktur `app/` per-konsep.
- ADR-003: Core membangun sendiri Settings, Branding, Audit.
- ADR-005: batas dependensi — `Core\` tidak mengimpor `App\`/`Modules\`;
  `Core\` non-UI tidak bergantung pada Filament.
- ADR-006: module-ready, bukan modular monolith penuh.
- ADR-007: retensi package.

Kondisi repo saat ini (baseline audit):

- `composer.json` belum memetakan `Core\` => `core/`.
- Folder `core/` belum ada.
- `bootstrap/providers.php` hanya berisi `AppServiceProvider` dan
  `AdminPanelProvider`.
- Verifikasi arsitektur (ADR-005 poin 5) belum ada.

## 3. Keputusan Arsitektur

### 3.1 Extension Points (ADR-008)

Core v1 memakai 4 mekanisme extension:

1. **Contracts** — interface publik yang mendefinisikan API Core
   (mis. `OrganizationContext`, `SettingRepository`). Dibuat saat milestone
   yang membutuhkannya, bukan di M1.
2. **Config** — binding/override via `config/core.php`; aplikasi dapat
   mengganti implementasi default melalui konfigurasi.
3. **Events** — integrasi yang tidak membutuhkan return value
   (audit, notification). Core melempar event; konsumen mendengarkan.
4. **Actions** — operasi tunggal reusable (`CreateOrganization`), dipakai
   aplikasi/modul dan oleh Core sendiri.

Tidak ada pipeline/middleware-based extension di v1 (YAGNI).

### 3.2 Service Provider (ADR-009)

- `Core\CoreServiceProvider` adalah entry point tunggal Core.
- Sub-provider per domain didaftarkan dari daftar di `config/core.php`
  (kunci `core.providers`), bukan hardcode di provider maupun langsung di
  `bootstrap/providers.php`.
- `bootstrap/providers.php` tetap pendek dan tidak berubah per-domain.

Alasan: cocok untuk ekstraksi package di masa depan, memudahkan penambahan
domain baru tanpa menyentuh file bootstrap, dan tetap mudah dilacak.

### 3.3 Configuration & Bootstrap (ADR-010)

- Core dikembangkan in-repo di `core/`, dibootstrapped seperti package.
- Sumber konfigurasi di `core/Config/core.php`; di-merge via
  `mergeConfigFrom` di `CoreServiceProvider`.
- Publishable dengan tag `core-config` (Laravel vendor:publish) sehingga
  aplikasi dapat meng-override — tetapi sumber tetap di-commit in-repo.
- Kunci konfigurasi mengikuti pola `core.{domain}.{key}`.

## 4. Scaffolding Fisik

### 4.1 composer.json

Tambahkan ke `autoload.psr-4`:

```json
"Core\\": "core/"
```

### 4.2 Struktur core/

```text
core/
├── CoreServiceProvider.php   # entry point; daftarkan sub-provider dari config
├── Config/
│   └── core.php              # struktur config per-domain; publishable
└── Exceptions/
    └── CoreException.php     # base exception (rujukan konvensi coding.md)
```

`core/Config/core.php`:

```php
<?php

return [
    'providers' => [
        // Sub-provider Core didaftarkan di sini saat domain dibuat.
    ],
    // Domain sub-sistem ditambahkan saat milestone yang membutuhkannya,
    // misal: 'context' => [...], 'settings' => [...]
];
```

`CoreServiceProvider`:

- `mergeConfigFrom(__DIR__.'/Config/core.php', 'core')`.
- Daftarkan sub-provider dari `config('core.providers')` via
  `$this->app->register()` di `register()`.

`CoreException`:

- `namespace Core\Exceptions;`
- Extends `\RuntimeException`; base exception untuk semua exception Core
  (dirujuk oleh `docs/conventions/coding.md`).

### 4.3 bootstrap/providers.php

Tambah `Core\CoreServiceProvider::class`.

### 4.4 Verifikasi Arsitektur

Pest arch test di `tests/Arch/CoreArchTest.php` memverifikasi ADR-005:

1. `Core\` tidak mengimpor `App\` atau `Modules\`.
2. `Core\` non-UI tidak mengimpor Filament (pengecualian: komponen UI Core
   yang memang memakai Filament, bila ada di masa depan).

Pest arch testing membutuhkan plugin `pestphp/pest-plugin-arch` — tambahkan
ke `require-dev` di `composer.json` (belum terpasang; baseline audit hanya
mencatat `pestphp/pest` dan `pestphp/pest-plugin-laravel`).

Quality gate: `composer check` (Pint → Pest → PHPStan).

### 4.5 Update Konvensi

`docs/conventions/directory-structure.md`:

- `core/` dipindahkan dari "target akhir" menjadi struktur aktual.
- Tambah dokumentasi `config/core.php` dan pola kunci `core.{domain}.{key}`.

## 5. Dampak pada TODO.md

Item §1.1 yang sudah dijawab ADR existing dicentang:

- Define Core application architecture → ADR-001, ADR-002, ADR-005
- Define Core namespaces → ADR-001
- Define application layer boundaries → ADR-005
- Define service provider strategy → ADR-009 (baru)
- Define configuration strategy → ADR-010 (baru)
- Define bootstrap strategy → ADR-010 (baru)

Item yang sengaja **tidak** dikerjakan di M1 (YAGNI; lahir di milestone yang
membutuhkannya):

- Define extension points → ADR-008 (mekanisme), kontrak nyata per-kebutuhan
- Define Core contracts → per-kebutuhan (M4: context, M5: auth, dst.)
- Define Core interfaces → per-kebutuhan
- Define Core events → per-kebutuhan (M8: audit, notification)
- Define Core actions → per-kebutuhan (M3: CreateOrganization, dst.)
- Define Core exceptions → hanya `CoreException` di M1; sisanya per-kebutuhan

## 6. Non-Goals

- Tidak membuat kontrak/interface/event/action spekulatif.
- Tidak membuat folder `core/` sub-sistem kosong (bertentangan dengan
  ADR-002 / konvensi directory-structure).
- Tidak mengubah batas dependensi yang sudah ditetapkan ADR-005.
- Tidak mengekstrak Core ke package terpisah (ADR-010: in-repo dulu).
- Tidak menyentuh `app/` atau `modules/` dalam milestone ini.

## 7. Verifikasi / Acceptance

- `composer check` lolos (Pint, Pest termasuk arch test baru, PHPStan).
- `php artisan about` / aplikasi tetap boot normal dengan provider baru.
- Arch test gagal jika `Core\` mengimpor `App\`/`Modules\` atau Filament
  (non-UI).
- `php artisan vendor:publish --tag=core-config` menghasilkan
  `config/core.php` di aplikasi.

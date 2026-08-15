# M0 — Project Baseline Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menetapkan fondasi keputusan arsitektur Mitra White Label melalui 7 ADR, 3 dokumen konvensi, dan 1 audit baseline — seluruhnya dokumen, tanpa perubahan kode.

**Architecture:** ADR-centric. Setiap keputusan arsitektur menjadi ADR bernomor (Context → Decision → Consequences) di `docs/architecture/`, didampingi dokumen konvensi praktis di `docs/conventions/` yang merujuk ke ADR. Semua dokumen berbahasa Indonesia.

**Tech Stack:** Markdown, Git. (Repositori: Laravel 13.25 / Filament 5.7.6 / PHP 8.4 — hanya untuk konteks audit, tidak dimodifikasi.)

## Global Constraints

- Output M0 seluruhnya dokumen — **dilarang** scaffolding kode, `composer remove`, perubahan autoload/config/skrip, atau migration/model/resource baru.
- Bahasa dokumen: Bahasa Indonesia.
- Template ADR: **Context → Decision → Consequences**.
- Keputusan tidak boleh bertentangan dengan locked decisions PRD §64 (standalone, single org, module-ready, generator policy, internet-independent).
- Namespace Core: `Core\` top-level (implementasi autoload ditunda ke M1, hanya didokumentasikan di M0).
- Primary key Core: UUID + soft-delete (keputusan, bukan implementasi).
- Penghapusan package redundant dijadwalkan M7 (Settings/Branding) dan M8 (Audit) — hanya didokumentasikan di M0.
- Referensi silang antar dokumen wajib konsisten (path `docs/architecture/adr-00X-*.md`).

---

### Task 1: Setup Struktur Direktori & Template ADR

**Files:**
- Create: `docs/architecture/.gitkeep` (placeholder agar folder ter-commit; dihapus setelah ADR pertama dibuat)
- Create: `docs/conventions/.gitkeep`
- Create: `docs/architecture/adr-template.md`

**Interfaces:**
- Produces: `docs/architecture/adr-template.md` — template yang dipakai semua ADR berikutnya (Task 3–9). Template berisi heading tetap: `# ADR-XXX: <Judul>`, `**Status:**`, `**Tanggal:**`, `## Context`, `## Decision`, `## Consequences`.

- [ ] **Step 1: Buat folder dan template ADR**

Buat file `docs/architecture/adr-template.md`:

```markdown
# ADR-XXX: <Judul Keputusan>

**Status:** Accepted
**Tanggal:** 2026-08-15

## Context

[Latar belakang masalah dan opsi yang dipertimbangkan.]

## Decision

[Keputusan yang diambil.]

## Consequences

- [Dampak positif]
- [Dampak negatif / trade-off]
- [Catatan implementasi / deferral]
```

Buat juga `docs/architecture/.gitkeep` dan `docs/conventions/.gitkeep` (file kosong) agar kedua folder ter-commit sejak awal.

- [ ] **Step 2: Verifikasi struktur**

Run: `git status`
Expected: 3 file baru terlihat (2 `.gitkeep`, 1 `adr-template.md`).

- [ ] **Step 3: Commit**

```bash
git add docs/architecture docs/conventions
git commit -m "docs: add ADR template and docs scaffolding for M0"
```

---

### Task 2: Baseline Audit (docs/architecture/baseline-audit.md)

**Files:**
- Create: `docs/architecture/baseline-audit.md`

**Interfaces:**
- Consumes: hasil audit dari spec §2, §5 (versi terverifikasi, daftar package).
- Produces: daftar status package (keep/remove/evaluate) yang dirujuk ADR-007 (Task 8); daftar gap repo vs PRD yang menjadi justifikasi roadmap M1+.

- [ ] **Step 1: Tulis dokumen audit**

Buat `docs/architecture/baseline-audit.md` dengan konten berikut (isi lengkap, bukan placeholder):

```markdown
# Baseline Audit — Mitra White Label

**Tanggal:** 2026-08-15
**Status:** Final

## 1. Ringkasan

Audit repository awal terhadap `docs/PRD.md`. Tujuan: memverifikasi fondasi
teknis dan mengidentifikasi gap terhadap Core System yang ditargetkan PRD.

## 2. Verifikasi Versi

| Komponen | Versi yang Dibutuhkan (PRD) | Versi Terpasang | Status |
|---|---|---|---|
| PHP | ^8.3 | 8.4 | ✓ |
| Laravel | ^13.0 | 13.25.0 | ✓ |
| Filament | ^5.0 | 5.7.6 | ✓ |

## 3. Review Struktur Repository

Struktur saat ini masih vanilla Laravel starterkit:

- `app/Models/` hanya berisi `User`.
- `app/Filament/` hanya berisi panel admin (`AdminPanelProvider`) dan resource `Users`.
- Belum ada `core/`, `app/Domain/`, `app/Actions/`, atau `modules/`.
- Belum ada model Organization, OrganizationalUnit, Setting, AuditLog, SecurityEvent.

Gap lengkap terhadap PRD dirinci pada bagian 6.

## 4. Review Package Composer

### 4.1 Foundation (Keep)

| Package | Peran | Referensi PRD |
|---|---|---|
| `bezhansalleh/filament-shield` (4.3.1) + `spatie/laravel-permission` (8.3.0) | RBAC | §19 |
| `jeffgreco13/filament-breezy` (3.2.8) + `pragmarx/google2fa*` + `web-auth/webauthn-lib` | 2FA, passkey, session, recovery codes | §22–25 |
| `spatie/laravel-activitylog` (4.12.3) | Audit trail backend di belakang Audit System Core | §32 |

### 4.2 Redundant (Remove — dijadwalkan)

| Package | Alasan | Jadwal Penghapusan |
|---|---|---|
| `inerba/filament-db-config` (1.3.5) | Settings akan dibangun Core sendiri | M7 |
| `ashrafic/filament-white-label` (1.0.8) | Branding akan dibangun Core sendiri | M7 |
| `jacobtims/filament-logger` (1.2.0) | Audit akan dibangun Core sendiri | M8 |

Keputusan penuh: lihat ADR-007.

### 4.3 UX / Developer Plugin (Evaluate di M1+)

| Package | Catatan |
|---|---|
| `spykapps/theme-edinburgh` (1.0.3) | Theme — tidak memblokir Core |
| `swisnl/filament-backgrounds` (2.0.3) | UX login — tidak memblokir Core |
| `awcodes/filament-quick-create` (5.1.0) | UX CRUD — tidak memblokir Core |
| `dutchcodingcompany/filament-developer-logins` (2.1.0) | Dev convenience — tidak memblokir Core |
| `craft-forge/filament-language-switcher` (1.2.1) | UX bahasa — tidak memblokir Core |

### 4.4 Dev / Quality (Keep)

Pest 4.7, Larastan 3.10, Laravel Pint 1.30, Laravel Sail, Debugbar, Pail,
Faker, Mockery, Collision, Filacheck, Paratest — semua diperlukan untuk
quality gate `composer check`.

## 5. Review Package NPM

Tidak ditemukan redundancy. `package.json` hanya berisi tooling build
standar: Vite 8, Tailwind 4, laravel-vite-plugin, axios, concurrently.

## 6. Gap Repository vs PRD

Capability PRD yang belum ada di repository (menjadi roadmap M1+):

- Organization & Organizational Unit (PRD §9–14)
- Organizational Context & data scope (PRD §15–17)
- Authorization ber-scope organisasi (PRD §18–20)
- Settings architecture (PRD §27)
- White Label branding (PRD §28)
- Feature Registry (PRD §29)
- Module architecture (PRD §30–31)
- Audit System (PRD §32)
- Notifications abstraction (PRD §34)
- Console: `mitra:install`, `mitra:doctor`, `mitra:health`, `mitra:about` (PRD §37–41)
- Developer generators (PRD §42–50)

## 7. Kesimpulan

Fondasi teknis (PHP/Laravel/Filament) sesuai PRD. Gap utama adalah seluruh
capability Core yang akan dibangun mulai M1. Package redundant sudah
teridentifikasi dan penjadwalan penghapusannya diatur ADR-007.
```

- [ ] **Step 2: Verifikasi konten**

Run: `git diff --stat docs/architecture/baseline-audit.md`
Expected: 1 file baru. Cek manual: tabel versi sesuai data composer.lock, nama package persis.

- [ ] **Step 3: Commit**

```bash
git add docs/architecture/baseline-audit.md docs/architecture/.gitkeep
git commit -m "docs: add baseline audit for M0"
```

---

### Task 3: ADR-001 — Namespace Core Top-Level

**Files:**
- Create: `docs/architecture/adr-001-namespace-core.md`
- Delete: `docs/architecture/.gitkeep` (folder sudah berisi file)

**Interfaces:**
- Consumes: template ADR (Task 1).
- Produces: keputusan namespace yang dirujuk ADR-005 (Task 6), conventions/naming.md (Task 9), conventions/directory-structure.md (Task 10), dan TODO.md (Task 12).

- [ ] **Step 1: Tulis ADR-001**

Buat `docs/architecture/adr-001-namespace-core.md`:

```markdown
# ADR-001: Namespace Core Top-Level (`Core\`)

**Status:** Accepted
**Tanggal:** 2026-08-15

## Context

PRD §30 menetapkan Core System harus tetap independent dari business
modules, dan §64 menegaskan batas tegas Core → Application. Untuk itu
dibutuhkan namespace yang memisahkan Core dari application layer secara
struktural.

Opsi yang dipertimbangkan:

1. `Core\` top-level (PSR-4 `Core\` => `core/`).
2. `App\Core\` di dalam `app/Core/`.
3. `Mitra\Core\` (src/ atau core/).

## Decision

Gunakan namespace `Core\` top-level dengan pemetaan PSR-4:

```json
"autoload": {
    "psr-4": {
        "Core\\": "core/"
    }
}
```

Implementasi pemetaan di `composer.json` dilakukan pada M1 (scaffolding),
bukan di M0. Di M0 hanya keputusan yang direkam.

## Consequences

- Batas Core/Application sangat tegas dan dapat diverifikasi otomatis
  (import `Core\` dari `App\`/`Modules\` = pelanggaran aturan).
- `App\` tetap memakai namespace default Laravel; tidak ada perubahan
  pada kode existing.
- Kurang konvensional dibanding `App\Core\` — perlu dokumentasi yang
  jelas di conventions/directory-structure.md.
- Membuka peluang ekstraksi Core menjadi package terpisah di masa depan.
```

- [ ] **Step 2: Verifikasi**

Run: `git status`
Expected: `adr-001-namespace-core.md` terlihat, `.gitkeep` dihapus (folder tetap ada karena ada file lain).

- [ ] **Step 3: Commit**

```bash
git add -A docs/architecture
git commit -m "docs: add ADR-001 core namespace"
```

---

### Task 4: ADR-002 — Struktur App/ Per-Konsep

**Files:**
- Create: `docs/architecture/adr-002-struktur-app.md`

**Interfaces:**
- Produces: struktur `app/` final yang dirinci conventions/directory-structure.md (Task 10) dan dirujuk TODO.md (Task 12).

- [ ] **Step 1: Tulis ADR-002**

Buat `docs/architecture/adr-002-struktur-app.md`:

```markdown
# ADR-002: Struktur Application Layer Per-Konsep

**Status:** Accepted
**Tanggal:** 2026-08-15

## Context

TODO.md 1.2 meminta definisi struktur `app/` final. Struktur default
Laravel (Models/, Http/, Policies/) terlalu tipis untuk aplikasi
enterprise yang dibangun di atas Core — logic tersebar dan batas antar
konsep tidak jelas. PRD §30 menetapkan application layer berada di atas
Core tanpa memaksakan modular monolith.

## Decision

Organisasi `app/` per-konsep (bukan per-teknikal layer):

```text
app/
├── Models/
├── Domain/
├── Actions/
├── Services/
├── Contracts/
├── Enums/
├── Policies/
├── Support/
├── Filament/
└── Http/
```

`app/Models/` memuat model application; `app/Domain/` memuat entitas/
value object domain bila diperlukan; `app/Actions/` untuk reusable
action; `app/Services/` untuk orchestration. Folder yang belum terisi di
M0 dibuat saat milestone yang membutuhkannya — tidak dibuat di M0.

## Consequences

- Struktur mudah dikembangkan dan konsisten dengan konvensi Core.
- Folder kosong tidak dibuat di M0 (dokumen-only); pembuatan fisik
  mengikuti kebutuhan milestone.
- Deviasi dari layout Laravel default perlu didokumentasikan di
  conventions/directory-structure.md.
```

- [ ] **Step 2: Verifikasi**

Run: `git diff --stat HEAD`
Expected: 1 file baru.

- [ ] **Step 3: Commit**

```bash
git add docs/architecture/adr-002-struktur-app.md
git commit -m "docs: add ADR-002 app directory structure"
```

---

### Task 5: ADR-003 — Core Build Sendiri untuk Settings/Branding/Audit

**Files:**
- Create: `docs/architecture/adr-003-core-vs-package.md`

**Interfaces:**
- Consumes: daftar package redundant dari baseline-audit.md (Task 2).
- Produces: justifikasi keputusan yang dirujuk ADR-007 (Task 8) dan TODO.md deferral (Task 12).

- [ ] **Step 1: Tulis ADR-003**

Buat `docs/architecture/adr-003-core-vs-package.md`:

```markdown
# ADR-003: Core Membangun Sendiri Settings, Branding, dan Audit

**Status:** Accepted
**Tanggal:** 2026-08-15

## Context

PRD §27 (Settings), §28 (White Label), dan §32 (Audit) mensyaratkan
capability yang saat ini sudah terpasang sebagai package pihak ketiga:
`inerba/filament-db-config`, `ashrafic/filament-white-label`, dan
`jacobtims/filament-logger`. Package tersebut tidak memenuhi kebutuhan
PRD (misalnya branding per-organization, audit schema dengan actor/
subject/metadata/IP yang spesifik).

## Decision

Core membangun sendiri Settings, Branding (termasuk organization-level
branding dan fallback), dan Audit System sesuai PRD. Package redundant
ditandai untuk dihapus setelah penggantinya dibangun:

- `inerba/filament-db-config` → dihapus di M7 (Settings).
- `ashrafic/filament-white-label` → dihapus di M7 (Branding).
- `jacobtims/filament-logger` → dihapus di M8 (Audit).

## Consequences

- Kontrol penuh atas schema dan behavior, sesuai kebutuhan PRD.
- Effort pengembangan lebih besar daripada sekadar membungkus package.
- Masa transisi: package tetap terpasang sampai penggantinya siap,
  menghindari regression saat migrasi.
- `spatie/laravel-activitylog` tetap dipertahankan sebagai audit trail
  teknis di belakang Audit System Core (lihat ADR-007).
```

- [ ] **Step 2: Verifikasi**

Run: `git diff --stat HEAD`
Expected: 1 file baru.

- [ ] **Step 3: Commit**

```bash
git add docs/architecture/adr-003-core-vs-package.md
git commit -m "docs: add ADR-003 core builds settings branding audit"
```

---

### Task 6: ADR-004 — Primary Key UUID + Soft Delete

**Files:**
- Create: `docs/architecture/adr-004-primary-key-uuid.md`

**Interfaces:**
- Produces: keputusan ID strategy yang dirujuk conventions/naming.md (Task 9) dan TODO.md database foundation (M3, bukan M0).

- [ ] **Step 1: Tulis ADR-004**

Buat `docs/architecture/adr-004-primary-key-uuid.md`:

```markdown
# ADR-004: Primary Key UUID dengan Soft Delete

**Status:** Accepted
**Tanggal:** 2026-08-15

## Context

TODO.md 3.1 meminta strategi primary key. PRD §5 menetapkan deployment
standalone per client, sehingga data antar instalasi berpotensi
digabungkan/direferensikan di masa depan. Auto-increment integer
memperbesar risiko tabrakan pada skenario tersebut.

## Decision

Semua model Core menggunakan UUID string sebagai primary key:

- Kolom `id` bertipe `uuid` (string, bukan binary).
- `ramsey/uuid` (sudah terpasang) sebagai generator UUID.
- `HasUuids` Laravel digunakan pada model Core.
- Soft delete (`softDeletes`) diterapkan pada model master
  (Organization, OrganizationalUnit, User, Setting), tidak wajib pada
  log/event (AuditLog, SecurityEvent).

## Consequences

- Primary key tidak enumerable dan aman untuk integrasi lintas instalasi.
- Storage lebih besar daripada integer; index UUID string sedikit lebih
  lambat pada dataset sangat besar — dapat dimitigasi dengan index yang
  tepat (dibahas di M3).
- Seluruh foreign key Core mengikuti tipe UUID — harus konsisten sejak
  desain migration (M3).
- Implementasi di M3 (Database Foundation), bukan M0.
```

- [ ] **Step 2: Verifikasi**

Run: `git diff --stat HEAD`
Expected: 1 file baru.

- [ ] **Step 3: Commit**

```bash
git add docs/architecture/adr-004-primary-key-uuid.md
git commit -m "docs: add ADR-004 uuid primary key"
```

---

### Task 7: ADR-005 — Batas Core/Application

**Files:**
- Create: `docs/architecture/adr-005-batas-core-application.md`

**Interfaces:**
- Consumes: keputusan namespace dari ADR-001.
- Produces: aturan dependensi yang dirujuk conventions/coding.md (Task 11), TODO.md (Task 12), dan implementasi verifikasi otomatis di M1.

- [ ] **Step 1: Tulis ADR-005**

Buat `docs/architecture/adr-005-batas-core-application.md`:

```markdown
# ADR-005: Batas Core/Application dan Aturan Dependensi

**Status:** Accepted
**Tanggal:** 2026-08-15

## Context

PRD §30 menetapkan Core tidak boleh bergantung pada business modules,
dan §55 menekankan minimasi framework/vendor modification dengan
contracts/composition/actions. Dibutuhkan aturan dependensi yang tegas
dan dapat diverifikasi.

## Decision

Aturan dependensi:

1. `Core\` **tidak boleh** mengimpor `App\` atau `Modules\`.
2. `App\` **boleh** mengimpor `Core\`.
3. `Modules\<Name>\` **boleh** mengimpor `Core\` dan `App\` yang public.
4. `Core\` **tidak boleh** bergantung pada Filament untuk logika non-UI
   (Context, Settings, Audit, dll. harus independent dari Filament).
   Komponen Filament milik Core (resource/panel admin Core) dikecualikan.
5. Verifikasi otomatis (static analysis / arch test) akan diterapkan di
   M1 — M0 hanya merekam aturan.

Struktur direktori yang dimaksud (detail di conventions/
directory-structure.md):

```text
core/     → Core\
app/      → App\
modules/  → Modules\<Name>\
```

## Consequences

- Batas yang jelas memudahkan testing, upgrade, dan pemahaman arsitektur.
- Developer perlu disiplin; verifikasi otomatis di M1 menjadi pengaman.
- `Core\` yang bebas Filament memastikan context dapat dipakai di
  console command, job, dan service tanpa UI (PRD §15).
```

- [ ] **Step 2: Verifikasi**

Run: `git diff --stat HEAD`
Expected: 1 file baru.

- [ ] **Step 3: Commit**

```bash
git add docs/architecture/adr-005-batas-core-application.md
git commit -m "docs: add ADR-005 core application boundaries"
```

---

### Task 8: ADR-006 — Module-Ready, Bukan Modular Monolith

**Files:**
- Create: `docs/architecture/adr-006-module-architecture.md`

**Interfaces:**
- Produces: konvensi `modules/` yang dirinci conventions/directory-structure.md (Task 10) dan dirujuk TODO.md M12/M1.

- [ ] **Step 1: Tulis ADR-006**

Buat `docs/architecture/adr-006-module-architecture.md`:

```markdown
# ADR-006: Module-Ready (Bukan Modular Monolith Penuh)

**Status:** Accepted
**Tanggal:** 2026-08-15

## Context

PRD §30 menyatakan Core harus module-ready tetapi tidak memaksakan
Modular Monolith penuh. PRD §31 mendefinisikan module contract
(identity, name, version, config, provider, routes, migrations, models,
policies, Filament resources, permissions, features).

## Decision

Core menyediakan konvensi dan infrastruktur module tanpa memaksa seluruh
application logic masuk ke `modules/`:

```text
modules/
└── <ModuleName>/
    ├── Module.php
    ├── Config/
    ├── Database/
    ├── Domain/
    ├── Filament/
    ├── Models/
    └── Policies/
```

Keputusan:

- Module adalah unit opsional; application layer tetap bisa hidup di
  `app/` bila modul belum diperlukan.
- Module contract & discovery diimplementasikan di M1+ (Core Module
  system), bukan M0.
- Nama namespace: `Modules\<ModuleName>\`.
- Module generator (`mitra:make:module`) diimplementasikan di M10.

## Consequences

- Fleksibilitas: tim dapat memilih antara `app/` dan `modules/`.
- Tidak over-engineer Core dengan infrastruktur monolith penuh.
- Konvensi harus cukup jelas agar struktur module predictable (PRD §30).
```

- [ ] **Step 2: Verifikasi**

Run: `git diff --stat HEAD`
Expected: 1 file baru.

- [ ] **Step 3: Commit**

```bash
git add docs/architecture/adr-006-module-architecture.md
git commit -m "docs: add ADR-006 module architecture"
```

---

### Task 9: ADR-007 — Retention Package

**Files:**
- Create: `docs/architecture/adr-007-package-retention.md`

**Interfaces:**
- Consumes: baseline-audit.md bagian 4 (Task 2) dan ADR-003 (Task 5).
- Produces: daftar final keep/remove/evaluate yang dirujuk TODO.md (Task 12).

- [ ] **Step 1: Tulis ADR-007**

Buat `docs/architecture/adr-007-package-retention.md`:

```markdown
# ADR-007: Retention Package Core

**Status:** Accepted
**Tanggal:** 2026-08-15

## Context

PRD §56 menetapkan package philosophy: package hanya menjadi Core
dependency jika generic, stabil, reusable, tidak mengunci business
domain, dan tidak mengunci SaaS. Audit baseline (Task baseline-audit.md)
menemukan package yang overlap dengan capability yang akan dibangun Core
sendiri.

## Decision

### Keep (foundation)

- `bezhansalleh/filament-shield` + `spatie/laravel-permission` — RBAC (PRD §19).
- `jeffgreco13/filament-breezy` + `pragmarx/google2fa*` + `web-auth/webauthn-lib` — 2FA, passkey, session, recovery codes (PRD §22–25).
- `spatie/laravel-activitylog` — audit trail teknis di belakang Audit System Core (PRD §32).

### Remove (redundant — setelah pengganti Core siap)

| Package | Pengganti | Jadwal |
|---|---|---|
| `inerba/filament-db-config` | Core Settings | M7 |
| `ashrafic/filament-white-label` | Core Branding | M7 |
| `jacobtims/filament-logger` | Core Audit | M8 |

### Evaluate di M1+ (tidak memblokir Core)

- `spykapps/theme-edinburgh`, `swisnl/filament-backgrounds`,
  `awcodes/filament-quick-create`,
  `dutchcodingcompany/filament-developer-logins`,
  `craft-forge/filament-language-switcher`.

### Dev / Quality (keep)

- Pest, Larastan, Pint, Sail, Debugbar, Pail, Faker, Mockery, Collision,
  Filacheck, Paratest.

## Consequences

- Stack Core bersih dan sesuai PRD §56.
- Penghapusan bertahap menghindari regression (pengganti dibangun dulu).
- Keputusan final ada di sini; perubahan komposisi package di masa depan
  harus melalui amend ADR ini atau ADR baru.
```

- [ ] **Step 2: Verifikasi**

Run: `git diff --stat HEAD`
Expected: 1 file baru.

- [ ] **Step 3: Commit**

```bash
git add docs/architecture/adr-007-package-retention.md
git commit -m "docs: add ADR-007 package retention"
```

---

### Task 10: Konvensi Naming (docs/conventions/naming.md)

**Files:**
- Create: `docs/conventions/naming.md`
- Delete: `docs/conventions/.gitkeep` (folder sudah berisi file)

**Interfaces:**
- Consumes: ADR-001 (namespace), ADR-004 (UUID).
- Produces: aturan penamaan yang dirujuk conventions/coding.md dan dipakai seluruh milestone.

- [ ] **Step 1: Tulis konvensi naming**

Buat `docs/conventions/naming.md`:

```markdown
# Konvensi Penamaan

**Status:** Accepted
**Tanggal:** 2026-08-15
**Referensi:** ADR-001, ADR-004

## Kelas & File

- Kelas: `PascalCase`, satu kelas per file, nama file = nama kelas.
- Interface: `PascalCase` (mis. `OrganizationContext`).
- Enum: `PascalCase` (mis. `OrganizationalUnitType`), case value
  `UPPER_SNAKE` (mis. `HEAD_OFFICE`, `BRANCH`).
- Action: `VerbNoun` (mis. `CreateOrganization`).
- Service: `NounService` (mis. `OrganizationService`).

## Namespace

- `Core\` untuk Core System (ADR-001).
- `App\` untuk application layer.
- `Modules\<ModuleName>\` untuk modul.

## Database

- Tabel: `snake_case` jamak (mis. `organizational_units`).
- Pivot: gabungan nama tabel urut alfabetis
  (mis. `organizational_unit_user`, `organization_user`).
- Kolom foreign key: `snake_case` singular + `_id`
  (mis. `organization_id`, `parent_id`).
- Primary key: `id` bertipe UUID string (ADR-004).
- Timestamps: `created_at`, `updated_at`; soft delete:
  `deleted_at`.
- Audit kolom: `created_by`, `updated_by` bila diperlukan.

## Permission

- Pola `resource.action` (PRD §19):
  `users.view`, `users.create`, `users.update`, `users.delete`,
  `organization_units.view`, dst.

## Bahasa

- Kode, kelas, metode, dan komentar: English.
- String yang tampil ke user (label, notifikasi): sesuai lokalisasi
  aplikasi (Bahasa Indonesia default).
```

- [ ] **Step 2: Verifikasi konsistensi**

Cek manual: tidak ada kontradiksi dengan ADR-001 (namespace `Core\`) dan ADR-004 (UUID).

- [ ] **Step 3: Commit**

```bash
git add -A docs/conventions
git commit -m "docs: add naming conventions"
```

---

### Task 11: Konvensi Struktur Direktori (docs/conventions/directory-structure.md)

**Files:**
- Create: `docs/conventions/directory-structure.md`

**Interfaces:**
- Consumes: ADR-001, ADR-002, ADR-005, ADR-006.
- Produces: peta direktori final yang dirujuk conventions/coding.md dan dipakai implementasi M1+.

- [ ] **Step 1: Tulis konvensi direktori**

Buat `docs/conventions/directory-structure.md`:

```markdown
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

## Aturan

- `core/` tidak pernah mengimpor `app/` atau `modules/` (ADR-005).
- `app/Filament/` hanya berisi komponen UI; logika bisnis di
  Domain/Actions/Services.
- `modules/` opsional; struktur module mengikuti ADR-006.
- Folder fisik dibuat sesuai kebutuhan milestone — peta ini adalah
  target akhir, bukan kewajiban membuat folder kosong di M0.

## Nama Folder

- `PascalCase` untuk folder modul (`modules/Inventory/`).
- `snake_case` untuk folder umum (jarang; ikuti konvensi Laravel).
```

- [ ] **Step 2: Verifikasi konsistensi**

Cek manual: struktur di file ini identik dengan ADR-005 dan ADR-006 (tidak ada kontradiksi penamaan folder).

- [ ] **Step 3: Commit**

```bash
git add docs/conventions/directory-structure.md
git commit -m "docs: add directory structure conventions"
```

---

### Task 12: Konvensi Coding (docs/conventions/coding.md)

**Files:**
- Create: `docs/conventions/coding.md`

**Interfaces:**
- Consumes: ADR-005 (batas dependensi), ADR-007 (package), konvensi naming (Task 10).
- Produces: baseline kualitas yang dirujuk TODO.md item quality gate (Task 13) dan milestone M11.

- [ ] **Step 1: Tulis konvensi coding**

Buat `docs/conventions/coding.md`:

```markdown
# Konvensi Coding

**Status:** Accepted
**Tanggal:** 2026-08-15
**Referensi:** ADR-005, ADR-007, naming.md

## Quality Gate

- `composer check` adalah standar quality gate:
  Pint (format) → Pest (test) → PHPStan (static analysis).
- Minimum PHP 8.3 (kompatibel dengan versi terpasang 8.4).
- Database test: SQLite in-memory (PRD §53); MySQL/PostgreSQL untuk
  production.

## Batas Dependensi (ADR-005)

- `Core\` tidak mengimpor `App\` / `Modules\`.
- `Core\` non-UI tidak bergantung pada Filament.
- Verifikasi otomatis menyusul di M1.

## Struktur Logic

- Prefer Action untuk operasi tunggal yang reusable
  (`CreateOrganization`), Service untuk orchestration multi-langkah.
- Jangan menumpuk logic di controller/Resource — pindahkan ke Action
  atau Service.
- Prefer Composition over inheritance.
- Tidak memodifikasi vendor; gunakan contracts/extension points
  (PRD §55).

## Error Handling

- Exception application diturunkan dari `Core\Exceptions\CoreException`
  (dibuat di M1).
- Jangan menampilkan secrets ke user (PRD §39).

## Format & Tools

- Ikuti Laravel Pint default config (`pint.json` existing).
- PHPStan level sesuai `phpstan.neon` existing; tingkatkan bertahap.

## Git Workflow

- Commit message: conventional commits
  (`feat:`, `fix:`, `docs:`, `chore:`, `refactor:`, `test:`).
- Commit kecil dan sering; satu task = satu commit.
```

- [ ] **Step 2: Verifikasi**

Run: `git diff --stat HEAD`
Expected: 1 file baru.

- [ ] **Step 3: Commit**

```bash
git add docs/conventions/coding.md
git commit -m "docs: add coding conventions"
```

---

### Task 13: Update TODO.md (M0 Checklist)

**Files:**
- Modify: `docs/TODO.md:11-24` (bagian 0 — Project Baseline)

**Interfaces:**
- Consumes: seluruh ADR + konvensi (Task 2–12).
- Produces: checklist M0 yang mencerminkan status final; deferral penghapusan package tercatat.

- [ ] **Step 1: Update checklist M0**

Ganti bagian 0 `docs/TODO.md` (baris 11–24) menjadi:

```markdown
# 0. Project Baseline

- [x] Review current repository structure against `PRD.md` — `docs/architecture/baseline-audit.md`
- [x] Review installed Composer packages — `docs/architecture/baseline-audit.md`
- [x] Review installed NPM packages — `docs/architecture/baseline-audit.md`
- [ ] Remove unused / redundant packages — **deferred ke M7 (Settings/Branding) & M8 (Audit)**, lihat `docs/architecture/adr-007-package-retention.md`
- [x] Verify Laravel 13 compatibility — `docs/architecture/baseline-audit.md`
- [x] Verify Filament 5 compatibility — `docs/architecture/baseline-audit.md`
- [x] Verify PHP 8.3+ compatibility — `docs/architecture/baseline-audit.md`
- [x] Establish coding conventions — `docs/conventions/coding.md`
- [x] Establish naming conventions — `docs/conventions/naming.md`
- [x] Establish namespace conventions — `docs/architecture/adr-001-namespace-core.md`, `docs/architecture/adr-002-struktur-app.md`
- [x] Establish Core vs Application boundaries — `docs/architecture/adr-005-batas-core-application.md`
- [x] Document architectural decisions — `docs/architecture/adr-001-namespace-core.md` s.d. `adr-007-package-retention.md`
```

- [ ] **Step 2: Verifikasi checklist**

Cek manual: semua 13 item M0 berstatus `[x]` atau berisi catatan deferral; referensi path benar-benar ada di repo.

- [ ] **Step 3: Commit**

```bash
git add docs/TODO.md
git commit -m "docs: update TODO M0 checklist"
```

---

### Task 14: Verifikasi Final M0

**Files:**
- (tidak ada perubahan file)

- [ ] **Step 1: Verifikasi kelengkapan dokumen**

Run: `git ls-files docs/architecture docs/conventions`
Expected:
- `docs/architecture/baseline-audit.md`
- `docs/architecture/adr-template.md`
- `docs/architecture/adr-001-namespace-core.md` s.d. `adr-007-package-retention.md`
- `docs/conventions/naming.md`, `directory-structure.md`, `coding.md`

- [ ] **Step 2: Verifikasi konsistensi referensi**

Run: `git grep -n "adr-00" docs/ | grep -v "adr-template"`
Expected: setiap referensi path ADR mengarah ke file yang ada (tidak ada referensi putus).

- [ ] **Step 3: Verifikasi tidak ada perubahan kode**

Run: `git diff main --stat -- app/ composer.json package.json config/ database/ routes/`
Expected: tidak ada output (hanya `docs/` yang berubah di M0).

- [ ] **Step 4: Verifikasi status repo bersih**

Run: `git status`
Expected: working tree clean.

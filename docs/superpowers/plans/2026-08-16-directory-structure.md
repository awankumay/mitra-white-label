# Directory Structure Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menetapkan struktur direktori final `core/`, `app/`, `modules/` dan konvensi per folder di `docs/conventions/directory-structure.md`, lalu menandai checklist TODO.md §1.2.

**Architecture:** Milestone dokumentasi murni (bukan kode). Spec `docs/superpowers/specs/2026-08-16-directory-structure-design.md` §3 mendefinisikan peta direktori final dan §4 konvensi per folder. Implementasi = memindahkan keputusan spec ke dokumen konvensi yang menjadi acuan developer, lalu mencentang checklist TODO.

**Tech Stack:** Markdown, git. Tidak ada perubahan kode PHP.

## Global Constraints

- Bahasa dokumen: Bahasa Indonesia (konsisten dengan `directory-structure.md` dan ADR existing).
- `core/` tidak boleh mengimpor `app/`/`modules/`; `core/` non-UI tidak bergantung Filament (ADR-005) — pengecualian UI Core di `core/Filament/`.
- Model milik Core hidup di subfolder domain `core/<Domain>/Models/` — **bukan** di `app/Models/` (spec §3.1).
- Policy untuk model Core tetap di `app/Policies/` (spec §4.6).
- Struktur `app/Filament/` mengikuti default Filament v5: `Resources/<Plural>/` (dengan `Pages/` + `Schemas/` di dalam), `Pages/` datar, `Widgets/` datar (spec §3.2).
- `modules/` memakai pola penuh ala `app/` (spec §3.3).
- `Support/` hanya utilitas generik murni — logika bisnis dilarang (spec §4.7).
- Folder fisik tidak dibuat di milestone ini (YAGNI, ADR-002).
- Commit message: conventional commits (`docs:`), satu task = satu commit.

---

### Task 1: Update peta direktori `core/`, `app/`, `modules/` di `docs/conventions/directory-structure.md`

**Files:**
- Modify: `docs/conventions/directory-structure.md:1-46` (header + bagian "Peta Direktori" + bagian "Aturan")

**Interfaces:**
- Consumes: Spec §3.1, §3.2, §3.3 (peta direktori final), ADR-001, ADR-002, ADR-005, ADR-006.
- Produces: Peta direktori final yang konsisten dengan spec; dipakai Task 2 (Konvensi Folder) dan Task 3 (TODO checklist).

- [ ] **Step 1: Update header dokumen (status/tanggal/referensi)**

Ubah baris 3-5 dari:

```markdown
**Status:** Accepted
**Tanggal:** 2026-08-15
**Referensi:** ADR-001, ADR-002, ADR-005, ADR-006
```

menjadi:

```markdown
**Status:** Accepted
**Tanggal:** 2026-08-16
**Referensi:** ADR-001, ADR-002, ADR-005, ADR-006, ADR-008, ADR-009, ADR-010,
spec `docs/superpowers/specs/2026-08-16-directory-structure-design.md`
```

- [ ] **Step 2: Ganti blok peta `core/`**

Ganti seluruh blok `core/` (baris 9-23, dari `core/                     # Core System — namespace Core\` sampai `└── Actions/`) dengan:

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
```

- [ ] **Step 3: Ganti blok peta `app/`**

Ganti seluruh blok `app/` (baris 25-35, dari `app/                      # Application layer — namespace App\` sampai `└── Http/`) dengan:

```text
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
```

- [ ] **Step 4: Ganti blok peta `modules/`**

Ganti seluruh blok `modules/` (baris 37-45, dari `modules/                  # Business modules — namespace Modules\<Name>\` sampai `└── Policies/`) dengan:

```text
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

- [ ] **Step 5: Update bagian "Aturan"**

Ganti blok "## Aturan" (baris 59-64) dari:

```markdown
## Aturan

- `core/` tidak pernah mengimpor `app/` atau `modules/` (ADR-005).
- `app/Filament/` hanya berisi komponen UI; logika bisnis di
  Domain/Actions/Services.
- `modules/` opsional; struktur module mengikuti ADR-006.
- Folder fisik dibuat sesuai kebutuhan milestone — peta ini adalah
  target akhir; `core/` sudah aktif sejak M1.
```

menjadi:

```markdown
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
```

- [ ] **Step 6: Verifikasi hasil**

Jalankan:

```bash
git diff docs/conventions/directory-structure.md
```

Periksa: peta `core/` memuat `Database/`, `Filament/`, `Enums/`; peta `app/` memuat folder Laravel standar dan struktur Filament v5 (`Resources/<Plural>/` dengan `Pages/` + `Schemas/`); peta `modules/` memuat `Actions/`, `Services/`, `Contracts/`, `Enums/`, `Support/`. Tidak ada sisa blok lama.

- [ ] **Step 7: Commit**

```bash
git add docs/conventions/directory-structure.md
git commit -m "docs: define final directory structure maps (TODO 1.2)"
```

---

### Task 2: Tambah bagian "Konvensi Folder" di `docs/conventions/directory-structure.md`

**Files:**
- Modify: `docs/conventions/directory-structure.md` (tambah bagian baru setelah "Aturan", sebelum "Nama Folder")

**Interfaces:**
- Consumes: Spec §4.1-§4.9 (konvensi per folder), Task 1 (peta final sudah ada di file yang sama).
- Produces: Bagian "Konvensi Folder" yang menjadi acuan penempatan class per folder.

- [ ] **Step 1: Tambahkan bagian "Konvensi Folder"**

Sisipkan setelah blok "## Aturan" (sebelum "## Nama Folder"):

```markdown
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

- Semua policy — termasuk untuk model milik Core — di `app/Policies/`
  (`app/Policies/OrganizationPolicy.php`). Authorization adalah concern
  application layer; Core bebas dari konsep policy (ADR-005).
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

- Artefak database milik Core di `core/Database/` (Migrations/, Factories/,
  Seeders/), dimuat via `loadMigrationsFrom` di `CoreServiceProvider`.
- Artefak database aplikasi tetap di `database/` root aplikasi (default Laravel).
```

- [ ] **Step 2: Verifikasi hasil**

Jalankan:

```bash
grep -n "## Konvensi Folder" docs/conventions/directory-structure.md
```

Periksa: muncul dengan sub-bagian Domain, Actions, Services, Contracts, Enums, Policies, Support, Filament, Database — tepat sebelum "## Nama Folder".

- [ ] **Step 3: Commit**

```bash
git add docs/conventions/directory-structure.md
git commit -m "docs: add folder conventions to directory structure (TODO 1.2)"
```

---

### Task 3: Tandai checklist TODO.md §1.2

**Files:**
- Modify: `docs/TODO.md:45-56` (bagian "## 1.2 Directory Structure")

**Interfaces:**
- Consumes: Spec §6.2 (pemetaan item checklist → bagian spec), Task 1-2 (dokumen konvensi final).
- Produces: Checklist §1.2 tercentang penuh dengan referensi.

- [ ] **Step 1: Centang semua item §1.2 dengan referensi**

Ganti blok "## 1.2 Directory Structure" (baris 45-56) dari:

```markdown
## 1.2 Directory Structure

- [ ] Define final `app/` structure
- [ ] Define `Core` structure
- [ ] Define `Domain` conventions
- [ ] Define `Actions` conventions
- [ ] Define `Services` conventions
- [ ] Define `Contracts` conventions
- [ ] Define `Enums` conventions
- [ ] Define `Support` conventions
- [ ] Define `Filament` conventions
- [ ] Define `modules/` conventions
```

menjadi:

```markdown
## 1.2 Directory Structure

- [x] Define final `app/` structure — `docs/conventions/directory-structure.md`, spec §3.2
- [x] Define `Core` structure — `docs/conventions/directory-structure.md`, spec §3.1
- [x] Define `Domain` conventions — `docs/conventions/directory-structure.md`, spec §4.1
- [x] Define `Actions` conventions — `docs/conventions/directory-structure.md`, spec §4.2
- [x] Define `Services` conventions — `docs/conventions/directory-structure.md`, spec §4.3
- [x] Define `Contracts` conventions — `docs/conventions/directory-structure.md`, spec §4.4
- [x] Define `Enums` conventions — `docs/conventions/directory-structure.md`, spec §4.5
- [x] Define `Support` conventions — `docs/conventions/directory-structure.md`, spec §4.7
- [x] Define `Filament` conventions — `docs/conventions/directory-structure.md`, spec §3.2, §4.8
- [x] Define `modules/` conventions — `docs/conventions/directory-structure.md`, spec §3.3
```

- [ ] **Step 2: Verifikasi hasil**

Jalankan:

```bash
grep -n "Define" docs/TODO.md | head -20
```

Periksa: sepuluh baris §1.2 semuanya `[x]` dengan referensi; baris di luar §1.2 tidak berubah.

- [ ] **Step 3: Commit**

```bash
git add docs/TODO.md
git commit -m "docs: mark directory structure checklist done (TODO 1.2)"
```

---

### Task 4: Verifikasi akhir konsistensi dokumen

**Files:**
- Verify only: `docs/conventions/directory-structure.md`, `docs/TODO.md`, `docs/superpowers/specs/2026-08-16-directory-structure-design.md`

**Interfaces:**
- Consumes: Task 1-3 (dokumen final).
- Produces: Jaminan konsistensi lintas dokumen; tidak ada kode berubah.

- [ ] **Step 1: Cek konsistensi peta vs spec**

Jalankan:

```bash
git diff HEAD~3 --stat
grep -n "Filament" docs/conventions/directory-structure.md
grep -n "Database/" docs/conventions/directory-structure.md
```

Periksa: hanya tiga file dokumen yang berubah (`docs/conventions/directory-structure.md`, `docs/TODO.md`, `docs/superpowers/specs/2026-08-16-directory-structure-design.md`); `Filament/` muncul di peta `core/`, `app/`, `modules/`; `Database/` muncul di peta `core/` dan `modules/`.

- [ ] **Step 2: Cek tidak ada placeholder / blok lama**

Jalankan:

```bash
grep -n "target akhir" docs/conventions/directory-structure.md
grep -c "Konvensi Folder" docs/conventions/directory-structure.md
```

Periksa: "target akhir" hanya di bagian "Aturan" (kalimat "peta ini adalah target akhir"); "Konvensi Folder" muncul tepat 1 kali (judul bagian).

- [ ] **Step 3: Pastikan arch test tidak terpengaruh**

Jalankan (opsional, hanya jika `tests/Arch` sudah ada):

```bash
php artisan test --filter=Arch 2>/dev/null || echo "Arch test belum ada — skip"
```

Periksa: tidak ada kegagalan; karena milestone ini hanya mengubah dokumen, hasil arch test (jika ada) harus tetap sama seperti sebelumnya.

- [ ] **Step 4: Commit verifikasi (jika ada perbaikan)**

Jika Step 1-3 menemukan masalah, perbaiki inline lalu:

```bash
git add -A
git commit -m "docs: fix directory structure doc consistency"
```

Jika bersih, tidak ada commit tambahan — milestone selesai.

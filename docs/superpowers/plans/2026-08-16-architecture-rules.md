# Architecture Rules Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menetapkan aturan arsitektural §1.3 — update konvensi (coding.md, naming.md, PRD §19) dengan format permission Shield, dan perkuat arch test untuk aturan dependensi baru.

**Architecture:** Dua bagian: (1) dokumen konvensi yang mengikat developer (Model, Policy, Action, Service, Event/Listener, aturan dependensi), (2) verifikasi otomatis via Pest arch test. Format permission mengikuti Shield aktual (`resource:action`, separator `:`, snake) — konvensi mengikuti alat, PRD/naming.md direkonsiliasi.

**Tech Stack:** Markdown (dokumen), Pest arch test (PHP), git. Tidak ada scaffolding aplikasi baru.

## Global Constraints

- Bahasa dokumen: Bahasa Indonesia (konsisten dokumen existing).
- Format permission: `resource:action` (separator `:`, snake) — hasil Shield, bukan `resource.action`.
- `Core\` tidak mengimpor `App\`/`Modules\`; `Core\` non-UI tidak bergantung Filament (ADR-005) — arch test existing tidak boleh rusak.
- Model Core hidup di `core/<Domain>/Models/`, bukan `app/Models/`; policy di `app/Policies/` (keputusan sesi struktur direktori).
- Action UI: label dari lang (ID default), icon `Filament\Support\Icons\Heroicon` enum, action general via Concerns.
- Event: Core melempar, konsumen mendengarkan — Core tidak mendengarkan event aplikasi.
- Tidak mengubah konfigurasi `config/filament-shield.php` (konvensi mengikuti alat).
- Commit message: conventional commits (`docs:` untuk dokumen, `test:` untuk arch test), satu task = satu commit.

---

### Task 1: Rekonsiliasi format permission di `docs/conventions/naming.md`

**Files:**
- Modify: `docs/conventions/naming.md:36-40` (bagian "## Permission")

**Interfaces:**
- Consumes: Spec §5.2 (format permission Shield terverifikasi), config `filament-shield.php` (`separator => ':'`, `case => 'snake'`).
- Produces: Format permission final `resource:action` yang menjadi acuan Task 3 (PRD §19) dan Task 5 (coding.md).

- [ ] **Step 1: Update bagian Permission di naming.md**

Ganti blok "## Permission" (baris 36-40) dari:

```markdown
## Permission

- Pola `resource.action` (PRD §19):
  `users.view`, `users.create`, `users.update`, `users.delete`,
  `organization_units.view`, dst.
```

menjadi:

```markdown
## Permission

- Pola `resource:action` (format Filament Shield, PRD §19):
  `users:view`, `users:create`, `users:update`, `users:delete`,
  `organization_units:view`, dst.
- Method policy di-generate Shield ke snake_case: `viewAny` → `view_any`,
  `forceDeleteAny` → `force_delete_any`.
- Separator dan case mengikuti `config/filament-shield.php`
  (`permissions.separator => ':'`, `permissions.case => 'snake'`).
```

- [ ] **Step 2: Verifikasi hasil**

Jalankan:

```bash
grep -n "resource" docs/conventions/naming.md
```

Periksa: `resource:action` muncul, tidak ada sisa `resource.action`.

- [ ] **Step 3: Commit**

```bash
git add docs/conventions/naming.md
git commit -m "docs: reconcile permission format with Filament Shield (TODO 1.3)"
```

---

### Task 2: Update PRD §19 — pola permission

**Files:**
- Modify: `docs/PRD.md:635-653` (bagian "Permission menggunakan pola" + blok contoh)

**Interfaces:**
- Consumes: Task 1 (format final `resource:action`).
- Produces: PRD konsisten dengan naming.md dan Shield aktual.

- [ ] **Step 1: Update blok permission di PRD §19**

Ganti blok (baris 637-639):

```markdown
```text
resource.action
```
```

menjadi:

```markdown
```text
resource:action
```
```

- [ ] **Step 2: Update contoh permission di PRD §19**

Ganti blok contoh (baris 643-653):

```markdown
```text
users.view
users.create
users.update
users.delete

organization_units.view
organization_units.create
organization_units.update
organization_units.delete
```
```

menjadi:

```markdown
```text
users:view
users:create
users:update
users:delete

organization_units:view
organization_units:create
organization_units:update
organization_units:delete
```
```

Periksa dengan:

```bash
grep -n "\.view\|\.create\|\.update\|\.delete" docs/PRD.md | head -20
```

Expected: tidak ada match (semua sudah `:`), atau match hanya di luar §19 yang tidak terkait format permission.

- [ ] **Step 3: Commit**

```bash
git add docs/PRD.md
git commit -m "docs: update PRD 19 permission format to Shield convention (TODO 1.3)"
```

---

### Task 3: Update `docs/conventions/coding.md` — Architecture Rules

**Files:**
- Modify: `docs/conventions/coding.md:1-46` (seluruh file, tambah sub-bagian)

**Interfaces:**
- Consumes: Spec §3 (aturan dependensi), §4 (Model), §6 (Action), §7 (Service), §8 (Event/Listener), Task 1 (format permission).
- Produces: Bagian "Architecture Rules" di coding.md yang menjadi acuan developer.

- [ ] **Step 1: Tambah sub-bagian "Architecture Rules" setelah "## Batas Dependensi (ADR-005)"**

Sisipkan setelah blok "## Batas Dependensi (ADR-005)" (baris 15-19):

```markdown
## Architecture Rules

### Aturan Dependensi

- `Core\` tidak mengimpor `App\` / `Modules\` (ADR-005) — diverifikasi arch test.
- `App\` boleh mengimpor `Core\`, tetapi **tidak** mengimpor `Modules\`.
- `Modules\<Name>\` boleh mengimpor `Core\` dan `App\` public (ADR-006).
- `Core\` non-UI tidak bergantung pada Filament; komponen UI Core di `core/Filament/`.
- Core **melempar event, konsumen mendengarkan** — Core tidak mendengarkan event aplikasi.

### Model

- Model Core di `core/<Domain>/Models/`, model aplikasi di `app/Models/`,
  model module di `modules/<Name>/Models/`.
- Thin model: model memegang atribut, casts, relasi, scope sederhana — bukan
  logika bisnis (→ Action/Service).
- Relasi lintas-batas diekspresikan via contract, bukan import model Core langsung.

### Policy

- Policy untuk resource Filament di-generate Filament Shield ke `app/Policies/`.
- Format permission: `resource:action` (separator `:`, snake).
- Policy manual hanya untuk di luar resource Filament; jangan menumpuk logika
  bisnis di policy (delegasikan ke Action/Service).

### Action

- Action = operasi tunggal reusable: `CreateOrganizationAction`, `final`,
  `handle()`/`__invoke()`, constructor injection.
- Action tidak memanggil Service (Service yang memanggil Action).
- Action UI: label dari lang (ID default), icon `Heroicon` enum, action general
  via Concerns di `app/Filament/Concerns/`.

### Service

- Service = koordinator multi-langkah: `OrganizationService`, `final`,
  constructor injection.
- Alur yang mengubah banyak record dibungkus `DB::transaction()`.

### Event/Listener

- Event Core terkolokasi per-domain: `core/<Domain>/Events/`.
- Penamaan event: `NounVerbPastTense` (`OrganizationCreated`).
- Listener aplikasi di `app/Listeners/`; event untuk integrasi non-return-value.
```

- [ ] **Step 2: Verifikasi hasil**

Jalankan:

```bash
grep -n "Architecture Rules" docs/conventions/coding.md
grep -n "resource:action" docs/conventions/coding.md
```

Periksa: sub-bagian "Architecture Rules" ada, `resource:action` muncul.

- [ ] **Step 3: Commit**

```bash
git add docs/conventions/coding.md
git commit -m "docs: add architecture rules conventions (TODO 1.3)"
```

---

### Task 4: Perkuat `tests/Arch/CoreArchTest.php` — 2 arch test baru

**Files:**
- Modify: `tests/Arch/CoreArchTest.php:1-9` (seluruh file)

**Interfaces:**
- Consumes: Spec §3.2 (2 arch test baru), ADR-005 (aturan existing tetap).
- Produces: Arch test `App` tidak pakai `Modules`; tidak ada model Core di `App\Models`.

- [ ] **Step 1: Tulis arch test baru (tambahkan setelah 2 test existing)**

Ganti seluruh isi `tests/Arch/CoreArchTest.php` dari:

```php
<?php

arch('Core must not use App or Modules')
    ->expect('Core')
    ->not->toUse(['App', 'Modules']);

arch('Core non-UI must not use Filament')
    ->expect('Core')
    ->not->toUse('Filament');
```

menjadi:

```php
<?php

arch('Core must not use App or Modules')
    ->expect('Core')
    ->not->toUse(['App', 'Modules']);

arch('Core non-UI must not use Filament')
    ->expect('Core')
    ->not->toUse('Filament');

arch('App must not use Modules')
    ->expect('App')
    ->not->toUse('Modules');

arch('Core must not use App\\Models')
    ->expect('Core')
    ->not->toUse('App\\Models');
```

Catatan: test ke-4 mempertegas aturan "model Core tidak hidup di `App\Models`" —
karena Core tidak boleh memakai `App\Models` sama sekali (model Core hidup di
`core/<Domain>/Models/`). Test ke-1 (`Core` tidak pakai `App`) sudah mencakup
ini secara umum; test ke-4 membuat aturan lokasi model eksplisit dan terdokumentasi.

- [ ] **Step 2: Jalankan arch test — verifikasi lolos**

Jalankan:

```bash
php artisan test --filter=Arch
```

Expected: 4 passed (2 existing + 2 baru), tidak ada kegagalan.

- [ ] **Step 3: Verifikasi arch test baru benar-benar menguji (negative check opsional)**

Untuk memastikan test tidak false-positive, buat file sementara `tests/Arch/_tmp_violation.php`:

```php
<?php

namespace App\Models;

use Modules\Inventory\Inventory;
```

Lalu jalankan:

```bash
php artisan test --filter=Arch
```

Expected: test `App must not use Modules` **gagal** (membuktikan arch test bekerja). Hapus file sementara (Windows: `del tests\Arch\_tmp_violation.php`; Unix: `rm tests/Arch/_tmp_violation.php`).

Jalankan lagi dan pastikan 4 passed.

- [ ] **Step 4: Commit**

```bash
git add tests/Arch/CoreArchTest.php
git commit -m "test: enforce App-Modules and Core model location rules (TODO 1.3)"
```

---

### Task 5: Tandai checklist TODO.md §1.3

**Files:**
- Modify: `docs/TODO.md:58-67` (bagian "## 1.3 Architecture Rules")

**Interfaces:**
- Consumes: Spec §9.5 (pemetaan checklist → bagian spec), Task 1-4 (dokumen final).
- Produces: Checklist §1.3 tercentang penuh.

- [ ] **Step 1: Centang semua item §1.3 dengan referensi**

Ganti blok "## 1.3 Architecture Rules" dari:

```markdown
## 1.3 Architecture Rules

- [ ] Define Core dependency rules
- [ ] Prevent Core → Business Module dependency
- [ ] Define Module → Core dependency rules
- [ ] Define Model conventions
- [ ] Define Policy conventions
- [ ] Define Action conventions
- [ ] Define Service conventions
- [ ] Define Event/Listener conventions
```

menjadi:

```markdown
## 1.3 Architecture Rules

- [x] Define Core dependency rules — `docs/conventions/coding.md`, spec §3
- [x] Prevent Core → Business Module dependency — `docs/conventions/coding.md`, spec §3.1, arch test §3.2
- [x] Define Module → Core dependency rules — `docs/conventions/coding.md`, spec §3.1
- [x] Define Model conventions — `docs/conventions/coding.md`, spec §4
- [x] Define Policy conventions — `docs/conventions/coding.md`, spec §5
- [x] Define Action conventions — `docs/conventions/coding.md`, spec §6
- [x] Define Service conventions — `docs/conventions/coding.md`, spec §7
- [x] Define Event/Listener conventions — `docs/conventions/coding.md`, spec §8
```

- [ ] **Step 2: Verifikasi hasil**

Jalankan:

```bash
grep -n "Define" docs/TODO.md | sed -n '1,30p'
```

Periksa: delapan baris §1.3 semuanya `[x]` dengan referensi; item di luar §1.3 tidak berubah.

- [ ] **Step 3: Commit**

```bash
git add docs/TODO.md
git commit -m "docs: mark architecture rules checklist done (TODO 1.3)"
```

---

### Task 6: Verifikasi akhir

**Files:**
- Verify only: `docs/conventions/naming.md`, `docs/PRD.md`, `docs/conventions/coding.md`, `tests/Arch/CoreArchTest.php`, `docs/TODO.md`

**Interfaces:**
- Consumes: Task 1-5.
- Produces: Jaminan konsistensi lintas dokumen + arch test hijau.

- [ ] **Step 1: Cek tidak ada sisa pola `resource.action`**

Jalankan:

```bash
grep -rn "resource\.action" docs/ PRD.md 2>/dev/null || grep -rn "users\.view\|users\.create" docs/ 2>/dev/null || echo "OK: no legacy permission format"
```

Periksa: tidak ada `resource.action` atau `users.view` di dokumen (kecuali konteks "diubah dari").

- [ ] **Step 2: Cek konsistensi format permission di 3 dokumen**

Jalankan:

```bash
grep -n "resource:action" docs/conventions/naming.md docs/conventions/coding.md
grep -n "resource:action" docs/PRD.md
```

Periksa: `resource:action` muncul konsisten di naming.md, coding.md, PRD §19.

- [ ] **Step 3: Jalankan arch test + suite yang relevan**

Jalankan:

```bash
php artisan test --filter=Arch
php artisan test --testsuite=Unit
```

Expected: Arch 4 passed; Unit suite tetap hijau.

- [ ] **Step 4: Pastikan hanya file yang dimaksud berubah**

Jalankan:

```bash
git diff --stat HEAD~5
```

Periksa: hanya `docs/conventions/naming.md`, `docs/PRD.md`, `docs/conventions/coding.md`, `tests/Arch/CoreArchTest.php`, `docs/TODO.md` yang berubah dalam 5 commit terakhir.

- [ ] **Step 5: Commit perbaikan (jika ada)**

Jika Step 1-4 menemukan masalah, perbaiki inline lalu:

```bash
git add -A
git commit -m "docs: fix architecture rules consistency"
```

Jika bersih, tidak ada commit tambahan — milestone selesai.

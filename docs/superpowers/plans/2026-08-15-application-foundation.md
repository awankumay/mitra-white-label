# Application Foundation (TODO 1.1) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menetapkan fondasi arsitektur Core System — ADR baru, scaffolding `core/` dengan PSR-4, `CoreServiceProvider`, konfigurasi publishable, dan verifikasi arsitektur otomatis.

**Architecture:** Core hidup in-repo di `core/` dengan namespace `Core\` (ADR-001), dibootstrapped seperti package (ADR-010): `CoreServiceProvider` sebagai entry point tunggal yang me-register sub-provider dari `config/core.php` (ADR-009). Batas dependensi tegas `Core\` → tidak boleh mengimpor `App\`/`Modules\`/Filament non-UI (ADR-005) dijamin oleh Pest arch test. Extension points v1: contracts + config + events + actions (ADR-008) — tanpa kontrak spekulatif di M1.

**Tech Stack:** Laravel 13, PHP 8.3+, Pest v4 (arch testing built-in), Composer PSR-4, Laravel Pint, PHPStan/Larastan.

## Global Constraints

- Namespace: `Core\` → `core/` (PSR-4, ADR-001). Nama file = nama kelas, `PascalCase`.
- Aturan dependensi (ADR-005): `Core\` **tidak boleh** mengimpor `App\` atau `Modules\`; `Core\` non-UI **tidak boleh** mengimpor Filament. Komponen UI Core (bila ada) dikecualikan.
- `CoreServiceProvider` extends `Illuminate\Support\ServiceProvider`; sub-provider didaftarkan dari `config('core.providers')` — bukan hardcode di bootstrap.
- Sumber config di `core/Config/core.php`, di-merge via `mergeConfigFrom`, publishable tag `core-config`, kunci `core.{domain}.{key}`.
- `CoreException` extends `\RuntimeException`, namespace `Core\Exceptions`.
- PHP minimum 8.3; `composer check` (Pint → Pest → PHPStan) adalah quality gate.
- Bahasa: kode, kelas, metode, komentar = English. String user-facing = localization (Bahasa Indonesia default).
- Commit: conventional commits (`feat:`, `fix:`, `docs:`, `chore:`, `refactor:`, `test:`); satu task = satu commit.
- Windows environment: heredoc `<<'EOF'` TIDAK berfungsi di cmd.exe — gunakan `git commit -F <path-ke-file-message>` dengan file commit message, atau `git commit -m "..."`.

---

### Task 1: PSR-4 `Core\` Mapping + Scaffolding `core/`

**Files:**
- Modify: `composer.json` (bagian `autoload.psr-4`)
- Create: `core/Exceptions/CoreException.php`
- Modify: `bootstrap/providers.php`

**Interfaces:**
- Consumes: —
- Produces:
  - `Core\Exceptions\CoreException` — class `CoreException extends \RuntimeException {}`, namespace `Core\Exceptions`.
  - `Core\CoreServiceProvider` (Task 2) — namespace `Core`, extends `Illuminate\Support\ServiceProvider`.
  - `bootstrap/providers.php` — array provider berisi `Core\CoreServiceProvider::class` sebagai entri pertama (sebelum `App\Providers\AppServiceProvider::class`).

- [ ] **Step 1: Tambah mapping PSR-4 di composer.json**

Edit `composer.json` bagian `autoload.psr-4` menjadi:

```json
"autoload": {
    "psr-4": {
        "Core\\": "core/",
        "App\\": "app/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/"
    }
},
```

- [ ] **Step 2: Regenerate autoloader**

Run: `composer dump-autoload`
Expected: exit 0, output berisi `Generated autoload files` (atau `Generated optimized autoload files`).

- [ ] **Step 3: Buat CoreException**

Create `core/Exceptions/CoreException.php`:

```php
<?php

namespace Core\Exceptions;

use RuntimeException;

class CoreException extends RuntimeException
{
}
```

- [ ] **Step 4: Tambah CoreServiceProvider ke bootstrap**

Modify `bootstrap/providers.php` menjadi:

```php
<?php

return [
    Core\CoreServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
];
```

- [ ] **Step 5: Verifikasi boot aplikasi**

Run: `php artisan about`
Expected: exit 0, output tabel informasi aplikasi tanpa error (provider belum punya logic, jadi pasti boot).

- [ ] **Step 6: Commit**

```bash
git add composer.json composer.lock bootstrap/providers.php core/Exceptions/CoreException.php
git commit -m "feat: scaffold core namespace and CoreException"
```

> Catatan: `composer.lock` ikut di-commit hanya jika berubah oleh `composer dump-autoload` (biasanya tidak untuk dump-autoload). Jika `git status` menunjukkan `composer.lock` berubah, ikutkan; jika tidak, jangan.

---

### Task 2: Config `core.php` + `CoreServiceProvider`

**Files:**
- Create: `core/Config/core.php`
- Create: `core/CoreServiceProvider.php`
- Modify: `config/app.php` — tambah entri `providers` di array `providers`? **TIDAK** — `bootstrap/providers.php` sudah didaftarkan di Task 1; tidak ada perubahan di file ini.

**Interfaces:**
- Consumes: `Core\CoreServiceProvider` (didaftarkan Task 1), daftar provider `bootstrap/providers.php`.
- Produces:
  - `config('core.providers')` — array (kosong di M1) berisi class-string sub-provider.
  - `Core\CoreServiceProvider::register()` — `mergeConfigFrom(__DIR__.'/Config/core.php', 'core')` lalu daftarkan tiap provider di `config('core.providers')` via `$this->app->register()`.
  - `Core\CoreServiceProvider::boot()` — `$this->publishes([...], 'core-config')`.
  - Tag publish: `core-config` → menyalin `core/Config/core.php` ke `config/core.php`.

- [ ] **Step 1: Tulis failing test untuk register sub-provider**

Create `tests/Unit/Core/CoreServiceProviderTest.php`:

```php
<?php

namespace Tests\Unit\Core;

use Core\CoreServiceProvider;
use Tests\TestCase;

class CoreServiceProviderTest extends TestCase
{
    public function test_core_config_is_merged(): void
    {
        $this->assertTrue(config()->has('core.providers'));
        $this->assertIsArray(config('core.providers'));
    }

    public function test_core_service_provider_registers_providers_from_config(): void
    {
        // Simulasi: daftar provider dari config di-set sebelum provider boot.
        config()->set('core.providers', [StubSubProvider::class]);

        $this->app->register(CoreServiceProvider::class);
        $this->app->boot();

        $this->assertTrue($this->app->providerIsLoaded(StubSubProvider::class));
    }
}

class StubSubProvider extends \Illuminate\Support\ServiceProvider
{
}
```

- [ ] **Step 2: Jalankan test untuk verifikasi gagal**

Run: `php artisan test --filter=CoreServiceProviderTest`
Expected: FAIL — `config('core.providers')` belum ada (null), `CoreServiceProvider` belum ada. Test pertama gagal dengan "undefined array key" / assertion false.

- [ ] **Step 3: Buat config core.php**

Create `core/Config/core.php`:

```php
<?php

return [
    'providers' => [
        // Sub-provider Core didaftarkan di sini saat domain dibuat,
        // contoh: Core\Organization\OrganizationServiceProvider::class.
    ],
    // Domain sub-sistem ditambahkan saat milestone yang membutuhkannya,
    // misal: 'context' => [...], 'settings' => [...]
];
```

- [ ] **Step 4: Buat CoreServiceProvider**

Create `core/CoreServiceProvider.php`:

```php
<?php

namespace Core;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/core.php', 'core');

        foreach ((array) $this->app['config']->get('core.providers', []) as $provider) {
            $this->app->register($provider);
        }
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/Config/core.php' => config_path('core.php'),
        ], 'core-config');
    }
}
```

- [ ] **Step 5: Jalankan test untuk verifikasi lolos**

Run: `php artisan test --filter=CoreServiceProviderTest`
Expected: PASS (2 tests). Jika gagal dengan provider sudah terdaftar / double registration, pastikan test menggunakan `$this->app->register()` sekali dan `config()->set()` sebelum register — tidak ada double-boot karena `providerIsLoaded` guard.

- [ ] **Step 6: Verifikasi publish tag**

Run: `php artisan vendor:publish --tag=core-config --no-interaction`
Expected: file `config/core.php` dibuat (salinan dari `core/Config/core.php`).

Kemudian **hapus hasil publish** agar tidak double-source (sumber tetap di `core/Config/core.php`):

```bash
del config\core.php
```

> Alternatif jika tidak ingin publish saat dev: cukup jalankan dengan `--dry-run`? Tidak ada. Jalankan publish lalu hapus seperti di atas, atau skip step ini jika sudah yakin konfigurasi `publishes` benar. Verifikasi wajib minimal sekali di lingkungan dev.

- [ ] **Step 7: Commit**

```bash
git add core/Config/core.php core/CoreServiceProvider.php tests/Unit/Core/CoreServiceProviderTest.php
git commit -m "feat: add CoreServiceProvider with merged core config"
```

> `config/core.php` hasil publish jangan di-commit (sudah dihapus di Step 6). Jika `config/core.php` tidak sengaja ter-commit, hapus dari index: `git rm --cached config/core.php`.

---

### Task 3: Pest Arch Test — Batas Dependensi Core (ADR-005)

**Files:**
- Modify: `phpunit.xml` — tambah testsuite `Arch`
- Modify: `phpstan.neon` — tambah `core/` ke paths
- Create: `tests/Arch/CoreArchTest.php`
- Modify: `pint.json` — tambah `core` ke exclude? **TIDAK** — Pint harus format `core/`. Tidak ada perubahan.

**Interfaces:**
- Consumes: namespace `Core\` (Task 1), `Core\CoreServiceProvider` (Task 2).
- Produces:
  - Pest arch expectations: `expect('Core')->not->toUse(['App', 'Modules'])` dan `expect('Core')->not->toUse(['Filament'])` — dengan catatan pengecualian Filament untuk komponen UI Core di masa depan (tidak ada sekarang, jadi full `not->toUse`).
  - `phpunit.xml` testsuite `Arch` → `<directory>tests/Arch</directory>`.
  - `phpstan.neon` paths: `- app/` dan `- core/`.

- [ ] **Step 1: Tambah testsuite Arch di phpunit.xml**

Edit `phpunit.xml` — tambah di dalam `<testsuites>`:

```xml
<testsuites>
    <testsuite name="Unit">
        <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="Feature">
        <directory>tests/Feature</directory>
    </testsuite>
    <testsuite name="Arch">
        <directory>tests/Arch</directory>
    </testsuite>
</testsuites>
```

- [ ] **Step 2: Tulis failing arch test**

Create `tests/Arch/CoreArchTest.php`:

```php
<?php

arch('Core must not use App or Modules')
    ->expect('Core')
    ->not->toUse(['App', 'Modules']);

arch('Core non-UI must not use Filament')
    ->expect('Core')
    ->not->toUse('Filament');
```

- [ ] **Step 3: Jalankan arch test — verifikasi gagal**

Run: `php artisan test --testsuite=Arch`
Expected: FAIL — `Core\CoreServiceProvider` meng-`use Illuminate\Support\ServiceProvider` (bukan App/Modules/Filament, jadi seharusnya **PASS**). 

> **Catatan penting:** Dengan implementasi Task 2, arch test ini seharusnya sudah PASS karena `CoreServiceProvider` hanya meng-`use` `Illuminate\Support\ServiceProvider`. Jika ternyata FAIL, itu menandakan ada import terlarang yang lolos — periksa `core/` untuk `use App\`/`use Modules\`/`use Filament\`.

> Untuk verifikasi "test benar-benar menangkap pelanggaran" (failing test yang valid), tambahkan sementara file `core/ForbiddenProbe.php` berisi `use App\Models\User;` lalu jalankan arch test → harus FAIL. Setelah itu hapus probe tersebut.

- [ ] **Step 4: Validasi arch test menangkap pelanggaran (probe)**

Buat sementara `core/ForbiddenProbe.php`:

```php
<?php

namespace Core;

use App\Models\User;

class ForbiddenProbe
{
    public function probe(): void
    {
        new User();
    }
}
```

Run: `php artisan test --testsuite=Arch`
Expected: FAIL — `Core\ForbiddenProbe` menggunakan `App\Models\User`, melanggar `not->toUse(['App', 'Modules'])`.

Hapus probe:

```bash
del core\ForbiddenProbe.php
```

- [ ] **Step 5: Jalankan semua test**

Run: `php artisan test`
Expected: PASS — Unit (2 test), Arch (2 test), Feature (existing).

- [ ] **Step 6: Tambah core/ ke PHPStan**

Edit `phpstan.neon`:

```yaml
parameters:

    paths:
        - app/
        - core/

    # Level 10 is the highest level
    level: 5
```

- [ ] **Step 7: Jalankan quality gate lengkap**

Run: `composer check`
Expected: exit 0 — Pint (format, tanpa perubahan), Pest (semua test PASS), PHPStan (level 5, tanpa error di `app/` + `core/`).

- [ ] **Step 8: Commit**

```bash
git add phpunit.xml phpstan.neon tests/Arch/CoreArchTest.php
git commit -m "test: enforce Core dependency boundaries with arch test"
```

---

### Task 4: ADR-008, ADR-009, ADR-010 + Update Konvensi

**Files:**
- Create: `docs/architecture/adr-008-extension-points.md`
- Create: `docs/architecture/adr-009-service-provider-strategy.md`
- Create: `docs/architecture/adr-010-configuration-bootstrap.md`
- Modify: `docs/conventions/directory-structure.md`
- Modify: `docs/TODO.md`

**Interfaces:**
- Consumes: keputusan desain dari spec §3 (extension points, provider strategy, config/bootstrap), hasil Task 1-3.
- Produces: dokumen ADR (mengikuti `docs/architecture/adr-template.md`), konvensi `directory-structure.md` yang menyebut `core/` sebagai struktur aktual + pola `core.{domain}.{key}`, TODO.md §1.1 yang di-update (item dijawab ADR dicentang; item YAGNI diberi catatan).

- [ ] **Step 1: Baca template ADR**

Run: `type docs\architecture\adr-template.md`
Expected: struktur template (Status, Tanggal, Context, Decision, Consequences). Ikuti format ini.

- [ ] **Step 2: Tulis ADR-008 — Extension Points**

Create `docs/architecture/adr-008-extension-points.md`:

```markdown
# ADR-008: Extension Points Core

**Status:** Accepted
**Tanggal:** 2026-08-15

## Context

PRD §55 menekankan minimasi framework/vendor modification dengan
contracts/composition/actions. Core perlu mekanisme extension yang jelas
tanpa infrastruktur berat.

## Decision

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

## Consequences

- API Core diekspresikan sebagai contracts; implementasi dapat diganti
  via config.
- Integrasi lintas sub-sistem memakai events — tanpa coupling langsung.
- Actions menjadi unit reusable yang bisa dipanggil dari mana saja.
- Penambahan mekanisme extension baru (mis. pipeline) dievaluasi saat
  kebutuhan nyata muncul.
```

- [ ] **Step 3: Tulis ADR-009 — Service Provider Strategy**

Create `docs/architecture/adr-009-service-provider-strategy.md`:

```markdown
# ADR-009: Strategi Service Provider Core

**Status:** Accepted
**Tanggal:** 2026-08-15

## Context

Core terdiri dari beberapa domain (Organization, Settings, Branding,
Audit, Security, dll). Dibutuhkan strategi pendaftaran provider yang
mendukung ekstraksi package di masa depan, tanpa membuat
`bootstrap/providers.php` panjang dan berubah-ubah.

## Decision

- `Core\CoreServiceProvider` adalah entry point tunggal Core.
- Sub-provider per domain didaftarkan dari daftar di `config/core.php`
  (kunci `core.providers`), bukan hardcode di provider maupun langsung di
  `bootstrap/providers.php`.
- `bootstrap/providers.php` tetap pendek dan tidak berubah per-domain.

## Consequences

- Penambahan domain baru cukup menambah satu entri di `config/core.php`.
- `bootstrap/providers.php` stabil — memudahkan upgrade & ekstraksi.
- Sub-provider tetap dapat di-disable aplikasi dengan mengosongkan daftar
  di config yang dipublish.
- Daftar provider harus dijaga agar tidak menjadi tempat sampah;
  provider yang tidak terpakai tidak didaftarkan.
```

- [ ] **Step 4: Tulis ADR-010 — Configuration & Bootstrap**

Create `docs/architecture/adr-010-configuration-bootstrap.md`:

```markdown
# ADR-010: Konfigurasi & Bootstrap Core (In-Repo, Seperti Package)

**Status:** Accepted
**Tanggal:** 2026-08-15

## Context

Core dikembangkan in-repo (ADR-001: `core/`). Dibutuhkan strategi
konfigurasi dan bootstrap yang konsisten dengan cara kerja package
Laravel, sehingga ekstraksi ke package terpisah di masa depan tidak
memerlukan perubahan arsitektur.

## Decision

- Core dikembangkan in-repo di `core/`, dibootstrapped seperti package.
- Sumber konfigurasi di `core/Config/core.php`; di-merge via
  `mergeConfigFrom` di `CoreServiceProvider`.
- Publishable dengan tag `core-config` (Laravel vendor:publish) sehingga
  aplikasi dapat meng-override — tetapi sumber tetap di-commit in-repo.
- Kunci konfigurasi mengikuti pola `core.{domain}.{key}`.

## Consequences

- Konfigurasi Core terpusat, terlihat, dan dapat di-override per-install.
- Aplikasi yang tidak butuh override cukup memakai default in-repo.
- Ekstraksi package di masa depan tinggal memindahkan `core/` + PSR-4.
- Developer harus ingat: sumber config adalah `core/Config/core.php`;
  `config/core.php` (hasil publish) hanya untuk override.
```

- [ ] **Step 5: Update konvensi directory-structure.md**

Modify `docs/conventions/directory-structure.md` — ubah bagian `core/` dari "target akhir" menjadi struktur aktual, dan tambah catatan config. Buka file, lalu:

- Di bawah peta direktori `core/`, tambahkan blok:

```markdown
## Konfigurasi Core

- Sumber: `core/Config/core.php` (di-commit in-repo).
- Bootstrap: `Core\CoreServiceProvider` di `bootstrap/providers.php`.
- Sub-provider domain: daftar di `config('core.providers')`.
- Publishable: `php artisan vendor:publish --tag=core-config` → menyalin
  ke `config/core.php` untuk override aplikasi.
- Pola kunci: `core.{domain}.{key}`.
```

- Ubah kalimat "Folder fisik dibuat sesuai kebutuhan milestone — peta ini adalah target akhir, bukan kewajiban membuat folder kosong di M0" menjadi:

```markdown
- Folder fisik dibuat sesuai kebutuhan milestone — peta ini adalah
  target akhir; `core/` sudah aktif sejak M1.
```

- [ ] **Step 6: Update TODO.md §1.1**

Modify `docs/TODO.md` bagian §1.1 (lines 30-43). Ganti checklist menjadi:

```markdown
## 1.1 Application Foundation

- [x] Define Core application architecture — ADR-001, ADR-002, ADR-005
- [x] Define Core namespaces — ADR-001
- [x] Define application layer boundaries — ADR-005
- [x] Define extension points — ADR-008 (mekanisme; kontrak nyata per-kebutuhan)
- [x] Define service provider strategy — ADR-009
- [x] Define configuration strategy — ADR-010
- [x] Define bootstrap strategy — ADR-010
- [ ] Define Core contracts — per-kebutuhan (M4: context, M5: auth, dst.)
- [ ] Define Core interfaces — per-kebutuhan
- [ ] Define Core events — per-kebutuhan (M8: audit, notification)
- [ ] Define Core actions — per-kebutuhan (M3: CreateOrganization, dst.)
- [x] Define Core exceptions — `CoreException` dibuat; sisanya per-kebutuhan
```

- [ ] **Step 7: Commit**

```bash
git add docs/architecture/adr-008-extension-points.md docs/architecture/adr-009-service-provider-strategy.md docs/architecture/adr-010-configuration-bootstrap.md docs/conventions/directory-structure.md docs/TODO.md
git commit -m "docs: record extension points, provider strategy, config bootstrap (ADR-008..010)"
```

---

### Task 5: Verifikasi Akhir (Acceptance)

**Files:**
- Tidak ada file baru. Verifikasi menyeluruh.

**Interfaces:**
- Consumes: semua hasil Task 1-4.

- [ ] **Step 1: Jalankan `composer check`**

Run: `composer check`
Expected: exit 0 — Pint clean, semua test PASS (Unit + Feature + Arch), PHPStan level 5 tanpa error.

- [ ] **Step 2: Verifikasi boot aplikasi**

Run: `php artisan about`
Expected: exit 0, tanpa exception.

- [ ] **Step 3: Verifikasi publish config**

Run: `php artisan vendor:publish --tag=core-config --no-interaction`
Expected: `config/core.php` dibuat. Cek isinya identik dengan `core/Config/core.php`.

Lalu hapus hasil publish (agar tidak double-source di repo):

```bash
del config\core.php
```

- [ ] **Step 4: Verifikasi status git bersih**

Run: `git status`
Expected: tidak ada untracked/modified selain yang sudah di-commit. Jika ada `config/core.php` tersisa, hapus.

- [ ] **Step 5: Rangkum deliverable**

Cek file yang harus ada:

- `composer.json` — PSR-4 `Core\` => `core/`
- `core/CoreServiceProvider.php`
- `core/Config/core.php`
- `core/Exceptions/CoreException.php`
- `bootstrap/providers.php` — memuat `Core\CoreServiceProvider::class`
- `tests/Arch/CoreArchTest.php`
- `phpunit.xml` — testsuite Arch
- `phpstan.neon` — paths `core/`
- `docs/architecture/adr-008-extension-points.md`
- `docs/architecture/adr-009-service-provider-strategy.md`
- `docs/architecture/adr-010-configuration-bootstrap.md`
- `docs/conventions/directory-structure.md` — ter-update
- `docs/TODO.md` — §1.1 ter-update
- `docs/superpowers/specs/2026-08-15-application-foundation-design.md` — spec (sudah ada)

- [ ] **Step 6: Commit final (jika ada sisa perubahan)**

Jika `git status` menunjukkan perubahan yang belum di-commit:

```bash
git add -A
git commit -m "chore: finalize Application Foundation milestone"
```

Jika bersih, skip.

---

## Self-Review

**1. Spec coverage:**
- §3.1 Extension Points → Task 4 (ADR-008) ✓
- §3.2 Service Provider → Task 2, Task 4 (ADR-009) ✓
- §3.3 Config & Bootstrap → Task 2, Task 4 (ADR-010) ✓
- §4.1 composer.json PSR-4 → Task 1 ✓
- §4.2 Struktur core/ → Task 1 (CoreException), Task 2 (provider + config) ✓
- §4.3 bootstrap/providers.php → Task 1 ✓
- §4.4 Verifikasi arsitektur → Task 3 ✓
- §4.5 Update konvensi → Task 4 ✓
- §5 Dampak TODO.md → Task 4 ✓
- §7 Acceptance → Task 5 ✓

**2. Placeholder scan:** Semua step berisi kode/command konkret. Tidak ada "TBD", "implement later", atau deskripsi tanpa kode. ✓

**3. Type consistency:**
- `Core\CoreServiceProvider` konsisten (Task 1 daftar bootstrap, Task 2 class, Task 3 arch test `Core` namespace).
- `config('core.providers')` konsisten (Task 2 read, Task 4 dokumentasi).
- `Core\Exceptions\CoreException` konsisten (Task 1 class, Task 4 referensi).
- Arch test `expect('Core')->not->toUse(['App', 'Modules'])` — string `'Core'` merujuk namespace, konsisten dengan `Core\` PSR-4. ✓

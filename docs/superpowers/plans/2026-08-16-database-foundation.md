# Database Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menetapkan fondasi database Core System — konvensi database, schema Core (8 migration), konversi `users` ke UUIDv7, dan constraint — sesuai spec `docs/superpowers/specs/2026-08-16-database-foundation-design.md`.

**Architecture:** Tiga bagian: (1) dokumen (konvensi `database.md`, ADR-011, update `directory-structure.md` + TODO.md), (2) konversi `users` ke UUID + tabel package morph, (3) 8 migration Core baru di `core/Database/Migrations/` + trait `UsesUuid` + update `User` model. Semua migration Core anonymous class tanpa namespace, dimuat via `loadMigrationsFrom` di `CoreServiceProvider::boot()` (ADR-010: Core = package in-repo).

**Tech Stack:** Laravel 13, Filament 5, PHP 8.3+, `ramsey/uuid` v4.9.3, Pest (SQLite in-memory), MySQL/MariaDB/PostgreSQL (production), git.

## Global Constraints

- Bahasa dokumen: Bahasa Indonesia (konsisten dokumen existing).
- Semua model Core pakai UUID string PK (`$table->uuid('id')->primary()`), UUIDv7 via trait `Core\Support\Concerns\UsesUuid` (ramsey + HasUuids) — ADR-004 + spec §3.1.
- `users` dikonversi ke UUID; `roles`/`permissions` tetap bigint (milik Spatie); `db_config`/`white_label_settings` tidak disentuh.
- Migration Core di `core/Database/Migrations/` (anonymous class, tanpa namespace); migration yang diedit tetap di `database/migrations/`.
- onDelete policy hybrid: pivot cascade, child master cascade, parent-child restrict, referensi user nullOnDelete (spec §3.10).
- Soft delete hanya pada master (User, Organization, OrganizationalUnit, Setting) — bukan pada log.
- `settings`: satu tabel, scope nullable, unique `(key, organization_id, organizational_unit_id, user_id)`.
- `audit_logs`/`security_events` tanpa soft delete; `user_id` nullOnDelete.
- `organizational_units.type` = string + PHP backed enum `OrganizationalUnitType` (UPPER_SNAKE: HEAD_OFFICE, BRANCH, SUB_OFFICE, SITE), default `HEAD_OFFICE`.
- Migration dianggap immutable setelah rilis; pra-rilis boleh edit (proyek ini pra-rilis).
- Tidak menambah dependency baru (validasi package: tidak ada yang layak).
- `composer check` (Pint → Pest → PHPStan) adalah quality gate — wajib lolos di tiap akhir task.
- UUID di-generate via Eloquent `creating` event (HasUuids). Jangan panggil `Event::fake()` SEBELUM factory `->create()` — itu membungkam `creating` event dan menghasilkan UUID kosong (rules/testing.md:29). Pola aman: `User::factory()->create()` dulu, `Event::fake()` setelahnya.
- UUID adalah keputusan arsitektur (ADR-004: integrasi lintas instalasi standalone), BUKAN mekanisme keamanan — jangan menambahkan dokumentasi/komentar yang mengklaim UUID "mengamankan" data sensitif (tidak ada basis di rules/security.md).
- Commit message: conventional commits (`docs:`, `feat:`, `test:`), satu task = satu commit.

---

### Task 1: Konvensi Database — `docs/conventions/database.md`

**Files:**
- Create: `docs/conventions/database.md`

**Interfaces:**
- Consumes: Spec §3, §6; `docs/conventions/naming.md` (konvensi existing).
- Produces: Dokumen konvensi database yang menjadi acuan Task 2 (ADR-011) dan seluruh milestone berikutnya.

- [ ] **Step 1: Tulis dokumen database.md**

Buat file `docs/conventions/database.md` dengan isi:

```markdown
# Konvensi Database

**Status:** Accepted
**Tanggal:** 2026-08-16
**Referensi:** ADR-004, ADR-011, spec `docs/superpowers/specs/2026-08-16-database-foundation-design.md`

## Primary Key

- Semua model Core memakai UUID string sebagai primary key:
  `$table->uuid('id')->primary()` (ADR-004).
- Pivot memakai composite primary key (mis. `['organization_id', 'user_id']`).

## UUID Strategy

- Versi: **UUIDv7** (time-ordered) — index tetap kompak dan cache-friendly.
- Generator: `ramsey/uuid` + trait `HasUuids` Laravel, dibungkus trait Core
  `Core\Support\Concerns\UsesUuid` (override `newUniqueId()` → `Uuid::uuid7()`).
- Model Core memakai `UsesUuid` — bukan `HasUuids` langsung — agar versi UUID
  konsisten di seluruh Core.
- Foreign key Core mengikuti UUID (string) — konsisten sejak migration.

## Timestamps

- `$table->timestamps()` (`created_at`, `updated_at`) di semua tabel.
- Soft delete: `$table->softDeletes()` (`deleted_at`) hanya pada model master
  (User, Organization, OrganizationalUnit, Setting) — ADR-004.
- Tabel log (`audit_logs`, `security_events`) **tanpa** soft delete.

## Foreign Key

- Pakai `$table->uuid('...')->constrained()` (nama otomatis), bukan tipe lain.
- Nama kolom FK: `snake_singular_id` (mis. `organization_id`, `parent_id`).
- onDelete policy hybrid (spec §3.10):
  - Pivot (`organization_user`, `organizational_unit_user`) → `cascadeOnDelete()`.
  - Child master (`organizational_units.organization_id`) → `cascadeOnDelete()`.
  - Parent-child (`organizational_units.parent_id`) → `restrictOnDelete()`.
  - Referensi user (audit columns, audit_logs.user_id, security_events.user_id,
    users.primary_organizational_unit_id) → `nullOnDelete()`.

## Indexing

- Index kolom yang muncul di `WHERE`, `JOIN`, `ORDER BY`, `GROUP BY`.
- Composite index untuk pola query umum, mis. `(organization_id, parent_id)`.
- Unique constraint: `organizations.name`, `settings` scope unique, composite
  PK pivot.

## Audit Columns

- `created_by` / `updated_by`: uuid nullable, `constrained('users')->nullOnDelete()`.
- Dipasang **per-kebutuhan** — hanya tabel dengan ownership semantics
  (`organizations`, `organizational_units`, `settings`).
- Riwayat lengkap (actor, action, old/new) ditangani Audit System (M8).

## Immutability

- Migration dianggap **immutable** setelah rilis — perubahan schema memakai
  migration baru.
- Proyek pra-rilis boleh mengubah migration dasar (konversi users ke UUID).

## Lokasi Migration

- Migration Core di `core/Database/Migrations/` (anonymous class, tanpa
  namespace), dimuat via `loadMigrationsFrom` di `CoreServiceProvider::boot()`.
- Migration aplikasi/package tetap di `database/migrations/`.
- Uniqueness nama file migration dijaga lintas kedua folder (tabel `migrations`
  menyimpan nama file).
```

- [ ] **Step 2: Verifikasi hasil**

```bash
grep -n "UsesUuid\|loadMigrationsFrom\|nullOnDelete\|cascadeOnDelete" docs/conventions/database.md
```

Periksa: semua istilah kunci muncul.

- [ ] **Step 3: Commit**

```bash
git add docs/conventions/database.md
git commit -m "docs: add database conventions (TODO 3.1)"
```

---

### Task 2: ADR-011 — Database Foundation

**Files:**
- Create: `docs/architecture/adr-011-database-foundation.md`

**Interfaces:**
- Consumes: Spec §7, Task 1 (konvensi).
- Produces: ADR yang merekam keputusan M2; dirujuk konvensi database.md.

- [ ] **Step 1: Tulis ADR-011**

Buat file `docs/architecture/adr-011-database-foundation.md`:

```markdown
# ADR-011: Database Foundation (UUIDv7, Settings, onDelete)

**Status:** Accepted
**Tanggal:** 2026-08-16
**Referensi:** ADR-004, PRD §8/§11/§13/§27/§32, spec `docs/superpowers/specs/2026-08-16-database-foundation-design.md`

## Context

TODO.md §3 membutuhkan konvensi database, schema Core, dan constraint.
ADR-004 menetapkan UUID string untuk semua model Core tetapi belum memilih
versi UUID. PRD §27 menetapkan settings 4 scope; §11 hierarki unit;
§32 audit. Kondisi aktual: `users` masih bigint; package morph
(notifications, activitylog, Spatie) menunjuk bigint.

## Decision

1. **UUIDv7** (perluasan ADR-004): time-ordered, index kompak, mitigasi
   kekhawatiran performa ADR-004. Generator ramsey/uuid + trait Core
   `UsesUuid` (override `newUniqueId()`).
2. **Konversi `users` ke UUID** + tabel package morph (sessions,
   model_has_roles, model_has_permissions, notifications, activity_log)
   ikut disesuaikan. `roles`/`permissions` tetap bigint (milik package).
3. **Single `settings` table** dengan scope nullable
   (`organization_id`, `organizational_unit_id`, `user_id`); unique
   `(key, org_id, unit_id, user_id)` — NULL distinct di MySQL, 4 scope aman.
4. **Hybrid onDelete**: pivot/child master cascade, parent-child restrict,
   referensi user nullOnDelete.
5. **Adjacency list + recursive CTE** untuk hierarki unit — tanpa dependency
   baru (validasi package: tidak ada yang sehat & kompatibel Laravel 13).
   Validasi hierarki (cycle, parent ≠ self, se-organization, depth) di
   application layer (M3).
6. **`audit_logs`/`security_events` dibuat di M2**; storage final Audit
   System (spatie activitylog vs tabel ini) diputuskan di M8.

## Consequences

- Primary key tidak enumerable, aman integrasi lintas instalasi.
- Storage UUID lebih besar; index time-ordered memitigasi fragmentasi.
- FK Core semua UUID — konsisten sejak migration.
- Migration package diedit karena pra-rilis; setelah rilis immutable.
- Validasi hierarki/assignment di aplikasi, bukan DB trigger.
```

- [ ] **Step 2: Verifikasi**

```bash
grep -n "UUIDv7\|settings\|onDelete\|adjacency" docs/architecture/adr-011-database-foundation.md
```

Periksa: semua keputusan muncul.

- [ ] **Step 3: Commit**

```bash
git add docs/architecture/adr-011-database-foundation.md
git commit -m "docs: add ADR-011 database foundation (TODO 3)"
```

---

### Task 3: Trait `Core\Support\Concerns\UsesUuid`

**Files:**
- Create: `core/Support/Concerns/UsesUuid.php`

**Interfaces:**
- Consumes: `ramsey/uuid` v4.9.3 (terpasang).
- Produces: Trait `Core\Support\Concerns\UsesUuid` dengan `newUniqueId(): string` + `uniqueIds(): array` — dipakai Task 4 (User model) dan model Core M3+.

- [ ] **Step 1: Tulis failing test**

Buat `tests/Unit/Support/UsesUuidTest.php`:

```php
<?php

namespace Tests\Unit\Support;

use Core\Support\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class UsesUuidStub extends Model
{
    use UsesUuid;
}

class UsesUuidTest extends TestCase
{
    public function test_generates_uuid_v7(): void
    {
        $model = new UsesUuidStub;
        $id = $model->newUniqueId();

        $this->assertTrue(Uuid::isValid($id));
        $this->assertSame(7, Uuid::fromString($id)->getVersion());
    }

    public function test_unique_ids_uses_id_column(): void
    {
        $this->assertSame(['id'], (new UsesUuidStub)->uniqueIds());
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor/bin/pest tests/Unit/Support/UsesUuidTest.php`
Expected: FAIL — `Class "Core\Support\Concerns\UsesUuid" not found`.

- [ ] **Step 3: Tulis implementasi**

Buat `core/Support/Concerns/UsesUuid.php`:

```php
<?php

namespace Core\Support\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Ramsey\Uuid\Uuid;

trait UsesUuid
{
    use HasUuids;

    public function newUniqueId(): string
    {
        return Uuid::uuid7()->toString();
    }

    public function uniqueIds(): array
    {
        return ['id'];
    }
}
```

- [ ] **Step 4: Run test — pastikan pass**

Run: `vendor/bin/pest tests/Unit/Support/UsesUuidTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add core/Support/Concerns/UsesUuid.php tests/Unit/Support/UsesUuidTest.php
git commit -m "feat: add UsesUuid trait with UUIDv7 (TODO 3)"
```

---

### Task 4: Konversi `users` ke UUID

**Files:**
- Modify: `database/migrations/0001_01_01_000000_create_users_table.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/Database/UsersUuidTest.php`

**Interfaces:**
- Consumes: Task 3 (`UsesUuid`), ADR-004.
- Produces: `users` ber-PK UUID + `primary_organizational_unit_id` kolom (tanpa FK), `softDeletes`; `User` model pakai `UsesUuid` + `SoftDeletes`. Jadi acuan Task 5 (sessions).

- [ ] **Step 1: Tulis failing test**

Buat `tests/Feature/Database/UsersUuidTest.php`:

```php
<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class UsersUuidTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_id_is_uuid_v7(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(Uuid::isValid($user->id));
        $this->assertSame(7, Uuid::fromString($user->id)->getVersion());
    }

    public function test_users_table_has_primary_unit_column(): void
    {
        $this->assertTrue(\Schema::hasColumn('users', 'primary_organizational_unit_id'));
    }

    public function test_users_table_has_soft_deletes(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $this->assertSoftDeleted($user);
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor/bin/pest tests/Feature/Database/UsersUuidTest.php`
Expected: FAIL — `users` masih bigint; `primary_organizational_unit_id` belum ada; `SoftDeletes` belum di model.

- [ ] **Step 3: Update migration users**

Di `database/migrations/0001_01_01_000000_create_users_table.php`, ganti blok `Schema::create('users', ...)`:

```php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->uuid('primary_organizational_unit_id')->nullable();
    $table->softDeletes();
    $table->timestamps();
});
```

Perhatikan: `primary_organizational_unit_id` **tanpa** FK di sini (FK ditambahkan Task 9, setelah `organizational_units` ada).

- [ ] **Step 4: Update `app/Models/User.php`**

Tambah use traits:

```php
use Core\Support\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\SoftDeletes;
```

dan di body class:

```php
use HasFactory, UsesUuid, SoftDeletes;
```

- [ ] **Step 5: Run test — pastikan pass**

Run: `vendor/bin/pest tests/Feature/Database/UsersUuidTest.php`
Expected: PASS (3 tests).

- [ ] **Step 6: Run quality gate**

Run: `composer check`
Expected: Pint, Pest, PHPStan semua lolos.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/0001_01_01_000000_create_users_table.php app/Models/User.php tests/Feature/Database/UsersUuidTest.php
git commit -m "feat: convert users to UUID primary key (TODO 3.2)"
```

---

### Task 5: Konversi `sessions.user_id`

**Files:**
- Modify: `database/migrations/0001_01_01_000000_create_users_table.php` (blok sessions)
- Test: `tests/Feature/Database/SessionsUuidTest.php`

**Interfaces:**
- Consumes: Task 4 (`users` PK uuid).
- Produces: `sessions.user_id` uuid, FK → `users`, index.

- [ ] **Step 1: Tulis failing test**

Buat `tests/Feature/Database/SessionsUuidTest.php`:

```php
<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SessionsUuidTest extends TestCase
{
    use RefreshDatabase;

    public function test_sessions_user_id_is_uuid(): void
    {
        $columns = Schema::getColumnListing('sessions');

        $this->assertContains('user_id', $columns);
    }

    public function test_sessions_user_id_has_foreign_key_to_users(): void
    {
        $foreignKeys = Schema::getForeignKeys('sessions');
        $usersFk = collect($foreignKeys)->first(fn ($fk) => in_array('user_id', $fk['columns']));

        $this->assertNotNull($usersFk);
        $this->assertSame('users', $usersFk['foreign_table']);
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor/bin/pest tests/Feature/Database/SessionsUuidTest.php`
Expected: FAIL — FK masih `foreignId` bigint.

- [ ] **Step 3: Update blok sessions**

Di `0001_01_01_000000_create_users_table.php`, ganti:

```php
$table->foreignId('user_id')->nullable()->index();
```

menjadi:

```php
$table->uuid('user_id')->nullable();
$table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
$table->index('user_id');
```

- [ ] **Step 4: Run test — pastikan pass**

Run: `vendor/bin/pest tests/Feature/Database/SessionsUuidTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add database/migrations/0001_01_01_000000_create_users_table.php tests/Feature/Database/SessionsUuidTest.php
git commit -m "feat: convert sessions.user_id to UUID (TODO 3.2)"
```

---

### Task 6: Konversi morph package — Spatie, notifications, activity_log

**Files:**
- Modify: `database/migrations/2026_04_01_152618_create_permission_tables.php` (model_id → uuid)
- Modify: `database/migrations/2026_08_15_020711_create_notifications_table.php` (notifiable_id → uuid)
- Modify: `database/migrations/2026_04_01_153839_create_activity_log_table.php` (subject_id, causer_id → uuid)
- Test: `tests/Feature/Database/PackageMorphUuidTest.php`

**Interfaces:**
- Consumes: Task 4 (`users` PK uuid).
- Produces: Semua morph ke model UUID memakai uuid; `roles`/`permissions` tetap bigint.

- [ ] **Step 1: Tulis failing test**

Buat `tests/Feature/Database/PackageMorphUuidTest.php`:

```php
<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PackageMorphUuidTest extends TestCase
{
    use RefreshDatabase;

    public function test_spatie_model_id_is_uuid(): void
    {
        $this->assertSame('string', Schema::getColumnType('model_has_roles', 'model_id'));
        $this->assertSame('string', Schema::getColumnType('model_has_permissions', 'model_id'));
    }

    public function test_notifications_notifiable_id_is_uuid(): void
    {
        $this->assertSame('string', Schema::getColumnType('notifications', 'notifiable_id'));
    }

    public function test_activity_log_morphs_are_uuid(): void
    {
        $this->assertSame('string', Schema::getColumnType('activity_log', 'subject_id'));
        $this->assertSame('string', Schema::getColumnType('activity_log', 'causer_id'));
    }

    public function test_roles_and_permissions_stay_bigint(): void
    {
        $this->assertSame('int', Schema::getColumnType('roles', 'id'));
        $this->assertSame('int', Schema::getColumnType('permissions', 'id'));
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor/bin/pest tests/Feature/Database/PackageMorphUuidTest.php`
Expected: FAIL — semua morph masih bigint.

- [ ] **Step 3: Update Spatie permission migration**

Di `2026_04_01_152618_create_permission_tables.php`, di blok `model_has_permissions` dan `model_has_roles`, ganti:

```php
$table->unsignedBigInteger($columnNames['model_morph_key']);
```

menjadi:

```php
$table->uuid($columnNames['model_morph_key']);
```

(`model_morph_key` = `model_id`; `roles`/`permissions` dan pivot `role_has_permissions` tidak berubah.)

- [ ] **Step 4: Update notifications migration**

Di `2026_08_15_020711_create_notifications_table.php`, ganti:

```php
$table->morphs('notifiable');
```

menjadi:

```php
$table->uuidMorphs('notifiable');
```

- [ ] **Step 5: Update activity_log migration**

Di `2026_04_01_153839_create_activity_log_table.php`, ganti:

```php
$table->nullableMorphs('subject', 'subject');
$table->nullableMorphs('causer', 'causer');
```

menjadi:

```php
$table->uuidMorphs('subject', 'subject');
$table->uuidMorphs('causer', 'causer');
```

- [ ] **Step 6: Run test — pastikan pass**

Run: `vendor/bin/pest tests/Feature/Database/PackageMorphUuidTest.php`
Expected: PASS (4 tests).

- [ ] **Step 7: Run quality gate**

Run: `composer check`
Expected: lolos.

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_04_01_152618_create_permission_tables.php database/migrations/2026_08_15_020711_create_notifications_table.php database/migrations/2026_04_01_153839_create_activity_log_table.php tests/Feature/Database/PackageMorphUuidTest.php
git commit -m "feat: convert package morphs to UUID (TODO 3.2)"
```

---

### Task 7: Migration Core — organizations & organizational_units

**Files:**
- Create: `core/Database/Migrations/2026_08_16_000001_create_organizations_table.php`
- Create: `core/Database/Migrations/2026_08_16_000002_create_organizational_units_table.php`
- Modify: `core/CoreServiceProvider.php` (tambah `loadMigrationsFrom` di boot)
- Test: `tests/Feature/Database/OrganizationSchemaTest.php`

**Interfaces:**
- Consumes: Task 4 (`users` uuid), spec §4.2–4.3.
- Produces: Tabel `organizations` dan `organizational_units` dengan FK & index; `CoreServiceProvider` memuat migration Core. Jadi acuan Task 8 (pivot) dan Task 9 (FK primary unit).

- [ ] **Step 1: Tulis failing test**

Buat `tests/Feature/Database/OrganizationSchemaTest.php`:

```php
<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrganizationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizations_table_created(): void
    {
        $this->assertTrue(Schema::hasTable('organizations'));
        $this->assertSame('string', Schema::getColumnType('organizations', 'id'));
        $this->assertTrue(Schema::hasColumns('organizations', ['name', 'created_by', 'updated_by', 'deleted_at']));
    }

    public function test_organizational_units_table_created(): void
    {
        $this->assertTrue(Schema::hasTable('organizational_units'));
        $this->assertSame('string', Schema::getColumnType('organizational_units', 'id'));
        $this->assertTrue(Schema::hasColumns('organizational_units', ['organization_id', 'parent_id', 'name', 'type', 'deleted_at']));
    }

    public function test_organizations_name_is_unique(): void
    {
        $unique = collect(Schema::getIndexes('organizations'))->first(fn ($i) => $i['unique'] && in_array('name', $i['columns']));
        $this->assertNotNull($unique);
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor/bin/pest tests/Feature/Database/OrganizationSchemaTest.php`
Expected: FAIL — tabel belum ada.

- [ ] **Step 3: Update CoreServiceProvider boot**

Di `core/CoreServiceProvider.php`, tambahkan `loadMigrationsFrom` di method `boot()`:

```php
public function boot(): void
{
    $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

    $this->publishes([
        __DIR__.'/Config/core.php' => config_path('core.php'),
    ], 'core-config');
}
```

- [ ] **Step 4: Buat migration organizations**

Buat `core/Database/Migrations/2026_08_16_000001_create_organizations_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->uuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
```

- [ ] **Step 5: Buat migration organizational_units**

Buat `core/Database/Migrations/2026_08_16_000002_create_organizational_units_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizational_units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('parent_id')->nullable()->constrained('organizational_units')->restrictOnDelete();
            $table->string('name');
            $table->string('type')->default('HEAD_OFFICE');
            $table->uuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['organization_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizational_units');
    }
};
```

- [ ] **Step 6: Run test — pastikan pass**

Run: `vendor/bin/pest tests/Feature/Database/OrganizationSchemaTest.php`
Expected: PASS (3 tests).

- [ ] **Step 7: Run quality gate**

Run: `composer check`
Expected: lolos.

- [ ] **Step 8: Commit**

```bash
git add core/Database/Migrations/2026_08_16_000001_create_organizations_table.php core/Database/Migrations/2026_08_16_000002_create_organizational_units_table.php core/CoreServiceProvider.php tests/Feature/Database/OrganizationSchemaTest.php
git commit -m "feat: add organizations and organizational_units tables (TODO 3.2)"
```

---

### Task 8: Migration Core — pivot tables

**Files:**
- Create: `core/Database/Migrations/2026_08_16_000003_create_organizational_unit_user_table.php`
- Create: `core/Database/Migrations/2026_08_16_000004_create_organization_user_table.php`
- Test: `tests/Feature/Database/PivotSchemaTest.php`

**Interfaces:**
- Consumes: Task 7 (`organizations`, `organizational_units`).
- Produces: Pivot `organizational_unit_user` & `organization_user` dengan composite PK + FK cascade. Jadi acuan Task 10 (settings FK org/unit/user).

- [ ] **Step 1: Tulis failing test**

Buat `tests/Feature/Database/PivotSchemaTest.php`:

```php
<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PivotSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizational_unit_user_table(): void
    {
        $this->assertTrue(Schema::hasTable('organizational_unit_user'));
        $this->assertTrue(Schema::hasColumns('organizational_unit_user', ['organizational_unit_id', 'user_id']));
    }

    public function test_organization_user_table(): void
    {
        $this->assertTrue(Schema::hasTable('organization_user'));
        $this->assertTrue(Schema::hasColumns('organization_user', ['organization_id', 'user_id']));
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor/bin/pest tests/Feature/Database/PivotSchemaTest.php`
Expected: FAIL — tabel belum ada.

- [ ] **Step 3: Buat migration organizational_unit_user**

Buat `core/Database/Migrations/2026_08_16_000003_create_organizational_unit_user_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizational_unit_user', function (Blueprint $table) {
            $table->uuid('organizational_unit_id')->constrained()->cascadeOnDelete();
            $table->uuid('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['organizational_unit_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizational_unit_user');
    }
};
```

- [ ] **Step 4: Buat migration organization_user**

Buat `core/Database/Migrations/2026_08_16_000004_create_organization_user_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_user', function (Blueprint $table) {
            $table->uuid('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['organization_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_user');
    }
};
```

- [ ] **Step 5: Run test — pastikan pass**

Run: `vendor/bin/pest tests/Feature/Database/PivotSchemaTest.php`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add core/Database/Migrations/2026_08_16_000003_create_organizational_unit_user_table.php core/Database/Migrations/2026_08_16_000004_create_organization_user_table.php tests/Feature/Database/PivotSchemaTest.php
git commit -m "feat: add organization and unit pivot tables (TODO 3.2)"
```

---

### Task 9: Migration Core — FK primary unit

**Files:**
- Create: `core/Database/Migrations/2026_08_16_000005_add_primary_unit_foreign_key_to_users_table.php`
- Test: `tests/Feature/Database/PrimaryUnitFkTest.php`

**Interfaces:**
- Consumes: Task 7 (`organizational_units`), Task 4 (kolom `users.primary_organizational_unit_id` tanpa FK).
- Produces: FK `users.primary_organizational_unit_id` → `organizational_units` nullOnDelete.

- [ ] **Step 1: Tulis failing test**

Buat `tests/Feature/Database/PrimaryUnitFkTest.php`:

```php
<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PrimaryUnitFkTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_unit_foreign_key_exists(): void
    {
        $foreignKeys = Schema::getForeignKeys('users');
        $primaryFk = collect($foreignKeys)->first(fn ($fk) => in_array('primary_organizational_unit_id', $fk['columns']));

        $this->assertNotNull($primaryFk);
        $this->assertSame('organizational_units', $primaryFk['foreign_table']);
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor/bin/pest tests/Feature/Database/PrimaryUnitFkTest.php`
Expected: FAIL — FK belum ada.

- [ ] **Step 3: Buat migration**

Buat `core/Database/Migrations/2026_08_16_000005_add_primary_unit_foreign_key_to_users_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('primary_organizational_unit_id')
                ->references('id')
                ->on('organizational_units')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['primary_organizational_unit_id']);
        });
    }
};
```

- [ ] **Step 4: Run test — pastikan pass**

Run: `vendor/bin/pest tests/Feature/Database/PrimaryUnitFkTest.php`
Expected: PASS (1 test).

- [ ] **Step 5: Commit**

```bash
git add core/Database/Migrations/2026_08_16_000005_add_primary_unit_foreign_key_to_users_table.php tests/Feature/Database/PrimaryUnitFkTest.php
git commit -m "feat: add primary unit FK to users (TODO 3.2)"
```

---

### Task 10: Migration Core — settings

**Files:**
- Create: `core/Database/Migrations/2026_08_16_000006_create_settings_table.php`
- Test: `tests/Feature/Database/SettingsSchemaTest.php`

**Interfaces:**
- Consumes: Task 7 (organizations, units), Task 4 (users).
- Produces: Tabel `settings` dengan scope nullable + unique scope.

- [ ] **Step 1: Tulis failing test**

Buat `tests/Feature/Database/SettingsSchemaTest.php`:

```php
<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SettingsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_table_created(): void
    {
        $this->assertTrue(Schema::hasTable('settings'));
        $this->assertTrue(Schema::hasColumns('settings', ['key', 'value', 'organization_id', 'organizational_unit_id', 'user_id', 'created_by', 'updated_by']));
    }

    public function test_scope_unique_constraint(): void
    {
        DB::table('settings')->insert(['key' => 'app.name', 'value' => json_encode('A'), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('settings')->insert(['key' => 'app.name', 'value' => json_encode('B'), 'created_at' => now(), 'updated_at' => now()]);
        $this->assertTrue(true); // scope berbeda (System, org, unit, user) boleh
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor/bin/pest tests/Feature/Database/SettingsSchemaTest.php`
Expected: FAIL — tabel belum ada.

- [ ] **Step 3: Buat migration settings**

Buat `core/Database/Migrations/2026_08_16_000006_create_settings_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key');
            $table->json('value');
            $table->uuid('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->uuid('organizational_unit_id')->nullable()->constrained()->cascadeOnDelete();
            $table->uuid('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->uuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['key', 'organization_id', 'organizational_unit_id', 'user_id'], 'settings_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
```

- [ ] **Step 4: Run test — pastikan pass**

Run: `vendor/bin/pest tests/Feature/Database/SettingsSchemaTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add core/Database/Migrations/2026_08_16_000006_create_settings_table.php tests/Feature/Database/SettingsSchemaTest.php
git commit -m "feat: add settings table with scope unique (TODO 3.2)"
```

---

### Task 11: Migration Core — audit_logs & security_events

**Files:**
- Create: `core/Database/Migrations/2026_08_16_000007_create_audit_logs_table.php`
- Create: `core/Database/Migrations/2026_08_16_000008_create_security_events_table.php`
- Test: `tests/Feature/Database/LogSchemaTest.php`

**Interfaces:**
- Consumes: Task 7 (`organizations`), Task 4 (`users`).
- Produces: Tabel `audit_logs` & `security_events` (tanpa soft delete, `user_id` nullOnDelete, index).

- [ ] **Step 1: Tulis failing test**

Buat `tests/Feature/Database/LogSchemaTest.php`:

```php
<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LogSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logs_table(): void
    {
        $this->assertTrue(Schema::hasTable('audit_logs'));
        $this->assertTrue(Schema::hasColumns('audit_logs', ['organization_id', 'user_id', 'action', 'subject_type', 'subject_id', 'ip_address', 'metadata', 'occurred_at']));
        $this->assertFalse(Schema::hasColumn('audit_logs', 'deleted_at'));
    }

    public function test_security_events_table(): void
    {
        $this->assertTrue(Schema::hasTable('security_events'));
        $this->assertTrue(Schema::hasColumns('security_events', ['event', 'user_id', 'ip_address', 'user_agent', 'metadata', 'occurred_at']));
        $this->assertFalse(Schema::hasColumn('security_events', 'deleted_at'));
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor/bin/pest tests/Feature/Database/LogSchemaTest.php`
Expected: FAIL — tabel belum ada.

- [ ] **Step 3: Buat migration audit_logs**

Buat `core/Database/Migrations/2026_08_16_000007_create_audit_logs_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->uuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type');
            $table->uuid('subject_id')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');

            $table->index(['subject_type', 'subject_id']);
            $table->index(['action', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
```

- [ ] **Step 4: Buat migration security_events**

Buat `core/Database/Migrations/2026_08_16_000008_create_security_events_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event');
            $table->uuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');

            $table->index(['user_id', 'occurred_at']);
            $table->index(['event', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
```

- [ ] **Step 5: Run test — pastikan pass**

Run: `vendor/bin/pest tests/Feature/Database/LogSchemaTest.php`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add core/Database/Migrations/2026_08_16_000007_create_audit_logs_table.php core/Database/Migrations/2026_08_16_000008_create_security_events_table.php tests/Feature/Database/LogSchemaTest.php
git commit -m "feat: add audit_logs and security_events tables (TODO 3.2)"
```

---

### Task 12: Update `directory-structure.md` & TODO.md

**Files:**
- Modify: `docs/conventions/directory-structure.md`
- Modify: `docs/TODO.md`

**Interfaces:**
- Consumes: Task 1, Task 2, seluruh migration.
- Produces: Konvensi & roadmap konsisten dengan keputusan final.

- [ ] **Step 1: Update directory-structure.md**

Di `docs/conventions/directory-structure.md`, bagian "Konvensi Folder → Database", ganti blok:

```markdown
### Database

- Artefak database milik Core di `core/Database/` (Migrations/, Factories/,
  Seeders/), dimuat via `loadMigrationsFrom` di `CoreServiceProvider`.
- Artefak database aplikasi tetap di `database/` root aplikasi (default Laravel).
```

menjadi:

```markdown
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
```

- [ ] **Step 2: Update TODO.md**

Di `docs/TODO.md`, centang checklist §3.1 (8 item), §3.2 (7 item), §3.3 (5 item):

- §3.1: semua jadi `- [x]` dengan referensi `docs/conventions/database.md`.
- §3.2: semua jadi `- [x]` dengan referensi migration `core/Database/Migrations/`.
- §3.3: semua jadi `- [x]` dengan referensi `core/Database/Migrations/` + validasi aplikasi (M3) untuk hierarki/assignment.

Contoh format (sesuaikan per item):

```markdown
- [x] Define primary key strategy — `docs/conventions/database.md` (ADR-004)
- [x] Design `organizations` — `core/Database/Migrations/2026_08_16_000001_create_organizations_table.php`
```

- [ ] **Step 3: Verifikasi**

```bash
grep -n "core/Database" docs/conventions/directory-structure.md docs/TODO.md
```

Periksa: referensi konsisten.

- [ ] **Step 4: Commit**

```bash
git add docs/conventions/directory-structure.md docs/TODO.md
git commit -m "docs: update directory structure and TODO for database foundation (TODO 3)"
```

---

### Task 13: Final verification

**Files:**
- Test: seluruh `tests/Feature/Database/` + `tests/Unit/Support/UsesUuidTest.php`

**Interfaces:**
- Consumes: seluruh task.
- Produces: Verifikasi akhir bahwa milestone selesai & quality gate lolos.

- [ ] **Step 1: Fresh migrate + status**

```bash
php artisan migrate:fresh --seed
php artisan migrate:status
```

Expected: semua migration (termasuk `core/Database/Migrations/*`) tampil "Ran".

- [ ] **Step 2: Full test suite**

```bash
composer check
```

Expected: Pint, Pest (termasuk arch test), PHPStan semua lolos.

- [ ] **Step 3: Verifikasi arch test tidak rusak**

```bash
vendor/bin/pest tests/Arch/CoreArchTest.php
```

Expected: PASS — `Core\` tidak impor `App\`/`Modules\`/Filament (non-UI); `UsesUuid` hanya pakai Laravel + Ramsey.

- [ ] **Step 4: No uncommitted changes**

```bash
git status --short
```

Expected: clean (semua task sudah di-commit).

---

## Self-Review

**Spec coverage:**
- §3.1 UUIDv7 → Task 3 (trait) + Task 4 (users).
- §3.2 users konversi → Task 4.
- §3.3 package morph → Task 5 (sessions) + Task 6 (Spatie/notifications/activity_log).
- §3.4–3.5 relasi & primary unit → Task 8 (pivot) + Task 9 (FK).
- §3.6 hierarki adjacency → Task 7 (parent_id + index); validasi aplikasi = M3 (non-goal).
- §3.7 audit columns → Task 7 (created_by/updated_by di org & unit) + Task 10 (settings).
- §3.8 settings → Task 10.
- §3.9 audit/security → Task 11.
- §3.10 onDelete → tersebar di Task 7, 8, 9, 10, 11.
- §3.11 type enum → Task 7 (`string('type')->default('HEAD_OFFICE')`); enum PHP dibuat di M3 (YAGNI — model belum ada di M2).
- §6 konvensi → Task 1.
- §7 ADR-011 → Task 2.
- §8.1 directory-structure → Task 12.
- §8.2 TODO → Task 12.
- §10 verifikasi → Task 13 + tiap task.

**Placeholder scan:** Tidak ada TBD/TODO/placeholder — setiap step punya kode & command lengkap.

**Type consistency:** `UsesUuid::newUniqueId(): string`, `uniqueIds(): array` konsisten di Task 3 & dipakai Task 4. Nama kolom (`primary_organizational_unit_id`, `organization_id`, `parent_id`, dll.) konsisten lintas Task 7–11. Nama tabel (`organizations`, `organizational_units`, pivot, `settings`, `audit_logs`, `security_events`) konsisten dengan spec.

**Catatan (bukan placeholder):** Enum `OrganizationalUnitType` dan model `Organization`/`OrganizationalUnit` sengaja TIDAK dibuat di M2 (YAGNI — lahir di M3 bersama model/action/service; spec §9 non-goals).

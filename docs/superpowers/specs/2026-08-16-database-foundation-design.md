# Design — Database Foundation (TODO 3)

**Tanggal:** 2026-08-16
**Status:** Draft (menunggu review)
**Sumber:** `docs/TODO.md` §3, `docs/PRD.md`, ADR-004, ADR-005, ADR-009, ADR-010
**Metode:** Brainstorming (sesi 2026-08-16)

## 1. Ringkasan

Milestone "Database Foundation" (TODO.md §3) menetapkan fondasi database
Core System: konvensi database, schema Core nyata, konversi `users` ke UUID,
dan constraint. Milestone ini menjadi prasyarat M3 (Organization + Unit).

Deliverable:

1. **Konvensi database** — `docs/conventions/database.md` (baru), menjawab
   TODO §3.1.
2. **ADR-011** — `docs/architecture/adr-011-database-foundation.md` (baru),
   merekam keputusan M2 yang belum punya ADR.
3. **Schema Core** — 8 migration baru di `core/Database/Migrations/`,
   menjawab TODO §3.2 dan §3.3.
4. **Konversi `users` ke UUID** — edit migration dasar + migration package
   yang ber-relasi, plus update `app/Models/User.php`.
5. **Update `directory-structure.md`** — merinci `core/Database/` (Migrations,
   Factories, Seeders) sesuai keputusan.
6. **Update TODO.md** — checklist §3.1–3.3.

## 2. Konteks

- ADR-004 menetapkan semua model Core memakai UUID string sebagai primary key,
  soft delete pada model master, FK mengikuti UUID — implementasinya di
  milestone ini (M3 di ADR-004 = M2 di TODO.md).
- Kondisi aktual: `users` masih `$table->id()` (bigint). Spatie permission
  (`roles`/`permissions`) memakai id bigint (milik package, bukan model Core).
  `notifications` sudah `uuid('id')` tapi `notifiable_id` morph masih bigint.
  `activity_log` (spatie) memakai bigint morph. Ada tabel package yang akan
  dihapus di milestone berikutnya (`db_config` M7, `white_label_settings` M7,
  `filament_logger` M8) — tidak disentuh di M2.
- `ramsey/uuid` v4.9.3 terpasang (ADR-004).
- Konvensi existing: tabel snake_case jamak, pivot gabungan nama tabel urut
  alfabetis, kolom FK `snake_singular_id`, timestamps standar, audit kolom
  `created_by`/`updated_by` "bila diperlukan" (naming.md).
- Directory structure accepted menetapkan `core/Database/{Migrations,Factories,Seeders}/`
  dimuat via `loadMigrationsFrom` (konsisten ADR-009/010: Core = package in-repo).

## 3. Keputusan Arsitektur

### 3.1 UUIDv7 (perluasan ADR-004)

- Generator: `ramsey/uuid` (sudah terpasang) + trait `HasUuids` Laravel.
- Versi: **UUIDv7** (time-ordered) — index tetap kompak dan cache-friendly,
  memitigasi kekhawatiran performa index di ADR-004 §Consequences.
- Implementasi: trait Core `Core\Support\Concerns\UsesUuid` yang memakai
  `HasUuids` dan meng-override `newUniqueId()`:

```php
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

- Model Core memakai `UsesUuid` (bukan `HasUuids` langsung) agar versi UUID
  konsisten di seluruh Core.

### 3.2 Konversi `users` ke UUID

`database/migrations/0001_01_01_000000_create_users_table.php`:

- `$table->id()` → `$table->uuid('id')->primary()`.
- Tambah `$table->softDeletes()` (ADR-004: User = master).
- Kolom `primary_organizational_unit_id` (uuid, nullable) **tanpa FK** di
  migration dasar; FK ditambahkan migration terpisah setelah
  `organizational_units` ada (ordering).

`app/Models/User.php`:

- Tambah `use HasFactory, UsesUuid, SoftDeletes` (dari trait Core).
- Wajib untuk konversi — tanpa ini Eloquent tetap menganggap PK
  auto-increment.

### 3.3 Tabel package yang ikut disesuaikan (morph → uuid)

| Tabel | Kolom | Perubahan |
|---|---|---|
| `sessions` | `user_id` | `foreignId()` → `uuid()->nullable()->index()`, FK → `users` |
| `model_has_roles`, `model_has_permissions` (Spatie) | `model_id` (morph) | `unsignedBigInteger` → `uuid` |
| `notifications` | `notifiable_id` (morph) | `unsignedBigInteger` → `uuid` |
| `activity_log` (spatie) | `subject_id`, `causer_id` (morph) | `unsignedBigInteger` → `uuid` |

- `roles`/`permissions` sendiri **tetap bigint** — milik package Spatie,
  bukan model Core.
- `db_config`, `white_label_settings` **tidak disentuh** (package yang akan
  dihapus M7/M8).
- Catatan: migration package diedit karena proyek **pra-rilis** (belum ada
  data produksi; `migrate:fresh` aman). Setelah rilis, migration dianggap
  immutable (best-practices).

### 3.4 Relasi Organization–User

- Pivot `organization_user` (many-to-many) + `organizational_unit_user`.
- **Tanpa** kolom `organization_id` di `users` (PRD §8 default single
  organization; §13 user tidak punya single organization_unit_id sebagai
  sumber authorization).

### 3.5 Primary Organizational Unit

- Kolom `users.primary_organizational_unit_id` (uuid, nullable, FK →
  `organizational_units` nullOnDelete).
- Satu sumber kebenaran untuk default context (PRD §14), bukan sumber
  authorization (PRD §13 tidak dilanggar).
- Validasi aplikasi (M3): harus salah satu unit yang di-assign.

### 3.6 Hierarki Organizational Unit

- **Adjacency list** (`parent_id` nullable + FK restrictOnDelete) +
  recursive CTE untuk tree navigation (PRD §11).
- Validasi package (LaraPlugins): tidak ada package hierarki yang sehat dan
  kompatibel Laravel 13 → **tanpa dependency baru**.
- Validasi hierarki (cycle, parent ≠ self, parent se-organization, depth)
  ditegakkan di application layer (M3) — DB hanya menjamin referensial
  integrity via FK + index.

### 3.7 Kolom audit (`created_by`/`updated_by`)

- **Per-kebutuhan** (discretionary): hanya tabel dengan ownership semantics
  (`organizations`, `organizational_units`, `settings`).
- Riwayat lengkap (actor, action, old/new) ditangani Audit System M8
  (spatie activitylog sebagai storage teknis, ADR-007).
- Tipe uuid nullable, `nullOnDelete` (referensi user soft-deletable).

### 3.8 Settings

- **Satu tabel** `settings` dengan kolom scope nullable
  (`organization_id`, `organizational_unit_id`, `user_id`).
- Scope: System = ketiganya null; Organization/Unit/User = kolom terkait isi.
- Unique scope `(key, organization_id, organizational_unit_id, user_id)` —
  MySQL memperlakukan NULL sebagai distinct, sehingga 4 scope aman di satu
  tabel.
- `value` bertipe json.
- `settings` **tidak** di-soft-delete (bukan master).

### 3.9 Audit & Security Events

- `audit_logs` dan `security_events` **dibuat di M2** (keputusan sesi).
- M8 memilih storage final Audit System: spatie `activity_log` (sudah
  terpasang) **atau** tabel `audit_logs` ini.
- `user_id` (actor) → `nullOnDelete` (jejak tetap ada walau user
  force-deleted).
- Tanpa soft delete (log).

### 3.10 onDelete policy (hybrid pragmatis)

| FK | onDelete |
|---|---|
| `organizational_units.organization_id` → organizations | `cascade` |
| `organizational_units.parent_id` → organizational_units | `restrict` |
| `organizational_unit_user.*` → units/users | `cascade` |
| `organization_user.*` → orgs/users | `cascade` |
| `users.primary_organizational_unit_id` → units | `nullOnDelete` |
| `settings.organization_id` / `organizational_unit_id` / `user_id` | `cascade` |
| `audit_logs.organization_id` | `cascade` |
| `audit_logs.user_id` / `security_events.user_id` | `nullOnDelete` |
| `sessions.user_id` | `cascade` (default Laravel) |

### 3.11 `organizational_units.type` (PRD §12)

- Kolom `string('type')` (bukan DB enum) + PHP backed enum
  `Core\Organization\Enums\OrganizationalUnitType` (case `UPPER_SNAKE`:
  `HEAD_OFFICE`, `BRANCH`, `SUB_OFFICE`, `SITE`).
- Extensible dari application layer tanpa alter schema; portabel lintas
  driver (SQLite tidak punya enum).
- Default `HEAD_OFFICE`.

## 4. Schema (Migration)

### 4.1 Lokasi & urutan

Migration Core baru di `core/Database/Migrations/` (anonymous class, tanpa
namespace, dimuat via `loadMigrationsFrom` di `CoreServiceProvider::boot()`).
Migration yang diedit tetap di `database/migrations/`.

Urutan eksekusi (urutan nama file):

```text
database/migrations/0001_01_01_000000_create_users_table.php   ← users uuid + softDeletes
database/migrations/... (sessions, Spatie, notifications, activity_log — diedit)
core/Database/Migrations/2026_08_16_000001_create_organizations_table.php
core/Database/Migrations/2026_08_16_000002_create_organizational_units_table.php
core/Database/Migrations/2026_08_16_000003_create_organizational_unit_user_table.php
core/Database/Migrations/2026_08_16_000004_create_organization_user_table.php
core/Database/Migrations/2026_08_16_000005_add_primary_unit_foreign_key_to_users_table.php
core/Database/Migrations/2026_08_16_000006_create_settings_table.php
core/Database/Migrations/2026_08_16_000007_create_audit_logs_table.php
core/Database/Migrations/2026_08_16_000008_create_security_events_table.php
```

### 4.2 `organizations`

```php
Schema::create('organizations', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name')->unique();
    $table->uuid('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->uuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->softDeletes();
    $table->timestamps();
});
```

### 4.3 `organizational_units`

```php
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
```

- Tanpa unique DB pada `name` (duplikat antar cabang sah; validasi aplikasi M3).

### 4.4 Pivot

```php
// organizational_unit_user
$table->uuid('organizational_unit_id')->constrained()->cascadeOnDelete();
$table->uuid('user_id')->constrained()->cascadeOnDelete();
$table->primary(['organizational_unit_id', 'user_id']);

// organization_user
$table->uuid('organization_id')->constrained()->cascadeOnDelete();
$table->uuid('user_id')->constrained()->cascadeOnDelete();
$table->primary(['organization_id', 'user_id']);
```

### 4.5 `settings`

```php
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
```

### 4.6 `audit_logs`

```php
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
```

### 4.7 `security_events`

```php
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
```

## 5. Indexes & Constraints (TODO §3.3)

| Tabel | Index / Constraint | Tujuan |
|---|---|---|
| `organizational_units` | `(organization_id, parent_id)` | tree traversal |
| `organizational_unit_user` | composite PK | lookup assignment |
| `organization_user` | composite PK | lookup membership |
| `settings` | unique scope `(key, org_id, unit_id, user_id)` | cegah duplikat scope |
| `audit_logs` | `(subject_type, subject_id)`, `(action, occurred_at)` | filter audit |
| `security_events` | `(user_id, occurred_at)`, `(event, occurred_at)` | filter event |
| `users` | `email` unique (existing) | lookup login |

## 6. Konvensi Database Baru — `docs/conventions/database.md`

| Topik | Keputusan |
|---|---|
| Primary key | `$table->uuid('id')->primary()` semua tabel Core (ADR-004) |
| UUID version | v7 time-ordered; trait `Core\Support\Concerns\UsesUuid` |
| Timestamps | `timestamps()`; soft delete `softDeletes()` pada master (User, Organization, Unit, Setting) |
| FK | `constrained()`; `snake_singular_id`; hybrid onDelete (§3.10) |
| Indexing | index kolom WHERE/JOIN/ORDER BY; composite index pola query umum |
| Audit columns | `created_by`/`updated_by` uuid nullable, nullOnDelete, hanya jika ownership semantics |
| Immutability | migration dianggap immutable setelah rilis; pra-rilis boleh edit |

## 7. ADR-011 — `adr-011-database-foundation.md`

Rekam keputusan M2 yang belum punya ADR:

1. UUIDv7 (perluasan ADR-004).
2. Konversi `users` ke UUID + tabel package morph.
3. Single `settings` table + nullable scope + unique scope.
4. Hybrid onDelete policy.
5. Adjacency list + recursive CTE (tanpa dependency baru — validasi package).
6. `audit_logs`/`security_events` dibuat di M2, storage final diputuskan M8.

## 8. Dampak pada Dokumen Existing

### 8.1 `directory-structure.md`

- Detail `core/Database/` — Migrations/ (anonymous class, tanpa namespace,
  dimuat via `loadMigrationsFrom`), Factories/ (namespace
  `Core\Database\Factories\`, tidak auto-discovered — perlu `newFactory()`
  atau registrasi eksplisit), Seeders/ (namespace `Core\Database\Seeders\`,
  dipanggil eksplisit).
- Tegaskan: migration Core hidup di `core/Database/Migrations/` (bukan di
  `database/migrations/`), konsisten dengan ADR-010 (Core = package in-repo)
  dan keputusan sesi (Opsi A).

### 8.2 TODO.md

- Checklist §3.1 (8 item konvensi), §3.2 (7 tabel), §3.3 (5 item constraint)
  dicentang.
- Item yang sengaja **tidak** dikerjakan M2 (YAGNI): model/relasi/action
  Organization (M3), Audit System lengkap (M8), Settings System (M7).

## 9. Non-Goals

- Tidak membuat model/relasi/action/service Organization (M3).
- Tidak membangun Audit System lengkap (M8) — hanya tabel.
- Tidak membangun Settings System (M7) — hanya tabel.
- Tidak menyentuh `db_config`/`white_label_settings` (package dihapus M7/M8).
- Tidak mengubah `roles`/`permissions` (milik Spatie, tetap bigint).
- Tidak menambah dependency baru (validasi package: tidak ada yang layak).
- Tidak mengekstrak Core ke package terpisah.

## 10. Verifikasi / Acceptance

- `composer check` lolos (Pint, Pest, PHPStan).
- `php artisan migrate:fresh` di SQLite berjalan tanpa error; schema test
  assert kolom kunci (`Schema::hasColumn`).
- Unique scope test: `settings` duplikat (key + scope sama) → exception;
  scope berbeda → boleh.
- FK behavior test: delete organization → unit ter-cascade; delete unit
  ber-child → restricted. SQLite in-memory perlu pragma `foreign_keys=on`
  (default off); MySQL/Postgres di CI menegakkan alami.
- UUID test: User baru ter-generate UUID v7 (timestamp prefix terbaca).
- Arch test (Core tidak impor App/Modules/Filament) tetap lolos.
- `php artisan migrate:status` menampilkan migration Core.

## 11. Referensi

- `docs/TODO.md` §3, `docs/PRD.md`, ADR-004, ADR-005, ADR-007, ADR-009,
  ADR-010, `docs/conventions/{naming,directory-structure,coding,environment}.md`
- Laravel 13 migrator source (vendor): `glob($path.'/*_*.php')` non-rekursif;
  `BaseCommand::getMigrationPaths()` tidak membaca `database.migrations.paths`.

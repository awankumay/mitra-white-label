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

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

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

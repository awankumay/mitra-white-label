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

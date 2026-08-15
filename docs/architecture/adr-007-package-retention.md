# ADR-007: Retention Package Core

**Status:** Accepted
**Tanggal:** 2026-08-15

## Context

PRD §56 menetapkan package philosophy: package hanya menjadi Core
dependency jika generic, stabil, reusable, tidak mengunci business
domain, dan tidak mengunci SaaS. Audit baseline (baseline-audit.md)
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

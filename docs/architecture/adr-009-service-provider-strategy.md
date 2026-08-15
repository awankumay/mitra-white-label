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

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

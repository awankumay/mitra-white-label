# ADR-001: Namespace Core Top-Level (`Core\`)

**Status:** Accepted
**Tanggal:** 2026-08-15

## Context

PRD §30 menetapkan Core System harus tetap independent dari business
modules, dan §64 menegaskan batas tegas Core → Application. Untuk itu
dibutuhkan namespace yang memisahkan Core dari application layer secara
struktural.

Opsi yang dipertimbangkan:

1. `Core\` top-level (PSR-4 `Core\` => `core/`).
2. `App\Core\` di dalam `app/Core/`.
3. `Mitra\Core\` (src/ atau core/).

## Decision

Gunakan namespace `Core\` top-level dengan pemetaan PSR-4:

```json
"autoload": {
    "psr-4": {
        "Core\\": "core/"
    }
}
```

Implementasi pemetaan di `composer.json` dilakukan pada M1 (scaffolding),
bukan di M0. Di M0 hanya keputusan yang direkam.

## Consequences

- Batas Core/Application sangat tegas dan dapat diverifikasi otomatis
  (import `Core\` dari `App\`/`Modules\` = pelanggaran aturan).
- `App\` tetap memakai namespace default Laravel; tidak ada perubahan
  pada kode existing.
- Kurang konvensional dibanding `App\Core\` — perlu dokumentasi yang
  jelas di conventions/directory-structure.md.
- Membuka peluang ekstraksi Core menjadi package terpisah di masa depan.

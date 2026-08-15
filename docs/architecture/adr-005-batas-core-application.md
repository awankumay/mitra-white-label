# ADR-005: Batas Core/Application dan Aturan Dependensi

**Status:** Accepted
**Tanggal:** 2026-08-15

## Context

PRD §30 menetapkan Core tidak boleh bergantung pada business modules,
dan §55 menekankan minimasi framework/vendor modification dengan
contracts/composition/actions. Dibutuhkan aturan dependensi yang tegas
dan dapat diverifikasi.

## Decision

Aturan dependensi:

1. `Core\` **tidak boleh** mengimpor `App\` atau `Modules\`.
2. `App\` **boleh** mengimpor `Core\`.
3. `Modules\<Name>\` **boleh** mengimpor `Core\` dan `App\` yang public.
4. `Core\` **tidak boleh** bergantung pada Filament untuk logika non-UI
   (Context, Settings, Audit, dll. harus independent dari Filament).
   Komponen Filament milik Core (resource/panel admin Core) dikecualikan.
5. Verifikasi otomatis (static analysis / arch test) akan diterapkan di
   M1 — M0 hanya merekam aturan.

Struktur direktori yang dimaksud (detail di conventions/
directory-structure.md):

```text
core/     → Core\
app/      → App\
modules/  → Modules\<Name>\
```

## Consequences

- Batas yang jelas memudahkan testing, upgrade, dan pemahaman arsitektur.
- Developer perlu disiplin; verifikasi otomatis di M1 menjadi pengaman.
- `Core\` yang bebas Filament memastikan context dapat dipakai di
  console command, job, dan service tanpa UI (PRD §15).

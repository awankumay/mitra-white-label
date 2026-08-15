# ADR-008: Extension Points Core

**Status:** Accepted
**Tanggal:** 2026-08-15

## Context

PRD §55 menekankan minimasi framework/vendor modification dengan
contracts/composition/actions. Core perlu mekanisme extension yang jelas
tanpa infrastruktur berat.

## Decision

Core v1 memakai 4 mekanisme extension:

1. **Contracts** — interface publik yang mendefinisikan API Core
   (mis. `OrganizationContext`, `SettingRepository`). Dibuat saat milestone
   yang membutuhkannya, bukan di M1.
2. **Config** — binding/override via `config/core.php`; aplikasi dapat
   mengganti implementasi default melalui konfigurasi.
3. **Events** — integrasi yang tidak membutuhkan return value
   (audit, notification). Core melempar event; konsumen mendengarkan.
4. **Actions** — operasi tunggal reusable (`CreateOrganization`), dipakai
   aplikasi/modul dan oleh Core sendiri.

Tidak ada pipeline/middleware-based extension di v1 (YAGNI).

## Consequences

- API Core diekspresikan sebagai contracts; implementasi dapat diganti
  via config.
- Integrasi lintas sub-sistem memakai events — tanpa coupling langsung.
- Actions menjadi unit reusable yang bisa dipanggil dari mana saja.
- Penambahan mekanisme extension baru (mis. pipeline) dievaluasi saat
  kebutuhan nyata muncul.

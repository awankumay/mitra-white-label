# ADR-002: Struktur Application Layer Per-Konsep

**Status:** Accepted
**Tanggal:** 2026-08-15

## Context

TODO.md 1.2 meminta definisi struktur `app/` final. Struktur default
Laravel (Models/, Http/, Policies/) terlalu tipis untuk aplikasi
enterprise yang dibangun di atas Core — logic tersebar dan batas antar
konsep tidak jelas. PRD §30 menetapkan application layer berada di atas
Core tanpa memaksakan modular monolith.

## Decision

Organisasi `app/` per-konsep (bukan per-teknikal layer):

```text
app/
├── Models/
├── Domain/
├── Actions/
├── Services/
├── Contracts/
├── Enums/
├── Policies/
├── Support/
├── Filament/
└── Http/
```

`app/Models/` memuat model application; `app/Domain/` memuat entitas/
value object domain bila diperlukan; `app/Actions/` untuk reusable
action; `app/Services/` untuk orchestration. Folder yang belum terisi di
M0 dibuat saat milestone yang membutuhkannya — tidak dibuat di M0.

## Consequences

- Struktur mudah dikembangkan dan konsisten dengan konvensi Core.
- Folder kosong tidak dibuat di M0 (dokumen-only); pembuatan fisik
  mengikuti kebutuhan milestone.
- Deviasi dari layout Laravel default perlu didokumentasikan di
  conventions/directory-structure.md.

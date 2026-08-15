# ADR-006: Module-Ready (Bukan Modular Monolith Penuh)

**Status:** Accepted
**Tanggal:** 2026-08-15

## Context

PRD §30 menyatakan Core harus module-ready tetapi tidak memaksakan
Modular Monolith penuh. PRD §31 mendefinisikan module contract
(identity, name, version, config, provider, routes, migrations, models,
policies, Filament resources, permissions, features).

## Decision

Core menyediakan konvensi dan infrastruktur module tanpa memaksa seluruh
application logic masuk ke `modules/`:

```text
modules/
└── <ModuleName>/
    ├── Module.php
    ├── Config/
    ├── Database/
    ├── Domain/
    ├── Filament/
    ├── Models/
    └── Policies/
```

Keputusan:

- Module adalah unit opsional; application layer tetap bisa hidup di
  `app/` bila modul belum diperlukan.
- Module contract & discovery diimplementasikan di M1+ (Core Module
  system), bukan M0.
- Nama namespace: `Modules\<ModuleName>\`.
- Module generator (`mitra:make:module`) diimplementasikan di M10.

## Consequences

- Fleksibilitas: tim dapat memilih antara `app/` dan `modules/`.
- Tidak over-engineer Core dengan infrastruktur monolith penuh.
- Konvensi harus cukup jelas agar struktur module predictable (PRD §30).

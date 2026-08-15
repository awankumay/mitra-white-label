# Struktur Direktori

**Status:** Accepted
**Tanggal:** 2026-08-15
**Referensi:** ADR-001, ADR-002, ADR-005, ADR-006

## Peta Direktori

```text
core/                     # Core System — namespace Core\
├── Context/
├── Contracts/
├── Enums/
├── Exceptions/
├── Features/             # Feature Registry
├── Modules/              # Module contract & discovery
├── Organization/         # Organization, OrganizationalUnit, Assignment
├── Settings/
├── Branding/
├── Audit/
├── Security/             # SecurityEvents, 2FA policy, passkey support
├── Support/
└── Actions/

app/                      # Application layer — namespace App\
├── Models/
├── Domain/
├── Actions/
├── Services/
├── Contracts/
├── Enums/
├── Policies/
├── Support/
├── Filament/             # AdminPanelProvider, Resources, Pages, Widgets
└── Http/

modules/                  # Business modules — namespace Modules\<Name>\
└── <ModuleName>/
    ├── Module.php
    ├── Config/
    ├── Database/
    ├── Domain/
    ├── Filament/
    ├── Models/
    └── Policies/
```

## Aturan

- `core/` tidak pernah mengimpor `app/` atau `modules/` (ADR-005).
- `app/Filament/` hanya berisi komponen UI; logika bisnis di
  Domain/Actions/Services.
- `modules/` opsional; struktur module mengikuti ADR-006.
- Folder fisik dibuat sesuai kebutuhan milestone — peta ini adalah
  target akhir, bukan kewajiban membuat folder kosong di M0.

## Nama Folder

- `PascalCase` untuk folder modul (`modules/Inventory/`).
- `snake_case` untuk folder umum (jarang; ikuti konvensi Laravel).

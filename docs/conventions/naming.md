# Konvensi Penamaan

**Status:** Accepted
**Tanggal:** 2026-08-15
**Referensi:** ADR-001, ADR-004

## Kelas & File

- Kelas: `PascalCase`, satu kelas per file, nama file = nama kelas.
- Interface: `PascalCase` (mis. `OrganizationContext`).
- Enum: `PascalCase` (mis. `OrganizationalUnitType`), case value
  `UPPER_SNAKE` (mis. `HEAD_OFFICE`, `BRANCH`).
- Action: `VerbNoun` (mis. `CreateOrganization`).
- Service: `NounService` (mis. `OrganizationService`).

## Namespace

- `Core\` untuk Core System (ADR-001).
- `App\` untuk application layer.
- `Modules\<ModuleName>\` untuk modul.

## Database

- Tabel: `snake_case` jamak (mis. `organizational_units`).
- Pivot: gabungan nama tabel urut alfabetis
  (mis. `organizational_unit_user`, `organization_user`).
- Kolom foreign key: `snake_case` singular + `_id`
  (mis. `organization_id`, `parent_id`).
- Primary key: `id` bertipe UUID string (ADR-004).
- Timestamps: `created_at`, `updated_at`; soft delete:
  `deleted_at`.
- Audit kolom: `created_by`, `updated_by` bila diperlukan.

## Permission

- Pola `action:subject` (format Filament Shield v4.3.1, PRD §19):
  `view:users`, `create:users`, `update:users`, `delete:users`,
  `view:organizational_units`, dst.
- Method policy di-generate Shield ke snake_case: `viewAny` → `view_any`,
  `forceDeleteAny` → `force_delete_any`.
- Separator dan case mengikuti `config/filament-shield.php`
  (`permissions.separator => ':'`, `permissions.case => 'snake'`).
- Policy resource model Core di-generate ke `core/<Domain>/Policies/`
  (Shield menurunkan path dari lokasi model); model `app/Models/` → `app/Policies/`.

## Bahasa

- Kode, kelas, metode, dan komentar: English.
- String yang tampil ke user (label, notifikasi): sesuai lokalisasi
  aplikasi (Bahasa Indonesia default).

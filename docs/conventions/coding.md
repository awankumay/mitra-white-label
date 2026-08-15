# Konvensi Coding

**Status:** Accepted
**Tanggal:** 2026-08-15
**Referensi:** ADR-005, ADR-007, naming.md

## Quality Gate

- `composer check` adalah standar quality gate:
  Pint (format) → Pest (test) → PHPStan (static analysis).
- Minimum PHP 8.3 (kompatibel dengan versi terpasang 8.4).
- Database test: SQLite in-memory (PRD §53); MySQL/PostgreSQL untuk
  production.

## Batas Dependensi (ADR-005)

- `Core\` tidak mengimpor `App\` / `Modules\`.
- `Core\` non-UI tidak bergantung pada Filament.
- Verifikasi otomatis menyusul di M1.

## Struktur Logic

- Prefer Action untuk operasi tunggal yang reusable
  (`CreateOrganization`), Service untuk orchestration multi-langkah.
- Jangan menumpuk logic di controller/Resource — pindahkan ke Action
  atau Service.
- Prefer Composition over inheritance.
- Tidak memodifikasi vendor; gunakan contracts/extension points
  (PRD §55).

## Error Handling

- Exception application diturunkan dari `Core\Exceptions\CoreException`
  (dibuat di M1).
- Jangan menampilkan secrets ke user (PRD §39).

## Format & Tools

- Ikuti Laravel Pint default config (`pint.json` existing).
- PHPStan level sesuai `phpstan.neon` existing; tingkatkan bertahap.

## Git Workflow

- Commit message: conventional commits
  (`feat:`, `fix:`, `docs:`, `chore:`, `refactor:`, `test:`).
- Commit kecil dan sering; satu task = satu commit.

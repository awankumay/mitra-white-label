# Konvensi Coding

**Status:** Accepted
**Tanggal:** 2026-08-16
**Referensi:** ADR-001 s.d. ADR-010, naming.md, directory-structure.md,
environment.md, spec `docs/superpowers/specs/2026-08-16-architecture-rules-design.md`

## Quality Gate

- `composer check` adalah standar quality gate:
  Pint (format) → Pest (test) → PHPStan (static analysis).
- Minimum PHP 8.3 (kompatibel dengan versi terpasang 8.4).
- Database test: SQLite in-memory (PRD §53); MySQL/PostgreSQL untuk
  production.

## Batas Dependensi (ADR-005)

- `Core\` tidak mengimpor `App\` / `Modules\`.
- `Core\` non-UI tidak bergantung pada Filament.
- Verifikasi otomatis via Pest arch test (`tests/Arch/CoreArchTest.php`).

## Architecture Rules

### Aturan Dependensi

- `Core\` tidak mengimpor `App\` / `Modules\` (ADR-005) — diverifikasi arch test.
- `App\` boleh mengimpor `Core\`, tetapi **tidak** mengimpor `Modules\`.
- `Modules\<Name>\` boleh mengimpor `Core\` dan `App\` public (ADR-006).
- `Core\` non-UI tidak bergantung pada Filament; komponen UI Core di `core/Filament/`.
- Core **melempar event, konsumen mendengarkan** — Core tidak mendengarkan event aplikasi.

### Model

- Model Core di `core/<Domain>/Models/`, model aplikasi di `app/Models/`,
  model module di `modules/<Name>/Models/`.
- Thin model: model memegang atribut, casts, relasi, scope sederhana — bukan
  logika bisnis (→ Action/Service).
- Relasi lintas-batas diekspresikan via contract, bukan import model Core langsung.

### Policy

- Policy untuk resource Filament di-generate Filament Shield ke `app/Policies/`.
- Format permission: `resource:action` (separator `:`, snake).
- Policy manual hanya untuk di luar resource Filament; jangan menumpuk logika
  bisnis di policy (delegasikan ke Action/Service).

### Action

- Action = operasi tunggal reusable: `CreateOrganizationAction`, `final`,
  `handle()`/`__invoke()`, constructor injection.
- Action tidak memanggil Service (Service yang memanggil Action).
- Action UI: label dari lang (ID default), icon `Heroicon` enum, action general
  via Concerns di `app/Filament/Concerns/`.

### Service

- Service = koordinator multi-langkah: `OrganizationService`, `final`,
  constructor injection.
- Alur yang mengubah banyak record dibungkus `DB::transaction()`.

### Event/Listener

- Event Core terkolokasi per-domain: `core/<Domain>/Events/`.
- Penamaan event: `NounVerbPastTense` (`OrganizationCreated`).
- Listener aplikasi di `app/Listeners/`; event untuk integrasi non-return-value.

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

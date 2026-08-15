# M0 — Project Baseline Design

**Tanggal:** 2026-08-15
**Status:** Draft
**Referensi:** `docs/PRD.md`, `docs/TODO.md` (bagian 0)

---

## 1. Ringkasan

M0 adalah milestone pertama dari roadmap Mitra White Label. Tujuan M0 bukan membangun fitur, melainkan **menetapkan fondasi keputusan arsitektur** yang akan menjadi acuan seluruh milestone berikutnya (M1–M13).

Output M0 seluruhnya berupa **dokumen** — tidak ada perubahan kode, scaffolding, atau penghapusan package.

---

## 2. Keputusan Kunci (Locked)

| Keputusan | Pilihan |
|---|---|
| Output M0 | Keputusan + dokumen saja |
| Pendekatan dokumentasi | ADR-centric |
| Bahasa dokumen | Bahasa Indonesia |
| Namespace Core | `Core\` top-level (PSR-4 terpisah) |
| Struktur `app/` | Per-konsep (Domain/Actions/Services/...) |
| Kebijakan capability | Core build sendiri (Settings/Branding/Audit) |
| Primary key | UUID + soft-delete |
| Modul | Module-ready, bukan modular monolith |
| Package redundant | Didokumentasikan, penghapusan ditunda |

---

## 3. Struktur Dokumen yang Dihasilkan

```text
docs/
├── architecture/
│   ├── baseline-audit.md               # Audit repo vs PRD + daftar package
│   ├── adr-001-namespace-core.md       # Core\ top-level vs alternatif
│   ├── adr-002-struktur-app.md         # app/ per-konsep vs default
│   ├── adr-003-core-vs-package.md      # build sendiri vs bungkus package
│   ├── adr-004-primary-key-uuid.md     # UUID + soft-delete
│   ├── adr-005-batas-core-application.md # aturan dependensi Core/App/Module
│   ├── adr-006-module-architecture.md  # module-ready, bukan modular monolith
│   └── adr-007-package-retention.md    # keep / remove / evaluate
└── conventions/
    ├── naming.md                        # kelas, file, tabel, kolom, permission
    ├── directory-structure.md           # peta core/ + app/ + modules/ final
    └── coding.md                        # Pint/PHPStan/Pest, git workflow
```

Setiap ADR mengikuti template **Context → Decision → Consequences**.

---

## 4. Batas Core/Application (intisari untuk ADR-005)

```text
core/                    # Core System — namespace Core\
├── Context/
├── Contracts/
├── Enums/
├── Exceptions/
├── Features/            # Feature Registry
├── Modules/             # Module contract & discovery
├── Organization/        # Organization, OrganizationalUnit, Assignment
├── Settings/
├── Branding/
├── Audit/
├── Security/            # SecurityEvents, 2FA policy, passkey support
├── Support/
└── Actions/

app/                     # Application layer — namespace App\
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

modules/                 # future business modules — namespace Modules\<Name>\
└── (belum ada di Core, hanya konvensi)
```

**Aturan dependensi:**
- `Core\` tidak boleh mengimpor `App\` atau `Modules\`
- `App\` boleh mengimpor `Core\`
- `Modules\<Name>\` boleh mengimpor `Core\` dan `App\` public
- `Core\` tidak boleh bergantung ke Filament untuk logic non-UI; komponen Filament milik Core (resource/panel admin Core) dikecualikan

---

## 5. Kebijakan Package (intisari untuk ADR-007)

| Status | Package | Alasan |
|---|---|---|
| Keep | `bezhansalleh/filament-shield`, `spatie/laravel-permission` | RBAC — PRD §19 |
| Keep | `jeffgreco13/filament-breezy`, `pragmarx/google2fa*`, `web-auth/webauthn-lib` | 2FA/passkey/session — PRD §22-25 |
| Keep | `spatie/laravel-activitylog` | audit trail backend |
| Remove (ditunda) | `inerba/filament-db-config` | Settings dibangun Core sendiri |
| Remove (ditunda) | `ashrafic/filament-white-label` | Branding dibangun Core sendiri |
| Remove (ditunda) | `jacobtims/filament-logger` | Audit dibangun Core sendiri |
| Evaluate di M1+ | theme-edinburgh, backgrounds, quick-create, developer-logins, language-switcher | plugin UX, tidak memblokir Core |

Penghapusan package **tidak dilakukan di M0** — dijadwalkan pada milestone yang membangun penggantinya (M7 Settings/Branding, M8 Audit).

---

## 6. Deliverable M0

1. 7 ADR di `docs/architecture/`
2. 3 dokumen konvensi di `docs/conventions/`
3. 1 dokumen audit baseline (`docs/architecture/baseline-audit.md`)
4. Update `TODO.md` — centang item M0 selesai, tandai deferral penghapusan package ke M7/M8

---

## 7. Non-Goals M0

- Tidak ada scaffolding kode (`core/`, `app/Domain/`, dll. belum dibuat)
- Tidak ada `composer remove`
- Tidak ada migration, model, atau resource baru
- Tidak ada perubahan `composer.json` autoload, konfigurasi CI, atau skrip
- Tidak ada implementasi Organization/Context/Settings (itu M1+)

---

## 8. Verifikasi M0

1. Setiap ADR konsisten dengan "locked decisions" PRD §64
2. Konvensi naming/directory/coding tidak saling bertentangan dan merujuk ADR yang benar
3. Semua 13 item TODO M0 ter-cover (dikerjakan atau didefer dengan alasan tertulis)
4. Dokumen direview sebelum dianggap final

---

## 9. Daftar ADR yang Akan Ditulis

| ADR | Topik |
|---|---|
| ADR-001 | Namespace `Core\` top-level |
| ADR-002 | Struktur `app/` per-konsep |
| ADR-003 | Core build sendiri untuk Settings/Branding/Audit |
| ADR-004 | UUID + soft-delete untuk primary key |
| ADR-005 | Batas Core/Application + aturan dependensi |
| ADR-006 | Module-ready (bukan modular monolith) |
| ADR-007 | Retention package (keep/remove/evaluate) |

# Baseline Audit — Mitra White Label

**Tanggal:** 2026-08-15
**Status:** Final

## 1. Ringkasan

Audit repository awal terhadap `docs/PRD.md`. Tujuan: memverifikasi fondasi
teknis dan mengidentifikasi gap terhadap Core System yang ditargetkan PRD.

## 2. Verifikasi Versi

| Komponen | Versi yang Dibutuhkan (PRD) | Versi Terpasang | Status |
|---|---|---|---|
| PHP | ^8.3 | 8.4 | ✓ |
| Laravel | ^13.0 | 13.25.0 | ✓ |
| Filament | ^5.0 | 5.7.6 | ✓ |

## 3. Review Struktur Repository

Struktur saat ini masih vanilla Laravel starterkit:

- `app/Models/` hanya berisi `User`.
- `app/Filament/` hanya berisi panel admin (`AdminPanelProvider`) dan resource `Users`.
- Belum ada `core/`, `app/Domain/`, `app/Actions/`, atau `modules/`.
- Belum ada model Organization, OrganizationalUnit, Setting, AuditLog, SecurityEvent.

Gap lengkap terhadap PRD dirinci pada bagian 6.

## 4. Review Package Composer

### 4.1 Foundation (Keep)

| Package | Peran | Referensi PRD |
|---|---|---|
| `bezhansalleh/filament-shield` (4.3.1) + `spatie/laravel-permission` (8.3.0) | RBAC | §19 |
| `jeffgreco13/filament-breezy` (3.2.8) + `pragmarx/google2fa*` + `web-auth/webauthn-lib` | 2FA, passkey, session, recovery codes | §22–25 |
| `spatie/laravel-activitylog` (4.12.3) | Audit trail backend di belakang Audit System Core | §32 |

### 4.2 Redundant (Remove — dijadwalkan)

| Package | Alasan | Jadwal Penghapusan |
|---|---|---|
| `inerba/filament-db-config` (1.3.5) | Settings akan dibangun Core sendiri | M7 |
| `ashrafic/filament-white-label` (1.0.8) | Branding akan dibangun Core sendiri | M7 |
| `jacobtims/filament-logger` (1.2.0) | Audit akan dibangun Core sendiri | M8 |

Keputusan penuh: lihat ADR-007.

### 4.3 UX / Developer Plugin (Evaluate di M1+)

| Package | Catatan |
|---|---|
| `spykapps/theme-edinburgh` (1.0.3) | Theme — tidak memblokir Core |
| `swisnl/filament-backgrounds` (2.0.3) | UX login — tidak memblokir Core |
| `awcodes/filament-quick-create` (5.1.0) | UX CRUD — tidak memblokir Core |
| `dutchcodingcompany/filament-developer-logins` (2.1.0) | Dev convenience — tidak memblokir Core |
| `craft-forge/filament-language-switcher` (1.2.1) | UX bahasa — tidak memblokir Core |

### 4.4 Dev / Quality (Keep)

Pest 4.7, Larastan 3.10, Laravel Pint 1.30, Laravel Sail, Debugbar, Pail,
Faker, Mockery, Collision, Filacheck, Paratest — semua diperlukan untuk
quality gate `composer check`.

## 5. Review Package NPM

Tidak ditemukan redundancy. `package.json` hanya berisi tooling build
standar: Vite 8, Tailwind 4, laravel-vite-plugin, axios, concurrently.

## 6. Gap Repository vs PRD

Capability PRD yang belum ada di repository (menjadi roadmap M1+):

- Organization & Organizational Unit (PRD §9–14)
- Organizational Context & data scope (PRD §15–17)
- Authorization ber-scope organisasi (PRD §18–20)
- Settings architecture (PRD §27)
- White Label branding (PRD §28)
- Feature Registry (PRD §29)
- Module architecture (PRD §30–31)
- Audit System (PRD §32)
- Notifications abstraction (PRD §34)
- Console: `mitra:install`, `mitra:doctor`, `mitra:health`, `mitra:about` (PRD §37–41)
- Developer generators (PRD §42–50)

## 7. Kesimpulan

Fondasi teknis (PHP/Laravel/Filament) sesuai PRD. Gap utama adalah seluruh
capability Core yang akan dibangun mulai M1. Package redundant sudah
teridentifikasi dan penjadwalan penghapusannya diatur ADR-007.

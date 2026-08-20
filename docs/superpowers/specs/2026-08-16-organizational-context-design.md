# Organizational Context — Design Spec

> Milestone M4 (TODO §5). Scope terbatas pada **Organizational Context**; Data Scope Architecture (§6) dikerjakan terpisah setelah context stabil.
>
> Source of truth: `docs/PRD.md` §14–16 (Primary Organizational Unit, Organizational Context, Context Lifecycle).

## 1. Tujuan

Menyediakan abstraction `OrganizationContext` dan `OrganizationalUnitContext` yang:

- Independent dari Filament (PRD §15, ADR-005).
- Dapat digunakan oleh Models, Policies, Actions, Services, Jobs, Commands, Notifications, modul aplikasi, dan Filament UI (PRD §15).
- Menyelesaikan lifecycle: Login → Organization Context → Primary Organizational Unit → Current Unit (PRD §16).
- Mendukung switching unit dengan validasi akses, persistensi, dan pencegahan seleksi tidak sah (PRD §16).

## 2. Keputusan Desain

| Aspek | Keputusan | Alasan |
|---|---|---|
| Scope | Hanya §5 (Context) | §6 (Data Scope) terpisah; butuh model bisnis pemakai scope |
| Pendekatan | Session-first Core resolver | Pilihan user; sesuai ADR-005/008 |
| Persistensi | Session (`core.context.session_key`) | Per-device, ringan, tanpa migrasi |
| Switcher UI | `PanelsRenderHook::USER_MENU_BEFORE` | Bukan tenancy Filament (out-of-scope PRD §31); context tetap independen |
| Kontrak | `core/Contracts/` | ADR-008 (public Core API) |
| Implementasi | `core/Context/` | ADR-002 directory structure |
| `SwitchUnitAction` | `core/Context/Actions/` | Terima primitif (user id + unit id), tanpa import `App\Models\User` |
| Jobs/Commands | Tanpa session → `null`; set eksplisit | Session tidak tersedia di queue/console |

## 3. Arsitektur

### 3.1 Struktur File

```
core/
├── Config/core.php                       # + blok 'context'
├── Contracts/
│   ├── OrganizationContext.php           # kontrak
│   └── OrganizationalUnitContext.php     # kontrak
├── Context/
│   ├── OrganizationContextManager.php    # impl
│   ├── OrganizationalUnitContextManager.php
│   ├── ContextResolver.php
│   ├── Actions/
│   │   └── SwitchUnitAction.php
│   └── ContextServiceProvider.php        # binding contract → impl
└── (Organization/, Support/ existing)
```

### 3.2 Kontrak

**`OrganizationContext`:**

```php
public function organization(): ?Organization;
public function organizationId(): ?string;
public function set(Organization $organization): void;
public function clear(): void;
public function has(): bool;
```

**`OrganizationalUnitContext`:**

```php
public function current(): ?OrganizationalUnit;
public function currentId(): ?string;
public function set(OrganizationalUnit $unit): void;
public function clear(): void;
public function has(): bool;
```

Kontrak di `core/Contracts/`; implementasi di `core/Context/`. Binding via `ContextServiceProvider` yang didaftarkan dari `config('core.providers')` (ADR-009/010).

### 3.3 Alur Data

- Session hanya menyimpan `unit_id` (current unit). `organization_id` **tidak** disimpan di session — selalu di-derive dari current unit (unit → organization) atau dari pivot `organization_user` jika user tidak punya unit.
- `ContextResolver` adalah helper yang dipakai kedua manager untuk membaca session + fallback; manager menyimpan hasil resolusi sebagai state per-request.

```text
Login → Auth::user() → ContextResolver
   ↓ session kosong?
   ↓ primary unit → organization
   ↓ session punya unit_id? → pakai dari session
Context tersimpan di container (singleton)
   ↓
app(OrganizationContext::class)->organization()
app(OrganizationalUnitContext::class)->current()
```

## 4. Resolusi & Lifecycle

### 4.1 Default Resolution (login → context)

```text
User Login
   ↓
Auth::user() tersedia
   ↓
Session punya unit_id? ── ya → validasi unit di-assign ── ya → current unit = unit tsb
   ↓ tidak / tidak valid              ↓ tidak valid → clear session
Primary organizational unit? ── ya → current unit = primary
   ↓ tidak
Unit pertama yang di-assign? ── pakai
   ↓ tidak
Organization dari pivot organization_user? ── ya → org context tersedia, unit context kosong
   ↓ tidak
Context kosong (has() = false)
```

- Primary unit adalah default (PRD §14).
- Multi-unit tanpa primary → fallback unit pertama (urutan pivot stabil).
- Tak punya unit tapi punya organization → org context tersedia, unit context kosong.

### 4.2 Current Unit Resolution

```text
Session punya unit_id? ── ya → validasi masih di-assign ── ya → pakai
   ↓ tidak / tidak valid                    ↓ tidak valid → clear session
Primary unit? ── ya → pakai
   ↓ tidak
Unit pertama di-assign? ── pakai
   ↓ tidak
null
```

- Validasi saat baca: session basi (unit tak lagi di-assign) → clear + fallback.
- `current()` selalu valid — tidak pernah mengembalikan unit di luar akses user.

### 4.3 Context Lifecycle

- Session adalah source of truth untuk current unit & organization (per-device).
- Container memegang singleton resolver yang membaca session.
- Setiap request di-resolve ulang (session sama, DB sama) → konsisten.

## 5. Context Switching

### 5.1 `SwitchUnitAction` (core/Context/Actions/)

- Input: `string $userId`, `string $unitId` (primitif, tanpa import `App\Models\User`).
- Logika:
  1. Validasi target unit di-assign ke user (`units()->where(...)->exists()`).
  2. Valid → `context->set(unit)` + simpan `unit_id` ke session.
  3. Tidak valid → throw `OrganizationException::invalidAssignment` (pola M3).
- Dipakai dari Filament maupun non-Filament (Actions/Services/Commands).

### 5.2 Unit Switcher di Filament

- `PanelsRenderHook::USER_MENU_BEFORE` (hook yang sama dipakai language switcher).
- Dropdown unit yang dapat diakses user (`$user->units()`).
- Unit aktif ditandai (checkmark).
- Klik → POST `/context/switch-unit` → `SwitchUnitAction` → redirect back.
- Hanya tampil jika user punya >1 unit.

### 5.3 Route & Controller

- Route baru di `routes/web.php`: `POST /context/switch-unit`.
- Controller `SwitchUnitController` di `app/Http/Controllers/` (App layer, boleh sentuh User).
- Validasi CSRF via Laravel standar.
- Setelah switch → redirect back + notifikasi Filament.

### 5.4 Persistence

- Session key: `config('core.context.session_key', 'context.unit_id')` di `core/Config/core.php`.
- `SetPrimaryUnitAction` (existing) tidak mengubah current unit session — primary adalah default, current adalah preferensi sementara.

## 6. Non-Filament Usage

- **Services/Actions**: `app(OrganizationalUnitContext::class)->current()` — tersedia jika session ada; di CLI/queue, null.
- **Policies**: cek `$context->has()` + `$context->currentId()`.
- **Jobs**: set context eksplisit di `handle()` via `app(...)->set($unit)` atau terima `unit_id` di constructor; jangan andalkan session.
- **Console Commands**: `--unit=` opsi untuk men-set context, atau `set()` manual.

## 7. Konfigurasi

`core/Config/core.php` ditambah blok:

```php
'context' => [
    'session_key' => 'context.unit_id',
],
```

`CoreServiceProvider` mendaftarkan `ContextServiceProvider` via `config('core.providers')` (ADR-009).

## 8. Error Handling

- Tak ada unit/org → `has()=false`, `current()=null`, tidak throw.
- Session basi (unit tak lagi di-assign) → clear + fallback, tidak throw.
- Switch ke unit tak di-assign → throw `OrganizationException::invalidAssignment`.

## 9. Testing

| File | Cakupan |
|---|---|
| `tests/Unit/Context/ContextManagerTest.php` | set/clear/has, validasi session basi |
| `tests/Unit/Context/ContextResolverTest.php` | default resolution (primary, first unit, no unit) |
| `tests/Feature/Context/SwitchUnitTest.php` | switch valid/invalid, authorization, redirect |
| `tests/Feature/Context/ContextFilamentTest.php` | switcher render, unit list, active mark |
| `tests/Unit/Context/ContextNonFilamentTest.php` | Services/Actions/Jobs/Commands pakai context |

## 10. Out of Scope (M4)

- Data Scope Architecture (§6) — milestone terpisah.
- Filament tenancy / multi-tenant (PRD §31 out-of-scope Core v1).
- Helper global (`context_unit()`, `context_organization()`) — ditunda, bisa ditambahkan saat dibutuhkan.

## 11. Non-Filament Usage (Praktik)

- **Services/Actions**: `app(OrganizationalUnitContext::class)->current()` — tersedia jika session ada; di CLI/queue, `null`.
- **Policies**: cek `$context->has()` + `$context->currentId()` sebelum otorisasi scope.
- **Jobs**: set context eksplisit di `handle()` via `app(OrganizationalUnitContext::class)->set($unit)` atau terima `unit_id` di constructor; jangan andalkan session.
- **Console Commands**: opsi `--unit=` untuk men-set context, atau `set()` manual.
- **Model scoping** (Data Scope, §6) adalah milestone terpisah — jangan implementasikan di M4.

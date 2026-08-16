# Authentication & Security — Design Spec

> Milestone M5 (TODO §7). Berdiri di atas Data Scope (M6-TODO, sudah dikerjakan) dan
> Organizational Context (M4) yang stabil. Scope terbatas pada **Authentication**;
> RBAC/authorization detail ditangani M6-TODO §8 (Shield sudah aktif, policy ada).
>
> Source of truth: `docs/PRD.md` §6, §21–26, §54, §57, §64; `docs/TODO.md` §7, §9.2.

## 1. Tujuan

Menyediakan authentication modern dan account security untuk Core:

- Login, logout, password reset, email verification, password confirmation, session handling (TODO §7.1).
- Password change, active session management, session revocation, revoke-all (TODO §7.2).
- TOTP 2FA + QR enrollment + recovery codes (TODO §7.3).
- Passkeys WebAuthn: register, name, view, revoke (TODO §7.4).
- Security events: login success/failure, password change, 2FA changes, passkey changes, session revocation (TODO §7.5).

Authentication **tidak bergantung pada external identity provider** (PRD §6, §21) dan **tidak membuat custom cryptography** (PRD §24, §54) — semua mekanisme memakai package established yang sudah terinstall.

## 2. Keputusan Desain

| Aspek | Keputusan | Alasan |
|---|---|---|
| Foundation UX | `jeffgreco13/filament-breezy` v3.2 (sudah terinstall) | PRD §22: Breezy sebagai foundation account/security UX; sudah mendukung 2FA, passkey, browser sessions, Sanctum tokens |
| 2FA | TOTP + recovery codes via Breezy (pragmarx/google2fa) | Established, tanpa custom crypto (PRD §24) |
| Passkeys | WebAuthn via Breezy (`web-auth/webauthn-lib`) | Sama, established; tidak perlu package tambahan |
| Session management | `enableBrowserSessions` Breezy (butuh session driver `database`) | SESSION_DRIVER sudah `database`; PRD §25 |
| Email verification | Filament `emailVerification()` + `MustVerifyEmail` | PRD §21; keputusan user: wajib |
| Password reset | Filament `passwordReset()` | Native Filament, tidak perlu Fortify |
| Kebijakan 2FA | Opsional + force via env `AUTH_2FA_FORCE`; **super admin selalu wajib** | Keputusan user: mitigasi risiko role tertinggi; PRD §23 |
| Kebijakan auth storage | Config (`core/Config/core.php` + env), bukan DB settings | Settings System (TODO §9) belum ada; migration path ke M7 didokumentasikan |
| Security events | Core domain `Core\Security` + events + listeners | PRD §26, §32–33: audit/security events arsitektur terpusat; Core melempar event, aplikasi mendengarkan |
| Rate limit | Filament login bawaan (5/menit) | Sudah ada di `Login.php`; tidak perlu custom |
| Database | Migrations Breezy di-publish (`breezy_sessions`, `passkeys`) | `security_events` sudah ada (M2) |
| Testing | Pest feature/unit + arch test | `composer check` quality gate (conventions/coding.md) |

## 3. Arsitektur

### 3.1 Struktur File

```
core/
├── Config/
│   └── core.php                          # + blok 'auth' (kebijakan, env override)
├── Security/
│   ├── Enums/
│   │   └── SecurityEventType.php         # LoginSucceeded, LoginFailed, PasswordChanged,
│   │                                     #   TwoFactorEnabled, TwoFactorDisabled,
│   │                                     #   PasskeyRegistered, PasskeyRevoked, SessionRevoked
│   ├── Events/
│   │   ├── SecurityEventOccurred.php     # satu event generik (type + metadata + user)
│   │   └── ...                           # (opsional) event spesifik per-kejadian
│   ├── Models/
│   │   └── SecurityEvent.php             # model untuk tabel security_events
│   └── Services/
│       └── SecurityEventRecorder.php     # Action tunggal: catat event ke DB
│
app/
├── Listeners/
│   └── Security/
│       ├── RecordSecurityEvent.php            # listener untuk event Core SecurityEventOccurred
│       ├── RecordLoginSucceeded.php           # Auth\Events\Login → SecurityEventOccurred
│       ├── RecordLoginFailed.php              # Auth\Events\Failed → SecurityEventOccurred
│       └── RecordPasskeyUsed.php              # Breezy PasskeyUsedToAuthenticate → event login
├── Livewire/
│   └── Security/
│       ├── UpdatePassword.php                 # extends Breezy UpdatePassword (+ dispatch)
│       ├── TwoFactorAuthentication.php        # extends Breezy 2FA (+ dispatch)
│       ├── Passkeys.php                       # extends Breezy Passkeys (+ dispatch)
│       └── BrowserSessions.php                # extends Breezy BrowserSessions (+ dispatch)
├── Models/
│   └── User.php                          # + MustVerifyEmail, TwoFactorAuthenticatable,
│                                         #   relasi securityEvents() HasMany
├── Providers/
│   └── Filament/
│       └── AdminPanelProvider.php        # aktifkan Breezy 2FA/passkey/session + email verification
│                                         #   + daftarkan override komponen security
│
database/
├── migrations/
│   ├── xxxx_create_breezy_sessions_table.php   # published
│   ├── xxxx_alter_breezy_sessions_table.php    # published
│   └── xxxx_create_passkeys_table.php          # published
└── seeders/
    └── DatabaseSeeder.php                # + role default (Super Admin, Administrator, Manager,
                                          #   Supervisor, Staff, Viewer) + permissions
```

### 3.2 Kebijakan Auth — `core/Config/core.php`

```php
// blok baru di core/Config/core.php
'auth' => [
    'two_factor' => [
        'enabled' => true,
        'force'   => (bool) env('AUTH_2FA_FORCE', false),
        'super_admin_forced' => true,          // super admin selalu wajib 2FA
    ],
    'passkey' => [
        'enabled' => true,
        'relying_party_id' => env('AUTH_PASSKEY_RP_ID'),   // null → request host
        'relying_party_name' => env('APP_NAME', 'Mitra'),
        'auto_prompt' => false,
    ],
    'password' => [
        // Laravel Password::default() + rules; Breezy passwordUpdateRules
        'rules' => ['min:8', 'mixedCase', 'numbers', 'symbols', 'uncompromised(3)'],
    ],
    'session' => [
        'lifetime' => (int) env('SESSION_LIFETIME', 120),   // reuse existing
    ],
    'rate_limit' => [
        'login' => 5,   // per menit per email/ip — Filament default; dibiarkan
    ],
],
```

- Config ini dibaca oleh `AdminPanelProvider` (via `config('core.auth.*')`).
- Env var baru: `AUTH_2FA_FORCE`, `AUTH_PASSKEY_RP_ID` (opsional) — didokumentasikan di
  `docs/conventions/environment.md` dan `.env.example` (kebijakan penambahan env var).

### 3.3 Security Events — Core

`SecurityEvent` model (tabel sudah ada dari M2):

```php
// core/Security/Models/SecurityEvent.php
class SecurityEvent extends Model
{
    protected $table = 'security_events';
    protected $primaryKey = 'id';
    public $incrementing = false;      // UUID
    protected $keyType = 'string';
    protected $casts = ['metadata' => 'array', 'occurred_at' => 'datetime'];
}
```

> `Model::unguard()` global aktif (AppServiceProvider) — tidak perlu `$guarded`.
> Model Core tidak mengimpor `App\*` (ADR-005). `user_id` di tabel `security_events`
> adalah FK UUID ke `users` — relasi di `User` model (app layer) menggunakan `HasMany`.

`SecurityEventType` enum:

```php
enum SecurityEventType: string
{
    case LoginSucceeded    = 'login_succeeded';
    case LoginFailed       = 'login_failed';
    case PasswordChanged   = 'password_changed';
    case TwoFactorEnabled  = 'two_factor_enabled';
    case TwoFactorDisabled = 'two_factor_disabled';
    case PasskeyRegistered = 'passkey_registered';
    case PasskeyRevoked    = 'passkey_revoked';
    case SessionRevoked    = 'session_revoked';
}
```

`SecurityEventOccurred` event (Core, colocated per-domain):

```php
class SecurityEventOccurred
{
    public function __construct(
        public readonly SecurityEventType $type,
        public readonly ?string $userId,        // UUID string; nullable (login gagal sebelum auth)
        public readonly array $metadata = [],   // ip, user_agent, dst.
        public readonly ?\DateTimeInterface $occurredAt = null,
    ) {}
}
```

`SecurityEventRecorder` service (Action tunggal, final):

```php
final class SecurityEventRecorder
{
    public function record(
        SecurityEventType $type,
        ?string $userId = null,
        array $metadata = [],
        ?\DateTimeInterface $occurredAt = null,
    ): void {
        SecurityEvent::query()->create([
            'event'        => $type->value,
            'user_id'      => $userId,
            'ip_address'   => $metadata['ip_address'] ?? request()->ip(),
            'user_agent'   => $metadata['user_agent'] ?? request()->userAgent(),
            'metadata'     => $metadata,
            'occurred_at'  => $occurredAt ?? now(),
        ]);
    }
}
```

- Core **melempar event, aplikasi mendengarkan** (conventions/coding.md) — Core tidak mendengarkan event aplikasi.
- Listeners aplikasi (`app/Listeners/Security/*`) mendengarkan `SecurityEventOccurred` dan memanggil recorder.
- Trigger security events — **berdasarkan event yang benar-benar tersedia** (hasil inspeksi Breezy v3.2):
  - **Login success**: `Illuminate\Auth\Events\Login` (Laravel) — dicatat listener aplikasi.
  - **Login failed**: `Illuminate\Auth\Events\Failed` (Laravel) — dicatat listener aplikasi.
  - **Passkey authentication**: `Jeffgreco13\FilamentBreezy\Events\PasskeyUsedToAuthenticate` — listener aplikasi.
  - **Password change**: Breezy `UpdatePassword::submit()` **tidak memunculkan event**.
    Solusi tanpa vendor modification: komponen My Profile kustom `App\Livewire\UpdatePassword`
    yang **extends** `Jeffgreco13\FilamentBreezy\Livewire\UpdatePassword` dan override `submit()`
    — tetap memanggil parent, lalu `SecurityEventOccurred::dispatch(PasswordChanged)`
    (pattern `myProfileComponents(['update_password' => CustomUpdatePassword::class])`,
    didokumentasikan Breezy).
  - **2FA enable/disable/regenerate**: Breezy `TwoFactorAuthentication` **tidak memunculkan event**
    (hanya aksi + notification). Solusi: override komponen `TwoFactorAuthentication` serupa
    (extends + override `enableAction`/`disableAction`/`regenerateCodesAction` → dispatch event),
    didaftarkan via `myProfileComponents(['two_factor_authentication' => Custom::class])`.
  - **Passkey register/revoke**: Breezy `Passkeys` **tidak memunculkan event** selain
    `PasskeyUsedToAuthenticate`. Solusi: override komponen `Passkeys` serupa.
  - **Session revocation**: Breezy `BrowserSessions::logoutOtherBrowserSessions()` **tidak
    memunculkan event**. Solusi: override komponen `BrowserSessions` serupa.

> Prinsip: jangan memodifikasi vendor. Semua hook via override komponen Breezy
> (extension point resmi) atau listener event Laravel/Filament. Semua jalur
> berakhir di `SecurityEventOccurred` → listener aplikasi → `SecurityEventRecorder`.

### 3.4 User Model

```php
// app/Models/User.php — perubahan
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasFactory, HasPanelShield, HasRoles, Notifiable, SoftDeletes, UsesUuid,
        TwoFactorAuthenticatable;

    public function securityEvents(): HasMany
    {
        return $this->hasMany(SecurityEvent::class, 'user_id');
    }
}
```

- `securityEvents()` adalah `HasMany` (tabel `security_events` memakai `user_id` polos, bukan morph).
- `MustVerifyEmail` menuntut method `hasVerifiedEmail()`/`markEmailAsVerified()` — disediakan
  `Illuminate\Foundation\Auth\User` (Authenticatable) bawaan; tidak perlu implement manual.

### 3.5 AdminPanelProvider — perubahan

```php
// app/Providers/Filament/AdminPanelProvider.php
return $panel
    ->default()
    ->id('admin')
    ->path('admin')
    ->login()
    ->passwordReset()                          // NEW: password reset (TODO §7.1)
    ->emailVerification()                      // NEW: email verification wajib
    ->colors([...])
    ->whiteLabel()
    ->resources([...])
    ->discoverResources(...)
    ->pages([...])
    ->widgets([...])
    ->middleware([...])                        // tetap
    ->plugins([
        FilamentShieldPlugin::make()...,
        ThemeEdinburghPlugin::make(),
        FilamentBackgroundsPlugin::make(),
        FilamentLoggerPlugin::make(),
        BreezyCore::make()
            ->myProfile()
            ->enableTwoFactorAuthentication(
                force: config('core.auth.two_factor.force'),   // env AUTH_2FA_FORCE
            )
            ->enablePasskeys(
                relyingPartyName: config('core.auth.passkey.relying_party_name'),
                relyingPartyId: config('core.auth.passkey.relying_party_id') ?: null,
            )
            ->enableBrowserSessions(),
        QuickCreatePlugin::make()...,
        FilamentDeveloperLoginsPlugin::make()...,
        FilamentLanguageSwitcherPlugin::make()...,
    ])
    ->authMiddleware([
        Authenticate::class,
        // 2FA middleware otomatis ditambahkan Breezy saat enableTwoFactorAuthentication
    ])
    ->viteTheme('resources/css/filament/admin/theme.css');
```

**Catatan super admin 2FA wajib:**
- `force: config('core.auth.two_factor.force')` + `super_admin_forced: true`.
- Karena `super_admin` di Shield bypass gate via intercept `before`, Breezy `force`
  tidak otomatis memaksa mereka. Implementasi: listener/`booted` pada panel yang
  me-redirect super admin ke halaman 2FA bila belum confirmed, ATAU override
  `MustTwoFactor` middleware Breezy dengan pengecekan role. Detail final saat implementasi;
  invariant: **super admin tidak bisa masuk tanpa 2FA confirmed**.

**Kustomisasi komponen security (untuk security events):**
- Breezy `myProfileComponents()` menerima override — didokumentasikan resmi.
- `app/Livewire/Security/UpdatePassword.php` (extends Breezy `UpdatePassword`, override `submit()` → parent + dispatch event).
- `app/Livewire/Security/TwoFactorAuthentication.php` (extends Breezy, override `enableAction`/`disableAction`/`regenerateCodesAction` → dispatch event).
- `app/Livewire/Security/Passkeys.php` (extends Breezy, override aksi create/revoke → dispatch event).
- `app/Livewire/Security/BrowserSessions.php` (extends Breezy, override `logoutOtherBrowserSessions` → dispatch event).
- Daftarkan di `AdminPanelProvider` via `->myProfileComponents([...])`.
- Override memanggil parent (komposisi, bukan fork vendor).

### 3.6 Seeder & Roles

- `DatabaseSeeder` menambahkan role default (PRD §19):
  `Super Admin`, `Administrator`, `Manager`, `Supervisor`, `Staff`, `Viewer`
  (snake: `super_admin`, `administrator`, `manager`, `supervisor`, `staff`, `viewer`).
- Permissions mengikuti konvensi `action:subject` (Shield v4.3.1) — Shield generate
  dari resources; seeder hanya memastikan role inti + super_admin assign.

### 3.7 Alur Login

```
Login (password) → Filament rate limit 5/menit → Auth::attempt
      ↓ (sukses)
MustVerifyEmail? → belum → email verification prompt page
      ↓ (verified)
2FA? → enabled & belum confirmed session → TwoFactorPage (TOTP / recovery code)
      ↓ (confirmed)
Context unit resolve (M4) → dashboard
```

- Email verification: `emailVerification()` — user harus klik link sebelum masuk aplikasi.
- 2FA: challenge setelah login, sekali per session (Breezy `scopeToPanel` default true).
- Passkey: login page menampilkan opsi passkey (Breezy render hook `AUTH_LOGIN_FORM_AFTER`).
- Logout: Filament native.

## 4. Error Handling

| Kasus | Perilaku |
|---|---|
| Login gagal (salah password) | Filament rate limit + pesan generik; `LoginFailed` security event dicatat |
| 2FA salah / recovery code salah | Pesan error; rate limit 5/menit per user (Filament multi-factor) |
| User belum verified email | Redirect ke email verification prompt; tidak bisa akses panel |
| Super admin tanpa 2FA confirmed | Redirect wajib ke 2FA setup (invariant) |
| Session di-revoke (device lain) | Session database dihapus; user harus login ulang |
| Passkey invalid (attestation gagal) | Pesan error dari Breezy; tidak ada passkey tersimpan |
| Passkey relying party mismatch | Breezy tolak; login gagal dengan pesan generik |

## 5. Testing

### Unit (`tests/Unit/Security/`)
- `SecurityEventTypeTest` — enum value/format konsisten.
- `SecurityEventRecorderTest` — record menyimpan event + metadata + ip/user_agent fallback.
- `SecurityEventOccurredTest` — event payload.

### Feature (`tests/Feature/Auth/`)
- `LoginTest` — login sukses, login gagal, rate limit, logout.
- `PasswordResetTest` — request reset, reset dengan token, invalid token.
- `EmailVerificationTest` — redirect bila belum verified, verifikasi sukses.
- `TwoFactorTest` — enable, confirm, login dengan TOTP, recovery code, disable, regenerate codes.
- `PasskeyTest` — register (mocked WebAuthn), revoke, login via passkey (mocked).
- `BrowserSessionsTest` — list sessions, revoke session, revoke other sessions.
- `SecurityEventRecordingTest` — login sukses/gagal mencatat event; password change,
  2FA enable/disable, passkey register/revoke, session revoked mencatat event (via override komponen).
- `SuperAdminTwoFactorTest` — super admin tanpa 2FA di-redirect; dengan 2FA bisa masuk.

### Arch (`tests/Arch/`)
- `CoreArchTest` update — `Core\Security\*` tidak mengimpor `App\*` / Filament.
- Core event → listener aplikasi pattern.

## 6. Out of Scope (M5)

- UI Settings auth (TODO §9.2) — kebijakan via config; migration path ke M7.
- RBAC detail (TODO §8) — Shield sudah aktif; role/permission detail M6.
- Passkey auto-prompt UX lanjutan (`auto_prompt` tetap false).
- Sanctum tokens UI (Breezy `enableSanctumTokens`) — tidak diminta TODO §7; skip untuk M5.
- Custom cryptography / external IdP.

## 7. Future Migration Path (M7 Settings)

Ketika Settings System (TODO §9) diimplementasikan:
1. `core/Config/core.php` blok `auth` menjadi **default** untuk typed settings.
2. Security settings page (System → Security) membaca/menulis setting: `security.two_factor_force`,
   `security.session_lifetime`, `security.password_rules`, `security.rate_limit_login`.
3. `AdminPanelProvider` membaca dari settings repository dengan fallback config default.
4. Kebijakan "super admin wajib 2FA" tetap invariant (bisa jadi setting global `super_admin_2fa_required`).

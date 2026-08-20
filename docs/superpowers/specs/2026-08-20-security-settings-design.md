# Security Settings — Design Spec

> Milestone M7 (TODO §9.2 System Settings — Security). Berdiri di atas Settings System
> (M7, spec `2026-08-18-settings-system-design.md`) dan Authentication/Security (M5,
> spec `2026-08-16-authentication-security-design.md`).
>
> Source of truth: `docs/PRD.md` §22, §23, §25, §27; `docs/TODO.md` §7.2, §9.2.

## 1. Tujuan

Menyediakan halaman **Security Settings** (System tier) yang menyimpan kebijakan
keamanan instance-wide ke tabel `settings` lewat arsitektur Settings yang sudah ada
(registry + repository + cascading). Mengikuti preseden Application settings (TODO
§9.2): **storage + UI saja — runtime application di-defer ke follow-up task**, bukan
bagian dari task ini.

Ini melengkapi TODO §7.2 "Implement security settings page — deferred ke M7".

## 2. Keputusan Desain

| Aspek | Keputusan | Alasan |
|---|---|---|
| Scope task | Storage + UI saja; runtime application di-defer | Konsisten preseden TODO §9.2 baris Application settings; menghindari blast radius mengunci admin/login |
| Grup registry | `security` (System tier) | PRD §27 menempatkan Security di bawah System settings |
| Key | 3 key: `security.two_factor_required`, `security.password_min_length`, `security.password_require_complexity` | Fokus pada 2FA policy (PRD §23) + password policy; tidak spekulatif di luar PRD |
| Type | `bool` / `int` | Sudah didukung `DatabaseSettingsRepository::cast()` — tanpa perubahan repository |
| Scope key | Hanya `SettingScope::System` | Kebijakan keamanan bersifat instance-wide, bukan per-org/unit/user |
| Default `two_factor_required` | `(bool) env('AUTH_2FA_FORCE', false)` | Nilai registry mencerminkan kebijakan runtime saat ini (M5) |
| Permission | `view:settings` / `update:settings` | Konsisten spec settings-system §2 — satu subject untuk semua halaman settings |
| Halaman | `app/Filament/Pages/Settings/SecuritySettings.php` baru | Mengikuti pola `ApplicationSettings` (System tier) |
| Nav | `navigationSort = 52`, group `nav.administration` | Setelah ApplicationSettings (50) dan BrandingSettings (51) |

## 3. Arsitektur

### 3.1 Registry — `core/Config/core.php`, grup `security`

```php
'security.two_factor_required' => [
    'type' => 'bool',
    'default' => (bool) env('AUTH_2FA_FORCE', false),
    'scopes' => [SettingScope::System],
    'group' => 'security',
],
'security.password_min_length' => [
    'type' => 'int',
    'default' => 8,
    'scopes' => [SettingScope::System],
    'group' => 'security',
],
'security.password_require_complexity' => [
    'type' => 'bool',
    'default' => true,
    'scopes' => [SettingScope::System],
    'group' => 'security',
],
```

- Tidak ada env var baru — hanya memakai `AUTH_2FA_FORCE` yang sudah ada untuk default.
- Tidak ada migration baru — tabel `settings` existing (ADR-011).

### 3.2 Halaman — `app/Filament/Pages/Settings/SecuritySettings.php`

Mengikuti pola `ApplicationSettings` persis:

- `protected string $view = 'filament.pages.settings.security-settings'`
- `protected static ?int $navigationSort = 52`
- `getNavigationGroup()` → `trans('nav.administration')`
- `canAccess()` → `(bool) Auth::user()?->can('view:settings')`
- `mount()`: untuk tiap key `keysInGroup('security')`, isi `$this->data[$field]` dari
  `getForScope($key, SettingScope::System)` fallback ke `definition($key)['default']`,
  lalu `$this->form->fill($this->data)`
- `form(Schema $schema)`:
  - `Toggle::make('security_two_factor_required')->label('Wajibkan 2FA')`
    — helperText: super admin selalu wajib 2FA
  - `TextInput::make('security_password_min_length')->label('Panjang Minimum Password')`
    — `->numeric()->minValue(6)->maxValue(128)`
  - `Toggle::make('security_password_require_complexity')->label('Wajib Password Kompleks')`
    — helperText: mixedCase + numbers + symbols
  - `->statePath('data')`
- `save()`: `abort_unless(Auth::user()?->can('update:settings'), 403)`; untuk tiap key
  `keysInGroup('security')`, `$repository->set($key, $state[$field], SettingScope::System)`;
  notifikasi sukses "Pengaturan disimpan".

### 3.3 View — `resources/views/filament/pages/settings/security-settings.blade.php`

Salinan pola `application-settings.blade.php`:

```blade
<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
        <div class="mt-6">
            <x-filament::button type="submit">
                {{ __('Simpan') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
```

## 4. Error Handling

| Kondisi | Perilaku |
|---|---|
| Akses halaman tanpa `view:settings` | `canAccess()` false → 403 (pola Filament) |
| `save()` tanpa `update:settings` | `abort_unless(..., 403)` |
| `security_password_min_length` di luar 6–128 | Validasi form `minValue`/`maxValue` → error form |
| Key tak terdaftar | Tidak mungkin — halaman iterasi `keysInGroup('security')` |

## 5. Testing

`tests/Feature/Settings/SecuritySettingsPageTest.php` (pola ApplicationSettingsPageTest,
PHPUnit class-style, `RefreshDatabase`, namespace `Tests\Feature\Settings`):

1. Page accessible dengan `view:settings`.
2. Page forbidden tanpa `view:settings`.
3. `save()` persists ketiga key via `SettingsRepository` — verifikasi tipe:
   `assertSame(true, ...)` untuk bool, `assertSame(10, ...)` untuk int (bukan string).
4. `save()` forbidden tanpa `update:settings`.

## 6. Dokumentasi

- `docs/TODO.md` §9.2: baris "Security settings" → `[x]`, dengan catatan runtime
  application di-defer ke follow-up task.
- `docs/conventions/settings.md`: tambahkan contoh key grup `security` di bagian
  "Menambah Field Baru" (opsional, sejalan konvensi yang sudah ada).
- `docs/conventions/environment.md`: catat mirror `AUTH_2FA_FORCE` →
  `security.two_factor_required` (default registry), agar dokumentasi env konsisten.

## 7. Out of Scope

- **Runtime application** — middleware `ForceSuperAdminTwoFactor` tetap memakai
  `config('core.auth.two_factor.force')`; password rules tetap
  `config('core.auth.password.rules')`. Wiring nilai tersimpan ke runtime = follow-up task.
- **Super admin 2FA paksa** — tetap hardcoded `super_admin_forced => true`, tidak berubah.
- Localization / Mail / Storage settings (TODO §9.2) — task terpisah.
- Migration / perubahan `DatabaseSettingsRepository` / `SettingsRegistry` — tidak ada.

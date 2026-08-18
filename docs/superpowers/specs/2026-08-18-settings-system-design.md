# Settings System — Design Spec

> Milestone M7 (TODO §9.1, sebagian §9.2). Berdiri di atas Database Foundation (M2, ADR-011)
> dan Organizational Context (M4).
>
> Source of truth: `docs/PRD.md` §15, §17, §27, §56; ADR-007, ADR-010, ADR-011.

## 1. Tujuan

Menyediakan domain layer di atas tabel `settings` (sudah ada sejak M2, ADR-011) yang
memungkinkan aplikasi menyimpan dan membaca konfigurasi bertingkat sesuai PRD §27:

```text
System
  ↓
Organization
  ↓
Organizational Unit
  ↓
User
```

Spec ini mencakup **arsitektur penuh** (registry, resolusi scope, cache, kontrak) dan
**satu contoh nyata**: tier System, grup `application` (menggantikan scaffold
`app/Filament/Pages/GeneralSettings.php` dari package `inerba/filament-db-config`).

Field System lainnya (Security, Localization, Mail, Storage) serta tier
Organization/Unit/User (§9.3–§9.5) memakai arsitektur yang sama tapi dikerjakan sebagai
task lanjutan di luar spec ini (lihat §10 Out of Scope).

## 2. Keputusan Desain

| Aspek | Keputusan | Alasan |
|---|---|---|
| Storage | Tabel `settings` existing (M2, ADR-011) — tidak ada migration baru untuk schema inti | Sudah locked di ADR-011 §3: scope nullable `organization_id`/`organizational_unit_id`/`user_id` |
| Resolusi antar tier | Cascading fallback: **User → Unit → Organization → System → default registry** | Key seperti `timezone` relevan di Unit *dan* User (PRD §27); satu key bisa di-override makin spesifik |
| Tier yang diizinkan per key | Deklaratif di `SettingsRegistry` (whitelist `scopes` per key) | Tidak semua key masuk akal di semua tier (mis. `app.name` cuma System) |
| Tipe data | Cast dideklarasikan di registry (`type`), bukan `getInt()/getBool()` per pemanggilan | Sumber kebenaran terpusat, cegah key liar/typo (`unknownKey`) |
| Cache | Per-tier (raw value), bukan per-hasil-cascade | Cache driver default project adalah `database` (tanpa tags); invalidasi presisi per tier saat `set()`/`forget()`, tanpa window basi |
| Enum scope | `Core\Settings\Enums\SettingScope` (System\|Organization\|Unit\|User) — **baru**, bukan reuse `Core\Enums\DataScope` | `DataScope` (M6) untuk kepemilikan record model (PRD §17, 3 kategori); `SettingScope` untuk tier resolusi Settings (PRD §27, 4 tier termasuk User) — konsep berbeda |
| Context source | `Core\Contracts\OrganizationContext` / `OrganizationalUnitContext` (M4) + `Auth::user()` | Sudah ada, bebas Filament (PRD §15) |
| Struktur modul | `core/Settings/` mengikuti pola `core/Context/` (ServiceProvider + Manager + Contract di `core/Contracts/`) | Konsisten ADR-002, konvensi milestone sebelumnya |
| Package `inerba/filament-db-config` | **Berhenti dipakai** (halaman diganti versi native), **tetap terpasang** di composer.json | Keputusan eksplisit sesi ini — penghapusan package (ADR-007) ditunda ke siklus terpisah, di luar scope spec ini |
| Permission | `view:settings` / `update:settings` (format `action:subject`, PRD §19) — satu subject untuk semua tier | Otorisasi dibedakan oleh scope target (mis. field mana yang boleh diedit di halaman tier tsb), bukan oleh nama permission — konsisten pola `Scope::can()` M6 |

## 3. Arsitektur

### 3.1 Struktur File

```
core/
├── Contracts/
│   └── SettingsRepository.php          # interface: get, getForScope, set, forget
├── Exceptions/
│   └── SettingsException.php           # unknownKey(), invalidScope()
└── Settings/
    ├── SettingsServiceProvider.php     # bind SettingsRepository → DatabaseSettingsRepository; register registry
    ├── SettingsRegistry.php            # daftar key: type, default, scopes, group (murni data, tanpa I/O)
    ├── DatabaseSettingsRepository.php  # cascading resolve + cache per-tier + persist ke tabel `settings`
    └── Enums/
        └── SettingScope.php            # System | Organization | Unit | User
```

### 3.2 Enum `SettingScope`

```php
// core/Settings/Enums/SettingScope.php
enum SettingScope: string
{
    case User = 'user';
    case Unit = 'unit';
    case Organization = 'organization';
    case System = 'system';
}
```

Urutan case merepresentasikan urutan cascading (paling spesifik → paling umum).

### 3.3 Pemetaan Tier → Kolom `settings`

| Tier | `organization_id` | `organizational_unit_id` | `user_id` |
|---|---|---|---|
| System | null | null | null |
| Organization | terisi | null | null |
| Unit | terisi (dari unit) | terisi | null |
| User | null | null | terisi |

User tier lepas dari org/unit — preferensi personal (PRD §27: Language, Timezone, Theme,
Notifications adalah milik user, bukan kontekstual ke unit tertentu).

### 3.4 `SettingsRegistry`

```php
// core/Settings/SettingsRegistry.php
final class SettingsRegistry
{
    /** @var array<string, array{type: string, default: mixed, scopes: SettingScope[], group: string}> */
    private array $definitions = [];

    public function register(array $definitions): void; // merge, dipanggil dari SettingsServiceProvider::boot()
    public function definition(string $key): array;      // throw SettingsException::unknownKey()
    public function keysInGroup(string $group): array;
    public function has(string $key): bool;
    public function allowsScope(string $key, SettingScope $scope): bool;
}
```

Isi awal (grup `application`, dari `core/Config/core.php['settings']['definitions']`):

```php
'app.name' => [
    'type' => 'string',
    'default' => null, // null → fallback ke config('app.name')
    'scopes' => [SettingScope::System],
    'group' => 'application',
],
'app.locale' => [
    'type' => 'string',
    'default' => 'id',
    'scopes' => [SettingScope::System],
    'group' => 'application',
],
'app.timezone' => [
    'type' => 'string',
    'default' => 'Asia/Jakarta',
    'scopes' => [SettingScope::System, SettingScope::Unit, SettingScope::User],
    'group' => 'application',
],
```

`app.timezone` sengaja mengizinkan 3 tier untuk membuktikan cascading resolution
end-to-end walau UI Unit/User belum dibangun di spec ini — nilai bisa di-set langsung
lewat `SettingsRepository::set()` di test/tinker sebagai bukti arsitektur bekerja.

### 3.5 Kontrak `SettingsRepository`

```php
// core/Contracts/SettingsRepository.php
interface SettingsRepository
{
    /** Resolusi cascading pakai context saat ini (Auth::user(), unit/org aktif). */
    public function get(string $key, mixed $default = null): mixed;

    /** Baca nilai mentah SATU tier saja, tanpa cascade. Untuk hydrate form edit. */
    public function getForScope(string $key, SettingScope $scope, ?string $scopeId = null): mixed;

    public function set(string $key, mixed $value, SettingScope $scope = SettingScope::System, ?string $scopeId = null): void;

    public function forget(string $key, SettingScope $scope = SettingScope::System, ?string $scopeId = null): void;
}
```

- `scopeId` = id target tier (`organization_id` untuk Organization, `organizational_unit_id`
  untuk Unit, `user_id` untuk User). Untuk `SettingScope::System`, `scopeId` selalu `null`.
- Implementasi: `Core\Settings\DatabaseSettingsRepository` (final class, di-bind singleton
  lewat `SettingsServiceProvider`).

### 3.6 Alur Resolusi `get()`

1. Ambil `definition($key)` dari registry — key tak terdaftar → `SettingsException::unknownKey()`.
2. Ambil context saat ini: `Auth::user()?->id`, `app(OrganizationalUnitContext::class)->currentId()`,
   `app(OrganizationContext::class)->organizationId()`.
3. Iterasi tier `User → Unit → Organization → System`, **hanya tier yang ada di
   `definition['scopes']`**.
4. Tiap tier: cek cache `settings.raw.{key}.{tier}.{scopeId}` → miss → query
   `settings` table (`WHERE key = ? AND <kolom scope sesuai tier> = ?`) → simpan ke cache
   (TTL `core.settings.cache_ttl`) → jika ada baris, cast sesuai `type` dan **return**.
5. Tidak ketemu di tier manapun → return `$default` param, atau `definition['default']`
   bila `$default` tidak dikirim.

### 3.7 Caching

- Cache **per-tier mentah**, bukan per-hasil-cascade — supaya `set()`/`forget()` bisa
  invalidasi tepat satu cache key tanpa perlu cache tags (cache driver default project:
  `database`, lihat `config/cache.php`).
- Key cache: `settings.raw.{key}.{tier-value}.{scopeId ?? 'null'}`.
- `set()`/`forget()` di tier X: tulis/hapus baris di tabel `settings`, lalu
  `Cache::forget()` **hanya** cache key tier X yang bersangkutan.
- TTL: `core.settings.cache_ttl` (default 3600 detik — aman karena invalidasi presisi).

## 4. Error Handling

| Kondisi | Perilaku |
|---|---|
| `get()`/`set()` dengan key tak terdaftar di registry | `SettingsException::unknownKey($key)` |
| `set()` ke tier yang tidak ada di `definition['scopes']` | `SettingsException::invalidScope($key, $scope)` |
| `set()`/`forget()` ke `SettingScope::System` dengan `$scopeId` bukan null | `SettingsException::invalidScope()` (System tidak butuh id) |
| `set()`/`forget()` ke Organization/Unit/User tanpa `$scopeId` | `SettingsException::invalidScope()` |
| Tidak ada baris tersimpan di tier manapun saat `get()` | Bukan exception — fallback ke `$default`/registry default |

## 5. Contoh Nyata — Application Settings (System tier)

`app/Filament/Pages/Settings/ApplicationSettings.php` — Filament Page native
(menggantikan `app/Filament/Pages/GeneralSettings.php` yang extends
`Inerba\DbConfig\AbstractPageSettings`):

- `mount()`: untuk tiap key hasil `SettingsRegistry::keysInGroup('application')`,
  isi form dari `getForScope($key, SettingScope::System)`.
- `save()`: untuk tiap field submit, `Settings::set($key, $value, SettingScope::System)`.
- Field awal: `app.name`, `app.locale`, `app.timezone`.
- Otorisasi: `view:settings` untuk akses halaman, `update:settings` untuk `save()`
  (Shield permission tab "Custom"/"Pages", pola sama seperti page Filament lain di project
  ini — detail pengkabelan diverifikasi via `search-docs` saat implementasi).

`app/Filament/Pages/GeneralSettings.php` (scaffold lama) **dihapus** dan digantikan file
di atas. Package `inerba/filament-db-config` **tetap ada di `composer.json`** tapi tidak
direferensikan kode manapun setelah perubahan ini (lihat §2 tabel keputusan — penghapusan
package ditunda).

## 6. Konfigurasi

`core/Config/core.php` — penambahan:

```php
'providers' => [
    ContextServiceProvider::class,
    SettingsServiceProvider::class,   // baru
],
'settings' => [
    'cache_ttl' => (int) env('SETTINGS_CACHE_TTL', 3600),
    'definitions' => [
        // app.name, app.locale, app.timezone — lihat §3.4
    ],
],
```

Tidak ada environment variable baru yang wajib — `SETTINGS_CACHE_TTL` opsional dengan
default aman.

## 7. Testing

```text
tests/Unit/Settings/
├── SettingsRegistryTest.php           # register(), keysInGroup(), definition() unknownKey, allowsScope()
└── DatabaseSettingsRepositoryTest.php # urutan cascading per tier, type casting, cache hit vs query DB

tests/Feature/Settings/
├── SettingsCascadeTest.php            # set System + User key sama → get() ambil paling spesifik; hapus User → jatuh ke System
├── SettingsCacheInvalidationTest.php  # set() satu tier → cache tier itu ter-invalidate, cache tier lain tidak terganggu
└── ApplicationSettingsPageTest.php    # Livewire: mount/fill/call save, assertDatabaseHas('settings', ...), permission view:settings/update:settings ditolak untuk role tanpa permission
```

- Pola existing: PHPUnit class-style, `RefreshDatabase`, namespace
  `Tests\Unit\Settings` / `Tests\Feature\Settings`.

## 8. Dokumentasi

- `docs/conventions/settings.md` (baru): cara pakai `Settings::get()/set()` dari
  Action/Job/Console (bebas Filament, PRD §15), cara daftar key baru di registry, aturan
  cascading & cache — mirror struktur `docs/conventions/scope.md`.
- Update `docs/TODO.md`:
  - §9.1 Settings Architecture → semua `[x]`.
  - §9.2 System Settings → hanya baris "Application settings" → `[x]`; Security/
    Localization/Mail/Storage tetap `[ ]`.

## 9. Non-Filament Usage

Konsisten PRD §15 (context independent dari Filament), Settings dipakai lewat contract:

```php
app(SettingsRepository::class)->get('app.timezone');
```

atau facade tipis `Settings::get()/set()` yang membungkus binding container yang sama —
dapat dipanggil dari Action, Service, Job, Console Command tanpa Filament ter-load.

## 10. Out of Scope

- Field System selain grup `application` (Security, Localization, Mail, Storage) — task
  lanjutan, memakai arsitektur yang sama (registry + halaman baru per grup).
- §9.3 Organization Settings, §9.4 Organizational Unit Settings, §9.5 User Settings —
  arsitektur (cascading, `SettingScope::Organization/Unit/User`) sudah mendukung, tapi
  belum ada registry key maupun halaman Filament untuk tier ini.
- Penghapusan package `inerba/filament-db-config` dari `composer.json` dan migration
  drop tabel `db_config` lama — ditunda ke siklus terpisah (keputusan eksplisit §2).
- White Label / Branding (TODO §10, package `ashrafic/filament-white-label`) — domain
  berbeda (asset/file, bukan typed KV settings), spec terpisah meski nanti bisa memakai
  tabel `settings` yang sama sebagai storage.
- Security settings page yang disebut di TODO §7.2 ("deferred ke M7") — termasuk dalam
  "field System lainnya" di atas, bukan bagian contoh nyata spec ini.

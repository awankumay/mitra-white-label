# Settings Conventions

> Settings System — lihat spec `docs/superpowers/specs/2026-08-18-settings-system-design.md`.

## Tier & Kolom

| Tier | `organization_id` | `organizational_unit_id` | `user_id` |
|---|---|---|---|
| System | null | null | null |
| Organization | terisi | null | null |
| Unit | terisi | terisi | null |
| User | null | null | terisi |

Enum: `Core\Settings\Enums\SettingScope` (`system`, `organization`, `unit`, `user`).

## Registry

Setiap key WAJIB terdaftar di `Core\Settings\SettingsRegistry` sebelum dipakai — biasanya
lewat `core/Config/core.php['settings']['definitions']`, di-load `SettingsServiceProvider::boot()`:

```php
'app.timezone' => [
    'type' => 'string',           // string|int|bool|float|array
    'default' => 'Asia/Jakarta',
    'scopes' => [SettingScope::System, SettingScope::Unit, SettingScope::User],
    'group' => 'application',     // dipakai halaman Filament untuk kelompokkan field
],
```

Key tak terdaftar → `SettingsException::unknownKey()`. Scope tak diizinkan di key tsb →
`SettingsException::invalidScope()`.

## Membaca & Menulis

```php
app(SettingsRepository::class)->get('app.timezone');
// cascading: User → Unit → Organization → System → default registry (pakai context saat ini)

app(SettingsRepository::class)->getForScope('app.timezone', SettingScope::Unit, $unitId);
// nilai mentah SATU tier saja, null jika belum di-set eksplisit (tanpa fallback)

app(SettingsRepository::class)->set('app.timezone', 'Asia/Makassar', SettingScope::Unit, $unitId);
app(SettingsRepository::class)->forget('app.timezone', SettingScope::Unit, $unitId);
```

Bebas Filament — bisa dipanggil dari Action, Service, Job, Console Command (PRD §15).

## Cache

Cache per-tier mentah (bukan per-hasil-cascade), TTL `core.settings.cache_ttl`. `set()`/
`forget()` selalu invalidasi tepat cache tier yang ditulis — tidak perlu cache tags.

## Menambah Field Baru

1. Daftarkan key di `core/Config/core.php['settings']['definitions']`.
2. Tambahkan komponen form di halaman Filament terkait — nama field pakai underscore
   (`str_replace('.', '_', $key)`), jangan titik (bentrok dengan nested-array statePath
   Livewire).
3. `mount()`/`save()` halaman memakai `SettingsRegistry::keysInGroup()` — tidak perlu
   diubah kalau key baru masuk grup yang sudah ada.

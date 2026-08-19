# Branding (White Label) — Design Spec

**Terkait:** TODO.md §10 (White Label / Branding), PRD.md §28.
**Dependensi:** Settings System (M7) — `Core\Settings\*`, `core/Config/core.php['settings']`, tabel `settings`.
**Status:** Draft — menunggu review user.

---

## 1. Tujuan

Menyediakan Core Branding yang memungkinkan admin organisasi mengkustomisasi
identitas visual aplikasi (nama perusahaan, logo, warna, footer) dan langsung
terlihat efeknya di panel Filament, halaman login, serta header email
transaksional — dibangun di atas infrastruktur Settings (M7) yang sudah ada,
tanpa migrasi tabel baru.

## 2. Keputusan Desain

| # | Keputusan | Pilihan |
|---|---|---|
| 1 | Hubungan dengan `ashrafic/filament-white-label` | Dibangun paralel; package lama **tetap aktif**. Panggilan branding baru diletakkan **setelah** `->whiteLabel()` di `AdminPanelProvider` sehingga menang (last-call-wins/merge-by-key di Filament Panel config). Migrasi penuh + uninstall package jadi milestone terpisah. |
| 2 | Struktur data | Grup key baru `'branding'` di `SettingsRegistry` yang sudah ada, disimpan di tabel `settings` (M7) — **tidak ada tabel/registry baru**. |
| 3 | Field v1 | `branding.company_name`, `branding.logo`, `branding.dark_logo`, `branding.favicon`, `branding.primary_color`, `branding.secondary_color`, `branding.footer_text`. **Application Name** tetap pakai `app.name` yang sudah ada (M7) — tidak diduplikasi. |
| 4 | Scope per key | `[SettingScope::Organization, SettingScope::System]` untuk semua key branding (cascading Org → System), sesuai PRD §28 "Organization branding = primary requirement". |
| 5 | "Login Branding" / "Email Branding" | **Bukan field terpisah** — field utama (logo, warna, company name) diterapkan otomatis ke halaman login dan header email, bukan diinput dua kali. |
| 6 | UI edit | Satu Filament Page `BrandingSettings`, menyimpan ke `SettingScope::Organization` (organisasi aktif user yang login). Tidak ada UI System-tier di v1 (System tetap fallback definitif via default registry). |
| 7 | Resolusi Organization untuk konteks anonim (login/email) | Helper baca-branding (`BrandingResolver`) fallback ke `Organization::query()->value('id')` (satu-satunya org di DB) saat `OrganizationContext::organizationId()` null — hanya untuk baca, bukan tulis. |
| 8 | File (logo/dark_logo/favicon) | Disimpan sebagai **path string** di value settings (tipe cast `'string'` yang sudah ada di `DatabaseSettingsRepository::cast()` — **tidak perlu tipe `'file'` baru**). Upload via `FileUpload` Filament, disk `public` (config baru `core.branding.disk`). File lama dihapus manual di `save()` saat diganti. |
| 9 | Runtime application | Penuh — `brandName()`, `brandLogo()`, `darkModeBrandLogo()`, `favicon()`, `colors()` panel dibaca dinamis via closure dari `BrandingResolver`; header email markdown Laravel di-override untuk menyisipkan logo/nama (bukan warna tombol — lihat §7 Out of Scope). |
| 10 | Permission | Baru: `view:branding`, `update:branding` (format `action:subject`, ditambah ke `config/filament-shield.php['custom_permissions']`, sama pola dengan `view:settings`/`update:settings`). |

## 3. Arsitektur

### 3.1 Peta File

```text
core/Branding/
├── BrandingResolver.php          # baca branding.* dengan fallback org-anonim + resolve URL file
└── BrandingServiceProvider.php   # bind BrandingResolver, register definisi 'branding' ke SettingsRegistry

app/Filament/Pages/Settings/
└── BrandingSettings.php          # form edit, simpan ke SettingScope::Organization

resources/views/filament/pages/settings/
└── branding-settings.blade.php

resources/views/vendor/mail/html/
└── header.blade.php              # override header email bawaan Laravel (logo + nama)

core/Config/core.php              # + provider BrandingServiceProvider, + key 'branding' (disk)
config/filament-shield.php        # + 'view:branding', 'update:branding'
app/Providers/Filament/AdminPanelProvider.php  # + brandName/brandLogo/darkModeBrandLogo/favicon/colors dinamis, setelah ->whiteLabel()
```

### 3.2 Mengapa `BrandingResolver` Terpisah dari `SettingsRepository::get()`

`SettingsRepository::get()` meresolusi tier Organization lewat
`OrganizationContext::organizationId()` — yang berbasis **user yang login**.
Untuk halaman login/email (belum ada user), `organizationId()` selalu `null`,
sehingga cascading bawaan otomatis lompat ke System tier — tidak pernah
mengambil branding organisasi. `BrandingResolver` menyisipkan fallback
eksplisit (Keputusan #7) tanpa mengubah perilaku cascading generik
`Core\Settings` yang dipakai fitur lain (mis. `app.timezone`):

```php
final class BrandingResolver
{
    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly SettingsRegistry $registry,
        private readonly OrganizationContext $context,
    ) {}

    public function get(string $key): mixed
    {
        $orgId = $this->context->organizationId() ?? Organization::query()->value('id');

        if ($orgId !== null) {
            $value = $this->settings->getForScope($key, SettingScope::Organization, $orgId);
            if ($value !== null) {
                return $value;
            }
        }

        return $this->settings->getForScope($key, SettingScope::System)
            ?? $this->registry->definition($key)['default'];
    }

    public function url(string $key): ?string
    {
        $path = $this->get($key);

        return $path ? Storage::disk(config('core.branding.disk'))->url($path) : null;
    }
}
```

`BrandingServiceProvider::boot()` mendaftarkan definisi `'branding.*'` ke
`SettingsRegistry` yang sama (dipakai bersama `SettingsServiceProvider`),
mengikuti pola `keysInGroup('branding')` seperti `keysInGroup('application')`
di `ApplicationSettings`.

### 3.3 Definisi Registry

Didaftarkan oleh `BrandingServiceProvider::boot()`:

```php
'branding.company_name'    => ['type' => 'string', 'default' => null, 'scopes' => [Organization, System], 'group' => 'branding'],
'branding.logo'            => ['type' => 'string', 'default' => null, 'scopes' => [Organization, System], 'group' => 'branding'],
'branding.dark_logo'       => ['type' => 'string', 'default' => null, 'scopes' => [Organization, System], 'group' => 'branding'],
'branding.favicon'         => ['type' => 'string', 'default' => null, 'scopes' => [Organization, System], 'group' => 'branding'],
'branding.primary_color'   => ['type' => 'string', 'default' => null, 'scopes' => [Organization, System], 'group' => 'branding'],
'branding.secondary_color' => ['type' => 'string', 'default' => null, 'scopes' => [Organization, System], 'group' => 'branding'],
'branding.footer_text'     => ['type' => 'string', 'default' => null, 'scopes' => [Organization, System], 'group' => 'branding'],
```

`core.branding.disk` (config baru): `env('BRANDING_DISK', 'public')`.

### 3.4 Wiring Panel

Ditambahkan setelah `->whiteLabel()` di `AdminPanelProvider`:

```php
->brandName(fn () => app(BrandingResolver::class)->get('branding.company_name') ?? config('app.name'))
->brandLogo(fn () => app(BrandingResolver::class)->url('branding.logo'))
->darkModeBrandLogo(fn () => app(BrandingResolver::class)->url('branding.dark_logo'))
->favicon(fn () => app(BrandingResolver::class)->url('branding.favicon'))
->colors(fn () => array_filter([
    'primary' => ($hex = app(BrandingResolver::class)->get('branding.primary_color')) ? Color::hex($hex) : null,
    'secondary' => ($hex = app(BrandingResolver::class)->get('branding.secondary_color')) ? Color::hex($hex) : null,
]))
```

`colors()` bersifat **additive per-key** (`HasColors::$colors` array of sets,
di-merge saat `getColors()`), sedangkan `brandLogo()`/`favicon()`/dst.
bersifat **scalar overwrite** (last-call-wins). Penempatan setelah
`->whiteLabel()` cukup membuat closure ini menang tanpa menyentuh package
lama.

**Halaman login otomatis ikut ter-branding** — Filament merender login page
memakai `filament()->getBrandLogo()`/`getBrandName()`/`getFavicon()` dari
panel yang sama (`vendor/filament/filament/resources/views/components/logo.blade.php`),
sehingga tidak perlu wiring terpisah untuk "Login Branding".

### 3.5 Email Branding

Publish `resources/views/vendor/mail/html/header.blade.php` bawaan Laravel,
override untuk merender logo (via `BrandingResolver::url('branding.logo')`)
+ nama, fallback ke teks nama aplikasi kalau logo kosong. Cakupan v1 hanya
header (logo + nama) — pewarnaan tombol/tema CSS email tidak disentuh (lihat
§7 Out of Scope).

## 4. Error Handling & Edge Case

- **Admin tanpa organisasi** (`OrganizationContext::has()` false): `BrandingSettings::mount()` menampilkan `Notification::make()->warning()` dan form di-`disabled()`; `save()` melakukan `abort_unless($orgId !== null, ...)` sebagai pengaman terakhir.
- **Upload logo gagal/terlalu besar**: divalidasi native oleh `FileUpload` Filament (`->image()`, `->maxSize()`); tidak perlu penanganan khusus.
- **Ganti logo → file lama jadi orphan**: `save()` membandingkan path lama (`getForScope()`) vs path baru dari state; jika berbeda dan file lama ada di disk, `Storage::disk(...)->delete($old)` dipanggil sebelum `set()` menulis path baru.
- **`primary_color`/`secondary_color` kosong**: closure `colors()` di panel wiring memakai `array_filter` sehingga key kosong tidak menimpa `Color::Taupe` hardcoded yang sudah ada di `AdminPanelProvider`.
- **`Organization::query()->value('id')` null** (instalasi kosong sebelum installer M9 jalan): `BrandingResolver::get()` lanjut ke System tier → default registry (`null`) → panel wiring fallback ke `config('app.name')`/logo kosong/warna hardcoded. Tidak ada exception.
- **Cache staleness setelah `save()`**: sudah otomatis ditangani `DatabaseSettingsRepository::set()` (`forgetCache()` per exact cache key) — konsisten dengan pola M7, tidak perlu penanganan tambahan.
- **`ColorPicker` ditinggal kosong** (`null` di state form): `set()` menyimpan `null`, `BrandingResolver::get()` melanjut ke tier berikutnya seperti biasa.

## 5. Contoh Nyata — `BrandingSettings` Filament Page

```php
namespace App\Filament\Pages\Settings;

use Core\Branding\BrandingResolver;
use Core\Contracts\OrganizationContext;
use Core\Contracts\SettingsRepository;
use Core\Settings\Enums\SettingScope;
use Core\Settings\SettingsRegistry;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read Schema $form
 */
class BrandingSettings extends Page
{
    protected string $view = 'filament.pages.settings.branding-settings';

    protected static ?int $navigationSort = 51;

    protected static ?string $title = 'Branding Settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return trans('nav.administration');
    }

    public static function canAccess(): bool
    {
        return (bool) Auth::user()?->can('view:branding');
    }

    public function mount(): void
    {
        if (! app(OrganizationContext::class)->has()) {
            Notification::make()
                ->warning()
                ->title('Belum ada organisasi aktif')
                ->send();
        }

        $repository = app(SettingsRepository::class);
        $registry = app(SettingsRegistry::class);
        $orgId = app(OrganizationContext::class)->organizationId();

        foreach ($registry->keysInGroup('branding') as $key) {
            $field = str_replace('.', '_', $key);
            $this->data[$field] = ($orgId !== null
                ? $repository->getForScope($key, SettingScope::Organization, $orgId)
                : null) ?? $registry->definition($key)['default'];
        }

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('branding_company_name')->label('Nama Perusahaan'),
                FileUpload::make('branding_logo')->label('Logo')->image()->disk(config('core.branding.disk'))->directory('brand'),
                FileUpload::make('branding_dark_logo')->label('Logo (Dark Mode)')->image()->disk(config('core.branding.disk'))->directory('brand'),
                FileUpload::make('branding_favicon')->label('Favicon')->image()->disk(config('core.branding.disk'))->directory('brand'),
                ColorPicker::make('branding_primary_color')->label('Warna Primer'),
                ColorPicker::make('branding_secondary_color')->label('Warna Sekunder'),
                TextInput::make('branding_footer_text')->label('Teks Footer'),
            ])
            ->statePath('data')
            ->disabled(fn (): bool => ! app(OrganizationContext::class)->has());
    }

    public function save(): void
    {
        $orgId = app(OrganizationContext::class)->organizationId();
        abort_unless(Auth::user()?->can('update:branding') && $orgId !== null, 403);

        $repository = app(SettingsRepository::class);
        $registry = app(SettingsRegistry::class);
        $disk = Storage::disk(config('core.branding.disk'));
        $state = $this->form->getState();

        foreach ($registry->keysInGroup('branding') as $key) {
            $field = str_replace('.', '_', $key);
            $newValue = $state[$field];

            if (in_array($key, ['branding.logo', 'branding.dark_logo', 'branding.favicon'], true)) {
                $old = $repository->getForScope($key, SettingScope::Organization, $orgId);
                if ($old !== null && $old !== $newValue && $disk->exists($old)) {
                    $disk->delete($old);
                }
            }

            $repository->set($key, $newValue, SettingScope::Organization, $orgId);
        }

        Notification::make()->success()->title('Branding disimpan')->send();
    }
}
```

`ColorPicker` dipilih dibanding `TextInput` + regex manual — komponen native
Filament, sudah menghasilkan/validasi hex string langsung sebagai state.

## 6. Konfigurasi

**`core/Config/core.php`**:

```php
'providers' => [
    ContextServiceProvider::class,
    SettingsServiceProvider::class,
    BrandingServiceProvider::class,   // baru
],
// ...
'branding' => [
    'disk' => env('BRANDING_DISK', 'public'),
],
```

Definisi key `'branding.*'` didaftarkan oleh `BrandingServiceProvider::boot()`
ke `SettingsRegistry`, bukan array literal langsung di `core.php` — supaya
`Core\Branding\*` tetap satu paket logic yang bisa dibaca berdiri sendiri,
mengikuti pola `Core\Context\ContextServiceProvider`.

**`config/filesystems.php`**: tidak perlu perubahan; disk `public` bawaan
Laravel sudah cukup. `BRANDING_DISK` opsional di `.env`/`.env.example`
(default `public`).

**`config/filament-shield.php`**: tambah ke `custom_permissions`:
`'view:branding'`, `'update:branding'`.

**`app/Providers/Filament/AdminPanelProvider.php`**: tambah use statement
`Core\Branding\BrandingResolver`, lalu 5 baris chain
(`brandName`/`brandLogo`/`darkModeBrandLogo`/`favicon`/`colors`) setelah
`->whiteLabel()`.

**`resources/views/vendor/mail/html/header.blade.php`**: publish via
`php artisan vendor:publish --tag=laravel-mail`, override untuk render
`<img>` logo atau teks nama.

## 7. Testing

Mengikuti pola test M7 (`tests/Unit/Settings/*`, `tests/Feature/Settings/*`):

**Unit — `tests/Unit/Branding/BrandingResolverTest.php`**
- `get()` mengembalikan nilai Organization-tier saat user login & org context ada.
- `get()` fallback ke System-tier saat Organization-tier kosong.
- `get()` tanpa user login (anonymous) fallback ke satu-satunya `Organization` di DB.
- `get()` mengembalikan default registry (`null`) saat tidak ada Organization sama sekali di DB.
- `url()` mengembalikan `null` saat path kosong; mengembalikan URL disk yang benar saat path ada.

**Feature — `tests/Feature/Settings/BrandingSettingsPageTest.php`**
- `canAccess()` ditolak tanpa permission `view:branding`.
- Mount mengisi form dari nilai Organization-tier user yang login.
- `save()` menyimpan ke `SettingScope::Organization` milik org user, bukan System.
- `save()` menghapus file lama dari disk saat logo diganti dengan file baru.
- `save()` ditolak (403) tanpa permission `update:branding`.
- `save()` gagal aman saat `OrganizationContext` kosong.

**Feature — `tests/Feature/Branding/PanelBrandingApplicationTest.php`**
- Request ke halaman login (belum autentikasi) merender `brandLogo`/`favicon` hasil fallback org tunggal di DB.
- Setelah login & branding org disimpan, panel admin merender `brandName`/`colors` sesuai nilai tersimpan.

**Feature — `tests/Feature/Branding/EmailBrandingHeaderTest.php`**
- Render view `vendor.mail.html.header` menghasilkan `<img>` saat logo ada, teks company/app name saat logo kosong.

## 8. Out of Scope

- Uninstall `ashrafic/filament-white-label` dan migrasi penuh wiring panel — milestone terpisah.
- UI edit System-tier branding — System tier tetap fallback di registry, tanpa halaman edit di v1.
- Branding di level Organizational Unit (extensible future point per PRD §28) — `SettingScope::Unit` sudah ada di enum tapi key `branding.*` tidak mendaftarkan scope ini di v1.
- Theming warna/tombol pada template email (hanya header logo+nama yang disentuh).
- Dukungan multi-organization sungguhan (fallback "satu-satunya Organization di DB" cukup untuk arsitektur single-org yang dikunci PRD §8).
- Preview/live-preview branding di form sebelum disimpan.
- Custom cropping/resizing gambar (Filament `FileUpload` bawaan sudah cukup).

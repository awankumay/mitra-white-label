# Data Scope Architecture — Design Spec

> Milestone M6 (TODO §6). Berdiri di atas Organizational Context (M4) yang sudah stabil.
> Scope terbatas pada **Data Scope Architecture**; enforcement murni di query layer (bukan global scope / database RLS).
>
> Source of truth: `docs/PRD.md` §8, §17, §18, §20, §44, §57, §64.

## 1. Tujuan

Menyediakan konvensi dan helper untuk membatasi data menurut tiga kategori scope (PRD §17):

- **Global** — System configuration, Feature definitions.
- **Organization-scoped** — Organization profile, Organization settings, Organization branding.
- **Organizational-unit-scoped** — Operational transactions, Branch settings, Site-specific records.

Tidak semua model otomatis unit-scoped; scope adalah **opt-in eksplisit per entity** (PRD §17, §44).

## 2. Keputusan Desain

| Aspek | Keputusan | Alasan |
|---|---|---|
| Mekanisme | Query layer saja (helper + local scope) | Eksplisit, transparan, tanpa global scope tersembunyi; pilihan user |
| Bypass admin | Role `super_admin` | Konsisten PRD §19; satu role yang selalu lolos scope |
| Implementasi helper | `Core\Support\Scope` (statis, tanpa state) | Utilitas generik murni (ADR-002 §Support) |
| Penanda model | Interface `ScopedModel` (marker) | Sinyal eksplisit + deteksi programatik (`instanceof`) |
| Kategori scope | Enum `DataScope` di `core/Enums/` | Enum lintas-domain Core (konvensi directory-structure) |
| Kolom scope | `organization_id` + `organizational_unit_id` (nullable, selalu ada) | Konsisten dengan tabel `settings` (ADR-011) |
| Context source | `Core\Contracts\OrganizationContext` / `OrganizationalUnitContext` | Sudah ada dari M4, bebas Filament |
| Enforcement | Aplikasi (query + policy) | Tanpa triggers/RLS database (ADR-011 pola M3) |
| Konfigurasi baru | Tidak ada | Memakai context (M4) + role `super_admin` (Spatie) yang sudah ada |

## 3. Arsitektur

### 3.1 Struktur File

```
core/
├── Contracts/
│   └── ScopedModel.php             # marker interface
├── Enums/
│   └── DataScope.php               # Global | Organization | Unit
└── Support/
    └── Scope.php                   # helper statis — query scoping + cek akses
```

- `Scope` bebas Filament dan bebas `App\Models\User` — hanya terima primitif (`?string $unitId`, `?string $orgId`) atau `Authenticatable` (untuk cek role).
- Model scoped (di app layer) mengimplementasikan `ScopedModel` dan boleh memakai local scope convenience.

### 3.2 Enum `DataScope`

```php
// core/Enums/DataScope.php
enum DataScope: string
{
    case Global = 'global';          // tanpa kolom scope
    case Organization = 'organization'; // organization_id relevan
    case Unit = 'unit';              // organization_id + organizational_unit_id
}
```

### 3.3 Interface `ScopedModel`

```php
// core/Contracts/ScopedModel.php
interface ScopedModel
{
    // Marker — tanpa method wajib.
    // Implementasi: model yang punya kolom organization_id / organizational_unit_id.
}
```

### 3.4 Helper `Scope`

```php
// core/Support/Scope.php
final class Scope
{
    // ——— Query scoping ———

    /** where organizational_unit_id = ? — null → no-op */
    public static function unit(Builder $query, ?string $unitId): Builder;

    /** where organization_id = ? — null → no-op */
    public static function organization(Builder $query, ?string $orgId): Builder;

    /** where organizational_unit_id IN (units user) */
    public static function userUnits(Builder $query, Authenticatable $user): Builder;

    /** where organization_id IN (org milik user) */
    public static function userOrganizations(Builder $query, Authenticatable $user): Builder;

    /** units user ATAU org user — entity dua level */
    public static function userScope(Builder $query, Authenticatable $user): Builder;

    // ——— Akses / bypass ———

    /** true jika super_admin ATAU unit di-assign ke user */
    public static function can(Authenticatable $user, ?string $unitId): bool;

    /** true jika user punya role super_admin */
    public static function isSuperAdmin(Authenticatable $user): bool;
}
```

## 4. Konvensi Model & Query

### 4.1 Kolom scope

- Model scoped (Organization/Unit) punya dua kolom nullable: `organization_id` (FK `organizations`) dan `organizational_unit_id` (FK `organizational_units`). "Selalu ada" di sini berarti **pada model scoped, kedua kolom selalu ada** (bukan satu kolom kadang hilang) — konsisten dengan tabel `settings`.
- Invariant: jika `organizational_unit_id` terisi → `organization_id` terisi dan konsisten (unit → organization).
- Model non-scoped (Global) tidak punya kolom ini sama sekali.

### 4.2 Local scope convenience (opsional, app layer)

```php
// app/Models/Product.php — unit-scoped
class Product extends Model implements ScopedModel
{
    public function scopeInCurrentUnit(Builder $query): Builder
    {
        return Scope::unit($query, app(OrganizationalUnitContext::class)->currentId());
    }

    public function scopeInCurrentOrganization(Builder $query): Builder
    {
        return Scope::organization($query, app(OrganizationContext::class)->organizationId());
    }
}
```

- Local scope hanyalah convenience; repository/action boleh langsung `Scope::unit($query, $id)`.
- Di CLI/queue tanpa session → context null → `Scope::unit(null)` no-op (tidak memfilter), konsisten dengan perilaku context M4.

### 4.3 Pola query

Dua pola, dipilih sesuai konteks:

- **Context-driven** (di dalam request dengan session): pakai local scope convenience yang membaca context saat ini.

```php
// Unit-scoped — di unit saat ini (session)
Product::query()->inCurrentUnit()->get();

// Organization-scoped
OrganizationSetting::query()->inCurrentOrganization()->get();
```

- **User-driven** (butuh data spesifik user / bypass): pakai `Scope::userScope` dengan user eksplisit.

```php
// Multi-level — semua data user (units + org fallback)
Invoice::query()->userScope($user)->get();

// Admin bypass
if (! Scope::isSuperAdmin($user)) {
    $query = Scope::userScope($query, $user);
}
```

Aturan pakai: local scope convenience untuk "data unit/org saat ini"; `Scope::userScope` untuk "semua data yang boleh diakses user" (mis. daftar resource, laporan lintas-unit).

## 5. Policy & Resource Filament

### 5.1 Scope-aware policies

- Policy tetap cek permission dulu, lalu scope record:

```php
public function view(AuthUser $authUser, Product $product): bool
{
    return $authUser->can('view:product')
        && Scope::can($authUser, $product->organizational_unit_id);
}
```

- `Scope::can()` menangani `super_admin` sekaligus (true jika admin atau unit di-assign).
- Aksi koleksi cukup cek permission — daftar sudah dibatasi di `getEloquentQuery()`.

### 5.2 Scope-aware Filament resources

```php
class ProductResource extends Resource
{
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(
                ! Scope::isSuperAdmin(auth()->user()),
                fn (Builder $q) => Scope::userScope($q, auth()->user())
            );
    }
}
```

- List dibatasi di query; detail/edit/delete lewat policy per-record (dua lapis).

## 6. Aturan Bypass & Error Handling

| Kondisi | Perilaku |
|---|---|
| `super_admin` | Lolos semua scope — lihat semua unit/org, semua record |
| User biasa | Scope sesuai unit/org yang di-assign |
| Tanpa unit & tanpa org | Hanya data global; tidak bisa akses scoped |
| CLI/queue tanpa session | Context null → scope no-op — caller wajib set context eksplisit |

- Tidak ada throw untuk scope kosong — `Scope::unit(null)` no-op.
- `Scope::can()` false → policy false → 403 (bukan exception).
- Switch ke unit tak di-assign tetap `OrganizationException::invalidAssignment` (M4, tidak berubah).

## 7. Konfigurasi

- Tidak ada konfigurasi baru. Scope memakai:
  - Context contracts (M4) — `Core\Contracts\*`.
  - Role `super_admin` (Spatie) — perlu dipastikan ter-seed di `DatabaseSeeder` (cek saat plan).

## 8. Testing

```text
tests/Unit/Scope/
├── ScopeHelperTest.php        # unit/organization/userUnits/userOrganizations/userScope/can/isSuperAdmin
tests/Feature/Scope/
├── ScopePolicyTest.php        # policy per-record: super_admin bypass, unit mismatch → 403
├── ScopeResourceTest.php      # getEloquentQuery: list terbatas, admin lihat semua
└── ScopeQueryPatternTest.php  # pola query di action/service (integration)
```

- Ikuti pola existing: PHPUnit class-style, `RefreshDatabase`, namespace `Tests\Unit\Scope` / `Tests\Feature\Scope`.

## 9. Dokumentasi

- `docs/conventions/scope.md` (baru): konvensi scope — kolom, interface, enum, pola query, policy, resource, bypass.
- Update `docs/conventions/database.md` jika perlu (kolom scope pada tabel baru).
- Update `docs/TODO.md` §6 → semua `[x]` setelah implementasi.

## 10. Out of Scope

- Global scope otomatis (`addGlobalScope`) — keputusan: query layer eksplisit.
- Data isolation per-tenant / SaaS (PRD §4.1, §31).
- Helper global `context_unit()` / `context_organization()` — ditunda dari M4.
- Enforcement di database (triggers, RLS) — murni aplikasi.

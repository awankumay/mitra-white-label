# Design — Organization Core (TODO 4)

**Tanggal:** 2026-08-16
**Status:** Draft (menunggu review)
**Sumber:** `docs/TODO.md` §4.1-4.3, `docs/PRD.md` §8-16, ADR-005, ADR-007, ADR-010, spec `docs/superpowers/specs/2026-08-16-database-foundation-design.md`
**Metode:** Brainstorming (sesi 2026-08-16)
**Prasyarat:** M2 Database Foundation (selesai) — schema `organizations`, `organizational_units`, pivot, FK primary unit sudah ada.

## 1. Ringkasan

Milestone "Organization Core" (TODO.md §4.1-4.3) membangun lapisan domain
dan aplikasi di atas schema M2: model Organization & OrganizationalUnit,
action layer dengan validasi hierarki/assignment, policy & permission,
factory, seeder, dan UI Filament (resource + infolist).

Deliverable:

1. **Domain Core** — `core/Organization/Models/` (2 model), `Enums/`
   (OrganizationalUnitType), `Actions/` (9 action), `Exceptions/`
   (OrganizationException).
2. **App layer** — policy (Shield auto-generate CRUD + manual assignment),
   resource Filament (Organization & OrganizationalUnit, dengan Form &
   Infolist terpisah), `OrganizationalAccessSchema` reusable di UserResource.
3. **Data** — factory (`core/Database/Factories/` via `newFactory()`),
   seeder (default organization + root unit).
4. **Config** — Shield `custom_permissions` + tab custom_permissions;
   `core.organization.max_depth`.
5. **Perbaikan** — 3 policy usang (User/Activity/Role) ke format
   `action:subject`.
6. **Update docs** — konvensi (bila perlu), TODO.md §4.

**Defer ke milestone berikutnya:** §5 Organizational Context (context
resolution, switching, persistence), §6 Data Scope, HRIS master data
(divisi/jabatan/employee — business module).

## 2. Konteks

- M2 sudah membangun schema: `organizations`, `organizational_units`
  (parent_id adjacency list), `organization_user`, `organizational_unit_user`,
  `users.primary_organizational_unit_id` (FK nullOnDelete), `settings`.
- PRD §8: single organization default — satu installation = satu client;
  user tidak memilih organization saat login.
- PRD §13-14: user punya akses ke beberapa unit; satu primary unit sebagai
  default context.
- PRD §12: unit type default HEAD_OFFICE/BRANCH/SUB_OFFICE/SITE; Core tidak
  bergantung business-specific types (extensible).
- Directory-structure (accepted): model Core di `core/<Domain>/Models/`;
  policy (termasuk milik Core) di `app/Policies/` (revisi sesi: policy
  resource model Core di-generate Shield ke `core/<Domain>/Policies/` —
  lihat §6.1); Actions di
  `core/<Domain>/Actions/`; factory Core di `core/Database/Factories/`;
  resource Filament di `app/Filament/Resources/`.
- Architecture-rules spec §5.2 (accepted): format permission Shield
  `action:subject` (separator `:`, case snake) — `view_any:product`;
  Shield auto-generate policy untuk resource Filament (`policies.generate`
  dan `merge` aktif); developer tidak menulis policy manual untuk resource.
  (Revisi sesi 2026-08-16: format aktual Shield v4.3.1 = `action:subject`.)
- Kondisi aktual: 3 policy (`UserPolicy`, `ActivityPolicy`, `RolePolicy`)
  sempat memakai format lama underscore (`view_any_user`) — sudah
  disinkronkan ke format Shield via `shield:generate` (`view_any:user`).
- `Core\` → `core/` ter-autoload PSR-4 (composer.json) — model di
  `core/Organization/Models/` otomatis ter-discover.

## 3. Keputusan Arsitektur

### 3.1 Scope

M3 = TODO §4.1 + §4.2 + §4.3 (Organization, Organizational Unit, User
Assignment, validasi, UI). §5 (Context) dan §6 (Data Scope) milestone
terpisah. HRIS master data (divisi/jabatan/employee) adalah business
module terpisah — tidak bentrok dengan access-unit.

### 3.2 Struktur domain

```text
core/Organization/
├── Models/
│   ├── Organization.php
│   └── OrganizationalUnit.php
├── Enums/
│   └── OrganizationalUnitType.php
├── Actions/
│   ├── CreateOrganizationAction.php
│   ├── UpdateOrganizationAction.php
│   ├── DeleteOrganizationAction.php
│   ├── CreateOrganizationalUnitAction.php
│   ├── UpdateOrganizationalUnitAction.php
│   ├── DeleteOrganizationalUnitAction.php
│   ├── AssignUserToUnitAction.php
│   ├── RemoveUserFromUnitAction.php
│   └── SetPrimaryUnitAction.php
└── (Exceptions di core/Exceptions/OrganizationException.php)
```

### 3.3 Access-unit vs HRIS (separasi konsep)

- **Access-units** (`organizational_unit_user` + primary unit) = konsep
  authorization/context (PRD §13-14) — milik Core, dibangun M2.
- **HRIS master data** (divisi/jabatan/employee) = business module terpisah;
  Employee punya kolom `division_id`/`position_id`/`home_unit_id` di tabel
  `employees`, bukan pivot access.
- Keduanya ortogonal di data model. Yang durable adalah **Core Actions**
  (API mutasi); placement field form di UI adalah swappable — dibungkus
  `OrganizationalAccessSchema` reusable sehingga mudah dipindah ke
  EmployeeResource/module lain saat HRIS datang tanpa ubah logika.

### 3.4 Actions sebagai satu pintu mutasi

Semua mutasi (org/unit/assignment) lewat Action — validasi terpusat,
dipakai Filament maupun service/job/console (PRD §15 non-UI). Tanpa
`OrganizationService` (YAGNI — belum ada orkestrasi multi-langkah).

### 3.5 Validasi di Action layer

- Hierarki (Create/UpdateOrganizationalUnitAction): parent ≠ self,
  parent se-organization, cycle detection (ancestor walk), depth limit.
- Assignment (SetPrimaryUnitAction): primary unit harus di-assign ke user.
- Exception terpusat `Core\Exceptions\OrganizationException` (extends
  CoreException), pesan Bahasa Indonesia.

### 3.6 Policy & permission

- Policy CRUD Organization/Unit **di-generate Shield** ke
  `core/Organization/Policies/` (format `view_any:organization`,
  `view:organizational_unit`, dsb.) — tidak ditulis manual (spec §5.2).
- `OrganizationalAccessPolicy` (manual) untuk assignment custom:
  `assign_user_to_unit`, `remove_user_from_unit`, `set_primary_unit`.
- Shield config: `custom_permissions` diisi 3 permission assignment;
  `shield_resource.tabs.custom_permissions => true`.
- Perbaikan 3 policy usang (User/Activity/Role) ke format colon —
  `shield:generate` dengan `merge => true` akan menimpa format lama.

### 3.7 Factory & seeder

- Factory Core di `core/Database/Factories/` (namespace
  `Core\Database\Factories\`), di-discover via `newFactory()` di model.
- `OrganizationSeeder` di `core/Database/Seeders/` — default organization
  (`config('app.name')`) + root unit "Head Office" (HEAD_OFFICE),
  idempotent (`firstOrCreate`). Dipanggil eksplisit dari DatabaseSeeder.

### 3.8 Relasi User (app layer)

`app/Models/User.php` tambah: `organizations()`, `units()`,
`primaryOrganizationalUnit()`. Arah impor App → Core diizinkan (ADR-005).

## 4. Model & Enum

### 4.1 `OrganizationalUnitType` (`core/Organization/Enums/`)

```php
enum OrganizationalUnitType: string
{
    case HEAD_OFFICE = 'HEAD_OFFICE';
    case BRANCH = 'BRANCH';
    case SUB_OFFICE = 'SUB_OFFICE';
    case SITE = 'SITE';
}
```

- Backed string, case UPPER_SNAKE (naming.md). Aplikasi dapat menambah type
  sendiri (PRD §12 extensible); Core hanya 4 default.
- Cast di model: `'type' => OrganizationalUnitType::class`.

### 4.2 `Organization` (`core/Organization/Models/Organization.php`)

```php
class Organization extends Model
{
    use HasFactory, UsesUuid, SoftDeletes;

    protected $fillable = ['name', 'created_by', 'updated_by'];

    protected static function newFactory(): Factory
    {
        return OrganizationFactory::new();
    }

    public function organizationalUnits(): HasMany { ... }
    public function users(): BelongsToMany { ... } // pivot organization_user
}
```

### 4.3 `OrganizationalUnit` (`core/Organization/Models/OrganizationalUnit.php`)

```php
class OrganizationalUnit extends Model
{
    use HasFactory, UsesUuid, SoftDeletes;

    protected $fillable = [
        'organization_id', 'parent_id', 'name', 'type', 'created_by', 'updated_by',
    ];

    protected $casts = ['type' => OrganizationalUnitType::class];

    protected static function newFactory(): Factory
    {
        return OrganizationalUnitFactory::new();
    }

    public function organization(): BelongsTo { ... }
    public function parent(): BelongsTo { ... }
    public function children(): HasMany { ... }
    public function users(): BelongsToMany { ... } // pivot organizational_unit_user
    public function primaryUsers(): HasMany { ... } // User::primary_organizational_unit_id
}
```

### 4.4 Relasi User (`app/Models/User.php`)

```php
public function organizations(): BelongsToMany { ... }
public function units(): BelongsToMany { ... }
public function primaryOrganizationalUnit(): BelongsTo { ... }
```

Pivot table naming Laravel default sudah cocok dengan schema M2
(`organizational_unit_user`, `organization_user`) — tanpa pivot class khusus.

## 5. Actions

### 5.1 Daftar & signature

| Action | Signature | Perilaku |
|---|---|---|
| `CreateOrganizationAction` | `handle(string $name, ?string $createdBy = null): Organization` | create org |
| `UpdateOrganizationAction` | `handle(Organization $organization, array $data): Organization` | update fillable |
| `DeleteOrganizationAction` | `handle(Organization $organization): void` | soft delete |
| `CreateOrganizationalUnitAction` | `handle(Organization $organization, string $name, ?OrganizationalUnitType $type = null, ?string $parentId = null, ?string $createdBy = null): OrganizationalUnit` | create unit + validasi hierarki |
| `UpdateOrganizationalUnitAction` | `handle(OrganizationalUnit $unit, array $data): OrganizationalUnit` | update + validasi hierarki |
| `DeleteOrganizationalUnitAction` | `handle(OrganizationalUnit $unit): void` | soft delete |
| `AssignUserToUnitAction` | `handle(User $user, OrganizationalUnit $unit): void` | attach pivot (tanpa hapus existing) |
| `RemoveUserFromUnitAction` | `handle(User $user, OrganizationalUnit $unit): void` | detach pivot |
| `SetPrimaryUnitAction` | `handle(User $user, OrganizationalUnit $unit): void` | set primary; unit harus di-assign |

### 5.2 Validasi hierarki (Create/UpdateOrganizationalUnitAction)

1. `parent_id !== $unit->id` (parent ≠ self).
2. `parent->organization_id === $unit->organization_id` (se-organization).
3. Cycle detection: ancestor walk dari parent naik; jika ketemu unit id
   → `OrganizationException::invalidHierarchy('... membentuk siklus ...')`.
4. Depth: kedalaman dari root (walk parent hingga null) ≤
   `config('core.organization.max_depth', 10)`.

### 5.3 Validasi assignment (SetPrimaryUnitAction)

- `$user->units()->where('organizational_units.id', $unit->id)->exists()`
  → jika false, `OrganizationException::invalidAssignment('...')`.

### 5.4 Exception

`core/Exceptions/OrganizationException.php` extends `Core\Exceptions\CoreException`:
static factory `invalidHierarchy(string $message)`, `invalidAssignment(string $message)`.

## 6. Policy & Permission

### 6.1 Shield auto-generate (CRUD)

Resource Organization & OrganizationalUnit → `shield:generate` membuat
`OrganizationPolicy` & `OrganizationalUnitPolicy` di `core/Organization/Policies/`
(Shield menurunkan path policy dari lokasi model — keputusan sesi: ikuti
default Shield v4.3.1) dengan 11 method, format permission `action:subject`:

```text
view_any:organization, view:organization, create:organization,
update:organization, delete:organization, restore:organization,
force_delete:organization, force_delete_any:organization,
restore_any:organization, replicate:organization, reorder:organization
```

(sama untuk `organizational_unit`).

### 6.2 `OrganizationalAccessPolicy` (manual, custom)

`app/Policies/OrganizationalAccessPolicy.php`:

```php
public function assignUser(AuthUser $authUser): bool      // can('assign_user_to_unit')
public function removeUser(AuthUser $authUser): bool      // can('remove_user_from_unit')
public function setPrimaryUnit(AuthUser $authUser): bool  // can('set_primary_unit')
```

Didaftarkan eksplisit (tidak terikat resource tunggal).

### 6.3 Config Shield

```php
// config/filament-shield.php
'custom_permissions' => [
    'assign_user_to_unit',
    'remove_user_from_unit',
    'set_primary_unit',
],
// shield_resource.tabs
'custom_permissions' => true,
```

### 6.4 Perbaikan policy usang

`UserPolicy`, `ActivityPolicy`, `RolePolicy` → format `action:subject`
(`view_any:user`, `view:activity`, `view:role`, dst.) — via `shield:generate`
(merge) atau edit manual.

### 6.5 Registrasi

Auto-discovery Laravel (konvensi nama class) bekerja untuk
`OrganizationPolicy`/`OrganizationalUnitPolicy` (model Core, nama akhir
class cocok; lokasi `core/Organization/Policies/`). `OrganizationalAccessPolicy`
didaftarkan di `AppServiceProvider`.

## 7. Filament UI

### 7.1 Resource Organization

```
app/Filament/Resources/Organizations/
├── OrganizationResource.php
├── Pages/ListOrganizations.php, CreateOrganization.php, EditOrganization.php, ViewOrganization.php
├── Schemas/OrganizationForm.php        # name
├── Schemas/OrganizationInfolist.php    # name, created_at, organizational_units count
└── Tables/OrganizationsTable.php       # name, units count, created_at
```

### 7.2 Resource OrganizationalUnit

```
app/Filament/Resources/OrganizationalUnits/
├── OrganizationalUnitResource.php
├── Pages/ListOrganizationalUnits.php, Create..., Edit..., View...
├── Schemas/OrganizationalUnitForm.php    # organization (Select), parent (Select), name, type (Select enum)
├── Schemas/OrganizationalUnitInfolist.php
└── Tables/OrganizationalUnitsTable.php   # name, type badge, organization, parent, users count
```

### 7.3 Form → Action

Resource memanggil Action (`->action(fn (CreateOrganizationAction $a, array $data) => $a->handle(...))`),
bukan langsung model — satu pintu mutasi.

### 7.4 `OrganizationalAccessSchema` (reusable)

`app/Filament/Resources/Users/Schemas/OrganizationalAccessSchema.php`:

```php
Select::make('units')                           // multiple, relationship 'units'
Select::make('primary_organizational_unit_id')  // single, options dari units yang di-assign
```

Dipakai di `UserForm` (dan siap dipindah/reuse untuk HRIS nanti).

### 7.5 Navigation

Navigation group "Administration" (konsisten UserResource) — Organization &
OrganizationalUnit masuk group tersebut. Icons Heroicon.

## 8. Data

### 8.1 Factory (`core/Database/Factories/`)

```php
// OrganizationFactory.php
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;
    public function definition(): array
    {
        return ['name' => fake()->company(), 'created_by' => null, 'updated_by' => null];
    }
}

// OrganizationalUnitFactory.php
class OrganizationalUnitFactory extends Factory
{
    protected $model = OrganizationalUnit::class;
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'parent_id' => null,
            'name' => fake()->company(),
            'type' => OrganizationalUnitType::HEAD_OFFICE,
        ];
    }
    public function ofType(OrganizationalUnitType $type): static { ... }
}
```

Discovered via `newFactory()` di model.

### 8.2 Seeder (`core/Database/Seeders/OrganizationSeeder.php`)

```php
class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::firstOrCreate(
            ['name' => config('app.name')],
            ['name' => config('app.name')]
        );

        OrganizationalUnit::firstOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Head Office'],
            ['type' => OrganizationalUnitType::HEAD_OFFICE, 'parent_id' => null]
        );
    }
}
```

Idempotent. Dipanggil eksplisit dari `database/seeders/DatabaseSeeder.php`.

## 9. Config

### 9.1 `core/Config/core.php` — tambah

```php
'organization' => [
    'max_depth' => 10,
],
```

### 9.2 `config/filament-shield.php`

- `custom_permissions`: `assign_user_to_unit`, `remove_user_from_unit`,
  `set_primary_unit`.
- `shield_resource.tabs.custom_permissions`: `true`.

## 10. Testing

### 10.1 Struktur

```
tests/Unit/Organization/
├── OrganizationalUnitTypeTest.php
└── Actions/ (9 test files, satu per action)
tests/Feature/Organization/
├── OrganizationPolicyTest.php
├── OrganizationalUnitPolicyTest.php
├── OrganizationalAccessPolicyTest.php
├── OrganizationSeederTest.php
└── (Resource CRUD test — opsional, memakai Filament testing helpers)
```

### 10.2 Coverage wajib

1. CRUD actions (create/update/delete, soft delete, fillable).
2. Validasi hierarki: parent ≠ self → exception; parent lintas-org →
   exception; cycle → exception; depth > max → exception.
3. Validasi assignment: set primary unit yang tidak di-assign → exception;
   assign/remove pivot benar.
4. Policy: permission grant/deny.
5. Seeder idempotent (2x run, tidak duplikat).
6. Enum cast `type`.
7. Arch test: Core (model/action/enum) tidak impor Filament/App.

### 10.3 Gate

`composer check` (Pint → Pest → PHPStan) hijau di tiap akhir task.

## 11. Dampak pada Dokumen

- `docs/TODO.md` §4.1-4.3 — centang item selesai.
- `docs/conventions/directory-structure.md` — tidak ada perubahan struktural
  (pola sudah ditetapkan M0-M2).
- ADR baru? Keputusan M3 sudah tercakup ADR-005/007/010 + spec
  architecture-rules §5.2 — tidak perlu ADR baru (kecuali user minta).

## 12. Non-Goals

- Tidak membangun §5 Organizational Context (context resolution, switching,
  persistence) — milestone terpisah.
- Tidak membangun §6 Data Scope (global scope convention, scoped models).
- Tidak membangun HRIS master data (divisi/jabatan/employee) — business
  module terpisah; hanya `OrganizationalAccessSchema` reusable yang disiapkan.
- Tidak membuat `OrganizationService` (YAGNI — belum ada orkestrasi).
- Tidak mengubah schema M2 (migration sudah final).

## 13. Referensi

- `docs/TODO.md` §4, `docs/PRD.md` §8-16, ADR-005, ADR-007, ADR-010,
  `docs/conventions/{naming,directory-structure,database}.md`
- Spec `docs/superpowers/specs/2026-08-16-architecture-rules-design.md` §5.2
  (format permission Shield, policy auto-generate)
- Spec `docs/superpowers/specs/2026-08-16-database-foundation-design.md`
  (schema M2)
- `config/filament-shield.php` (kondisi aktual: separator `:`, generate
  `true`, merge `true`, custom_permissions `[]`)

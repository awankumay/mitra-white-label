# Authorization — Design Spec

> Milestone M6 (TODO §8). Berdiri di atas Authentication (M5) dan Data Scope (M6-TODO §6) yang sudah stabil.
> Scope terbatas pada **Authorization** — RBAC, policies, dan organizational authorization.
>
> Source of truth: `docs/PRD.md` §18, §19, §20, §64; `docs/TODO.md` §8; `docs/conventions/scope.md`;
> `docs/conventions/coding.md` (Policy section).

## 1. Tujuan

Menyediakan authorization yang menggabungkan **permission** (RBAC) dan **organizational scope** (PRD §18):

- RBAC: Shield + Spatie Permission terkonfigurasi, role hierarchy, role/permission management (TODO §8.1).
- Policies: konvensi + implementasi OrganizationPolicy, OrganizationalUnitPolicy, UserPolicy, scoped authorization (TODO §8.2).
- Organizational Authorization: permission + scope, validasi context, cegah cross-unit akses, test manager/unit/admin bypass (TODO §8.3).

Authorization mengikuti prinsip PRD §18:

```text
Authentication → Identity → Role → Permission → Organizational Scope
```

dan PRD §20 (permission + organization + organizational unit).

## 2. Keputusan Desain

| Aspek | Keputusan | Alasan |
|---|---|---|
| Foundation | Filament Shield v4.2 + Spatie Permission (sudah terinstall & aktif) | PRD §19: Shield/Spatie sebagai authorization foundation |
| Role hierarchy | **Inheritance top-down, child→parent** (kolom `parent_role_id` self-ref di tabel `roles`) | Keputusan user; PRD §19 role default; tidak ada hierarchy bawaan Shield/Spatie |
| Model Role | `App\Models\Role` extends `Spatie\Permission\Models\Role` | Menambah relasi parent/children tanpa vendor modifikasi |
| Resolve permission | Helper `RoleService` di `app/Services/` — gabungkan permission role + semua ancestor | Spatie tidak support hierarchy; layer aplikasi (ADR-005) |
| Super admin bypass | Tetap via Shield intercept `before` + `Scope::isSuperAdmin` | Invariant dari M5; tidak berubah |
| Policy pattern | Permission dulu, lalu `Scope::can` (konvensi scope.md) | `action:subject` + scope record |
| Scope-aware policies | Organization/OrganizationalUnit/User policies diperbarui | TODO §8.2; gap dari M3 (policy hanya cek permission) |
| Resource enforcement | `getEloquentQuery` + `Scope::userScope` (pola scope.md) | TODO §8.3; data dibatasi sejak query |
| Role management UI | Shield RoleResource (sudah ada) | Tidak perlu custom UI; cukup konfigurasi |
| Seeder | Tetapkan `parent_role_id` untuk role default | Hierarchy default konsisten sejak instalasi |
| Testing | Pest feature/unit + arch test | `composer check` quality gate |

## 3. Arsitektur

### 3.1 Struktur File

```
app/
├── Models/
│   └── Role.php                            # extends Spatie Role; parent()/children() relasi
├── Services/
│   └── RoleService.php                     # resolve inherited permissions; role checks
├── Policies/
│   ├── UserPolicy.php                      # ubah: + scope check (update/delete)
│   ├── ScopePolicy.php                     # ubah: generic, hapus draft view:product
│   └── ...                                 # OrganizationPolicy & OrganizationalUnitPolicy di Core (ubah)
│
core/Organization/Policies/
├── OrganizationPolicy.php                  # ubah: + Scope::canAccessOrganization
└── OrganizationalUnitPolicy.php            # ubah: + Scope::can(unit)
│
core/Support/
└── Scope.php                               # ubah: + canAccessOrganization helper
│
database/
├── migrations/
│   └── xxxx_add_parent_role_id_to_roles_table.php   # self-ref FK roles
└── seeders/
    └── DatabaseSeeder.php                  # ubah: parent_role_id hierarchy default
│
config/
└── permission.php                          # ubah: model role → App\Models\Role
│
tests/
├── Unit/Authorization/
│   ├── RoleServiceTest.php                 # inheritance resolve, cycle safety
│   └── RoleHierarchyTest.php               # relasi parent/children
└── Feature/Authorization/
    ├── OrganizationScopeTest.php           # manager/unit/admin access
    ├── OrganizationalUnitScopeTest.php     # unit-level access
    ├── UserPolicyScopeTest.php             # user scoped update/delete
    └── ResourceScopeTest.php               # resource query scoping
```

### 3.2 Role Hierarchy

- Kolom `parent_role_id` nullable, FK self-ref ke `roles.id` (`nullOnDelete`).
- Arah: role anak menyimpan parent-nya. `administrator.parent = super_admin`? **Tidak** —
  `super_admin` adalah bypass gate, bukan parent biasa. Hierarchy default (PRD §19):

```text
administrator (top-level, no parent)
    └── manager
        └── supervisor
            └── staff
                └── viewer
```

- `super_admin` **tidak punya parent** dan **tidak menjadi parent** — ia bypass gate (Shield intercept), bukan sumber inheritance.
- `panel_user` tidak masuk hierarchy (role teknis akses panel, bukan permission).

```php
// app/Models/Role.php
class Role extends SpatieRole
{
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_role_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_role_id');
    }

    /** Semua ancestor (root dulu), tanpa cycle. */
    public function ancestors(): Collection
    {
        $ancestors = collect();
        $seen = collect([$this->id]);
        $current = $this->parent;

        while ($current && ! $seen->contains($current->id)) {
            $ancestors->push($current);
            $seen->push($current->id);
            $current = $current->parent;
        }

        return $ancestors;
    }
}
```

### 3.3 RoleService

```php
// app/Services/RoleService.php
final class RoleService
{
    /** Semua permission efektif user = permission role user + semua ancestor (via role). */
    public function permissionsFor(User $user): Collection;      // Collection<Permission>

    /** Apakah user punya permission tertentu (termasuk warisan)? */
    public function userHasPermission(User $user, string $permission): bool;

    /** Semua descendant role (anak, cucu, dst.) — untuk UI hierarchy. */
    public function descendantsOf(Role $role): Collection;       // Collection<Role>

    /** Cegah cycle: apakah $roleId ancestor dari $candidateParentId? */
    public function wouldCreateCycle(Role $role, ?string $newParentId): bool;
}
```

- `permissionsFor`: `$user->roles` → untuk tiap role, gabungkan permission role + `ancestors()`.
  Deduplicate by id. Tidak menyentuh Spatie cache permission (Spatie resolve via `getAllPermissions`).
- Integrasi dengan gate: Shield `super_admin` intercept `before` tetap berlaku; untuk role lain,
  Spatie `hasPermissionTo` default tidak tahu hierarchy — `userHasPermission` dipakai oleh
  policies yang butuh inheritance, ATAU didaftarkan sebagai gate fallback di `AppServiceProvider`:

```php
Gate::before(fn ($user, $ability) => app(RoleService::class)->userHasPermission($user, $ability));
```

> Catatan: `Gate::before` ini **berjalan setelah** Shield intercept `before` super_admin. Untuk non-super-admin,
> ia menambah kemampuan warisan. Perlu dipastikan tidak menimpa deny eksplisit (Spatie `deny` via permission
> yang di-revoke tetap dihormati karena kita hanya menambah ability yang di-warisi, tidak menghapus).

### 3.4 Policy Pattern (konvensi scope.md, dikerjakan untuk §8.2)

`Scope::can($user, $unitId)` saat ini cek pivot `organizational_unit_user` (unit). Untuk cek akses
ke Organization (bukan unit), tambah helper di `Core\Support\Scope`:

```php
// core/Support/Scope.php — tambahan
public static function canAccessOrganization(Authenticatable $user, ?string $orgId): bool
{
    if (self::isSuperAdmin($user)) {
        return true;
    }

    if ($orgId === null) {
        return false;
    }

    return DB::table('organization_user')
        ->where('organization_id', $orgId)
        ->where('user_id', $user->getAuthIdentifier())
        ->exists();
}
```

```php
// core/Organization/Policies/OrganizationPolicy.php — view/update/delete
public function update(AuthUser $authUser, Organization $organization): bool
{
    return $authUser->can('update:organization')
        && Scope::canAccessOrganization($authUser, $organization->id);
}

// OrganizationalUnitPolicy — view/update/delete
public function view(AuthUser $authUser, OrganizationalUnit $unit): bool
{
    return $authUser->can('view:organizational_unit')
        && Scope::can($authUser, $unit->id);
}

// UserPolicy — update/delete (user target harus se-unit atau se-org)
public function update(AuthUser $authUser, User $user): bool
{
    return $authUser->can('update:user')
        && $this->sharesScope($authUser, $user);
}
```

- `Scope::can($user, $unitId)` — super_admin true, else cek pivot `organizational_unit_user`.
- `Scope::canAccessOrganization($user, $orgId)` — super_admin true, else cek pivot `organization_user`.
- `sharesScope` (UserPolicy): user target berbagi minimal satu unit ATAU satu organization dengan
  auth user (via `whereHas`), atau auth user super_admin.

### 3.5 Resource Enforcement (TODO §8.3)

```php
// OrganizationResource / OrganizationalUnitResource / UserResource
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->when(
            ! Scope::isSuperAdmin(auth()->user()),
            fn (Builder $q) => Scope::userScope($q, auth()->user())
        );
}
```

- OrganizationResource: `userScope` menyaring organization via `organization_user` pivot.
- OrganizationalUnitResource: `userScope` menyaring unit via `organizational_unit_user` pivot.
- UserResource: `userScope` menyaring user yang berbagi unit/org — butuh relasi (user di-assign
  ke unit yang sama). Implementasi: `whereHas('units', fn ($q) => Scope::userUnits($q, auth()->user()))`
  ATAU `whereHas('organizations', ...)`.

### 3.6 Seeder & Config

- `config/permission.php`: `'role' => App\Models\Role::class`.
- `DatabaseSeeder`: tetapkan hierarchy default:

```php
$manager = Role::firstOrCreate(['name' => 'manager']);
$supervisor = Role::firstOrCreate(['name' => 'supervisor']);
$staff = Role::firstOrCreate(['name' => 'staff']);
$viewer = Role::firstOrCreate(['name' => 'viewer']);

$supervisor->update(['parent_role_id' => $manager->id]);
$staff->update(['parent_role_id' => $supervisor->id]);
$viewer->update(['parent_role_id' => $staff->id]);
```

### 3.7 Alur Authorization

```
Request → Gate::before (Shield super_admin intercept) → true? → allow
      ↓ (bukan super_admin)
Gate::before (RoleService inheritance) → userHasPermission? → allow
      ↓
Policy (permission dulu) → can('action:subject') (dengan inheritance)
      ↓
Scope check → Scope::can / Scope::canAccessOrganization
      ↓
Resource query → userScope (data dibatasi di query)
```

## 4. Error Handling

| Kasus | Perilaku |
|---|---|
| User tanpa permission | `403` / Filament unauthorized |
| User punya permission tapi di luar scope | `403` (policy scope check gagal) |
| Cycle hierarchy (A.parent=B, B.parent=A) | `RoleService::wouldCreateCycle` tolak; seeder aman |
| `super_admin` | Bypass semua (gate + scope) — invariant |
| CLI/queue tanpa session | Scope no-op; caller set context eksplisit (konvensi scope.md) |

## 5. Testing

### Unit (`tests/Unit/Authorization/`)
- `RoleHierarchyTest` — parent/children relasi, ancestors tanpa cycle.
- `RoleServiceTest` — permissionsFor gabungan warisan, userHasPermission, descendantsOf, wouldCreateCycle.

### Feature (`tests/Feature/Authorization/`)
- `OrganizationScopeTest` — manager bisa akses org di-assign; user luar org 403; super_admin bypass.
- `OrganizationalUnitScopeTest` — user akses unit di-assign; cross-unit 403; admin bypass.
- `UserPolicyScopeTest` — user se-unit bisa di-update; user unit lain 403.
- `ResourceScopeTest` — resource Organization/Unit/User query ter-scope (list tidak menampilkan data luar scope).

### Arch (`tests/Arch/`)
- `CoreArchTest` — `Core\Organization\Policies` tidak mengimpor `App\*` (sudah ada; pastikan perubahan
  tidak melanggar — `Scope` ada di Core, aman).

## 6. Out of Scope (M6)

- UI role hierarchy editor (parent select di RoleResource) — cukup kolom + seeder; UI edit manual
  bisa via DB/shield role edit. **Deferred** ke improvement.
- Permission auto-assign per role baru selain default.
- Multi-organization / teams (PRD §64: single org default).
- Audit log untuk role/permission changes (M8).

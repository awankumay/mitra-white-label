# Authorization (M6) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement TODO §8 Authorization — role hierarchy (child→parent inheritance), scope-aware policies for Organization/OrganizationalUnit/User, resource query enforcement, and organizational authorization tests.

**Architecture:** Add `parent_role_id` self-referencing column to the Spatie `roles` table. A new `App\Models\Role` extends Spatie's Role with `parent()`/`children()`/`ancestors()`. A `RoleService` resolves effective permissions (role + all ancestors) and exposes hierarchy helpers, wired into the gate via `Gate::before` after Shield's super_admin intercept. Core policies become scope-aware: permission check first, then `Scope::can` (unit) or new `Scope::canAccessOrganization` (org). Filament resources restrict queries via `Scope::userScope`. Seeder sets the default hierarchy. Super admin bypass is unchanged.

**Tech Stack:** Laravel 13, Filament 5, Filament Shield v4.2, Spatie Permission, Pest/PHPUnit, UUIDv7.

## Global Constraints

- **Architecture (ADR-005):** `Core\` must NOT use `App\` or `Modules\`; Core non-UI must NOT use Filament. Verified by `tests/Arch/CoreArchTest.php`.
- **No vendor modification (PRD §55):** All Spatie/Shield extension via model subclass + service layer, never editing `vendor/`.
- **Permissions format:** `action:subject`, separator `:`, snake case — e.g. `view:organization`, `update:user` (Shield v4.3.1 default).
- **Bypass role:** Role named exactly `super_admin` (Spatie). Super admin bypasses all gates and scopes (Shield intercept `before` + `Scope::isSuperAdmin`). Super admin has NO parent and is never a parent in hierarchy.
- **Hierarchy direction:** child→parent (`parent_role_id` self-ref). Default chain: `administrator` (no parent) → `manager` → `supervisor` → `staff` → `viewer`. `panel_user` is NOT in hierarchy.
- **Policy pattern:** permission first, then scope check (conventions/scope.md): `can('action:subject') && Scope::can(...)`.
- **Scope helper:** `Core\Support\Scope` is final, stateless, free of Filament/App models — only primitives or `Authenticatable`. New method `canAccessOrganization(Authenticatable $user, ?string $orgId): bool`.
- **Roles table:** Spatie default — `id` unsignedBigInteger, `name` string, `guard_name` string, unique(`name`,`guard_name`). `parent_role_id` FK self-ref, nullable, `nullOnDelete`.
- **Test conventions:** PHPUnit class-style (`class XxxTest extends TestCase`), `use RefreshDatabase;`, namespaces `Tests\Unit\Authorization` / `Tests\Feature\Authorization`.
- **Quality gate:** `composer check` = Pint → Pest → PHPStan. Must pass after each task.
- **Seeder:** idempotent (`firstOrCreate`), sets hierarchy each run.

---

### Task 1: `parent_role_id` migration + `App\Models\Role`

**Files:**
- Create: `database/migrations/2026_08_17_000100_add_parent_role_id_to_roles_table.php`
- Create: `app/Models/Role.php`
- Modify: `config/permission.php`
- Test: `tests/Unit/Authorization/RoleHierarchyTest.php`

**Interfaces:**
- Produces:
  - `parent_role_id` column on `roles` (nullable, FK self-ref `nullOnDelete`).
  - `App\Models\Role` extends `Spatie\Permission\Models\Role` with:
    - `parent(): BelongsTo` (self)
    - `children(): HasMany` (self)
    - `ancestors(): Illuminate\Support\Collection` — root-first, cycle-safe (stops if a visited id repeats).

- [ ] **Step 1: Write the failing test**

`tests/Unit/Authorization/RoleHierarchyTest.php`:

```php
<?php

namespace Tests\Unit\Authorization;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_has_parent_and_children_relations(): void
    {
        $parent = Role::create(['name' => 'manager']);
        $child = Role::create(['name' => 'staff', 'parent_role_id' => $parent->id]);

        $this->assertSame($parent->id, $child->parent->id);
        $this->assertTrue($parent->children->contains('id', $child->id));
    }

    public function test_ancestors_returns_root_first_chain(): void
    {
        $top = Role::create(['name' => 'administrator']);
        $manager = Role::create(['name' => 'manager', 'parent_role_id' => $top->id]);
        $staff = Role::create(['name' => 'staff', 'parent_role_id' => $manager->id]);

        $ancestors = $staff->ancestors();

        $this->assertSame([$manager->id, $top->id], $ancestors->pluck('id')->all());
    }

    public function test_ancestors_is_cycle_safe(): void
    {
        $a = Role::create(['name' => 'a']);
        $b = Role::create(['name' => 'b', 'parent_role_id' => $a->id]);
        $a->update(['parent_role_id' => $b->id]); // cycle a<->b

        $a->refresh();
        $b->refresh();

        $this->assertCount(1, $a->ancestors());   // visits b once, stops before infinite loop
        $this->assertCount(1, $b->ancestors());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Authorization/RoleHierarchyTest.php`
Expected: FAIL — `Class "App\Models\Role" not found` (and missing column errors).

- [ ] **Step 3: Create migration**

`database/migrations/2026_08_17_000100_add_parent_role_id_to_roles_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_role_id')->nullable()->after('guard_name');
            $table->foreign('parent_role_id')->references('id')->on('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['parent_role_id']);
            $table->dropColumn('parent_role_id');
        });
    }
};
```

- [ ] **Step 4: Create `App\Models\Role`**

`app/Models/Role.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role as SpatieRole;

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

    /**
     * Semua ancestor (root dulu), cycle-safe.
     *
     * @return Collection<int, Role>
     */
    public function ancestors(): Collection
    {
        $ancestors = new Collection;
        $seen = [$this->id];
        $current = $this->parent;

        while ($current && ! in_array($current->id, $seen, true)) {
            $ancestors->push($current);
            $seen[] = $current->id;
            $current = $current->parent;
        }

        return $ancestors;
    }
}
```

- [ ] **Step 5: Point Spatie at the new Role model**

In `config/permission.php`, change:

```php
        'role' => Spatie\Permission\Models\Role::class,
```

to:

```php
        'role' => App\Models\Role::class,
```

(Add `use App\Models\Role;` at top or use the fully-qualified name.)

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test tests/Unit/Authorization/RoleHierarchyTest.php`
Expected: PASS (3 passed).

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_08_17_000100_add_parent_role_id_to_roles_table.php app/Models/Role.php config/permission.php tests/Unit/Authorization/RoleHierarchyTest.php
git commit -m "feat: add role hierarchy model and migration (TODO 8.1)"
```

---

### Task 2: `Scope::canAccessOrganization` helper

**Files:**
- Modify: `core/Support/Scope.php`
- Test: `tests/Unit/Scope/ScopeAccessTest.php`

**Interfaces:**
- Consumes: existing `Scope::isSuperAdmin`, `Scope::can`.
- Produces: `Core\Support\Scope::canAccessOrganization(Authenticatable $user, ?string $orgId): bool` — super_admin → true; null org → false; else check `organization_user` pivot exists.

- [ ] **Step 1: Write the failing test**

`tests/Unit/Scope/ScopeAccessTest.php`:

```php
<?php

namespace Tests\Unit\Scope;

use App\Models\User;
use Core\Organization\Models\Organization;
use Core\Support\Scope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ScopeAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_access_organization_when_assigned(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();
        $user->organizations()->attach($org->id);

        $this->assertTrue(Scope::canAccessOrganization($user, $org->id));
    }

    public function test_can_access_organization_false_when_not_assigned(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();

        $this->assertFalse(Scope::canAccessOrganization($user, $org->id));
    }

    public function test_can_access_organization_false_when_null(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(Scope::canAccessOrganization($user, null));
    }

    public function test_super_admin_can_access_any_organization(): void
    {
        $org = Organization::factory()->create();
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue(Scope::canAccessOrganization($user, $org->id));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Scope/ScopeAccessTest.php`
Expected: FAIL — `Call to undefined method Core\Support\Scope::canAccessOrganization()`

- [ ] **Step 3: Add the method**

In `core/Support/Scope.php`, after `can()`:

```php
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

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Scope/ScopeAccessTest.php`
Expected: PASS (4 passed).

- [ ] **Step 5: Commit**

```bash
git add core/Support/Scope.php tests/Unit/Scope/ScopeAccessTest.php
git commit -m "feat: add Scope::canAccessOrganization helper (TODO 8.2)"
```

---

### Task 3: `RoleService` — inherited permissions

**Files:**
- Create: `app/Services/RoleService.php`
- Test: `tests/Unit/Authorization/RoleServiceTest.php`

**Interfaces:**
- Consumes: `App\Models\Role` (Task 1), `App\Models\User`.
- Produces:
  - `App\Services\RoleService` (final class):
    - `permissionsFor(User $user): Illuminate\Support\Collection` — distinct permissions from user's roles + all ancestors.
    - `userHasPermission(User $user, string $permission): bool` — true if user is super_admin OR permission in `permissionsFor`.
    - `descendantsOf(Role $role): Illuminate\Support\Collection` — all descendants (children, grandchildren, ...).
    - `wouldCreateCycle(Role $role, ?string $newParentId): bool` — true if `$newParentId` is `$role` itself or any of its descendants.

- [ ] **Step 1: Write the failing test**

`tests/Unit/Authorization/RoleServiceTest.php`:

```php
<?php

namespace Tests\Unit\Authorization;

use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RoleServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RoleService;
    }

    public function test_permissions_for_includes_inherited_ancestor_permissions(): void
    {
        $top = Role::create(['name' => 'administrator']);
        $manager = Role::create(['name' => 'manager', 'parent_role_id' => $top->id]);
        $staff = Role::create(['name' => 'staff', 'parent_role_id' => $manager->id]);

        $top->givePermissionTo(Permission::create(['name' => 'view:organization']));
        $staff->givePermissionTo(Permission::create(['name' => 'view:user']));

        $user = User::factory()->create();
        $user->assignRole($staff);

        $names = $this->service->permissionsFor($user)->pluck('name');

        $this->assertTrue($names->contains('view:user'));
        $this->assertTrue($names->contains('view:organization'));   // inherited via manager→administrator
    }

    public function test_user_has_permission_checks_inheritance(): void
    {
        $manager = Role::create(['name' => 'manager']);
        $staff = Role::create(['name' => 'staff', 'parent_role_id' => $manager->id]);
        $manager->givePermissionTo(Permission::create(['name' => 'view:user']));

        $user = User::factory()->create();
        $user->assignRole($staff);

        $this->assertTrue($this->service->userHasPermission($user, 'view:user'));
        $this->assertFalse($this->service->userHasPermission($user, 'delete:user'));
    }

    public function test_super_admin_has_every_permission(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue($this->service->userHasPermission($user, 'anything:at_all'));
    }

    public function test_descendants_of_returns_whole_subtree(): void
    {
        $manager = Role::create(['name' => 'manager']);
        $supervisor = Role::create(['name' => 'supervisor', 'parent_role_id' => $manager->id]);
        $staff = Role::create(['name' => 'staff', 'parent_role_id' => $supervisor->id]);

        $ids = $this->service->descendantsOf($manager)->pluck('id');

        $this->assertTrue($ids->contains($supervisor->id));
        $this->assertTrue($ids->contains($staff->id));
    }

    public function test_would_create_cycle_detects_self_and_descendant(): void
    {
        $manager = Role::create(['name' => 'manager']);
        $supervisor = Role::create(['name' => 'supervisor', 'parent_role_id' => $manager->id]);

        $this->assertTrue($this->service->wouldCreateCycle($manager, $manager->id));       // self
        $this->assertTrue($this->service->wouldCreateCycle($manager, $supervisor->id));    // descendant
        $this->assertFalse($this->service->wouldCreateCycle($manager, null));              // valid detach
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Authorization/RoleServiceTest.php`
Expected: FAIL — `Class "App\Services\RoleService" not found`

- [ ] **Step 3: Implement `RoleService`**

`app/Services/RoleService.php`:

```php
<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

final class RoleService
{
    /**
     * Semua permission efektif user = permission semua role user + semua ancestor.
     *
     * @return Collection<int, \Spatie\Permission\Models\Permission>
     */
    public function permissionsFor(User $user): Collection
    {
        $permissions = new Collection;

        foreach ($user->roles as $role) {
            foreach ($role->permissions as $permission) {
                $permissions->push($permission);
            }

            foreach ($role->ancestors() as $ancestor) {
                foreach ($ancestor->permissions as $permission) {
                    $permissions->push($permission);
                }
            }
        }

        return $permissions->unique('id')->values();
    }

    public function userHasPermission(User $user, string $permission): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $this->permissionsFor($user)->contains('name', $permission);
    }

    /**
     * Semua descendant role (anak, cucu, dst.).
     *
     * @return Collection<int, Role>
     */
    public function descendantsOf(Role $role): Collection
    {
        $descendants = new Collection;
        $queue = $role->children;

        while ($queue->isNotEmpty()) {
            $current = $queue->shift();
            $descendants->push($current);
            $queue = $queue->merge($current->children);
        }

        return $descendants->unique('id')->values();
    }

    public function wouldCreateCycle(Role $role, ?string $newParentId): bool
    {
        if ($newParentId === null) {
            return false;
        }

        if ((string) $role->id === $newParentId) {
            return true;
        }

        return $this->descendantsOf($role)->contains(function (Role $descendant) use ($newParentId): bool {
            return (string) $descendant->id === $newParentId;
        });
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Authorization/RoleServiceTest.php`
Expected: PASS (5 passed).

- [ ] **Step 5: Commit**

```bash
git add app/Services/RoleService.php tests/Unit/Authorization/RoleServiceTest.php
git commit -m "feat: add RoleService with inherited permissions (TODO 8.1)"
```

---

### Task 4: Wire inheritance into the gate

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Feature/Authorization/InheritanceGateTest.php`

**Interfaces:**
- Consumes: `App\Services\RoleService` (Task 3), Shield super_admin intercept (already active).
- Produces: `Gate::before` registered in `AppServiceProvider::boot()` that grants abilities the user inherits via role hierarchy. Runs after Shield's super_admin `before` intercept.

- [ ] **Step 1: Write the failing test**

`tests/Feature/Authorization/InheritanceGateTest.php`:

```php
<?php

namespace Tests\Feature\Authorization;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InheritanceGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_gate_grants_inherited_permission_via_hierarchy(): void
    {
        $manager = Role::create(['name' => 'manager']);
        $staff = Role::create(['name' => 'staff', 'parent_role_id' => $manager->id]);
        $manager->givePermissionTo(Permission::create(['name' => 'view:user']));

        $user = User::factory()->create();
        $user->assignRole($staff);

        $this->assertTrue(Gate::forUser($user)->allows('view:user'));
    }

    public function test_gate_denies_permission_not_inherited(): void
    {
        $role = Role::create(['name' => 'staff']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue(Gate::forUser($user)->denies('delete:user'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Authorization/InheritanceGateTest.php`
Expected: FAIL — `allows('view:user')` is false (Spatie doesn't know hierarchy).

- [ ] **Step 3: Register the gate callback**

In `app/Providers/AppServiceProvider.php`:

Add to `boot()` (after `configureSecurityEvents()`):

```php
        $this->configureAuthorizationGate();
```

Add the method:

```php
    private function configureAuthorizationGate(): void
    {
        Gate::before(function ($user, $ability) {
            if (! $user instanceof \App\Models\User) {
                return null;
            }

            if ($user->hasRole('super_admin')) {
                return null;   // Shield intercept 'before' already handles super_admin; don't shadow it
            }

            $allowed = app(\App\Services\RoleService::class)->userHasPermission($user, $ability);

            return $allowed ? true : null;   // null → fall through to normal policy/permission checks
        });
    }
```

> Note: returning `null` lets Spatie's normal `can()` checks decide (deny). Returning `true` grants inherited abilities. Shield's own super_admin intercept runs as its own `Gate::before`; we skip super_admin here to avoid double-handling.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Authorization/InheritanceGateTest.php`
Expected: PASS (2 passed).

- [ ] **Step 5: Commit**

```bash
git add app/Providers/AppServiceProvider.php tests/Feature/Authorization/InheritanceGateTest.php
git commit -m "feat: wire role hierarchy into gate (TODO 8.1)"
```

---

### Task 5: Scope-aware Core policies

**Files:**
- Modify: `core/Organization/Policies/OrganizationPolicy.php`
- Modify: `core/Organization/Policies/OrganizationalUnitPolicy.php`
- Test: `tests/Feature/Authorization/OrganizationScopeTest.php`
- Test: `tests/Feature/Authorization/OrganizationalUnitScopeTest.php`

**Interfaces:**
- Consumes: `Core\Support\Scope::can` + `Scope::canAccessOrganization` (Task 2), permissions via Shield/Spatie.
- Produces:
  - `OrganizationPolicy::view/update/delete/restore/forceDelete/replicate` — add `&& Scope::canAccessOrganization($authUser, $organization->id)`.
  - `OrganizationalUnitPolicy::view/update/delete/restore/forceDelete/replicate` — add `&& Scope::can($authUser, $unit->id)`.
  - `viewAny/create` unchanged (no record to scope).

- [ ] **Step 1: Write the failing tests**

`tests/Feature/Authorization/OrganizationScopeTest.php`:

```php
<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Core\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganizationScopeTest extends TestCase
{
    use RefreshDatabase;

    private function userWithOrgPermission(): User
    {
        Permission::firstOrCreate(['name' => 'update:organization']);
        $role = Role::firstOrCreate(['name' => 'staff']);
        $role->givePermissionTo('update:organization');

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_user_can_update_assigned_organization(): void
    {
        $org = Organization::factory()->create();
        $user = $this->userWithOrgPermission();
        $user->organizations()->attach($org->id);

        $this->assertTrue($user->can('update', $org));
    }

    public function test_user_cannot_update_unassigned_organization(): void
    {
        $org = Organization::factory()->create();
        $user = $this->userWithOrgPermission();

        $this->assertFalse($user->can('update', $org));
    }

    public function test_super_admin_can_update_any_organization(): void
    {
        $org = Organization::factory()->create();
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue($user->can('update', $org));
    }
}
```

`tests/Feature/Authorization/OrganizationalUnitScopeTest.php`:

```php
<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganizationalUnitScopeTest extends TestCase
{
    use RefreshDatabase;

    private function userWithUnitPermission(): User
    {
        Permission::firstOrCreate(['name' => 'view:organizational_unit']);
        $role = Role::firstOrCreate(['name' => 'staff']);
        $role->givePermissionTo('view:organizational_unit');

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_user_can_view_assigned_unit(): void
    {
        $org = Organization::factory()->create();
        $unit = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);
        $user = $this->userWithUnitPermission();
        $user->units()->attach($unit->id);

        $this->assertTrue($user->can('view', $unit));
    }

    public function test_user_cannot_view_cross_unit(): void
    {
        $org = Organization::factory()->create();
        $unit = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);
        $user = $this->userWithUnitPermission();

        $this->assertFalse($user->can('view', $unit));
    }

    public function test_super_admin_can_view_any_unit(): void
    {
        $org = Organization::factory()->create();
        $unit = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue($user->can('view', $unit));
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Authorization/OrganizationScopeTest.php tests/Feature/Authorization/OrganizationalUnitScopeTest.php`
Expected: FAIL — `user cannot update unassigned organization` and `user cannot view cross unit` (current policies only check permission, not scope).

- [ ] **Step 3: Update `OrganizationPolicy`**

In `core/Organization/Policies/OrganizationPolicy.php`, add import and scope checks to record-bearing methods. The file currently imports `Core\Organization\Models\Organization` and `Illuminate\Foundation\Auth\User as AuthUser`. Add:

```php
use Core\Support\Scope;
```

Then update the record-bearing methods. Show the full updated `view`/`update`/`delete`/`restore`/`forceDelete`/`replicate` methods (they follow the same shape):

```php
    public function view(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('view:organization')
            && Scope::canAccessOrganization($authUser, $organization->id);
    }

    public function update(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('update:organization')
            && Scope::canAccessOrganization($authUser, $organization->id);
    }

    public function delete(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('delete:organization')
            && Scope::canAccessOrganization($authUser, $organization->id);
    }

    public function restore(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('restore:organization')
            && Scope::canAccessOrganization($authUser, $organization->id);
    }

    public function forceDelete(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('force_delete:organization')
            && Scope::canAccessOrganization($authUser, $organization->id);
    }

    public function replicate(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('replicate:organization')
            && Scope::canAccessOrganization($authUser, $organization->id);
    }
```

(`viewAny`, `create`, `forceDeleteAny`, `restoreAny`, `reorder` stay as-is — no record to scope.)

- [ ] **Step 4: Update `OrganizationalUnitPolicy`**

In `core/Organization/Policies/OrganizationalUnitPolicy.php`, add import:

```php
use Core\Support\Scope;
```

Then update the record-bearing methods with `Scope::can($authUser, $organizationalUnit->id)` — same six methods as above but with `Scope::can` and permission names `view:organizational_unit` etc.

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Authorization/OrganizationScopeTest.php tests/Feature/Authorization/OrganizationalUnitScopeTest.php`
Expected: PASS (6 passed).

- [ ] **Step 6: Commit**

```bash
git add core/Organization/Policies/OrganizationPolicy.php core/Organization/Policies/OrganizationalUnitPolicy.php tests/Feature/Authorization/OrganizationScopeTest.php tests/Feature/Authorization/OrganizationalUnitScopeTest.php
git commit -m "feat: make organization and unit policies scope-aware (TODO 8.2)"
```

---

### Task 6: Scope-aware `UserPolicy` + generic `ScopePolicy`

**Files:**
- Modify: `app/Policies/UserPolicy.php`
- Modify: `app/Policies/ScopePolicy.php`
- Test: `tests/Feature/Authorization/UserPolicyScopeTest.php`

**Interfaces:**
- Consumes: `Core\Support\Scope` (userUnits/userOrganizations), `App\Models\User` relations.
- Produces:
  - `UserPolicy::update/delete/restore/forceDelete` — add scope: auth user and target user share a unit OR an organization, or auth user is super_admin.
  - `ScopePolicy` — generic demo policy without the placeholder `view:product`; scoped to `OrganizationalUnit` with `view:organizational_unit` permission.

- [ ] **Step 1: Write the failing test**

`tests/Feature/Authorization/UserPolicyScopeTest.php`:

```php
<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserPolicyScopeTest extends TestCase
{
    use RefreshDatabase;

    private function userWithUpdatePermission(): User
    {
        Permission::firstOrCreate(['name' => 'update:user']);
        $role = Role::firstOrCreate(['name' => 'staff']);
        $role->givePermissionTo('update:user');

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_user_can_update_user_in_same_unit(): void
    {
        $org = \Core\Organization\Models\Organization::factory()->create();
        $unit = \Core\Organization\Models\OrganizationalUnit::factory()->create(['organization_id' => $org->id]);

        $authUser = $this->userWithUpdatePermission();
        $target = User::factory()->create();

        $authUser->units()->attach($unit->id);
        $target->units()->attach($unit->id);

        $this->assertTrue($authUser->can('update', $target));
    }

    public function test_user_cannot_update_user_in_other_unit(): void
    {
        $org = \Core\Organization\Models\Organization::factory()->create();
        $unitA = \Core\Organization\Models\OrganizationalUnit::factory()->create(['organization_id' => $org->id]);
        $unitB = \Core\Organization\Models\OrganizationalUnit::factory()->create(['organization_id' => $org->id]);

        $authUser = $this->userWithUpdatePermission();
        $target = User::factory()->create();

        $authUser->units()->attach($unitA->id);
        $target->units()->attach($unitB->id);

        $this->assertFalse($authUser->can('update', $target));
    }

    public function test_super_admin_can_update_any_user(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $authUser = User::factory()->create();
        $authUser->assignRole($role);
        $target = User::factory()->create();

        $this->assertTrue($authUser->can('update', $target));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Authorization/UserPolicyScopeTest.php`
Expected: FAIL — `user cannot update user in other unit` (UserPolicy only checks permission).

- [ ] **Step 3: Update `UserPolicy`**

Add import:

```php
use App\Models\User;
use Core\Support\Scope;
```

Add a private helper and update record-bearing methods. Replace the current `update`/`delete`/`restore`/`forceDelete` bodies:

```php
    public function update(AuthUser $authUser, User $user): bool
    {
        return $authUser->can('update:user')
            && $this->sharesScope($authUser, $user);
    }

    public function delete(AuthUser $authUser, User $user): bool
    {
        return $authUser->can('delete:user')
            && $this->sharesScope($authUser, $user);
    }

    public function restore(AuthUser $authUser, User $user): bool
    {
        return $authUser->can('restore:user')
            && $this->sharesScope($authUser, $user);
    }

    public function forceDelete(AuthUser $authUser, User $user): bool
    {
        return $authUser->can('force_delete:user')
            && $this->sharesScope($authUser, $user);
    }

    private function sharesScope(AuthUser $authUser, User $target): bool
    {
        if (Scope::isSuperAdmin($authUser)) {
            return true;
        }

        $authUnitIds = $authUser->units()->pluck('organizational_units.id');
        $targetUnitIds = $target->units()->pluck('organizational_units.id');

        if ($authUnitIds->intersect($targetUnitIds)->isNotEmpty()) {
            return true;
        }

        $authOrgIds = $authUser->organizations()->pluck('organizations.id');
        $targetOrgIds = $target->organizations()->pluck('organizations.id');

        return $authOrgIds->intersect($targetOrgIds)->isNotEmpty();
    }
```

- [ ] **Step 4: Update `ScopePolicy` (remove placeholder)**

`app/Policies/ScopePolicy.php` — current content uses `view:product` on an `OrganizationalUnit`. Change to the correct permission name and drop the demo:

```php
<?php

namespace App\Policies;

use App\Models\User;
use Core\Organization\Models\OrganizationalUnit;
use Core\Support\Scope;
use Illuminate\Auth\Access\HandlesAuthorization;

class ScopePolicy
{
    use HandlesAuthorization;

    public function view(User $authUser, OrganizationalUnit $unit): bool
    {
        return $authUser->can('view:organizational_unit')
            && Scope::can($authUser, $unit->id);
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Authorization/UserPolicyScopeTest.php`
Expected: PASS (3 passed).

- [ ] **Step 6: Commit**

```bash
git add app/Policies/UserPolicy.php app/Policies/ScopePolicy.php tests/Feature/Authorization/UserPolicyScopeTest.php
git commit -m "feat: make user policy scope-aware, fix ScopePolicy (TODO 8.2)"
```

---

### Task 7: Resource query enforcement

**Files:**
- Modify: `app/Filament/Resources/Organizations/OrganizationResource.php`
- Modify: `app/Filament/Resources/OrganizationalUnits/OrganizationalUnitResource.php`
- Modify: `app/Filament/Resources/Users/UserResource.php`
- Test: `tests/Feature/Authorization/ResourceScopeTest.php`

**Interfaces:**
- Consumes: `Core\Support\Scope::isSuperAdmin` + `Scope::userScope` / `Scope::userUnits` / `Scope::userOrganizations`.
- Produces:
  - Each resource's `getEloquentQuery()` restricted for non-super-admin:
    - OrganizationResource → `Scope::userOrganizations`
    - OrganizationalUnitResource → `Scope::userScope` (units + orgs)
    - UserResource → users sharing a unit or org with auth user.

- [ ] **Step 1: Write the failing test**

`tests/Feature/Authorization/ResourceScopeTest.php`:

```php
<?php

namespace Tests\Feature\Authorization;

use App\Filament\Resources\OrganizationalUnits\OrganizationalUnitResource;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResourceScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_organization_resource_scopes_query_for_non_super_admin(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $role = Role::firstOrCreate(['name' => 'panel_user']);
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->organizations()->attach($orgA->id);

        $this->actingAs($user);

        $query = OrganizationResource::getEloquentQuery();
        $ids = $query->pluck('id');

        $this->assertTrue($ids->contains($orgA->id));
        $this->assertFalse($ids->contains($orgB->id));
    }

    public function test_unit_resource_scopes_query_for_non_super_admin(): void
    {
        $org = Organization::factory()->create();
        $unitA = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);
        $unitB = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);

        $role = Role::firstOrCreate(['name' => 'panel_user']);
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->units()->attach($unitA->id);

        $this->actingAs($user);

        $query = OrganizationalUnitResource::getEloquentQuery();
        $ids = $query->pluck('id');

        $this->assertTrue($ids->contains($unitA->id));
        $this->assertFalse($ids->contains($unitB->id));
    }

    public function test_user_resource_scopes_query_for_non_super_admin(): void
    {
        $org = Organization::factory()->create();
        $unit = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);

        $role = Role::firstOrCreate(['name' => 'panel_user']);
        $authUser = User::factory()->create();
        $authUser->assignRole($role);
        $authUser->units()->attach($unit->id);

        $sameUnitUser = User::factory()->create();
        $sameUnitUser->units()->attach($unit->id);
        $otherUser = User::factory()->create();

        $this->actingAs($authUser);

        $query = UserResource::getEloquentQuery();
        $ids = $query->pluck('id');

        $this->assertTrue($ids->contains($sameUnitUser->id));
        $this->assertFalse($ids->contains($otherUser->id));
    }

    public function test_super_admin_sees_all_records(): void
    {
        $org = Organization::factory()->create();
        $unit = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);
        $otherUser = User::factory()->create();

        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $authUser = User::factory()->create();
        $authUser->assignRole($role);

        $this->actingAs($authUser);

        $this->assertSame(1, OrganizationResource::getEloquentQuery()->count());
        $this->assertSame(1, OrganizationalUnitResource::getEloquentQuery()->count());
        $this->assertSame(2, UserResource::getEloquentQuery()->count());   // authUser + otherUser
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Authorization/ResourceScopeTest.php`
Expected: FAIL — queries return all records (no scoping yet).

- [ ] **Step 3: Update `OrganizationResource`**

Add to `app/Filament/Resources/Organizations/OrganizationResource.php`:

```php
use Core\Support\Scope;
use Illuminate\Database\Eloquent\Builder;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(
                ! Scope::isSuperAdmin(auth()->user()),
                fn (Builder $q) => Scope::userOrganizations($q, auth()->user())
            );
    }
```

- [ ] **Step 4: Update `OrganizationalUnitResource`**

Add to `app/Filament/Resources/OrganizationalUnits/OrganizationalUnitResource.php`:

```php
use Core\Support\Scope;
use Illuminate\Database\Eloquent\Builder;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(
                ! Scope::isSuperAdmin(auth()->user()),
                fn (Builder $q) => Scope::userScope($q, auth()->user())
            );
    }
```

- [ ] **Step 5: Update `UserResource`**

Add to `app/Filament/Resources/Users/UserResource.php`:

```php
use Core\Support\Scope;
use Illuminate\Database\Eloquent\Builder;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(
                ! Scope::isSuperAdmin(auth()->user()),
                fn (Builder $q) => $q->where(function (Builder $q) {
                    $authUser = auth()->user();

                    $unitIds = $authUser->units()->pluck('organizational_units.id');
                    $orgIds = $authUser->organizations()->pluck('organizations.id');

                    $q->whereHas('units', fn (Builder $uq) => $uq->whereIn('organizational_units.id', $unitIds))
                        ->orWhereHas('organizations', fn (Builder $oq) => $oq->whereIn('organizations.id', $orgIds));
                })
            );
    }
```

> Note: `Scope::userScope` operates on columns `organizational_unit_id`/`organization_id` directly on the queried table — for `users` there is no such column, so we scope via the `units()`/`organizations()` relations instead. This matches the `sharesScope` logic from Task 6.

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Authorization/ResourceScopeTest.php`
Expected: PASS (4 passed).

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Resources/Organizations/OrganizationResource.php app/Filament/Resources/OrganizationalUnits/OrganizationalUnitResource.php app/Filament/Resources/Users/UserResource.php tests/Feature/Authorization/ResourceScopeTest.php
git commit -m "feat: scope resource queries by user access (TODO 8.3)"
```

---

### Task 8: Seeder hierarchy + docs + TODO

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `docs/conventions/scope.md`
- Modify: `docs/TODO.md`

**Interfaces:**
- Consumes: `App\Models\Role` (Task 1), default role names from M5.
- Produces: Default hierarchy `administrator → manager → supervisor → staff → viewer` set on each seed; docs updated; TODO §8 checked off.

- [ ] **Step 1: Update `DatabaseSeeder`**

Current seeder (from M5):

```php
        $roles = ['super_admin', 'administrator', 'manager', 'supervisor', 'staff', 'viewer', 'panel_user'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
```

Replace with:

```php
        $roles = ['super_admin', 'administrator', 'manager', 'supervisor', 'staff', 'viewer', 'panel_user'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $administrator = Role::where('name', 'administrator')->first();
        $manager = Role::where('name', 'manager')->first();
        $supervisor = Role::where('name', 'supervisor')->first();
        $staff = Role::where('name', 'staff')->first();
        $viewer = Role::where('name', 'viewer')->first();

        $supervisor->update(['parent_role_id' => $manager->id]);
        $staff->update(['parent_role_id' => $supervisor->id]);
        $viewer->update(['parent_role_id' => $staff->id]);
        $administrator->update(['parent_role_id' => null]);
```

(Import `App\Models\Role` in the seeder — it already imports `Spatie\Permission\Models\Role`; switch to `App\Models\Role`.)

- [ ] **Step 2: Verify seeder + hierarchy test**

Run: `php artisan test tests/Unit/Security/DefaultRolesSeederTest.php`
Expected: PASS (still 7 roles). The hierarchy assertions are covered by `RoleHierarchyTest`/`RoleServiceTest`.

- [ ] **Step 3: Update `docs/conventions/scope.md`**

Append a section:

```markdown
## Role Hierarchy

- Kolom `parent_role_id` (self-ref) pada tabel `roles` — arah child→parent.
- Inheritance top-down: permission role + semua ancestor (via `App\Services\RoleService`).
- Default: `administrator` (tanpa parent) → `manager` → `supervisor` → `staff` → `viewer`.
- `super_admin` bukan bagian hierarchy — bypass gate & scope (invariant).
- `panel_user` bukan bagian hierarchy (role teknis akses panel).
- Cegah cycle via `RoleService::wouldCreateCycle`.
```

- [ ] **Step 4: Update `docs/TODO.md`**

Check off §8.1, §8.2, §8.3 items that are now implemented (all except any explicitly deferred), and add a note referencing the spec/plan.

- [ ] **Step 5: Run full quality gate**

Run: `composer check`
Expected: Pint passes, all tests pass, PHPStan passes.

- [ ] **Step 6: Commit**

```bash
git add database/seeders/DatabaseSeeder.php docs/conventions/scope.md docs/TODO.md
git commit -m "feat: seed role hierarchy and document authorization (TODO 8)"
```

---

## Self-Review Notes

- **Spec §3.2 → Tasks 1, 3, 8:** Role hierarchy (migration, model, service, seeder).
- **Spec §3.3 → Task 3, 4:** RoleService + gate wiring.
- **Spec §3.4 → Tasks 2, 5, 6:** Scope helper + scope-aware policies.
- **Spec §3.5 → Task 7:** Resource query enforcement.
- **Spec §3.6 → Task 8:** Seeder hierarchy + config.
- **Spec §4 (Error Handling) → Tasks 3, 5, 6:** Cycle safety (wouldCreateCycle), 403 for out-of-scope, super admin bypass covered by tests.
- **Spec §5 (Testing) → Tasks 1-8:** All test files mapped.
- **Spec §6 (Out of Scope):** UI role hierarchy editor, permission auto-assign, multi-org, audit — correctly not in any task.

## Verification Checklist

- [ ] `composer check` passes (Pint, Pest, PHPStan)
- [ ] `roles` table has `parent_role_id` self-ref FK
- [ ] `App\Models\Role` resolves via Spatie (config/permission.php)
- [ ] Gate grants inherited permissions (staff inherits manager's permissions)
- [ ] Organization/Unit policies reject out-of-scope records; super_admin bypasses
- [ ] UserPolicy scopes update/delete to shared unit/org
- [ ] Resource queries restrict lists for non-super-admin
- [ ] Seeder sets `administrator → manager → supervisor → staff → viewer`

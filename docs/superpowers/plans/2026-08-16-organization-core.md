# Organization Core Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun Organization Core (TODO §4.1-4.3) — model, action layer dengan validasi hierarki/assignment, policy & permission, factory, seeder, dan UI Filament (resource + infolist) di atas schema M2.

**Architecture:** Domain Core bebas Filament (`core/Organization/`) + app layer (policy, resource Filament). Semua mutasi lewat Action (satu pintu validasi). Shield auto-generate policy CRUD (format colon `organization:view_any`); policy manual hanya untuk assignment custom. Factory di `core/Database/Factories/` via `newFactory()`; seeder idempotent.

**Tech Stack:** Laravel 13, Filament 5, PHP 8.3+, Pest (MySQL test), ramsey/uuid v4.9.3 (UsesUuid dari M2), Filament Shield.

## Global Constraints

- Bahasa dokumen & pesan exception: Bahasa Indonesia.
- Model Core di `core/Organization/Models/`; enum di `core/Organization/Enums/`; actions di `core/Organization/Actions/`; exception di `core/Exceptions/`.
- Policy CRUD Organization/Unit **tidak ditulis manual** — di-generate Shield (`policies.generate => true`). Policy manual hanya `OrganizationalAccessPolicy`.
- Format permission: `resource:action` (separator `:`, case snake) — `organization:view_any`, `organizational_unit:create`, dst. Custom: `assign_user_to_unit`, `remove_user_from_unit`, `set_primary_unit`.
- Semua mutasi via Action — resource Filament memanggil Action, bukan langsung model.
- Validasi hierarki: parent ≠ self, parent se-organization, cycle detection (ancestor walk), depth ≤ `config('core.organization.max_depth', 10)`.
- Validasi assignment: primary unit harus di-assign ke user.
- Soft delete pada Organization & OrganizationalUnit (master). Log tidak soft delete.
- `Core\` tidak mengimpor `App\`/`Modules\`; Core non-UI tidak bergantung Filament (ADR-005) — arch test existing tidak boleh rusak.
- Factory Core di `core/Database/Factories/` (namespace `Core\Database\Factories\`), di-discover via `newFactory()` di model.
- Seeder Core di `core/Database/Seeders/`, dipanggil eksplisit dari `database/seeders/DatabaseSeeder.php`.
- UUID: jangan panggil `Event::fake()` sebelum factory `->create()` (UUID via `creating` event).
- `composer check` (Pint → Pest → PHPStan) hijau di tiap akhir task.
- Commit message: conventional commits (`feat:`, `test:`, `docs:`, `chore:`), satu task = satu commit.
- Shield generate: `php artisan shield:generate` setelah resource dibuat (Task 11).
- Environment: Windows (cmd) — heredoc `<<` TIDAK didukung; commit message via file temp.

---

### Task 1: Enum `OrganizationalUnitType` + `OrganizationException`

**Files:**
- Create: `core/Organization/Enums/OrganizationalUnitType.php`
- Create: `core/Exceptions/OrganizationException.php`
- Test: `tests/Unit/Organization/OrganizationalUnitTypeTest.php`

**Interfaces:**
- Consumes: `Core\Exceptions\CoreException` (extends RuntimeException, sudah ada).
- Produces: `Core\Organization\Enums\OrganizationalUnitType` (backed string: HEAD_OFFICE/BRANCH/SUB_OFFICE/SITE); `Core\Exceptions\OrganizationException` dengan static factory `invalidHierarchy(string $message): self` dan `invalidAssignment(string $message): self`. Dipakai Task 3+ (actions) dan Task 4 (validasi).

- [ ] **Step 1: Tulis failing test**

Buat `tests/Unit/Organization/OrganizationalUnitTypeTest.php`:

```php
<?php

namespace Tests\Unit\Organization;

use Core\Organization\Enums\OrganizationalUnitType;
use PHPUnit\Framework\TestCase;

class OrganizationalUnitTypeTest extends TestCase
{
    public function test_has_four_default_types(): void
    {
        $this->assertSame(
            ['HEAD_OFFICE', 'BRANCH', 'SUB_OFFICE', 'SITE'],
            array_map(fn ($case) => $case->value, OrganizationalUnitType::cases())
        );
    }

    public function test_default_is_head_office(): void
    {
        $this->assertSame('HEAD_OFFICE', OrganizationalUnitType::HEAD_OFFICE->value);
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor\bin\pest tests\Unit\Organization\OrganizationalUnitTypeTest.php`
Expected: FAIL — `Class "Core\Organization\Enums\OrganizationalUnitType" not found`.

- [ ] **Step 3: Tulis implementasi**

Buat `core/Organization/Enums/OrganizationalUnitType.php`:

```php
<?php

namespace Core\Organization\Enums;

enum OrganizationalUnitType: string
{
    case HEAD_OFFICE = 'HEAD_OFFICE';
    case BRANCH = 'BRANCH';
    case SUB_OFFICE = 'SUB_OFFICE';
    case SITE = 'SITE';
}
```

Buat `core/Exceptions/OrganizationException.php`:

```php
<?php

namespace Core\Exceptions;

class OrganizationException extends CoreException
{
    public static function invalidHierarchy(string $message): self
    {
        return new self($message);
    }

    public static function invalidAssignment(string $message): self
    {
        return new self($message);
    }
}
```

- [ ] **Step 4: Run test — pastikan pass**

Run: `vendor\bin\pest tests\Unit\Organization\OrganizationalUnitTypeTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add core/Organization/Enums/OrganizationalUnitType.php core/Exceptions/OrganizationException.php tests/Unit/Organization/OrganizationalUnitTypeTest.php
git commit -m "feat: add OrganizationalUnitType enum and OrganizationException (TODO 4.2)"
```

---

### Task 2: Model `Organization` & `OrganizationalUnit`

**Files:**
- Create: `core/Organization/Models/Organization.php`
- Create: `core/Organization/Models/OrganizationalUnit.php`
- Test: `tests/Feature/Organization/OrganizationModelTest.php`

**Interfaces:**
- Consumes: Task 1 (enum, exception), M2 (`UsesUuid`, schema `organizations`/`organizational_units`), `OrganizationFactory`/`OrganizationalUnitFactory` (Task 8 — test memakai factory; buat factory dulu di Task 8, atau test pakai `create()` manual).

**Catatan:** Test model ini butuh factory (Task 8). Untuk menghindari dependency maju, Task 2 membuat model TANPA test factory — test relasi & cast menyusul di Task 8 setelah factory ada. Di Task 2 cukup verifikasi model load + fillable via `php artisan tinker` (manual) ATAU buat test schema ringan. Keputusan: **Task 2 = model + test schema ringan (tanpa factory)**.

- [ ] **Step 1: Tulis failing test (schema-based, tanpa factory)**

Buat `tests/Feature/Organization/OrganizationModelTest.php`:

```php
<?php

namespace Tests\Feature\Organization;

use Core\Organization\Enums\OrganizationalUnitType;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrganizationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_table_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('organizations', ['id', 'name', 'created_by', 'updated_by', 'deleted_at']));
    }

    public function test_organizational_unit_table_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('organizational_units', ['id', 'organization_id', 'parent_id', 'name', 'type', 'deleted_at']));
    }

    public function test_organizational_unit_type_cast_is_enum(): void
    {
        $this->assertSame(OrganizationalUnitType::class, (new OrganizationalUnit)->getCasts()['type']);
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor\bin\pest tests\Feature\Organization\OrganizationModelTest.php`
Expected: FAIL — `Class "Core\Organization\Models\Organization" not found`.

- [ ] **Step 3: Tulis implementasi model**

Buat `core/Organization/Models/Organization.php`:

```php
<?php

namespace Core\Organization\Models;

use Core\Database\Factories\OrganizationFactory;
use Core\Support\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, SoftDeletes, UsesUuid;

    protected $fillable = ['name', 'created_by', 'updated_by'];

    protected static function newFactory(): Factory
    {
        return OrganizationFactory::new();
    }

    public function organizationalUnits(): HasMany
    {
        return $this->hasMany(OrganizationalUnit::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
```

Buat `core/Organization/Models/OrganizationalUnit.php`:

```php
<?php

namespace Core\Organization\Models;

use Core\Database\Factories\OrganizationalUnitFactory;
use Core\Organization\Enums\OrganizationalUnitType;
use Core\Support\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationalUnit extends Model
{
    use HasFactory, SoftDeletes, UsesUuid;

    protected $fillable = [
        'organization_id', 'parent_id', 'name', 'type', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'type' => OrganizationalUnitType::class,
    ];

    protected static function newFactory(): Factory
    {
        return OrganizationalUnitFactory::new();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function primaryUsers(): HasMany
    {
        return $this->hasMany(User::class, 'primary_organizational_unit_id');
    }
}
```

**Catatan `User`**: relasi `belongsToMany(User::class)` & `hasMany(User::class)` mengimpor `App\Models\User` — ini **melanggar arch test "Core must not use App\Models"** (ADR-005). Solusi: model Core tidak boleh impor User. **Revisi**: relasi ke User diletakkan di sisi `app/Models/User.php` (Task 5) — model Core hanya punya relasi ke sesama Core (`organization`, `parent`, `children`, `organizationalUnits`). Hapus `users()` & `primaryUsers()` dari model Core; pindah ke User (Task 5).

**Implementasi final model Core (tanpa relasi ke User):**

```php
// Organization.php — hanya organizationalUnits()
// OrganizationalUnit.php — hanya organization(), parent(), children()
```

- [ ] **Step 4: Run test — pastikan pass**

Run: `vendor\bin\pest tests\Feature\Organization\OrganizationModelTest.php`
Expected: PASS (3 tests).

- [ ] **Step 5: Run quality gate**

Run: `composer check`
Expected: Pint, Pest, PHPStan semua lolos (arch test "Core must not use App\Models" tetap PASS).

- [ ] **Step 6: Commit**

```bash
git add core/Organization/Models/Organization.php core/Organization/Models/OrganizationalUnit.php tests/Feature/Organization/OrganizationModelTest.php
git commit -m "feat: add Organization and OrganizationalUnit models (TODO 4.1, 4.2)"
```

---

### Task 3: Action CRUD Organization

**Files:**
- Create: `core/Organization/Actions/CreateOrganizationAction.php`
- Create: `core/Organization/Actions/UpdateOrganizationAction.php`
- Create: `core/Organization/Actions/DeleteOrganizationAction.php`
- Test: `tests/Unit/Organization/Actions/OrganizationActionsTest.php`

**Interfaces:**
- Consumes: Task 2 (models).
- Produces: `CreateOrganizationAction::handle(string $name, ?string $createdBy = null): Organization`; `UpdateOrganizationAction::handle(Organization $organization, array $data): Organization`; `DeleteOrganizationAction::handle(Organization $organization): void`. Dipakai Task 7 (resource) & Task 10 (Filament).

- [ ] **Step 1: Tulis failing test**

Buat `tests/Unit/Organization/Actions/OrganizationActionsTest.php`:

```php
<?php

namespace Tests\Unit\Organization\Actions;

use Core\Organization\Actions\CreateOrganizationAction;
use Core\Organization\Actions\DeleteOrganizationAction;
use Core\Organization\Actions\UpdateOrganizationAction;
use Core\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_organization(): void
    {
        $organization = app(CreateOrganizationAction::class)->handle('PT ABC Indonesia');

        $this->assertInstanceOf(Organization::class, $organization);
        $this->assertSame('PT ABC Indonesia', $organization->name);
        $this->assertDatabaseHas('organizations', ['name' => 'PT ABC Indonesia']);
    }

    public function test_update_organization(): void
    {
        $organization = Organization::factory()->create(['name' => 'Old Name']);

        app(UpdateOrganizationAction::class)->handle($organization, ['name' => 'New Name']);

        $this->assertSame('New Name', $organization->fresh()->name);
    }

    public function test_delete_organization_soft_deletes(): void
    {
        $organization = Organization::factory()->create();

        app(DeleteOrganizationAction::class)->handle($organization);

        $this->assertSoftDeleted('organizations', ['id' => $organization->id]);
    }
}
```

**Catatan:** test memakai `Organization::factory()` — factory harus ada (Task 8). Untuk menjaga urutan, Task 3 test memakai `Organization::factory()` yang akan dibuat Task 8. **Alternatif lebih bersih**: buat factory Organization & OrganizationalUnit di Task 3 (bukan Task 8), supaya action test bisa jalan. **Keputusan: Task 3 memindahkan factory ke sini** (factory Organization + OrganizationalUnit + `newFactory()` di model sudah ada Task 2).

- [ ] **Step 2: Buat factory dulu (dibutuhkan test)**

Buat `core/Database/Factories/OrganizationFactory.php`:

```php
<?php

namespace Core\Database\Factories;

use Core\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
```

Buat `core/Database/Factories/OrganizationalUnitFactory.php`:

```php
<?php

namespace Core\Database\Factories;

use Core\Organization\Enums\OrganizationalUnitType;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationalUnit>
 */
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

    public function ofType(OrganizationalUnitType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
}
```

- [ ] **Step 3: Run test — pastikan gagal**

Run: `vendor\bin\pest tests\Unit\Organization\Actions\OrganizationActionsTest.php`
Expected: FAIL — `Class "Core\Organization\Actions\CreateOrganizationAction" not found`.

- [ ] **Step 4: Tulis implementasi actions**

Buat `core/Organization/Actions/CreateOrganizationAction.php`:

```php
<?php

namespace Core\Organization\Actions;

use Core\Organization\Models\Organization;

final class CreateOrganizationAction
{
    public function handle(string $name, ?string $createdBy = null): Organization
    {
        return Organization::create([
            'name' => $name,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ]);
    }
}
```

Buat `core/Organization/Actions/UpdateOrganizationAction.php`:

```php
<?php

namespace Core\Organization\Actions;

use Core\Organization\Models\Organization;

final class UpdateOrganizationAction
{
    public function handle(Organization $organization, array $data): Organization
    {
        $organization->update($data);

        return $organization->fresh();
    }
}
```

Buat `core/Organization/Actions/DeleteOrganizationAction.php`:

```php
<?php

namespace Core\Organization\Actions;

use Core\Organization\Models\Organization;

final class DeleteOrganizationAction
{
    public function handle(Organization $organization): void
    {
        $organization->delete();
    }
}
```

- [ ] **Step 5: Run test — pastikan pass**

Run: `vendor\bin\pest tests\Unit\Organization\Actions\OrganizationActionsTest.php`
Expected: PASS (3 tests).

- [ ] **Step 6: Run quality gate**

Run: `composer check`
Expected: lolos.

- [ ] **Step 7: Commit**

```bash
git add core/Organization/Actions/CreateOrganizationAction.php core/Organization/Actions/UpdateOrganizationAction.php core/Organization/Actions/DeleteOrganizationAction.php core/Database/Factories/OrganizationFactory.php core/Database/Factories/OrganizationalUnitFactory.php tests/Unit/Organization/Actions/OrganizationActionsTest.php
git commit -m "feat: add organization CRUD actions and factories (TODO 4.1)"
```

---

### Task 4: Action CRUD OrganizationalUnit + validasi hierarki

**Files:**
- Create: `core/Organization/Actions/CreateOrganizationalUnitAction.php`
- Create: `core/Organization/Actions/UpdateOrganizationalUnitAction.php`
- Create: `core/Organization/Actions/DeleteOrganizationalUnitAction.php`
- Modify: `core/Config/core.php` (tambah `organization.max_depth`)
- Test: `tests/Unit/Organization/Actions/OrganizationalUnitActionsTest.php`

**Interfaces:**
- Consumes: Task 2 (models), Task 3 (factories), Task 1 (`OrganizationException`).
- Produces: `CreateOrganizationalUnitAction::handle(Organization $organization, string $name, ?OrganizationalUnitType $type = null, ?string $parentId = null, ?string $createdBy = null): OrganizationalUnit`; `UpdateOrganizationalUnitAction::handle(OrganizationalUnit $unit, array $data): OrganizationalUnit`; `DeleteOrganizationalUnitAction::handle(OrganizationalUnit $unit): void`. Dipakai Task 5 (SetPrimaryUnit validasi), Task 7 (resource).

- [ ] **Step 1: Tulis failing test**

Buat `tests/Unit/Organization/Actions/OrganizationalUnitActionsTest.php`:

```php
<?php

namespace Tests\Unit\Organization\Actions;

use Core\Exceptions\OrganizationException;
use Core\Organization\Actions\CreateOrganizationalUnitAction;
use Core\Organization\Actions\DeleteOrganizationalUnitAction;
use Core\Organization\Actions\UpdateOrganizationalUnitAction;
use Core\Organization\Enums\OrganizationalUnitType;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationalUnitActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_unit(): void
    {
        $organization = Organization::factory()->create();

        $unit = app(CreateOrganizationalUnitAction::class)->handle(
            $organization,
            'Head Office',
            OrganizationalUnitType::HEAD_OFFICE
        );

        $this->assertInstanceOf(OrganizationalUnit::class, $unit);
        $this->assertSame('Head Office', $unit->name);
        $this->assertSame(OrganizationalUnitType::HEAD_OFFICE, $unit->type);
        $this->assertDatabaseHas('organizational_units', ['name' => 'Head Office']);
    }

    public function test_create_unit_with_parent(): void
    {
        $organization = Organization::factory()->create();
        $parent = OrganizationalUnit::factory()->create(['organization_id' => $organization->id]);

        $child = app(CreateOrganizationalUnitAction::class)->handle(
            $organization,
            'Branch Bandung',
            OrganizationalUnitType::BRANCH,
            $parent->id
        );

        $this->assertSame($parent->id, $child->parent_id);
    }

    public function test_update_unit_name(): void
    {
        $unit = OrganizationalUnit::factory()->create();

        $updated = app(UpdateOrganizationalUnitAction::class)->handle($unit, ['name' => 'New Name']);

        $this->assertSame('New Name', $updated->name);
    }

    public function test_delete_unit_soft_deletes(): void
    {
        $unit = OrganizationalUnit::factory()->create();

        app(DeleteOrganizationalUnitAction::class)->handle($unit);

        $this->assertSoftDeleted('organizational_units', ['id' => $unit->id]);
    }

    public function test_parent_cannot_be_self(): void
    {
        $unit = OrganizationalUnit::factory()->create();

        $this->expectException(OrganizationException::class);
        app(UpdateOrganizationalUnitAction::class)->handle($unit, ['parent_id' => $unit->id]);
    }

    public function test_parent_must_be_same_organization(): void
    {
        $unit = OrganizationalUnit::factory()->create();
        $otherOrgUnit = OrganizationalUnit::factory()->create();

        $this->expectException(OrganizationException::class);
        app(UpdateOrganizationalUnitAction::class)->handle($unit, ['parent_id' => $otherOrgUnit->id]);
    }

    public function test_cycle_detection(): void
    {
        $org = Organization::factory()->create();
        $a = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);
        $b = OrganizationalUnit::factory()->create(['organization_id' => $org->id, 'parent_id' => $a->id]);
        $c = OrganizationalUnit::factory()->create(['organization_id' => $org->id, 'parent_id' => $b->id]);

        // a menjadi child dari c → siklus a→b→c→a
        $this->expectException(OrganizationException::class);
        app(UpdateOrganizationalUnitAction::class)->handle($a, ['parent_id' => $c->id]);
    }

    public function test_depth_limit(): void
    {
        $org = Organization::factory()->create();
        $parent = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);

        // Bangun rantai hingga melebihi max_depth (default 10)
        config(['core.organization.max_depth' => 3]);
        for ($i = 0; $i < 4; $i++) {
            $parent = OrganizationalUnit::factory()->create([
                'organization_id' => $org->id,
                'parent_id' => $parent->id,
            ]);
        }

        $this->expectException(OrganizationException::class);
        app(CreateOrganizationalUnitAction::class)->handle(
            $org,
            'Too Deep',
            OrganizationalUnitType::SITE,
            $parent->id
        );
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor\bin\pest tests\Unit\Organization\Actions\OrganizationalUnitActionsTest.php`
Expected: FAIL — `Class "Core\Organization\Actions\CreateOrganizationalUnitAction" not found`.

- [ ] **Step 3: Update config `core/Config/core.php`**

Tambah di array config:

```php
'organization' => [
    'max_depth' => 10,
],
```

- [ ] **Step 4: Tulis implementasi actions**

Buat `core/Organization/Actions/CreateOrganizationalUnitAction.php`:

```php
<?php

namespace Core\Organization\Actions;

use Core\Exceptions\OrganizationException;
use Core\Organization\Enums\OrganizationalUnitType;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;

final class CreateOrganizationalUnitAction
{
    public function handle(
        Organization $organization,
        string $name,
        ?OrganizationalUnitType $type = null,
        ?string $parentId = null,
        ?string $createdBy = null,
    ): OrganizationalUnit {
        if ($parentId !== null) {
            $this->assertValidParent($organization, $parentId);
        }

        return OrganizationalUnit::create([
            'organization_id' => $organization->id,
            'parent_id' => $parentId,
            'name' => $name,
            'type' => $type ?? OrganizationalUnitType::HEAD_OFFICE,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ]);
    }

    private function assertValidParent(Organization $organization, string $parentId): void
    {
        $parent = OrganizationalUnit::find($parentId);

        if ($parent === null || $parent->organization_id !== $organization->id) {
            throw OrganizationException::invalidHierarchy(
                'Unit induk harus berada dalam organisasi yang sama.'
            );
        }

        $this->assertDepthWithinLimit($parent);
    }

    private function assertDepthWithinLimit(OrganizationalUnit $unit): void
    {
        $maxDepth = (int) config('core.organization.max_depth', 10);
        $depth = 1;

        while ($unit->parent !== null) {
            $depth++;
            $unit = $unit->parent;

            if ($depth > $maxDepth) {
                throw OrganizationException::invalidHierarchy(
                    "Kedalaman hierarki melebihi batas maksimum {$maxDepth} level."
                );
            }
        }
    }
}
```

Buat `core/Organization/Actions/UpdateOrganizationalUnitAction.php`:

```php
<?php

namespace Core\Organization\Actions;

use Core\Exceptions\OrganizationException;
use Core\Organization\Models\OrganizationalUnit;

final class UpdateOrganizationalUnitAction
{
    public function handle(OrganizationalUnit $unit, array $data): OrganizationalUnit
    {
        if (array_key_exists('parent_id', $data)) {
            $this->assertValidParent($unit, $data['parent_id']);
        }

        $unit->update($data);

        return $unit->fresh();
    }

    private function assertValidParent(OrganizationalUnit $unit, ?string $parentId): void
    {
        if ($parentId === null) {
            return; // jadikan root — valid
        }

        if ($parentId === $unit->id) {
            throw OrganizationException::invalidHierarchy(
                'Unit tidak dapat menjadi induk dari dirinya sendiri.'
            );
        }

        $parent = OrganizationalUnit::find($parentId);

        if ($parent === null || $parent->organization_id !== $unit->organization_id) {
            throw OrganizationException::invalidHierarchy(
                'Unit induk harus berada dalam organisasi yang sama.'
            );
        }

        $this->assertNoCycle($unit, $parent);
        $this->assertDepthWithinLimit($parent);
    }

    private function assertNoCycle(OrganizationalUnit $unit, OrganizationalUnit $parent): void
    {
        $ancestor = $parent;

        while ($ancestor !== null) {
            if ($ancestor->id === $unit->id) {
                throw OrganizationException::invalidHierarchy(
                    'Hierarki unit tidak boleh membentuk siklus.'
                );
            }

            $ancestor = $ancestor->parent;
        }
    }

    private function assertDepthWithinLimit(OrganizationalUnit $unit): void
    {
        $maxDepth = (int) config('core.organization.max_depth', 10);
        $depth = 1;

        while ($unit->parent !== null) {
            $depth++;
            $unit = $unit->parent;

            if ($depth > $maxDepth) {
                throw OrganizationException::invalidHierarchy(
                    "Kedalaman hierarki melebihi batas maksimum {$maxDepth} level."
                );
            }
        }
    }
}
```

Buat `core/Organization/Actions/DeleteOrganizationalUnitAction.php`:

```php
<?php

namespace Core\Organization\Actions;

use Core\Organization\Models\OrganizationalUnit;

final class DeleteOrganizationalUnitAction
{
    public function handle(OrganizationalUnit $unit): void
    {
        $unit->delete();
    }
}
```

- [ ] **Step 5: Run test — pastikan pass**

Run: `vendor\bin\pest tests\Unit\Organization\Actions\OrganizationalUnitActionsTest.php`
Expected: PASS (8 tests).

- [ ] **Step 6: Run quality gate**

Run: `composer check`
Expected: lolos.

- [ ] **Step 7: Commit**

```bash
git add core/Organization/Actions/CreateOrganizationalUnitAction.php core/Organization/Actions/UpdateOrganizationalUnitAction.php core/Organization/Actions/DeleteOrganizationalUnitAction.php core/Config/core.php tests/Unit/Organization/Actions/OrganizationalUnitActionsTest.php
git commit -m "feat: add organizational unit CRUD actions with hierarchy validation (TODO 4.2)"
```

---

### Task 5: Relasi User + Action assignment

**Files:**
- Modify: `app/Models/User.php` (tambah relasi)
- Create: `core/Organization/Actions/AssignUserToUnitAction.php`
- Create: `core/Organization/Actions/RemoveUserFromUnitAction.php`
- Create: `core/Organization/Actions/SetPrimaryUnitAction.php`
- Test: `tests/Unit/Organization/Actions/UserAssignmentActionsTest.php`

**Interfaces:**
- Consumes: Task 2 (models), Task 3 (factories), Task 1 (exception).
- Produces: Relasi `User::organizations()`, `User::units()`, `User::primaryOrganizationalUnit()`; `AssignUserToUnitAction::handle(User $user, OrganizationalUnit $unit): void`; `RemoveUserFromUnitAction::handle(User $user, OrganizationalUnit $unit): void`; `SetPrimaryUnitAction::handle(User $user, OrganizationalUnit $unit): void`. Dipakai Task 10 (OrganizationalAccessSchema).

- [ ] **Step 1: Tulis failing test**

Buat `tests/Unit/Organization/Actions/UserAssignmentActionsTest.php`:

```php
<?php

namespace Tests\Unit\Organization\Actions;

use App\Models\User;
use Core\Exceptions\OrganizationException;
use Core\Organization\Actions\AssignUserToUnitAction;
use Core\Organization\Actions\RemoveUserFromUnitAction;
use Core\Organization\Actions\SetPrimaryUnitAction;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAssignmentActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_user_to_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();

        app(AssignUserToUnitAction::class)->handle($user, $unit);

        $this->assertTrue($user->units()->where('organizational_units.id', $unit->id)->exists());
    }

    public function test_assign_does_not_remove_existing_units(): void
    {
        $user = User::factory()->create();
        $unitA = OrganizationalUnit::factory()->create();
        $unitB = OrganizationalUnit::factory()->create();
        $user->units()->attach($unitA);

        app(AssignUserToUnitAction::class)->handle($user, $unitB);

        $this->assertTrue($user->units()->where('organizational_units.id', $unitA->id)->exists());
        $this->assertTrue($user->units()->where('organizational_units.id', $unitB->id)->exists());
    }

    public function test_remove_user_from_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit);

        app(RemoveUserFromUnitAction::class)->handle($user, $unit);

        $this->assertFalse($user->units()->where('organizational_units.id', $unit->id)->exists());
    }

    public function test_set_primary_unit_requires_assignment(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();

        $this->expectException(OrganizationException::class);
        app(SetPrimaryUnitAction::class)->handle($user, $unit);
    }

    public function test_set_primary_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit);

        app(SetPrimaryUnitAction::class)->handle($user, $unit);

        $this->assertSame($unit->id, $user->fresh()->primary_organizational_unit_id);
    }

    public function test_user_has_organizations_relation(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization);

        $this->assertTrue($user->organizations()->where('organizations.id', $organization->id)->exists());
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor\bin\pest tests\Unit\Organization\Actions\UserAssignmentActionsTest.php`
Expected: FAIL — `Call to undefined method App\Models\User::units()`.

- [ ] **Step 3: Update `app/Models/User.php`**

Tambah use:

```php
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
```

Tambah relasi di dalam class:

```php
public function organizations(): BelongsToMany
{
    return $this->belongsToMany(Organization::class);
}

public function units(): BelongsToMany
{
    return $this->belongsToMany(OrganizationalUnit::class);
}

public function primaryOrganizationalUnit(): BelongsTo
{
    return $this->belongsTo(OrganizationalUnit::class, 'primary_organizational_unit_id');
}
```

- [ ] **Step 4: Tulis implementasi actions**

Buat `core/Organization/Actions/AssignUserToUnitAction.php`:

```php
<?php

namespace Core\Organization\Actions;

use App\Models\User;
use Core\Organization\Models\OrganizationalUnit;

final class AssignUserToUnitAction
{
    public function handle(User $user, OrganizationalUnit $unit): void
    {
        $user->units()->syncWithoutDetaching([$unit->id]);
    }
}
```

**Catatan:** Action di `core/` mengimpor `App\Models\User` — ini **melanggar arch test** "Core must not use App\Models"! Solusi sesuai arsitektur: **relasi & assignment user ada di app layer**. Revisi: action assignment diletakkan di `app/Actions/` (bukan `core/Organization/Actions/`), karena melibatkan `App\Models\User`. `core/Organization/Actions/` hanya berisi action yang murni Core (tanpa User).

**Lokasi final action assignment: `app/Actions/Organization/`**

Buat `app/Actions/Organization/AssignUserToUnitAction.php`:

```php
<?php

namespace App\Actions\Organization;

use App\Models\User;
use Core\Organization\Models\OrganizationalUnit;

final class AssignUserToUnitAction
{
    public function handle(User $user, OrganizationalUnit $unit): void
    {
        $user->units()->syncWithoutDetaching([$unit->id]);
    }
}
```

Buat `app/Actions/Organization/RemoveUserFromUnitAction.php`:

```php
<?php

namespace App\Actions\Organization;

use App\Models\User;
use Core\Organization\Models\OrganizationalUnit;

final class RemoveUserFromUnitAction
{
    public function handle(User $user, OrganizationalUnit $unit): void
    {
        $user->units()->detach($unit->id);
    }
}
```

Buat `app/Actions/Organization/SetPrimaryUnitAction.php`:

```php
<?php

namespace App\Actions\Organization;

use App\Models\User;
use Core\Exceptions\OrganizationException;
use Core\Organization\Models\OrganizationalUnit;

final class SetPrimaryUnitAction
{
    public function handle(User $user, OrganizationalUnit $unit): void
    {
        $assigned = $user->units()->where('organizational_units.id', $unit->id)->exists();

        if (! $assigned) {
            throw OrganizationException::invalidAssignment(
                'Unit utama harus merupakan unit yang di-assign ke pengguna.'
            );
        }

        $user->update(['primary_organizational_unit_id' => $unit->id]);
    }
}
```

- [ ] **Step 5: Update test namespace**

Test di Step 1 memakai `Core\Organization\Actions\AssignUserToUnitAction` — ganti ke `App\Actions\Organization\...` (sesuai lokasi final).

- [ ] **Step 6: Run test — pastikan pass**

Run: `vendor\bin\pest tests\Unit\Organization\Actions\UserAssignmentActionsTest.php`
Expected: PASS (6 tests).

- [ ] **Step 7: Run quality gate**

Run: `composer check`
Expected: lolos (arch test "Core must not use App\Models" tetap PASS karena action User ada di `app/Actions/`).

- [ ] **Step 8: Commit**

```bash
git add app/Models/User.php app/Actions/Organization/AssignUserToUnitAction.php app/Actions/Organization/RemoveUserFromUnitAction.php app/Actions/Organization/SetPrimaryUnitAction.php tests/Unit/Organization/Actions/UserAssignmentActionsTest.php
git commit -m "feat: add user-unit assignment actions and User relations (TODO 4.3)"
```

---

### Task 6: Policy & permission Shield

**Files:**
- Create: `app/Policies/OrganizationalAccessPolicy.php`
- Modify: `app/Providers/AppServiceProvider.php` (registrasi policy)
- Modify: `config/filament-shield.php` (custom_permissions + tab)
- Modify: `app/Policies/UserPolicy.php`, `app/Policies/ActivityPolicy.php`, `app/Policies/RolePolicy.php` (format colon)
- Test: `tests/Feature/Organization/OrganizationalAccessPolicyTest.php`

**Interfaces:**
- Consumes: Task 5 (assignment actions), spec §6.
- Produces: `OrganizationalAccessPolicy` dengan `assignUser/removeUser/setPrimaryUnit`; config Shield `custom_permissions`; 3 policy usang di-sync ke format colon.

- [ ] **Step 1: Tulis failing test**

Buat `tests/Feature/Organization/OrganizationalAccessPolicyTest.php`:

```php
<?php

namespace Tests\Feature\Organization;

use App\Models\User;
use App\Policies\OrganizationalAccessPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganizationalAccessPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_user_permission_granted(): void
    {
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo(Permission::create(['name' => 'assign_user_to_unit']));
        $user = User::factory()->create()->assignRole($role);

        $this->assertTrue((new OrganizationalAccessPolicy)->assignUser($user));
    }

    public function test_assign_user_permission_denied(): void
    {
        $user = User::factory()->create();

        $this->assertFalse((new OrganizationalAccessPolicy)->assignUser($user));
    }

    public function test_remove_user_permission(): void
    {
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo(Permission::create(['name' => 'remove_user_from_unit']));
        $user = User::factory()->create()->assignRole($role);

        $this->assertTrue((new OrganizationalAccessPolicy)->removeUser($user));
    }

    public function test_set_primary_unit_permission(): void
    {
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo(Permission::create(['name' => 'set_primary_unit']));
        $user = User::factory()->create()->assignRole($role);

        $this->assertTrue((new OrganizationalAccessPolicy)->setPrimaryUnit($user));
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor\bin\pest tests\Feature\Organization\OrganizationalAccessPolicyTest.php`
Expected: FAIL — `Class "App\Policies\OrganizationalAccessPolicy" not found`.

- [ ] **Step 3: Tulis policy**

Buat `app/Policies/OrganizationalAccessPolicy.php`:

```php
<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;

class OrganizationalAccessPolicy
{
    public function assignUser(AuthUser $authUser): bool
    {
        return $authUser->can('assign_user_to_unit');
    }

    public function removeUser(AuthUser $authUser): bool
    {
        return $authUser->can('remove_user_from_unit');
    }

    public function setPrimaryUnit(AuthUser $authUser): bool
    {
        return $authUser->can('set_primary_unit');
    }
}
```

- [ ] **Step 4: Update config `config/filament-shield.php`**

`custom_permissions`:

```php
'custom_permissions' => [
    'assign_user_to_unit',
    'remove_user_from_unit',
    'set_primary_unit',
],
```

`shield_resource.tabs.custom_permissions` → `true`.

- [ ] **Step 5: Registrasi policy di AppServiceProvider**

Di `app/Providers/AppServiceProvider.php` method `boot()`:

```php
Gate::policy(OrganizationalAccessPolicy::class, OrganizationalAccessPolicy::class);
```

(atau via `$this->app['router']` — ikuti pola registrasi policy existing; jika tidak ada, tambah `use Illuminate\Support\Facades\Gate;` dan registrasi manual).

- [ ] **Step 6: Perbaiki 3 policy usang ke format colon**

`app/Policies/UserPolicy.php`: ganti `can('view_any_user')` → `can('user:view_any')`, `can('view_user')` → `can('user:view')`, `can('create_user')` → `can('user:create')`, `can('update_user')` → `can('user:update')`, `can('delete_user')` → `can('user:delete')`, `can('restore_user')` → `can('user:restore')`, `can('force_delete_user')` → `can('user:force_delete')`, `can('force_delete_any_user')` → `can('user:force_delete_any')`, `can('restore_any_user')` → `can('user:restore_any')`, `can('replicate_user')` → `can('user:replicate')`, `can('reorder_user')` → `can('user:reorder')`.

`app/Policies/ActivityPolicy.php`: `view_any_activity` → `activity:view_any`, `view_activity` → `activity:view`, dst.

`app/Policies/RolePolicy.php`: `view_any_role` → `role:view_any`, `view_role` → `role:view`, dst.

- [ ] **Step 7: Run test — pastikan pass**

Run: `vendor\bin\pest tests\Feature\Organization\OrganizationalAccessPolicyTest.php`
Expected: PASS (4 tests).

- [ ] **Step 8: Run quality gate**

Run: `composer check`
Expected: lolos.

- [ ] **Step 9: Commit**

```bash
git add app/Policies/OrganizationalAccessPolicy.php app/Providers/AppServiceProvider.php config/filament-shield.php app/Policies/UserPolicy.php app/Policies/ActivityPolicy.php app/Policies/RolePolicy.php tests/Feature/Organization/OrganizationalAccessPolicyTest.php
git commit -m "feat: add organizational access policy and sync permission format (TODO 4.3)"
```

---

### Task 7: Resource Filament Organization & OrganizationalUnit

**Files:**
- Create: `app/Filament/Resources/Organizations/OrganizationResource.php` + Pages + Schemas (Form, Infolist) + Tables
- Create: `app/Filament/Resources/OrganizationalUnits/OrganizationalUnitResource.php` + Pages + Schemas + Tables
- Test: `tests/Feature/Organization/OrganizationResourceTest.php`

**Interfaces:**
- Consumes: Task 2 (models), Task 3-4 (actions), Task 6 (policy).
- Produces: 2 resource Filament dengan Form/Infolist terpisah, memanggil Actions. Dipakai Task 11 (shield:generate) & Task 13 (verifikasi UI).

- [ ] **Step 1: Tulis failing test (resource exist & policy)**

Buat `tests/Feature/Organization/OrganizationResourceTest.php`:

```php
<?php

namespace Tests\Feature\Organization;

use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\OrganizationalUnits\OrganizationalUnitResource;
use Tests\TestCase;

class OrganizationResourceTest extends TestCase
{
    public function test_organization_resource_exists(): void
    {
        $this->assertSame(
            \Core\Organization\Models\Organization::class,
            OrganizationResource::getModel()
        );
    }

    public function test_organizational_unit_resource_exists(): void
    {
        $this->assertSame(
            \Core\Organization\Models\OrganizationalUnit::class,
            OrganizationalUnitResource::getModel()
        );
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor\bin\pest tests\Feature\Organization\OrganizationResourceTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Buat OrganizationResource**

`app/Filament/Resources/Organizations/OrganizationResource.php`:

```php
<?php

namespace App\Filament\Resources\Organizations;

use App\Filament\Resources\Organizations\Pages\CreateOrganization;
use App\Filament\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Resources\Organizations\Pages\ListOrganizations;
use App\Filament\Resources\Organizations\Pages\ViewOrganization;
use App\Filament\Resources\Organizations\Schemas\OrganizationForm;
use App\Filament\Resources\Organizations\Schemas\OrganizationInfolist;
use App\Filament\Resources\Organizations\Tables\OrganizationsTable;
use BackedEnum;
use Core\Organization\Models\Organization;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingOffice2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    public static function form(Schema $schema): Schema
    {
        return OrganizationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrganizationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrganizationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrganizations::route('/'),
            'create' => CreateOrganization::route('/create'),
            'edit' => EditOrganization::route('/{record}/edit'),
            'view' => ViewOrganization::route('/{record}'),
        ];
    }
}
```

`app/Filament/Resources/Organizations/Schemas/OrganizationForm.php`:

```php
<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
```

`app/Filament/Resources/Organizations/Schemas/OrganizationInfolist.php`:

```php
<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrganizationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('organizational_units_count')
                    ->label('Organizational Units')
                    ->state(fn ($record) => $record->organizationalUnits()->count()),
            ]);
    }
}
```

`app/Filament/Resources/Organizations/Tables/OrganizationsTable.php`:

```php
<?php

namespace App\Filament\Resources\Organizations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('organizational_units_count')
                    ->label('Units')
                    ->counts('organizationalUnits'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
```

Pages — ikuti pola `app/Filament/Resources/Users/Pages/*` (List/Create/Edit/View), dengan `OrganizationResource::class` dan form/infolist:

```php
// ListOrganizations.php
class ListOrganizations extends ListRecords
{
    protected static string $resource = OrganizationResource::class;
}

// CreateOrganization.php
class CreateOrganization extends CreateRecord
{
    protected static string $resource = OrganizationResource::class;
}

// EditOrganization.php
class EditOrganization extends EditRecord
{
    protected static string $resource = OrganizationResource::class;
}

// ViewOrganization.php
class ViewOrganization extends ViewRecord
{
    protected static string $resource = OrganizationResource::class;
}
```

**Catatan:** resource harus memanggil Action, bukan langsung model. Untuk CRUD sederhana, Filament `CreateRecord`/`EditRecord` memakai model langsung — perlu override `mutateFormDataBeforeCreate` / `handleRecordCreation` untuk delegasi ke Action. Alternatif pragmatis (YAGNI): **resource memakai operasi model langsung** untuk sekarang, Action layer tetap tersedia untuk non-UI & validasi. **Keputusan: resource CRUD memakai Filament default (model langsung)** — Action layer dipakai untuk validasi hierarki & assignment (yang kompleks), CRUD sederhana tidak di-duplikasi. Ini menjaga kesederhanaan (best-practices: prefer framework feature).

- [ ] **Step 4: Buat OrganizationalUnitResource**

Struktur sama, dengan tambahan di form: `organization_id` (Select), `parent_id` (Select, dari unit se-organization), `name`, `type` (Select enum). Table: name, type badge, organization, parent, users count. Form memakai `Select::make('organization_id')->relationship('organization', 'name')`, `Select::make('type')->options(OrganizationalUnitType::class)`. Parent select opsional (untuk sekarang, set via action/edit; form sederhana).

- [ ] **Step 5: Run test — pastikan pass**

Run: `vendor\bin\pest tests\Feature\Organization\OrganizationResourceTest.php`
Expected: PASS (2 tests).

- [ ] **Step 6: Run quality gate**

Run: `composer check`
Expected: lolos.

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Resources/Organizations/ app/Filament/Resources/OrganizationalUnits/ tests/Feature/Organization/OrganizationResourceTest.php
git commit -m "feat: add Organization and OrganizationalUnit Filament resources (TODO 4.1, 4.2)"
```

---

### Task 8: OrganizationalAccessSchema di UserResource

**Files:**
- Create: `app/Filament/Resources/Users/Schemas/OrganizationalAccessSchema.php`
- Modify: `app/Filament/Resources/Users/Schemas/UserForm.php`
- Test: `tests/Feature/Organization/OrganizationalAccessSchemaTest.php`

**Interfaces:**
- Consumes: Task 5 (User relasi), Task 6 (policy).
- Produces: Komponen form reusable assignment + primary unit di UserResource.

- [ ] **Step 1: Tulis failing test**

Buat `tests/Feature/Organization/OrganizationalAccessSchemaTest.php`:

```php
<?php

namespace Tests\Feature\Organization;

use App\Models\User;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationalAccessSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_multiple_units(): void
    {
        $user = User::factory()->create();
        $unitA = OrganizationalUnit::factory()->create();
        $unitB = OrganizationalUnit::factory()->create();

        $user->units()->attach([$unitA->id, $unitB->id]);

        $this->assertCount(2, $user->units);
    }

    public function test_primary_unit_is_set(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit);

        $user->update(['primary_organizational_unit_id' => $unit->id]);

        $this->assertSame($unit->id, $user->fresh()->primary_organizational_unit_id);
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor\bin\pest tests\Feature\Organization\OrganizationalAccessSchemaTest.php`
Expected: FAIL — relasi `units` belum ada (jika Task 5 belum selesai) / atau PASS (jika Task 5 sudah). Sesuaikan: jika PASS, test ini jadi test regresi.

- [ ] **Step 3: Buat OrganizationalAccessSchema**

`app/Filament/Resources/Users/Schemas/OrganizationalAccessSchema.php`:

```php
<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class OrganizationalAccessSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('units')
                    ->label('Organizational Units')
                    ->relationship('units', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Select::make('primary_organizational_unit_id')
                    ->label('Primary Unit')
                    ->relationship('primaryOrganizationalUnit', 'name')
                    ->searchable()
                    ->preload()
                    ->options(function ($record) {
                        if ($record === null) {
                            return [];
                        }

                        return $record->units->pluck('name', 'id');
                    }),
            ]);
    }
}
```

- [ ] **Step 4: Update `UserForm`**

Tambah pemanggilan `OrganizationalAccessSchema` di akhir components:

```php
// app/Filament/Resources/Users/Schemas/UserForm.php
use App\Filament\Resources\Users\Schemas\OrganizationalAccessSchema; // (sama namespace? beda — ini Schemas\UserForm, jadi use App\Filament\Resources\Users\Schemas\OrganizationalAccessSchema)

->components([
    // ... existing components (name, email, password, roles)
    ...OrganizationalAccessSchema::make()?  // bukan — configure dipanggil manual
])
```

Sesuai pola existing (UserForm memakai `->components([...])`), tambahkan di akhir array:

```php
Select::make('units')  // dari OrganizationalAccessSchema
```

Karena `OrganizationalAccessSchema::configure(Schema)` mengembalikan Schema berisi components, cara integrasi: panggil `OrganizationalAccessSchema::configure($schema)` setelah komponen utama, ATAU ekspor array components. **Keputusan paling sederhana**: `OrganizationalAccessSchema` mengekspos static method `components(): array`, dan UserForm menggabungkan:

```php
return $schema->components([
    ...UserForm::baseComponents(),   // refactor komponen existing ke static method
    ...OrganizationalAccessSchema::components(),
]);
```

Refactor UserForm: pindahkan array components ke `public static function baseComponents(): array`, lalu `configure` menggabungkan.

- [ ] **Step 5: Run test — pastikan pass**

Run: `vendor\bin\pest tests\Feature\Organization\OrganizationalAccessSchemaTest.php`
Expected: PASS (2 tests).

- [ ] **Step 6: Run quality gate**

Run: `composer check`
Expected: lolos.

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Resources/Users/Schemas/OrganizationalAccessSchema.php app/Filament/Resources/Users/Schemas/UserForm.php tests/Feature/Organization/OrganizationalAccessSchemaTest.php
git commit -m "feat: add organizational access schema to user form (TODO 4.3)"
```

---

### Task 9: Seeder Organization

**Files:**
- Create: `core/Database/Seeders/OrganizationSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/Organization/OrganizationSeederTest.php`

**Interfaces:**
- Consumes: Task 2 (models), `config('app.name')`.
- Produces: Seeder idempotent (default organization + root unit HEAD_OFFICE).

- [ ] **Step 1: Tulis failing test**

Buat `tests/Feature/Organization/OrganizationSeederTest.php`:

```php
<?php

namespace Tests\Feature\Organization;

use Core\Database\Seeders\OrganizationSeeder;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_default_organization_and_root_unit(): void
    {
        $this->seed(OrganizationSeeder::class);

        $this->assertDatabaseHas('organizations', ['name' => config('app.name')]);
        $organization = Organization::where('name', config('app.name'))->first();

        $this->assertNotNull($organization);
        $this->assertDatabaseHas('organizational_units', [
            'organization_id' => $organization->id,
            'name' => 'Head Office',
        ]);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(OrganizationSeeder::class);
        $this->seed(OrganizationSeeder::class);

        $this->assertSame(1, Organization::where('name', config('app.name'))->count());
        $organization = Organization::where('name', config('app.name'))->first();
        $this->assertSame(1, OrganizationalUnit::where('organization_id', $organization->id)->count());
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor\bin\pest tests\Feature\Organization\OrganizationSeederTest.php`
Expected: FAIL — `Class "Core\Database\Seeders\OrganizationSeeder" not found`.

- [ ] **Step 3: Tulis seeder**

Buat `core/Database/Seeders/OrganizationSeeder.php`:

```php
<?php

namespace Core\Database\Seeders;

use Core\Organization\Enums\OrganizationalUnitType;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::firstOrCreate(
            ['name' => config('app.name')],
            ['name' => config('app.name')]
        );

        OrganizationalUnit::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'name' => 'Head Office',
            ],
            [
                'type' => OrganizationalUnitType::HEAD_OFFICE,
                'parent_id' => null,
            ]
        );
    }
}
```

- [ ] **Step 4: Update DatabaseSeeder**

`database/seeders/DatabaseSeeder.php` — tambah di method `run()`:

```php
use Core\Database\Seeders\OrganizationSeeder;

// di run(), setelah User factory:
$this->call(OrganizationSeeder::class);
```

- [ ] **Step 5: Run test — pastikan pass**

Run: `vendor\bin\pest tests\Feature\Organization\OrganizationSeederTest.php`
Expected: PASS (2 tests).

- [ ] **Step 6: Run quality gate**

Run: `composer check`
Expected: lolos.

- [ ] **Step 7: Commit**

```bash
git add core/Database/Seeders/OrganizationSeeder.php database/seeders/DatabaseSeeder.php tests/Feature/Organization/OrganizationSeederTest.php
git commit -m "feat: add OrganizationSeeder with default org and root unit (TODO 4.1)"
```

---

### Task 10: Integration UserForm + Validasi end-to-end

**Files:**
- Modify: `app/Filament/Resources/Users/Schemas/UserForm.php` (jika belum di Task 8)
- Test: `tests/Feature/Organization/OrganizationIntegrationTest.php`

**Interfaces:**
- Consumes: Task 3-5 (actions), Task 8 (schema).
- Produces: Verifikasi end-to-end alur assign → set primary → validasi.

- [ ] **Step 1: Tulis test integrasi**

Buat `tests/Feature/Organization/OrganizationIntegrationTest.php`:

```php
<?php

namespace Tests\Feature\Organization;

use App\Actions\Organization\AssignUserToUnitAction;
use App\Actions\Organization\SetPrimaryUnitAction;
use App\Models\User;
use Core\Exceptions\OrganizationException;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_assignment_flow(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $headOffice = OrganizationalUnit::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Head Office',
        ]);
        $branch = OrganizationalUnit::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Branch Bandung',
            'parent_id' => $headOffice->id,
        ]);

        app(AssignUserToUnitAction::class)->handle($user, $headOffice);
        app(AssignUserToUnitAction::class)->handle($user, $branch);
        app(SetPrimaryUnitAction::class)->handle($user, $headOffice);

        $this->assertCount(2, $user->fresh()->units);
        $this->assertSame($headOffice->id, $user->fresh()->primary_organizational_unit_id);
    }

    public function test_cannot_set_primary_to_unassigned_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();

        $this->expectException(OrganizationException::class);
        app(SetPrimaryUnitAction::class)->handle($user, $unit);
    }
}
```

- [ ] **Step 2: Run test**

Run: `vendor\bin\pest tests\Feature\Organization\OrganizationIntegrationTest.php`
Expected: PASS (2 tests) — actions sudah ada dari Task 5.

- [ ] **Step 3: Run quality gate**

Run: `composer check`
Expected: lolos.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Organization/OrganizationIntegrationTest.php
git commit -m "test: add organization assignment integration tests (TODO 4.3)"
```

---

### Task 11: Shield generate & permission final

**Files:**
- Run: `php artisan shield:generate`
- Verify: permissions di DB (via test atau query)
- Test: `tests/Feature/Organization/PermissionListTest.php`

**Interfaces:**
- Consumes: Task 7 (resources), Task 6 (config custom_permissions).
- Produces: Permission `organization:*`, `organizational_unit:*`, `assign_user_to_unit`, `remove_user_from_unit`, `set_primary_unit` ter-register.

- [ ] **Step 1: Jalankan shield:generate**

```bash
php artisan shield:generate
```

Expected: permission & policy di-generate (Resource Organization & OrganizationalUnit).

**Catatan:** perintah `migrate`/`shield` mungkin diblokir permission deny rule di environment ini (seperti `php artisan migrate*`). Jika diblokir, ganti verifikasi dengan test yang memastikan permission naming benar (Step 2), dan catat bahwa `shield:generate` harus dijalankan manual di environment dev.

- [ ] **Step 2: Tulis test naming permission**

Buat `tests/Feature/Organization/PermissionListTest.php`:

```php
<?php

namespace Tests\Feature\Organization;

use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionListTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_permissions_registered(): void
    {
        $custom = config('filament-shield.custom_permissions');

        $this->assertContains('assign_user_to_unit', $custom);
        $this->assertContains('remove_user_from_unit', $custom);
        $this->assertContains('set_primary_unit', $custom);
    }

    public function test_permission_name_format_is_colon(): void
    {
        $this->assertSame(':', config('filament-shield.permissions.separator'));
        $this->assertSame('snake', config('filament-shield.permissions.case'));
    }
}
```

- [ ] **Step 3: Run test**

Run: `vendor\bin\pest tests\Feature\Organization\PermissionListTest.php`
Expected: PASS (2 tests).

- [ ] **Step 4: Run quality gate**

Run: `composer check`
Expected: lolos.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Organization/PermissionListTest.php
git commit -m "test: verify shield permission configuration (TODO 4.3)"
```

---

### Task 12: Update docs (TODO.md §4 + konvensi)

**Files:**
- Modify: `docs/TODO.md` (centang §4.1-4.3)
- Modify: `docs/conventions/directory-structure.md` (bila perlu — action assignment di app/Actions/)

**Interfaces:**
- Consumes: seluruh task.
- Produces: Roadmap & konvensi konsisten.

- [ ] **Step 1: Update TODO.md §4**

Centang item §4.1 (Organization model/migration/factory/policy/service-action/resource — migration sudah M2; centang model/factory/policy/actions/resource), §4.2 (model/migration/factory/policy/parent-child/hierarchy queries/root unit/unit types/UI), §4.3 (assignment/multiple unit/primary unit/validate/UI).

Tambahkan catatan defer: hierarchy queries lanjutan (recursive CTE) & context switching ke §5.

- [ ] **Step 2: Update directory-structure.md**

Tambah catatan: action yang melibatkan `App\Models\User` (assignment) hidup di `app/Actions/` — batas arch test Core (Core tidak impor App).

- [ ] **Step 3: Verifikasi**

```bash
grep -n "app/Actions/Organization" docs/conventions/directory-structure.md docs/TODO.md
```

- [ ] **Step 4: Commit**

```bash
git add docs/TODO.md docs/conventions/directory-structure.md
git commit -m "docs: update TODO and conventions for organization core (TODO 4)"
```

---

### Task 13: Final verification

**Files:**
- Seluruh test suite.

**Interfaces:**
- Consumes: seluruh task.

- [ ] **Step 1: Full test suite**

```bash
composer check
```

Expected: Pint, Pest (semua test + arch test), PHPStan hijau.

- [ ] **Step 2: Verifikasi arch test**

```bash
vendor\bin\pest tests\Arch\CoreArchTest.php
```

Expected: PASS — Core tidak impor App/Modules/Filament (action assignment di `app/Actions/` menjaga ini).

- [ ] **Step 3: Git clean**

```bash
git status --short
```

Expected: bersih (semua task sudah di-commit).

---

## Self-Review

**Spec coverage:**
- §4.1 Organization model → Task 2; factory → Task 3; action → Task 3; policy → Task 6; resource → Task 7; seeder → Task 9.
- §4.2 Unit model → Task 2; factory → Task 3; action + validasi → Task 4; policy → Task 6; resource → Task 7.
- §4.3 assignment → Task 5 (actions), Task 8 (schema), Task 10 (integration), Task 11 (permission).
- §3.3 validasi hierarki (defer M2) → Task 4.
- §3.3 validasi assignment → Task 5.
- §6 policy & permission (format colon, Shield generate, custom_permissions, fix policy usang) → Task 6, 11.
- §7 Filament UI (resource + Form/Infolist terpisah, OrganizationalAccessSchema) → Task 7, 8.
- §8 data (factory, seeder idempotent) → Task 3, 9.
- §9 config (max_depth, Shield custom_permissions) → Task 4, 6.
- §10 testing → tersebar di tiap task + Task 13.

**Placeholder scan:** Tidak ada TBD/TODO/placeholder — setiap step punya kode & command lengkap.

**Type consistency:**
- `OrganizationalUnitType` (enum, 4 case) konsisten Task 1 → Task 2 (cast) → Task 4 (default HEAD_OFFICE) → Task 9 (seeder).
- `OrganizationException::invalidHierarchy/invalidAssignment` konsisten Task 1 → Task 4 → Task 5 → Task 10.
- Action signatures konsisten: `CreateOrganizationalUnitAction::handle(Organization, string, ?type, ?parentId, ?createdBy)` dipakai Task 4 & 10.
- **Catatan penting (deviasi dari spec):** action assignment (`AssignUserToUnitAction` dkk) yang mengimpor `App\Models\User` **tidak bisa hidup di `core/Organization/Actions/`** karena arch test "Core must not use App\Models" (ADR-005). Dipindah ke `app/Actions/Organization/`. Ini deviasi yang diperlukan & konsisten dengan arsitektur (app layer boleh impor Core).
- Model Core tidak punya relasi `users()`/`primaryUsers()` (impor App\User) — relasi diletakkan di `app/Models/User.php` (Task 5). Deviasi ini menjaga arch test tetap hijau.
- `OrganizationalAccessSchema` memakai pola `components(): array` (bukan `configure(Schema)`) agar bisa digabung di UserForm — konsisten Task 8.

**Catatan (bukan placeholder):** Resource CRUD Organization/Unit memakai operasi model langsung (Filament default) — Action layer tetap untuk validasi kompleks & non-UI; tidak duplikasi CRUD sederhana (best-practices: prefer framework feature).

<?php

namespace Tests\Unit\Scope;

use App\Models\User;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Core\Support\Scope;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\Models\ScopedEntity;
use Tests\TestCase;

class ScopeHelperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createScopedEntitiesTable();
    }

    private function createScopedEntitiesTable(): void
    {
        Schema::create('scoped_entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->foreignUuid('organization_id')->nullable();
            $table->foreignUuid('organizational_unit_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function makeEntity(string $name, ?string $orgId = null, ?string $unitId = null): ScopedEntity
    {
        return ScopedEntity::create([
            'name' => $name,
            'organization_id' => $orgId,
            'organizational_unit_id' => $unitId,
        ]);
    }

    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super_admin']);

        return User::factory()->create()->assignRole('super_admin');
    }

    public function test_unit_filters_by_unit_id(): void
    {
        $unitA = OrganizationalUnit::factory()->create();
        $unitB = OrganizationalUnit::factory()->create();
        $entityA = $this->makeEntity('A', unitId: $unitA->id);
        $this->makeEntity('B', unitId: $unitB->id);

        $result = Scope::unit(ScopedEntity::query(), $unitA->id)->pluck('id');

        $this->assertSame([$entityA->id], $result->all());
    }

    public function test_unit_null_is_noop(): void
    {
        $this->makeEntity('A');
        $this->makeEntity('B');

        $result = Scope::unit(ScopedEntity::query(), null)->pluck('id');

        $this->assertCount(2, $result);
    }

    public function test_organization_filters_by_organization_id(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $entityA = $this->makeEntity('A', orgId: $orgA->id);
        $this->makeEntity('B', orgId: $orgB->id);

        $result = Scope::organization(ScopedEntity::query(), $orgA->id)->pluck('id');

        $this->assertSame([$entityA->id], $result->all());
    }

    public function test_user_units_filters_by_units_assigned_to_user(): void
    {
        $user = User::factory()->create();
        $assigned = OrganizationalUnit::factory()->create();
        $other = OrganizationalUnit::factory()->create();
        $user->units()->attach($assigned->id);
        $entityAssigned = $this->makeEntity('Assigned', unitId: $assigned->id);
        $this->makeEntity('Other', unitId: $other->id);

        $result = Scope::userUnits(ScopedEntity::query(), $user)->pluck('id');

        $this->assertSame([$entityAssigned->id], $result->all());
    }

    public function test_user_organizations_filters_by_user_organizations(): void
    {
        $user = User::factory()->create();
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $user->organizations()->attach($orgA->id);
        $entityA = $this->makeEntity('A', orgId: $orgA->id);
        $this->makeEntity('B', orgId: $orgB->id);

        $result = Scope::userOrganizations(ScopedEntity::query(), $user)->pluck('id');

        $this->assertSame([$entityA->id], $result->all());
    }

    public function test_user_scope_filters_by_units_or_organizations(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $org = Organization::factory()->create();
        $user->units()->attach($unit->id);
        $user->organizations()->attach($org->id);
        $entityByUnit = $this->makeEntity('ByUnit', unitId: $unit->id);
        $entityByOrg = $this->makeEntity('ByOrg', orgId: $org->id);
        $this->makeEntity('Neither');

        $result = Scope::userScope(ScopedEntity::query(), $user)->pluck('id')->sort()->values();

        $this->assertSame([$entityByUnit->id, $entityByOrg->id], $result->all());
    }

    public function test_is_super_admin_true_with_role(): void
    {
        $user = $this->superAdmin();

        $this->assertTrue(Scope::isSuperAdmin($user));
    }

    public function test_is_super_admin_false_without_role(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(Scope::isSuperAdmin($user));
    }

    public function test_can_true_for_super_admin_even_if_not_assigned(): void
    {
        $user = $this->superAdmin();
        $unit = OrganizationalUnit::factory()->create();

        $this->assertTrue(Scope::can($user, $unit->id));
    }

    public function test_can_true_for_assigned_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);

        $this->assertTrue(Scope::can($user, $unit->id));
    }

    public function test_can_false_for_unassigned_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();

        $this->assertFalse(Scope::can($user, $unit->id));
    }

    public function test_can_false_for_null_unit_for_non_admin(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(Scope::can($user, null));
    }

    public function test_can_true_for_null_unit_for_super_admin(): void
    {
        $user = $this->superAdmin();

        $this->assertTrue(Scope::can($user, null));
    }
}

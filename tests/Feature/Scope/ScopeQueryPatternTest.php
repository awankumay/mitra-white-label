<?php

namespace Tests\Feature\Scope;

use App\Models\User;
use Core\Contracts\OrganizationContext;
use Core\Contracts\OrganizationalUnitContext;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Core\Support\Scope;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Tests\Fixtures\Models\ScopedEntity;
use Tests\TestCase;

class ScopeQueryPatternTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('scoped_entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->foreignUuid('organization_id')->nullable();
            $table->foreignUuid('organizational_unit_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function makeEntity(?string $orgId = null, ?string $unitId = null): ScopedEntity
    {
        return ScopedEntity::create([
            'name' => 'Entity',
            'organization_id' => $orgId,
            'organizational_unit_id' => $unitId,
        ]);
    }

    public function test_context_driven_unit_scope_uses_current_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);
        $entity = $this->makeEntity(unitId: $unit->id);
        $this->makeEntity(unitId: OrganizationalUnit::factory()->create()->id);
        Auth::login($user);
        Session::put('context.unit_id', $unit->id);

        // Context-driven: local scope convenience reads current context.
        $currentUnitId = app(OrganizationalUnitContext::class)->currentId();
        $result = Scope::unit(ScopedEntity::query(), $currentUnitId)->pluck('id');

        $this->assertSame([$entity->id], $result->all());
    }

    public function test_user_driven_scope_lists_all_user_units(): void
    {
        $user = User::factory()->create();
        $unitA = OrganizationalUnit::factory()->create();
        $unitB = OrganizationalUnit::factory()->create();
        $user->units()->attach([$unitA->id, $unitB->id]);
        $entityA = $this->makeEntity(unitId: $unitA->id);
        $entityB = $this->makeEntity(unitId: $unitB->id);

        $result = Scope::userScope(ScopedEntity::query(), $user)->pluck('id')->sort()->values();

        $this->assertSame([$entityA->id, $entityB->id], $result->all());
    }

    public function test_organization_context_derived_from_unit(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $unit = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);
        $user->units()->attach($unit->id);
        Auth::login($user);
        Session::put('context.unit_id', $unit->id);

        $this->assertSame($org->id, app(OrganizationContext::class)->organizationId());
    }
}

<?php

namespace Tests\Feature\Scope;

use App\Models\User;
use Core\Organization\Models\OrganizationalUnit;
use Core\Support\Scope;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\Models\ScopedEntity;
use Tests\TestCase;

class ScopeResourceTest extends TestCase
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

    private function makeEntity(?string $unitId = null): ScopedEntity
    {
        return ScopedEntity::create([
            'name' => 'Entity',
            'organizational_unit_id' => $unitId,
        ]);
    }

    public function test_resource_query_limited_to_user_units(): void
    {
        $user = User::factory()->create();
        $unitA = OrganizationalUnit::factory()->create();
        $unitB = OrganizationalUnit::factory()->create();
        $user->units()->attach($unitA->id);
        $entityA = $this->makeEntity($unitA->id);
        $this->makeEntity($unitB->id);

        // Pola getEloquentQuery() pada resource: userScope untuk non-admin.
        $query = Scope::userScope(ScopedEntity::query(), $user);

        $this->assertSame([$entityA->id], $query->pluck('id')->all());
    }

    public function test_super_admin_bypasses_scope(): void
    {
        Role::firstOrCreate(['name' => 'super_admin']);
        $user = User::factory()->create()->assignRole('super_admin');
        $unit = OrganizationalUnit::factory()->create();
        $this->makeEntity($unit->id);

        // Pola getEloquentQuery(): admin tidak di-scope, lihat semua.
        $query = Scope::isSuperAdmin($user)
            ? ScopedEntity::query()
            : Scope::userScope(ScopedEntity::query(), $user);

        $this->assertCount(1, $query->get());
    }

    public function test_super_admin_flag_false_for_regular_user(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(Scope::isSuperAdmin($user));
    }
}

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

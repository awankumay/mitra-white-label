<?php

namespace Tests\Feature\Authorization;

use App\Filament\Resources\OrganizationalUnits\Schemas\OrganizationalUnitForm;
use App\Models\User;
use Core\Organization\Models\Organization;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganizationalUnitFormScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function organizationSelect(): Select
    {
        // The parent_id select's `visible()` closure uses a Get utility that requires
        // a Livewire component, so build the schema without evaluating visibility.
        $schema = OrganizationalUnitForm::configure(Schema::make());

        /** @var Select $select */
        $select = collect($schema->getComponents(withHidden: true))
            ->firstWhere(fn ($component): bool => $component instanceof Select && $component->getName() === 'organization_id');

        return $select;
    }

    public function test_org_select_lists_only_users_organizations(): void
    {
        $orgA = Organization::factory()->create(['name' => 'Org A']);
        $orgB = Organization::factory()->create(['name' => 'Org B']);

        $role = Role::firstOrCreate(['name' => 'panel_user']);
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->organizations()->attach($orgA->id);

        $this->actingAs($user);

        $options = $this->organizationSelect()->getOptions();

        $this->assertSame([$orgA->id => 'Org A'], $options);
        $this->assertArrayNotHasKey($orgB->id, $options);
    }

    public function test_org_select_lists_all_organizations_for_super_admin(): void
    {
        $orgA = Organization::factory()->create(['name' => 'Org A']);
        $orgB = Organization::factory()->create(['name' => 'Org B']);

        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);

        $options = $this->organizationSelect()->getOptions();

        $this->assertSame([$orgA->id => 'Org A', $orgB->id => 'Org B'], $options);
    }

    public function test_org_select_has_no_options_without_auth_user(): void
    {
        Organization::factory()->create(['name' => 'Org A']);

        $this->assertSame([], $this->organizationSelect()->getOptions());
    }
}

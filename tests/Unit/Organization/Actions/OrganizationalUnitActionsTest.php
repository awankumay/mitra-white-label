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

    public function test_update_cannot_change_organization(): void
    {
        $unit = OrganizationalUnit::factory()->create();
        $otherOrg = Organization::factory()->create();

        $updated = app(UpdateOrganizationalUnitAction::class)->handle($unit, [
            'name' => 'Stays Put',
            'organization_id' => $otherOrg->id,
        ]);

        $this->assertSame($unit->organization_id, $updated->organization_id);
        $this->assertNotSame($otherOrg->id, $updated->organization_id);
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

    public function test_unit_at_max_depth_cannot_have_child(): void
    {
        $org = Organization::factory()->create();
        config(['core.organization.max_depth' => 3]);

        // root (depth 1) → child (depth 2) → grandchild (depth 3 = max depth, valid)
        $level1 = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);
        $level2 = OrganizationalUnit::factory()->create(['organization_id' => $org->id, 'parent_id' => $level1->id]);
        $level3 = OrganizationalUnit::factory()->create(['organization_id' => $org->id, 'parent_id' => $level2->id]);

        $this->expectException(OrganizationException::class);
        app(CreateOrganizationalUnitAction::class)->handle(
            $org,
            'Too Deep',
            OrganizationalUnitType::SITE,
            $level3->id
        );
    }

    public function test_move_under_unit_at_max_depth_throws(): void
    {
        $org = Organization::factory()->create();
        config(['core.organization.max_depth' => 3]);

        $level1 = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);
        $level2 = OrganizationalUnit::factory()->create(['organization_id' => $org->id, 'parent_id' => $level1->id]);
        $level3 = OrganizationalUnit::factory()->create(['organization_id' => $org->id, 'parent_id' => $level2->id]);
        $move = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);

        $this->expectException(OrganizationException::class);
        app(UpdateOrganizationalUnitAction::class)->handle($move, ['parent_id' => $level3->id]);
    }
}

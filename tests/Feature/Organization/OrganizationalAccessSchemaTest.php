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

<?php

namespace Tests\Unit\Context;

use App\Models\User;
use Core\Context\Actions\SwitchUnitAction;
use Core\Exceptions\OrganizationException;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class SwitchUnitActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_switches_to_assigned_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);

        app(SwitchUnitAction::class)->handle($user->id, $unit->id);

        $this->assertSame($unit->id, Session::get('context.unit_id'));
    }

    public function test_throws_when_unit_not_assigned(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();

        $this->expectException(OrganizationException::class);
        app(SwitchUnitAction::class)->handle($user->id, $unit->id);
    }

    public function test_throws_when_user_does_not_exist(): void
    {
        $unit = OrganizationalUnit::factory()->create();

        $this->expectException(OrganizationException::class);
        app(SwitchUnitAction::class)->handle('non-existent-user-id', $unit->id);
    }
}

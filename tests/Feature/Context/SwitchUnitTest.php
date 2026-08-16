<?php

namespace Tests\Feature\Context;

use App\Models\User;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SwitchUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // CSRF ditangani oleh middleware web group; nonaktifkan untuk test
        // (pola umum Laravel testing), auth tetap aktif.
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
    }

    public function test_switch_unit_redirects_back_with_success(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);

        $this->actingAs($user)
            ->post(route('context.switch-unit'), ['unit_id' => $unit->id])
            ->assertRedirect();
    }

    public function test_switch_unit_sets_session_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);

        $this->actingAs($user)->post(route('context.switch-unit'), ['unit_id' => $unit->id]);

        $this->assertSame($unit->id, session('context.unit_id'));
    }

    public function test_switch_unit_rejects_unassigned_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();

        $this->actingAs($user)
            ->post(route('context.switch-unit'), ['unit_id' => $unit->id])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    public function test_switch_unit_requires_authentication(): void
    {
        $unit = OrganizationalUnit::factory()->create();

        $this->post(route('context.switch-unit'), ['unit_id' => $unit->id])
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_switch_unit_rejects_missing_unit_id(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('context.switch-unit'))
            ->assertRedirect();
    }
}

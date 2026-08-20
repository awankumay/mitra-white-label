<?php

namespace Tests\Unit\Context;

use App\Models\User;
use Core\Contracts\OrganizationalUnitContext;
use Core\Contracts\OrganizationContext;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ContextNonFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_context_null_in_queue_without_auth(): void
    {
        // Simulasi queue/CLI: tidak ada Auth::user(), tidak ada session.
        $this->assertNull(app(OrganizationContext::class)->organization());
        $this->assertNull(app(OrganizationalUnitContext::class)->current());
    }

    public function test_service_can_use_current_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);
        Auth::login($user);

        // Contoh pola Service/Action: baca current unit.
        $currentUnitId = app(OrganizationalUnitContext::class)->currentId();

        $this->assertSame($unit->id, $currentUnitId);
    }

    public function test_job_sets_context_explicitly(): void
    {
        $unit = OrganizationalUnit::factory()->create();

        // Contoh pola Job: set eksplisit tanpa session.
        app(OrganizationalUnitContext::class)->set($unit);

        $this->assertSame($unit->id, app(OrganizationalUnitContext::class)->currentId());
    }

    public function test_policy_can_check_has_and_current_id(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);
        Auth::login($user);

        $context = app(OrganizationalUnitContext::class);

        // Contoh pola Policy: cek has() + currentId().
        $this->assertTrue($context->has());
        $this->assertSame($unit->id, $context->currentId());
    }
}

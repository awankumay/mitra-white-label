<?php

namespace Tests\Unit\Context;

use App\Models\User;
use Core\Context\OrganizationContextManager;
use Core\Context\OrganizationalUnitContextManager;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class ContextManagerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): User
    {
        $user = User::factory()->create();

        Auth::login($user);

        return $user;
    }

    public function test_organization_context_empty_when_unauthenticated(): void
    {
        $manager = app(OrganizationContextManager::class);

        $this->assertFalse($manager->has());
        $this->assertNull($manager->organization());
        $this->assertNull($manager->organizationId());
    }

    public function test_organization_context_has_organization_from_unit(): void
    {
        $user = $this->actingAsUser();
        $organization = Organization::factory()->create();
        $unit = OrganizationalUnit::factory()->create(['organization_id' => $organization->id]);
        $user->units()->attach($unit->id);

        $manager = app(OrganizationContextManager::class);

        $this->assertTrue($manager->has());
        $this->assertSame($organization->id, $manager->organizationId());
    }

    public function test_organization_context_from_pivot_when_no_units(): void
    {
        $user = $this->actingAsUser();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization);

        $manager = app(OrganizationContextManager::class);

        $this->assertTrue($manager->has());
        $this->assertSame($organization->id, $manager->organizationId());
    }

    public function test_unit_context_uses_primary_unit_by_default(): void
    {
        $user = $this->actingAsUser();
        $primary = OrganizationalUnit::factory()->create();
        $other = OrganizationalUnit::factory()->create();
        $user->units()->attach([$primary->id, $other->id]);
        $user->update(['primary_organizational_unit_id' => $primary->id]);

        $manager = app(OrganizationalUnitContextManager::class);

        $this->assertTrue($manager->has());
        $this->assertSame($primary->id, $manager->currentId());
    }

    public function test_unit_context_reads_session(): void
    {
        $user = $this->actingAsUser();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);
        Session::put('context.unit_id', $unit->id);

        $manager = app(OrganizationalUnitContextManager::class);

        $this->assertSame($unit->id, $manager->currentId());
    }

    public function test_unit_context_set_writes_session(): void
    {
        $user = $this->actingAsUser();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);

        app(OrganizationalUnitContextManager::class)->set($unit);

        $this->assertSame($unit->id, Session::get('context.unit_id'));
        $this->assertSame($unit->id, app(OrganizationalUnitContextManager::class)->currentId());
    }

    public function test_unit_context_clear_removes_session(): void
    {
        $user = $this->actingAsUser();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);
        Session::put('context.unit_id', $unit->id);

        app(OrganizationalUnitContextManager::class)->clear();

        $this->assertFalse(Session::has('context.unit_id'));
        // Fallback: user still has an assigned unit → context resolves to it.
        $this->assertSame($unit->id, app(OrganizationalUnitContextManager::class)->currentId());
    }

    public function test_unit_context_clear_makes_has_false_when_no_units(): void
    {
        $this->actingAsUser();

        app(OrganizationalUnitContextManager::class)->clear();

        $this->assertFalse(app(OrganizationalUnitContextManager::class)->has());
    }

    public function test_unit_context_returns_null_when_unauthenticated(): void
    {
        $manager = app(OrganizationalUnitContextManager::class);

        $this->assertFalse($manager->has());
        $this->assertNull($manager->current());
        $this->assertNull($manager->currentId());
    }
}

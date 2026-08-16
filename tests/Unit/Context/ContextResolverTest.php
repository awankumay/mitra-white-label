<?php

namespace Tests\Unit\Context;

use App\Models\User;
use Core\Context\ContextResolver;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class ContextResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_key_uses_config_default(): void
    {
        $resolver = app(ContextResolver::class);

        $this->assertSame('context.unit_id', $resolver->sessionKey());
    }

    public function test_session_key_reads_config(): void
    {
        config(['core.context.session_key' => 'custom.key']);
        $resolver = app(ContextResolver::class);

        $this->assertSame('custom.key', $resolver->sessionKey());
    }

    public function test_returns_null_when_user_has_no_units(): void
    {
        $user = User::factory()->create();

        $this->assertNull(app(ContextResolver::class)->resolveCurrentUnit($user));
    }

    public function test_uses_primary_unit_when_no_session(): void
    {
        $user = User::factory()->create();
        $primary = OrganizationalUnit::factory()->create();
        $other = OrganizationalUnit::factory()->create();
        $user->units()->attach([$primary->id, $other->id]);
        $user->update(['primary_organizational_unit_id' => $primary->id]);

        $this->assertSame($primary->id, app(ContextResolver::class)->resolveCurrentUnit($user)->id);
    }

    public function test_uses_first_assigned_unit_when_no_primary(): void
    {
        $user = User::factory()->create();
        $first = OrganizationalUnit::factory()->create();
        $second = OrganizationalUnit::factory()->create();
        $user->units()->attach([$first->id, $second->id]);

        $this->assertSame($first->id, app(ContextResolver::class)->resolveCurrentUnit($user)->id);
    }

    public function test_uses_session_unit_when_valid(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);
        Session::put('context.unit_id', $unit->id);

        $this->assertSame($unit->id, app(ContextResolver::class)->resolveCurrentUnit($user)->id);
    }

    public function test_clears_stale_session_and_falls_back(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);
        $stale = OrganizationalUnit::factory()->create();
        Session::put('context.unit_id', $stale->id);

        $resolved = app(ContextResolver::class)->resolveCurrentUnit($user);

        $this->assertSame($unit->id, $resolved->id);
        $this->assertFalse(Session::has('context.unit_id'));
    }

    public function test_resolve_organization_from_current_unit(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $unit = OrganizationalUnit::factory()->create(['organization_id' => $organization->id]);
        $user->units()->attach($unit->id);

        $this->assertSame($organization->id, app(ContextResolver::class)->resolveOrganization($user)->id);
    }

    public function test_resolve_organization_from_pivot_when_no_units(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization);

        $this->assertSame($organization->id, app(ContextResolver::class)->resolveOrganization($user)->id);
    }

    public function test_resolve_organization_null_when_no_units_and_no_orgs(): void
    {
        $user = User::factory()->create();

        $this->assertNull(app(ContextResolver::class)->resolveOrganization($user));
    }
}

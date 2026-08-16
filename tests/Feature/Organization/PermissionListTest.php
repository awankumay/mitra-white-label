<?php

namespace Tests\Feature\Organization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionListTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_permissions_registered(): void
    {
        $custom = config('filament-shield.custom_permissions');

        $this->assertContains('assign_user_to_unit', $custom);
        $this->assertContains('remove_user_from_unit', $custom);
        $this->assertContains('set_primary_unit', $custom);
    }

    public function test_permission_name_format_is_colon(): void
    {
        $this->assertSame(':', config('filament-shield.permissions.separator'));
        $this->assertSame('snake', config('filament-shield.permissions.case'));
    }
}

<?php

namespace Tests\Unit\Context;

use Core\Contracts\OrganizationContext;
use Core\Contracts\OrganizationalUnitContext;
use PHPUnit\Framework\TestCase;

class ContextContractTest extends TestCase
{
    public function test_organization_context_defines_expected_methods(): void
    {
        $this->assertTrue(interface_exists(OrganizationContext::class));
        $this->assertSame([
            'organization',
            'organizationId',
            'set',
            'clear',
            'has',
        ], get_class_methods(OrganizationContext::class));
    }

    public function test_organizational_unit_context_defines_expected_methods(): void
    {
        $this->assertTrue(interface_exists(OrganizationalUnitContext::class));
        $this->assertSame([
            'current',
            'currentId',
            'set',
            'clear',
            'has',
        ], get_class_methods(OrganizationalUnitContext::class));
    }
}

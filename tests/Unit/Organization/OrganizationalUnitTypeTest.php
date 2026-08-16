<?php

namespace Tests\Unit\Organization;

use Core\Organization\Enums\OrganizationalUnitType;
use PHPUnit\Framework\TestCase;

class OrganizationalUnitTypeTest extends TestCase
{
    public function test_has_four_default_types(): void
    {
        $this->assertSame(
            ['HEAD_OFFICE', 'BRANCH', 'SUB_OFFICE', 'SITE'],
            array_map(fn ($case) => $case->value, OrganizationalUnitType::cases())
        );
    }

    public function test_default_is_head_office(): void
    {
        $this->assertSame('HEAD_OFFICE', OrganizationalUnitType::HEAD_OFFICE->value);
    }
}

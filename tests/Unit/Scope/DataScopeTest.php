<?php

namespace Tests\Unit\Scope;

use Core\Enums\DataScope;
use PHPUnit\Framework\TestCase;

class DataScopeTest extends TestCase
{
    public function test_enum_has_expected_cases_and_values(): void
    {
        $this->assertSame('global', DataScope::Global->value);
        $this->assertSame('organization', DataScope::Organization->value);
        $this->assertSame('unit', DataScope::Unit->value);
    }
}

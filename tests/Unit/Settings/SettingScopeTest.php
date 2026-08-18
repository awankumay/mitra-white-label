<?php

namespace Tests\Unit\Settings;

use Core\Settings\Enums\SettingScope;
use PHPUnit\Framework\TestCase;

class SettingScopeTest extends TestCase
{
    public function test_enum_has_expected_cases_and_values(): void
    {
        $this->assertSame('user', SettingScope::User->value);
        $this->assertSame('unit', SettingScope::Unit->value);
        $this->assertSame('organization', SettingScope::Organization->value);
        $this->assertSame('system', SettingScope::System->value);
    }

    public function test_case_order_is_most_specific_first(): void
    {
        $this->assertSame(
            [SettingScope::User, SettingScope::Unit, SettingScope::Organization, SettingScope::System],
            SettingScope::cases(),
        );
    }
}

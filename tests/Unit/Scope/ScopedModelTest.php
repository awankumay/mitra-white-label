<?php

namespace Tests\Unit\Scope;

use Core\Contracts\ScopedModel;
use PHPUnit\Framework\TestCase;

class ScopedModelTest extends TestCase
{
    public function test_interface_exists_and_is_marker(): void
    {
        $this->assertTrue(interface_exists(ScopedModel::class));
        // Marker interface — no methods required.
        $this->assertEmpty(get_class_methods(ScopedModel::class));
    }

    public function test_an_implementing_class_is_detectable(): void
    {
        $model = new class implements ScopedModel {
        };

        $this->assertInstanceOf(ScopedModel::class, $model);
    }
}

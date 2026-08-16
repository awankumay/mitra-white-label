<?php

namespace Tests\Feature\Organization;

use Core\Organization\Enums\OrganizationalUnitType;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrganizationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_table_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('organizations', ['id', 'name', 'created_by', 'updated_by', 'deleted_at']));
    }

    public function test_organizational_unit_table_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('organizational_units', ['id', 'organization_id', 'parent_id', 'name', 'type', 'deleted_at']));
    }

    public function test_organizational_unit_type_cast_is_enum(): void
    {
        $this->assertSame(OrganizationalUnitType::class, (new OrganizationalUnit)->getCasts()['type']);
    }
}

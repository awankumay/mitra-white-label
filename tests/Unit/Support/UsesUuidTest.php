<?php

namespace Tests\Unit\Support;

use Core\Support\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class UsesUuidStub extends Model
{
    use UsesUuid;
}

class UsesUuidTest extends TestCase
{
    public function test_generates_uuid_v7(): void
    {
        $model = new UsesUuidStub;
        $id = $model->newUniqueId();

        $this->assertTrue(Uuid::isValid($id));
        $this->assertSame(7, Uuid::fromString($id)->getVersion());
    }

    public function test_unique_ids_uses_id_column(): void
    {
        $this->assertSame(['id'], (new UsesUuidStub)->uniqueIds());
    }
}

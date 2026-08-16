<?php

namespace Core\Support\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Ramsey\Uuid\Uuid;

trait UsesUuid
{
    use HasUuids;

    public function newUniqueId(): string
    {
        return Uuid::uuid7()->toString();
    }

    public function uniqueIds(): array
    {
        return ['id'];
    }
}

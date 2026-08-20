<?php

namespace Core\Security\Events;

use Core\Security\Enums\SecurityEventType;
use Illuminate\Foundation\Events\Dispatchable;

class SecurityEventOccurred
{
    use Dispatchable;

    public function __construct(
        public readonly SecurityEventType $type,
        public readonly ?string $userId = null,
        public readonly array $metadata = [],
        public readonly ?\DateTimeInterface $occurredAt = null,
    ) {}
}

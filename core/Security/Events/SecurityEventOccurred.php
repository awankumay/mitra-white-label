<?php

namespace Core\Security\Events;

use Core\Security\Enums\SecurityEventType;

class SecurityEventOccurred
{
    public function __construct(
        public readonly SecurityEventType $type,
        public readonly ?string $userId = null,
        public readonly array $metadata = [],
        public readonly ?\DateTimeInterface $occurredAt = null,
    ) {}
}

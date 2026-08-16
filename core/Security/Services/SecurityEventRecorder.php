<?php

namespace Core\Security\Services;

use Core\Security\Enums\SecurityEventType;
use Core\Security\Models\SecurityEvent;

final class SecurityEventRecorder
{
    public function record(
        SecurityEventType $type,
        ?string $userId = null,
        array $metadata = [],
        ?\DateTimeInterface $occurredAt = null,
    ): void {
        SecurityEvent::query()->create([
            'event' => $type->value,
            'user_id' => $userId,
            'ip_address' => $metadata['ip_address'] ?? request()->ip(),
            'user_agent' => $metadata['user_agent'] ?? request()->userAgent(),
            'metadata' => $metadata,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }
}

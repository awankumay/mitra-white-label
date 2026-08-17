<?php

namespace App\Listeners\Security;

use Core\Security\Events\SecurityEventOccurred;
use Core\Security\Services\SecurityEventRecorder;

class RecordSecurityEvent
{
    public function __construct(private readonly SecurityEventRecorder $recorder) {}

    public function handle(SecurityEventOccurred $event): void
    {
        $this->recorder->record(
            $event->type,
            $event->userId,
            $event->metadata,
            $event->occurredAt,
        );
    }
}

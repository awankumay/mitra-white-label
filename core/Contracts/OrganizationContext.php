<?php

namespace Core\Contracts;

use Core\Organization\Models\Organization;

interface OrganizationContext
{
    public function organization(): ?Organization;

    public function organizationId(): ?string;

    public function set(Organization $organization): void;

    public function clear(): void;

    public function has(): bool;
}

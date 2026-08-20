<?php

namespace Core\Contracts;

use Core\Organization\Models\OrganizationalUnit;

interface OrganizationalUnitContext
{
    public function current(): ?OrganizationalUnit;

    public function currentId(): ?string;

    public function set(OrganizationalUnit $unit): void;

    public function clear(): void;

    public function has(): bool;
}

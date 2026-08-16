<?php

namespace Core\Context;

use Core\Contracts\OrganizationContext;
use Core\Organization\Models\Organization;
use Illuminate\Support\Facades\Auth;

final class OrganizationContextManager implements OrganizationContext
{
    private ?Organization $resolved = null;

    public function organization(): ?Organization
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $user = Auth::user();

        if ($user === null) {
            return null;
        }

        return $this->resolved = app(ContextResolver::class)->resolveOrganization($user);
    }

    public function organizationId(): ?string
    {
        return $this->organization()?->id;
    }

    public function set(Organization $organization): void
    {
        $this->resolved = $organization;
    }

    public function clear(): void
    {
        $this->resolved = null;
    }

    public function has(): bool
    {
        return $this->organization() !== null;
    }
}

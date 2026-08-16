<?php

namespace Core\Context;

use Core\Contracts\OrganizationalUnitContext;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

final class OrganizationalUnitContextManager implements OrganizationalUnitContext
{
    private ?OrganizationalUnit $resolved = null;

    public function current(): ?OrganizationalUnit
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $user = Auth::user();

        if ($user === null) {
            return null;
        }

        return $this->resolved = app(ContextResolver::class)->resolveCurrentUnit($user);
    }

    public function currentId(): ?string
    {
        return $this->current()?->id;
    }

    public function set(OrganizationalUnit $unit): void
    {
        $this->resolved = $unit;

        Session::put(config('core.context.session_key', 'context.unit_id'), $unit->id);
    }

    public function clear(): void
    {
        $this->resolved = null;

        Session::forget(config('core.context.session_key', 'context.unit_id'));
    }

    public function has(): bool
    {
        return $this->current() !== null;
    }
}

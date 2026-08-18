<?php

namespace Core\Context;

use Core\Contracts\OrganizationalUnitContext;
use Core\Contracts\OrganizationContext;
use Illuminate\Support\ServiceProvider;

class ContextServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OrganizationContext::class, OrganizationContextManager::class);
        $this->app->singleton(OrganizationalUnitContext::class, OrganizationalUnitContextManager::class);
    }

    public function boot(): void
    {
        //
    }
}

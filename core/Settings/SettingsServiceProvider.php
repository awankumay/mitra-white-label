<?php

namespace Core\Settings;

use Core\Contracts\SettingsRepository;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsRegistry::class);
        $this->app->singleton(SettingsRepository::class, DatabaseSettingsRepository::class);
    }

    public function boot(): void
    {
        $this->app->make(SettingsRegistry::class)->register(config('core.settings.definitions', []));
    }
}

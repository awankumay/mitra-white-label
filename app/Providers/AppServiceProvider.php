<?php

namespace App\Providers;

use App\Policies\ActivityPolicy;
use App\Policies\OrganizationalAccessPolicy;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    protected array $policies = [
        Activity::class => ActivityPolicy::class,
        OrganizationalAccessPolicy::class => OrganizationalAccessPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configurePolicies();

        $this->configureDB();

        $this->configureModels();

        $this->configureFilament();

        $this->configureLimit();

        $this->configureAuthRedirect();

        $this->configureSecurityEvents();
    }

    private function configureAuthRedirect(): void
    {
        Authenticate::redirectUsing(
            fn (Request $request) => $request->expectsJson()
                ? null
                : route('filament.admin.auth.login')
        );
    }

    private function configurePolicies(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    private function configureDB(): void
    {
        DB::prohibitDestructiveCommands($this->app->environment('production'));
    }

    private function configureModels(): void
    {
        Model::preventAccessingMissingAttributes();

        Model::unguard();
    }

    private function configureFilament(): void
    {
        FilamentShield::prohibitDestructiveCommands($this->app->isProduction());

        Column::configureUsing(fn (Column $column) => $column->toggleable());

        Table::configureUsing(fn (Table $table) => $table
            ->reorderableColumns()
            ->deferColumnManager(false)
            ->deferFilters(false)
            ->paginationPageOptions([10, 25, 50])
        );
    }

    private function configureLimit(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
    }

    private function configureSecurityEvents(): void
    {
        $this->app['events']->listen(
            Core\Security\Events\SecurityEventOccurred::class,
            App\Listeners\Security\RecordSecurityEvent::class,
        );

        $this->app['events']->listen(
            Illuminate\Auth\Events\Login::class,
            App\Listeners\Security\RecordLoginSucceeded::class,
        );

        $this->app['events']->listen(
            Illuminate\Auth\Events\Failed::class,
            App\Listeners\Security\RecordLoginFailed::class,
        );

        $this->app['events']->listen(
            Jeffgreco13\FilamentBreezy\Events\PasskeyUsedToAuthenticate::class,
            App\Listeners\Security\RecordPasskeyUsed::class,
        );
    }
}

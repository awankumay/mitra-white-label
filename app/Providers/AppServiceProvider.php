<?php

namespace App\Providers;

use App\Listeners\Security\RecordLoginFailed;
use App\Listeners\Security\RecordLoginSucceeded;
use App\Listeners\Security\RecordPasskeyUsed;
use App\Listeners\Security\RecordSecurityEvent;
use App\Models\User;
use App\Policies\ActivityPolicy;
use App\Policies\OrganizationalAccessPolicy;
use App\Services\RoleService;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Core\Security\Events\SecurityEventOccurred;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Jeffgreco13\FilamentBreezy\Events\PasskeyUsedToAuthenticate;
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

        $this->configureAuthorizationGate();
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
            SecurityEventOccurred::class,
            RecordSecurityEvent::class,
        );

        $this->app['events']->listen(
            Login::class,
            RecordLoginSucceeded::class,
        );

        $this->app['events']->listen(
            Failed::class,
            RecordLoginFailed::class,
        );

        $this->app['events']->listen(
            PasskeyUsedToAuthenticate::class,
            RecordPasskeyUsed::class,
        );
    }

    private function configureAuthorizationGate(): void
    {
        Gate::before(function ($user, $ability) {
            if (! $user instanceof User) {
                return null;
            }

            if ($user->hasRole('super_admin')) {
                return null;   // Shield intercept 'before' already handles super_admin; don't shadow it
            }

            $allowed = app(RoleService::class)->userHasPermission($user, $ability);

            return $allowed ? true : null;   // null → fall through to normal policy/permission checks
        });
    }
}

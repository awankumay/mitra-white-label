# Settings System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the Settings System (TODO §9.1, and the "Application settings" line of §9.2) — a typed, scope-cascading settings engine on top of the existing `settings` table (M2/ADR-011), plus one real Filament page (`ApplicationSettings`) proving the architecture end-to-end.

**Architecture:** `core/Settings/SettingsRegistry` declares every valid key (type, default, allowed tiers, group). `core/Settings/DatabaseSettingsRepository` (bound to `Core\Contracts\SettingsRepository`) resolves `get()` by cascading `User → Unit → Organization → System`, caching each tier's raw value independently (so `set()`/`forget()` can invalidate exactly one cache entry, no cache tags needed). `app/Filament/Pages/Settings/ApplicationSettings.php` replaces the `inerba/filament-db-config` scaffold (`GeneralSettings.php`) for the System-tier `application` group.

**Tech Stack:** Laravel 13, Filament 5 (Schemas), Spatie Permission + Filament Shield, Livewire 4, Pest/PHPUnit (class-style, project convention), UUIDv7.

## Global Constraints

- **Architecture (ADR-005):** `Core\` must NOT use `App\`/`Modules\`; Core non-UI must NOT use Filament. Only `app/Filament/Pages/Settings/ApplicationSettings.php` (app layer, Task 5) touches Filament — every class under `core/Settings/` stays framework-agnostic.
- **No new schema:** The `settings` table already exists (`core/Database/Migrations/2026_08_16_000006_create_settings_table.php`, M2/ADR-011) with nullable `organization_id`/`organizational_unit_id`/`user_id` scope columns and a `json` `value` column. Do NOT create a new migration for it.
- **`SettingScope` is a new enum**, `core/Settings/Enums/SettingScope.php` (`System|Organization|Unit|User`) — do NOT reuse `Core\Enums\DataScope` (that enum is for model record ownership, PRD §17, and has no `User` case).
- **Cascading order (most specific first):** `User → Unit → Organization → System → registry default`. Only tiers listed in a key's `scopes` definition are checked.
- **Cache is per-tier, not per-resolved-cascade.** Cache key: `settings.raw.{key}.{tier}.{scopeId|'null'}`. `set()`/`forget()` at tier X must `Cache::forget()` exactly that key — no cache tags (project's default cache driver is `database`, see `config/cache.php`).
- **Context source:** `Core\Contracts\OrganizationContext` (`organizationId()`), `Core\Contracts\OrganizationalUnitContext` (`currentId()`) from M4, plus `Illuminate\Support\Facades\Auth::id()`. No new context mechanism.
- **Package `inerba/filament-db-config` stays in `composer.json`.** Do NOT run `composer remove`. Only stop referencing it (delete `app/Filament/Pages/GeneralSettings.php`, replace with the native page).
- **Permission format `action:subject`** (PRD §19): `view:settings` / `update:settings`. Add both to `config/filament-shield.php['custom_permissions']`. Create them in tests via `Permission::firstOrCreate(['name' => ...])` — this project has no permission seeder; every existing test creates permissions ad hoc (see `tests/Feature/Authorization/*Test.php`).
- **Livewire statePath pitfall:** registry keys use dots (`app.name`) but Filament/Livewire statePaths treat dots as nested-array separators. Form field names must use underscores (`app_name`); convert with `str_replace('.', '_', $key)` when moving between registry keys and form field names. Never `TextInput::make('app.name')`.
- **Test conventions:** PHPUnit class-style (`class XxxTest extends TestCase`), `use RefreshDatabase;`, namespaces `Tests\Unit\Settings` / `Tests\Feature\Settings`.
- **Out of scope — do NOT implement in this plan:** System fields outside the `application` group (Security/Localization/Mail/Storage); Organization/Unit/User tier UI; removing `inerba/filament-db-config` from `composer.json`; White Label/Branding (TODO §10).

---

### Task 1: `SettingScope` enum

**Files:**
- Create: `core/Settings/Enums/SettingScope.php`
- Test: `tests/Unit/Settings/SettingScopeTest.php`

**Interfaces:**
- Produces: `Core\Settings\Enums\SettingScope` — backed string enum, cases in this exact order: `User = 'user'`, `Unit = 'unit'`, `Organization = 'organization'`, `System = 'system'`. Order matters — later tasks iterate `SettingScope::cases()` to cascade most-specific-first.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Settings;

use Core\Settings\Enums\SettingScope;
use PHPUnit\Framework\TestCase;

class SettingScopeTest extends TestCase
{
    public function test_enum_has_expected_cases_and_values(): void
    {
        $this->assertSame('user', SettingScope::User->value);
        $this->assertSame('unit', SettingScope::Unit->value);
        $this->assertSame('organization', SettingScope::Organization->value);
        $this->assertSame('system', SettingScope::System->value);
    }

    public function test_case_order_is_most_specific_first(): void
    {
        $this->assertSame(
            [SettingScope::User, SettingScope::Unit, SettingScope::Organization, SettingScope::System],
            SettingScope::cases(),
        );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Settings/SettingScopeTest.php`
Expected: FAIL — `Class "Core\Settings\Enums\SettingScope" not found`

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace Core\Settings\Enums;

enum SettingScope: string
{
    case User = 'user';
    case Unit = 'unit';
    case Organization = 'organization';
    case System = 'system';
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Settings/SettingScopeTest.php`
Expected: PASS (2 passed)

- [ ] **Step 5: Commit**

```bash
git add core/Settings/Enums/SettingScope.php tests/Unit/Settings/SettingScopeTest.php
git commit -m "feat: add SettingScope enum (TODO 9.1)"
```

---

### Task 2: `SettingsException`

**Files:**
- Create: `core/Exceptions/SettingsException.php`
- Test: `tests/Unit/Settings/SettingsExceptionTest.php`

**Interfaces:**
- Consumes: `Core\Settings\Enums\SettingScope` (Task 1), `Core\Exceptions\CoreException` (existing).
- Produces: `Core\Exceptions\SettingsException extends CoreException` with `unknownKey(string $key): self` and `invalidScope(string $key, SettingScope $scope): self`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Settings;

use Core\Exceptions\SettingsException;
use Core\Settings\Enums\SettingScope;
use PHPUnit\Framework\TestCase;

class SettingsExceptionTest extends TestCase
{
    public function test_unknown_key_message_contains_key(): void
    {
        $exception = SettingsException::unknownKey('app.missing');

        $this->assertStringContainsString('app.missing', $exception->getMessage());
    }

    public function test_invalid_scope_message_contains_key_and_scope(): void
    {
        $exception = SettingsException::invalidScope('app.name', SettingScope::User);

        $this->assertStringContainsString('app.name', $exception->getMessage());
        $this->assertStringContainsString('user', $exception->getMessage());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Settings/SettingsExceptionTest.php`
Expected: FAIL — `Class "Core\Exceptions\SettingsException" not found`

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace Core\Exceptions;

use Core\Settings\Enums\SettingScope;

class SettingsException extends CoreException
{
    public static function unknownKey(string $key): self
    {
        return new self("Settings key [{$key}] is not registered.");
    }

    public static function invalidScope(string $key, SettingScope $scope): self
    {
        return new self("Settings key [{$key}] does not allow scope [{$scope->value}].");
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Settings/SettingsExceptionTest.php`
Expected: PASS (2 passed)

- [ ] **Step 5: Commit**

```bash
git add core/Exceptions/SettingsException.php tests/Unit/Settings/SettingsExceptionTest.php
git commit -m "feat: add SettingsException (TODO 9.1)"
```

---

### Task 3: `SettingsRegistry`

**Files:**
- Create: `core/Settings/SettingsRegistry.php`
- Test: `tests/Unit/Settings/SettingsRegistryTest.php`

**Interfaces:**
- Consumes: `Core\Settings\Enums\SettingScope` (Task 1), `Core\Exceptions\SettingsException` (Task 2).
- Produces: `Core\Settings\SettingsRegistry` — plain data class, no I/O:
  - `register(array $definitions): void` — merges `[key => ['type' => string, 'default' => mixed, 'scopes' => SettingScope[], 'group' => string]]`.
  - `definition(string $key): array` — throws `SettingsException::unknownKey()` if missing.
  - `has(string $key): bool`
  - `allowsScope(string $key, SettingScope $scope): bool`
  - `keysInGroup(string $group): array` — returns key strings, in registration order.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Settings;

use Core\Exceptions\SettingsException;
use Core\Settings\Enums\SettingScope;
use Core\Settings\SettingsRegistry;
use PHPUnit\Framework\TestCase;

class SettingsRegistryTest extends TestCase
{
    private function registry(): SettingsRegistry
    {
        $registry = new SettingsRegistry();
        $registry->register([
            'app.name' => [
                'type' => 'string',
                'default' => null,
                'scopes' => [SettingScope::System],
                'group' => 'application',
            ],
            'app.timezone' => [
                'type' => 'string',
                'default' => 'Asia/Jakarta',
                'scopes' => [SettingScope::System, SettingScope::Unit, SettingScope::User],
                'group' => 'application',
            ],
        ]);

        return $registry;
    }

    public function test_has_returns_true_for_registered_key(): void
    {
        $this->assertTrue($this->registry()->has('app.name'));
        $this->assertFalse($this->registry()->has('app.unknown'));
    }

    public function test_definition_returns_registered_definition(): void
    {
        $definition = $this->registry()->definition('app.timezone');

        $this->assertSame('string', $definition['type']);
        $this->assertSame('Asia/Jakarta', $definition['default']);
    }

    public function test_definition_throws_for_unknown_key(): void
    {
        $this->expectException(SettingsException::class);

        $this->registry()->definition('app.unknown');
    }

    public function test_allows_scope_checks_whitelist(): void
    {
        $registry = $this->registry();

        $this->assertTrue($registry->allowsScope('app.timezone', SettingScope::User));
        $this->assertFalse($registry->allowsScope('app.name', SettingScope::User));
    }

    public function test_keys_in_group_filters_by_group(): void
    {
        $this->assertSame(['app.name', 'app.timezone'], $this->registry()->keysInGroup('application'));
    }

    public function test_keys_in_group_returns_empty_for_unknown_group(): void
    {
        $this->assertSame([], $this->registry()->keysInGroup('security'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Settings/SettingsRegistryTest.php`
Expected: FAIL — `Class "Core\Settings\SettingsRegistry" not found`

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace Core\Settings;

use Core\Exceptions\SettingsException;
use Core\Settings\Enums\SettingScope;

final class SettingsRegistry
{
    /** @var array<string, array{type: string, default: mixed, scopes: SettingScope[], group: string}> */
    private array $definitions = [];

    /**
     * @param  array<string, array{type: string, default: mixed, scopes: SettingScope[], group: string}>  $definitions
     */
    public function register(array $definitions): void
    {
        $this->definitions = [...$this->definitions, ...$definitions];
    }

    /**
     * @return array{type: string, default: mixed, scopes: SettingScope[], group: string}
     */
    public function definition(string $key): array
    {
        if (! $this->has($key)) {
            throw SettingsException::unknownKey($key);
        }

        return $this->definitions[$key];
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->definitions);
    }

    public function allowsScope(string $key, SettingScope $scope): bool
    {
        return in_array($scope, $this->definition($key)['scopes'], true);
    }

    /**
     * @return string[]
     */
    public function keysInGroup(string $group): array
    {
        return array_keys(array_filter(
            $this->definitions,
            fn (array $definition): bool => $definition['group'] === $group,
        ));
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Settings/SettingsRegistryTest.php`
Expected: PASS (6 passed)

- [ ] **Step 5: Run Core architecture test to verify Core boundary holds**

Run: `php artisan test tests/Arch/CoreArchTest.php`
Expected: PASS — no Core → App/Modules/Filament violations

- [ ] **Step 6: Commit**

```bash
git add core/Settings/SettingsRegistry.php tests/Unit/Settings/SettingsRegistryTest.php
git commit -m "feat: add SettingsRegistry (TODO 9.1)"
```

---

### Task 4: `SettingsRepository` contract + `DatabaseSettingsRepository` + wiring

**Files:**
- Create: `core/Contracts/SettingsRepository.php`
- Create: `core/Settings/DatabaseSettingsRepository.php`
- Create: `core/Settings/SettingsServiceProvider.php`
- Modify: `core/Config/core.php`
- Test: `tests/Unit/Settings/DatabaseSettingsRepositoryTest.php`
- Test: `tests/Feature/Settings/SettingsCacheInvalidationTest.php`

**Interfaces:**
- Consumes: `Core\Settings\SettingsRegistry` (Task 3), `Core\Settings\Enums\SettingScope` (Task 1), `Core\Exceptions\SettingsException` (Task 2), `Core\Contracts\OrganizationContext` / `Core\Contracts\OrganizationalUnitContext` (M4, existing).
- Produces: `Core\Contracts\SettingsRepository` interface, bound singleton to `Core\Settings\DatabaseSettingsRepository`:
  - `get(string $key, mixed $default = null): mixed`
  - `getForScope(string $key, SettingScope $scope, ?string $scopeId = null): mixed`
  - `set(string $key, mixed $value, SettingScope $scope = SettingScope::System, ?string $scopeId = null): void`
  - `forget(string $key, SettingScope $scope = SettingScope::System, ?string $scopeId = null): void`

- [ ] **Step 1: Write the failing test — cascading, casting, defaults**

Create `tests/Unit/Settings/DatabaseSettingsRepositoryTest.php`:

```php
<?php

namespace Tests\Unit\Settings;

use Core\Contracts\OrganizationalUnitContext;
use Core\Contracts\SettingsRepository;
use Core\Exceptions\SettingsException;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Core\Settings\Enums\SettingScope;
use Core\Settings\SettingsRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSettingsRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(SettingsRegistry::class)->register([
            'app.name' => [
                'type' => 'string',
                'default' => 'Default App',
                'scopes' => [SettingScope::System],
                'group' => 'application',
            ],
            'app.timezone' => [
                'type' => 'string',
                'default' => 'Asia/Jakarta',
                'scopes' => [SettingScope::System, SettingScope::Unit, SettingScope::User],
                'group' => 'application',
            ],
            'security.session_lifetime_minutes' => [
                'type' => 'int',
                'default' => 120,
                'scopes' => [SettingScope::System],
                'group' => 'security',
            ],
        ]);
    }

    public function test_get_returns_registry_default_when_nothing_stored(): void
    {
        $this->assertSame('Default App', app(SettingsRepository::class)->get('app.name'));
    }

    public function test_get_casts_value_to_registered_type(): void
    {
        $repository = app(SettingsRepository::class);
        $repository->set('security.session_lifetime_minutes', 60, SettingScope::System);

        $this->assertSame(60, $repository->get('security.session_lifetime_minutes'));
    }

    public function test_get_throws_for_unknown_key(): void
    {
        $this->expectException(SettingsException::class);

        app(SettingsRepository::class)->get('unknown.key');
    }

    public function test_set_throws_when_scope_not_allowed_for_key(): void
    {
        $this->expectException(SettingsException::class);

        app(SettingsRepository::class)->set('app.name', 'X', SettingScope::User, 'some-user-id');
    }

    public function test_set_throws_when_scope_id_missing_for_non_system_scope(): void
    {
        $this->expectException(SettingsException::class);

        app(SettingsRepository::class)->set('app.timezone', 'UTC', SettingScope::User, null);
    }

    public function test_set_throws_when_scope_id_given_for_system_scope(): void
    {
        $this->expectException(SettingsException::class);

        app(SettingsRepository::class)->set('app.name', 'X', SettingScope::System, 'not-null');
    }

    public function test_get_for_scope_returns_null_when_not_set(): void
    {
        $this->assertNull(app(SettingsRepository::class)->getForScope('app.name', SettingScope::System));
    }

    public function test_get_for_scope_does_not_fallback_to_registry_default(): void
    {
        // Belum pernah di-set → tetap null, BUKAN 'Default App' (beda dari get()).
        $this->assertNull(app(SettingsRepository::class)->getForScope('app.name', SettingScope::System));
    }

    public function test_cascade_prefers_unit_over_system(): void
    {
        $org = Organization::factory()->create();
        $unit = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);
        $repository = app(SettingsRepository::class);

        $repository->set('app.timezone', 'Asia/Jakarta', SettingScope::System);
        $repository->set('app.timezone', 'Asia/Makassar', SettingScope::Unit, $unit->id);

        app(OrganizationalUnitContext::class)->set($unit);

        $this->assertSame('Asia/Makassar', $repository->get('app.timezone'));
    }

    public function test_forget_removes_override_and_falls_back_to_next_tier(): void
    {
        $org = Organization::factory()->create();
        $unit = OrganizationalUnit::factory()->create(['organization_id' => $org->id]);
        $repository = app(SettingsRepository::class);

        $repository->set('app.timezone', 'Asia/Jakarta', SettingScope::System);
        $repository->set('app.timezone', 'Asia/Makassar', SettingScope::Unit, $unit->id);
        app(OrganizationalUnitContext::class)->set($unit);

        $this->assertSame('Asia/Makassar', $repository->get('app.timezone'));

        $repository->forget('app.timezone', SettingScope::Unit, $unit->id);

        $this->assertSame('Asia/Jakarta', $repository->get('app.timezone'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Settings/DatabaseSettingsRepositoryTest.php`
Expected: FAIL — `Class "Core\Contracts\SettingsRepository" not found` (or binding error)

- [ ] **Step 3: Write the contract**

Create `core/Contracts/SettingsRepository.php`:

```php
<?php

namespace Core\Contracts;

use Core\Settings\Enums\SettingScope;

interface SettingsRepository
{
    public function get(string $key, mixed $default = null): mixed;

    public function getForScope(string $key, SettingScope $scope, ?string $scopeId = null): mixed;

    public function set(string $key, mixed $value, SettingScope $scope = SettingScope::System, ?string $scopeId = null): void;

    public function forget(string $key, SettingScope $scope = SettingScope::System, ?string $scopeId = null): void;
}
```

- [ ] **Step 4: Write the implementation**

Create `core/Settings/DatabaseSettingsRepository.php`:

```php
<?php

namespace Core\Settings;

use Core\Contracts\OrganizationalUnitContext;
use Core\Contracts\OrganizationContext;
use Core\Contracts\SettingsRepository;
use Core\Exceptions\SettingsException;
use Core\Settings\Enums\SettingScope;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DatabaseSettingsRepository implements SettingsRepository
{
    private const MISS = "\0__settings_miss__\0";

    public function __construct(private readonly SettingsRegistry $registry) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $definition = $this->registry->definition($key);

        foreach (SettingScope::cases() as $scope) {
            if (! in_array($scope, $definition['scopes'], true)) {
                continue;
            }

            $scopeId = $this->currentScopeId($scope);

            if ($scope !== SettingScope::System && $scopeId === null) {
                continue;
            }

            $value = $this->readCached($key, $scope, $scopeId);

            if ($value !== self::MISS) {
                return $this->cast($value, $definition['type']);
            }
        }

        return $default ?? $definition['default'];
    }

    public function getForScope(string $key, SettingScope $scope, ?string $scopeId = null): mixed
    {
        $definition = $this->registry->definition($key);
        $value = $this->readCached($key, $scope, $scopeId);

        return $value === self::MISS ? null : $this->cast($value, $definition['type']);
    }

    public function set(string $key, mixed $value, SettingScope $scope = SettingScope::System, ?string $scopeId = null): void
    {
        $this->guardScope($key, $scope, $scopeId);

        $userId = Auth::id();
        $query = $this->query($key, $scope, $scopeId);

        if ($query->exists()) {
            $query->update([
                'value' => json_encode($value),
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('settings')->insert([
                'id' => (string) Str::uuid7(),
                'key' => $key,
                'value' => json_encode($value),
                ...$this->scopeColumns($scope, $scopeId),
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->forgetCache($key, $scope, $scopeId);
    }

    public function forget(string $key, SettingScope $scope = SettingScope::System, ?string $scopeId = null): void
    {
        $this->guardScope($key, $scope, $scopeId);

        $this->query($key, $scope, $scopeId)->delete();

        $this->forgetCache($key, $scope, $scopeId);
    }

    private function guardScope(string $key, SettingScope $scope, ?string $scopeId): void
    {
        if (! $this->registry->allowsScope($key, $scope)) {
            throw SettingsException::invalidScope($key, $scope);
        }

        $requiresId = $scope !== SettingScope::System;

        if ($requiresId !== ($scopeId !== null)) {
            throw SettingsException::invalidScope($key, $scope);
        }
    }

    private function currentScopeId(SettingScope $scope): ?string
    {
        return match ($scope) {
            SettingScope::System => null,
            SettingScope::Organization => app(OrganizationContext::class)->organizationId(),
            SettingScope::Unit => app(OrganizationalUnitContext::class)->currentId(),
            SettingScope::User => Auth::id(),
        };
    }

    /**
     * @return array{organization_id: ?string, organizational_unit_id: ?string, user_id: ?string}
     */
    private function scopeColumns(SettingScope $scope, ?string $scopeId): array
    {
        return match ($scope) {
            SettingScope::System => ['organization_id' => null, 'organizational_unit_id' => null, 'user_id' => null],
            SettingScope::Organization => ['organization_id' => $scopeId, 'organizational_unit_id' => null, 'user_id' => null],
            SettingScope::Unit => [
                'organization_id' => DB::table('organizational_units')->where('id', $scopeId)->value('organization_id'),
                'organizational_unit_id' => $scopeId,
                'user_id' => null,
            ],
            SettingScope::User => ['organization_id' => null, 'organizational_unit_id' => null, 'user_id' => $scopeId],
        };
    }

    private function query(string $key, SettingScope $scope, ?string $scopeId): Builder
    {
        $query = DB::table('settings')->where('key', $key);

        foreach ($this->scopeColumns($scope, $scopeId) as $column => $value) {
            $query = $value === null ? $query->whereNull($column) : $query->where($column, $value);
        }

        return $query;
    }

    private function readCached(string $key, SettingScope $scope, ?string $scopeId): mixed
    {
        $cacheKey = $this->cacheKey($key, $scope, $scopeId);

        return Cache::remember($cacheKey, config('core.settings.cache_ttl', 3600), function () use ($key, $scope, $scopeId) {
            $row = $this->query($key, $scope, $scopeId)->first();

            return $row === null ? self::MISS : json_decode((string) $row->value, true);
        });
    }

    private function forgetCache(string $key, SettingScope $scope, ?string $scopeId): void
    {
        Cache::forget($this->cacheKey($key, $scope, $scopeId));
    }

    private function cacheKey(string $key, SettingScope $scope, ?string $scopeId): string
    {
        return sprintf('settings.raw.%s.%s.%s', $key, $scope->value, $scopeId ?? 'null');
    }

    private function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'int' => (int) $value,
            'bool' => (bool) $value,
            'float' => (float) $value,
            'array' => (array) $value,
            default => $value,
        };
    }
}
```

- [ ] **Step 5: Write the service provider**

Create `core/Settings/SettingsServiceProvider.php`:

```php
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
```

- [ ] **Step 6: Wire into `core/Config/core.php`**

Read the current file first. Add the import and two new top-level keys. Replace:

```php
use Core\Context\ContextServiceProvider;

return [
    'providers' => [
        ContextServiceProvider::class,
    ],
```

with:

```php
use Core\Context\ContextServiceProvider;
use Core\Settings\Enums\SettingScope;
use Core\Settings\SettingsServiceProvider;

return [
    'providers' => [
        ContextServiceProvider::class,
        SettingsServiceProvider::class,
    ],
```

Then add a new `'settings'` key alongside the existing `'organization'`/`'context'`/`'auth'` keys (order doesn't matter, but keep it grouped near `'context'`):

```php
    'settings' => [
        'cache_ttl' => (int) env('SETTINGS_CACHE_TTL', 3600),
        'definitions' => [
            'app.name' => [
                'type' => 'string',
                'default' => null,
                'scopes' => [SettingScope::System],
                'group' => 'application',
            ],
            'app.locale' => [
                'type' => 'string',
                'default' => 'id',
                'scopes' => [SettingScope::System],
                'group' => 'application',
            ],
            'app.timezone' => [
                'type' => 'string',
                'default' => 'Asia/Jakarta',
                'scopes' => [SettingScope::System, SettingScope::Unit, SettingScope::User],
                'group' => 'application',
            ],
        ],
    ],
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test tests/Unit/Settings/DatabaseSettingsRepositoryTest.php`
Expected: PASS (11 passed)

- [ ] **Step 8: Write the failing test — cache invalidation**

Create `tests/Feature/Settings/SettingsCacheInvalidationTest.php`:

```php
<?php

namespace Tests\Feature\Settings;

use Core\Contracts\SettingsRepository;
use Core\Settings\Enums\SettingScope;
use Core\Settings\SettingsRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingsCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(SettingsRegistry::class)->register([
            'app.name' => [
                'type' => 'string',
                'default' => 'Default App',
                'scopes' => [SettingScope::System],
                'group' => 'application',
            ],
        ]);
    }

    public function test_get_is_cached_and_does_not_reflect_direct_db_writes(): void
    {
        $repository = app(SettingsRepository::class);
        $repository->set('app.name', 'First', SettingScope::System);

        $this->assertSame('First', $repository->get('app.name'));

        // Ubah langsung di DB, bypass repository → cache belum tahu.
        DB::table('settings')
            ->where('key', 'app.name')
            ->whereNull('organization_id')
            ->whereNull('organizational_unit_id')
            ->whereNull('user_id')
            ->update(['value' => json_encode('Changed Directly')]);

        $this->assertSame('First', $repository->get('app.name'));
    }

    public function test_set_invalidates_cache_immediately(): void
    {
        $repository = app(SettingsRepository::class);
        $repository->set('app.name', 'First', SettingScope::System);
        $this->assertSame('First', $repository->get('app.name'));

        $repository->set('app.name', 'Second', SettingScope::System);

        $this->assertSame('Second', $repository->get('app.name'));
    }

    public function test_forget_invalidates_cache_and_falls_back_to_default(): void
    {
        $repository = app(SettingsRepository::class);
        $repository->set('app.name', 'First', SettingScope::System);
        $this->assertSame('First', $repository->get('app.name'));

        $repository->forget('app.name', SettingScope::System);

        $this->assertSame('Default App', $repository->get('app.name'));
    }
}
```

- [ ] **Step 9: Run test to verify it passes**

Run: `php artisan test tests/Feature/Settings/SettingsCacheInvalidationTest.php`
Expected: PASS (3 passed) — this validates against the already-written repository, no new production code needed for this step.

- [ ] **Step 10: Run Core architecture test to verify Core boundary holds**

Run: `php artisan test tests/Arch/CoreArchTest.php`
Expected: PASS

- [ ] **Step 11: Commit**

```bash
git add core/Contracts/SettingsRepository.php core/Settings/DatabaseSettingsRepository.php core/Settings/SettingsServiceProvider.php core/Config/core.php tests/Unit/Settings/DatabaseSettingsRepositoryTest.php tests/Feature/Settings/SettingsCacheInvalidationTest.php
git commit -m "feat: add SettingsRepository with cascading scope resolution and per-tier cache (TODO 9.1)"
```

---

### Task 5: `ApplicationSettings` Filament page (replaces `GeneralSettings.php`)

**Files:**
- Create: `app/Filament/Pages/Settings/ApplicationSettings.php`
- Create: `resources/views/filament/pages/settings/application-settings.blade.php`
- Delete: `app/Filament/Pages/GeneralSettings.php`
- Modify: `config/filament-shield.php`
- Test: `tests/Feature/Settings/ApplicationSettingsPageTest.php`

**Interfaces:**
- Consumes: `Core\Contracts\SettingsRepository`, `Core\Settings\SettingsRegistry`, `Core\Settings\Enums\SettingScope` (Task 4/3/1), `app.name`/`app.locale`/`app.timezone` registry keys (Task 4 Step 6).
- Produces: A working Filament page at `/admin/application-settings`, gated by `view:settings` (access) / `update:settings` (save).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Settings/ApplicationSettingsPageTest.php`:

```php
<?php

namespace Tests\Feature\Settings;

use App\Filament\Pages\Settings\ApplicationSettings;
use App\Models\User;
use Core\Contracts\SettingsRepository;
use Core\Settings\Enums\SettingScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ApplicationSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPermissions(array $permissions): User
    {
        $role = Role::create(['name' => 'settings-tester-' . uniqid()]);

        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission]));
        }

        return User::factory()->create()->assignRole($role);
    }

    public function test_page_accessible_with_view_permission(): void
    {
        $user = $this->userWithPermissions(['view:settings']);

        $this->actingAs($user)
            ->get(ApplicationSettings::getUrl())
            ->assertSuccessful();
    }

    public function test_page_forbidden_without_view_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(ApplicationSettings::getUrl())
            ->assertForbidden();
    }

    public function test_save_persists_values_via_settings_repository(): void
    {
        $user = $this->userWithPermissions(['view:settings', 'update:settings']);
        $this->actingAs($user);

        Livewire::test(ApplicationSettings::class)
            ->fillForm([
                'app_name' => 'Mitra Baru',
                'app_locale' => 'en',
                'app_timezone' => 'Asia/Makassar',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $repository = app(SettingsRepository::class);

        $this->assertSame('Mitra Baru', $repository->getForScope('app.name', SettingScope::System));
        $this->assertSame('en', $repository->getForScope('app.locale', SettingScope::System));
        $this->assertSame('Asia/Makassar', $repository->getForScope('app.timezone', SettingScope::System));
    }

    public function test_save_forbidden_without_update_permission(): void
    {
        $user = $this->userWithPermissions(['view:settings']);
        $this->actingAs($user);

        try {
            Livewire::test(ApplicationSettings::class)
                ->fillForm([
                    'app_name' => 'Should Not Save',
                    'app_locale' => 'en',
                    'app_timezone' => 'Asia/Makassar',
                ])
                ->call('save');

            $this->fail('Expected an HttpException (403) for missing update:settings permission.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $repository = app(SettingsRepository::class);
        $this->assertNull($repository->getForScope('app.name', SettingScope::System));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Settings/ApplicationSettingsPageTest.php`
Expected: FAIL — `Class "App\Filament\Pages\Settings\ApplicationSettings" not found`

- [ ] **Step 3: Delete the old scaffold**

```bash
git rm app/Filament/Pages/GeneralSettings.php
```

- [ ] **Step 4: Write the Filament page**

Create `app/Filament/Pages/Settings/ApplicationSettings.php`:

```php
<?php

namespace App\Filament\Pages\Settings;

use Core\Contracts\SettingsRepository;
use Core\Settings\Enums\SettingScope;
use Core\Settings\SettingsRegistry;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ApplicationSettings extends Page
{
    protected string $view = 'filament.pages.settings.application-settings';

    protected static ?int $navigationSort = 50;

    protected static ?string $title = 'Application Settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return trans('nav.administration');
    }

    public static function canAccess(): bool
    {
        return (bool) Auth::user()?->can('view:settings');
    }

    public function mount(): void
    {
        $repository = app(SettingsRepository::class);
        $registry = app(SettingsRegistry::class);

        foreach ($registry->keysInGroup('application') as $key) {
            $field = str_replace('.', '_', $key);
            $this->data[$field] = $repository->getForScope($key, SettingScope::System)
                ?? $repository->get($key);
        }

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('app_name')
                    ->label('Nama Aplikasi')
                    ->required(),
                TextInput::make('app_locale')
                    ->label('Locale Default')
                    ->required(),
                TextInput::make('app_timezone')
                    ->label('Timezone Default')
                    ->required(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        abort_unless(Auth::user()?->can('update:settings'), 403);

        $repository = app(SettingsRepository::class);
        $registry = app(SettingsRegistry::class);
        $state = $this->form->getState();

        foreach ($registry->keysInGroup('application') as $key) {
            $field = str_replace('.', '_', $key);
            $repository->set($key, $state[$field], SettingScope::System);
        }

        Notification::make()
            ->success()
            ->title('Pengaturan disimpan')
            ->send();
    }
}
```

- [ ] **Step 5: Write the Blade view**

Create `resources/views/filament/pages/settings/application-settings.blade.php`:

```blade
<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                {{ __('Simpan') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
```

- [ ] **Step 6: Register the permissions**

In `config/filament-shield.php`, find the `'custom_permissions'` array:

```php
    'custom_permissions' => [
        'assign_user_to_unit',
        'remove_user_from_unit',
        'set_primary_unit',
    ],
```

Replace with:

```php
    'custom_permissions' => [
        'assign_user_to_unit',
        'remove_user_from_unit',
        'set_primary_unit',
        'view:settings',
        'update:settings',
    ],
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test tests/Feature/Settings/ApplicationSettingsPageTest.php`
Expected: PASS (4 passed)

- [ ] **Step 8: Run Filament code checks (project changed under `app/Filament`)**

Run: `vendor/bin/filacheck --fix`
Expected: no unresolved deprecated-Filament-API issues

- [ ] **Step 9: Commit**

```bash
git add app/Filament/Pages/Settings/ApplicationSettings.php resources/views/filament/pages/settings/application-settings.blade.php config/filament-shield.php tests/Feature/Settings/ApplicationSettingsPageTest.php
git commit -m "feat: replace GeneralSettings scaffold with native ApplicationSettings page (TODO 9.1, 9.2)"
```

---

### Task 6: Documentation, TODO update, final verification

**Files:**
- Create: `docs/conventions/settings.md`
- Modify: `docs/TODO.md`

**Interfaces:**
- Consumes: Everything from Tasks 1-5.
- Produces: Authoritative convention doc for Settings; updated TODO status; a green full test suite.

- [ ] **Step 1: Write `docs/conventions/settings.md`**

```markdown
# Settings Conventions

> Settings System — lihat spec `docs/superpowers/specs/2026-08-18-settings-system-design.md`.

## Tier & Kolom

| Tier | `organization_id` | `organizational_unit_id` | `user_id` |
|---|---|---|---|
| System | null | null | null |
| Organization | terisi | null | null |
| Unit | terisi | terisi | null |
| User | null | null | terisi |

Enum: `Core\Settings\Enums\SettingScope` (`system`, `organization`, `unit`, `user`).

## Registry

Setiap key WAJIB terdaftar di `Core\Settings\SettingsRegistry` sebelum dipakai — biasanya
lewat `core/Config/core.php['settings']['definitions']`, di-load `SettingsServiceProvider::boot()`:

\`\`\`php
'app.timezone' => [
    'type' => 'string',           // string|int|bool|float|array
    'default' => 'Asia/Jakarta',
    'scopes' => [SettingScope::System, SettingScope::Unit, SettingScope::User],
    'group' => 'application',     // dipakai halaman Filament untuk kelompokkan field
],
\`\`\`

Key tak terdaftar → `SettingsException::unknownKey()`. Scope tak diizinkan di key tsb →
`SettingsException::invalidScope()`.

## Membaca & Menulis

\`\`\`php
app(SettingsRepository::class)->get('app.timezone');
// cascading: User → Unit → Organization → System → default registry (pakai context saat ini)

app(SettingsRepository::class)->getForScope('app.timezone', SettingScope::Unit, $unitId);
// nilai mentah SATU tier saja, null jika belum di-set eksplisit (tanpa fallback)

app(SettingsRepository::class)->set('app.timezone', 'Asia/Makassar', SettingScope::Unit, $unitId);
app(SettingsRepository::class)->forget('app.timezone', SettingScope::Unit, $unitId);
\`\`\`

Bebas Filament — bisa dipanggil dari Action, Service, Job, Console Command (PRD §15).

## Cache

Cache per-tier mentah (bukan per-hasil-cascade), TTL `core.settings.cache_ttl`. `set()`/
`forget()` selalu invalidasi tepat cache tier yang ditulis — tidak perlu cache tags.

## Menambah Field Baru

1. Daftarkan key di `core/Config/core.php['settings']['definitions']`.
2. Tambahkan komponen form di halaman Filament terkait — nama field pakai underscore
   (`str_replace('.', '_', $key)`), jangan titik (bentrok dengan nested-array statePath
   Livewire).
3. `mount()`/`save()` halaman memakai `SettingsRegistry::keysInGroup()` — tidak perlu
   diubah kalau key baru masuk grup yang sudah ada.
```

- [ ] **Step 2: Update `docs/TODO.md` §9.1 and §9.2**

Replace the §9.1 block:

```markdown
## 9.1 Settings Architecture

- [ ] Define settings contract
- [ ] Define settings repository/storage
- [ ] Define typed settings
- [ ] Define settings scopes
- [ ] Define default values
- [ ] Define fallback behavior
```

with:

```markdown
## 9.1 Settings Architecture

- [x] Define settings contract — `Core\Contracts\SettingsRepository`, spec §3.5
- [x] Define settings repository/storage — `Core\Settings\DatabaseSettingsRepository`, tabel `settings` (M2/ADR-011)
- [x] Define typed settings — `Core\Settings\SettingsRegistry` (`type` per key), spec §3.4
- [x] Define settings scopes — `Core\Settings\Enums\SettingScope`, spec §3.2-3.3
- [x] Define default values — `SettingsRegistry` (`default` per key), spec §3.4
- [x] Define fallback behavior — cascading `User → Unit → Organization → System`, spec §3.6
```

Then in §9.2, replace only the "Application settings" line:

```markdown
- [ ] Application settings
```

with:

```markdown
- [x] Application settings — `app/Filament/Pages/Settings/ApplicationSettings.php`, keys `app.name`/`app.locale`/`app.timezone`
```

(leave `Security settings`, `Localization settings`, `Mail settings`, `Storage settings` as `[ ]` — out of scope, see Global Constraints)

- [ ] **Step 3: Run the full test suite**

Run: `php artisan test`
Expected: all tests pass, including the new `tests/Unit/Settings/*` and `tests/Feature/Settings/*` suites

- [ ] **Step 4: Run formatting and quality gate**

Run: `vendor/bin/pint --dirty --format agent`
Expected: fixes applied (if any), no errors

Run: `composer check`
Expected: tests, static analysis (Larastan), and formatting all pass

- [ ] **Step 5: Commit**

```bash
git add docs/conventions/settings.md docs/TODO.md
git commit -m "docs: add settings conventions and update TODO (TODO 9.1, 9.2)"
```

---

## Self-Review Notes

**Spec coverage** (`docs/superpowers/specs/2026-08-18-settings-system-design.md`):
- §3.2 `SettingScope` enum → Task 1
- §4 Error handling (`unknownKey`, `invalidScope`) → Task 2
- §3.4 `SettingsRegistry` → Task 3
- §3.5 `SettingsRepository` contract → Task 4
- §3.6 Cascading resolution → Task 4 (`DatabaseSettingsRepositoryTest::test_cascade_prefers_unit_over_system`)
- §3.7 Per-tier cache + invalidation → Task 4 (`SettingsCacheInvalidationTest`)
- §4 Error handling table (invalid scope combinations) → Task 4 (`guardScope` + tests)
- §5 Contoh nyata Application Settings → Task 5
- §6 Konfigurasi (`core/Config/core.php`) → Task 4 Step 6
- §7 Testing → Tasks 1-5 (test files match spec's proposed layout, `SettingsCascadeTest` folded into `DatabaseSettingsRepositoryTest` since both exercise the same repository directly without HTTP/Livewire)
- §8 Documentation → Task 6
- §9 Non-Filament usage → documented in Task 6's `docs/conventions/settings.md` (no separate Facade class — matches existing codebase convention of `app(Contract::class)`, see `Core\Contracts\OrganizationContext` usage)
- §10 Out of scope → intentionally not implemented (see Global Constraints)

**Placeholder scan:** No TBD/TODO/vague steps. All code blocks are complete and runnable.

**Type consistency:** `SettingScope`, `SettingsRegistry`, `SettingsRepository`, `DatabaseSettingsRepository` method signatures are identical everywhere they're referenced (Tasks 1, 3, 4, 5, 6). `str_replace('.', '_', $key)` field-name convention is consistent between `ApplicationSettings::mount()` and `::save()` (Task 5). Permission names `view:settings`/`update:settings` consistent across Task 5's page, config, and tests.

# Organizational Context Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun abstraction `OrganizationContext` & `OrganizationalUnitContext` (M4, TODO §5) yang independent dari Filament, dengan resolusi default (login → primary unit), session-based current unit, `SwitchUnitAction` + switcher UI di Filament, dan dukungan non-Filament (Services/Actions/Policies/Jobs/Commands).

**Architecture:** Session-first Core resolver. Session hanya menyimpan `unit_id`; `organization_id` selalu di-derive dari current unit (unit → organization) atau pivot `organization_user` jika user tak punya unit. Kontrak (`OrganizationContext`, `OrganizationalUnitContext`) di `core/Contracts/`; implementasi (`*Manager`, `ContextResolver`, `SwitchUnitAction`) di `core/Context/`; binding contract → impl via `ContextServiceProvider` yang didaftarkan dari `config('core.providers')` (ADR-009/010). Context non-UI bebas Filament; UI switcher di app layer (`app/Http/Controllers/` + `resources/views/` + panel provider).

**Tech Stack:** Laravel 13, Filament 5 (v5.7.6), PHP 8.3+, Pest (MySQL test), `Core\Exceptions\OrganizationException` (sudah ada).

## Global Constraints

- Bahasa pesan exception & notifikasi user: Bahasa Indonesia. Nama kelas/metode: English.
- `Core\` tidak mengimpor `App\`/`Modules\`; Core non-UI tidak bergantung Filament (ADR-005) — arch test `tests/Arch/CoreArchTest.php` tidak boleh rusak.
- Kontrak di `core/Contracts/` (API publik Core, ADR-008); implementasi di `core/Context/` (ADR-002).
- Sub-provider domain didaftarkan via `config('core.providers')` (ADR-009) — `bootstrap/providers.php` tidak disentuh.
- Kunci config pola `core.{domain}.{key}` → `core.context.session_key` default `'context.unit_id'`.
- Session hanya menyimpan `unit_id` (current unit). `organization_id` tidak pernah disimpan di session.
- `current()`/`organization()` selalu valid — tidak pernah mengembalikan unit/org di luar akses user; session basi (unit tak lagi di-assign) → clear + fallback, tanpa throw.
- Tak ada unit/org → `has()=false`, `current()=null`, tidak throw.
- Switch ke unit tak di-assign → throw `OrganizationException::invalidAssignment`.
- `SwitchUnitAction` menerima primitif (`string $userId`, `string $unitId`) — tanpa import `App\Models\User`.
- Action convention: `final`, method `handle()`, constructor injection.
- Test: PHPUnit class-style (`class XxxTest extends TestCase`), `use RefreshDatabase;`, namespace `Tests\Unit\Context\...` / `Tests\Feature\Context\...`. `tests/TestCase.php` sudah `RefreshDatabase`.
- Factory: `Organization::factory()`, `OrganizationalUnit::factory()`, `User::factory()` (auto-discover).
- `composer check` (Pint → Pest → PHPStan) hijau di tiap akhir task.
- Commit message: conventional commits (`feat:`, `test:`, `docs:`, `chore:`), satu task = satu commit.
- Environment: Windows (cmd) — heredoc `<<` TIDAK didukung; commit message via file temp (lihat `docs/superpowers/plans/2026-08-16-organization-core.md` Task 1).
- Arch test `tests/Arch/CoreArchTest.php` memeriksa `Core` → `App`/`Modules`/`Filament` — jalankan `vendor\bin\pest tests\Arch` bila ragu.

---

### Task 1: Kontrak `OrganizationContext` & `OrganizationalUnitContext`

**Files:**
- Create: `core/Contracts/OrganizationContext.php`
- Create: `core/Contracts/OrganizationalUnitContext.php`
- Test: `tests/Unit/Context/ContextContractTest.php`

**Interfaces:**
- Consumes: `Core\Organization\Models\Organization`, `Core\Organization\Models\OrganizationalUnit` (model sudah ada dari M3).
- Produces: `Core\Contracts\OrganizationContext` (interface, method `organization(): ?Organization`, `organizationId(): ?string`, `set(Organization): void`, `clear(): void`, `has(): bool`); `Core\Contracts\OrganizationalUnitContext` (interface, method `current(): ?OrganizationalUnit`, `currentId(): ?string`, `set(OrganizationalUnit): void`, `clear(): void`, `has(): bool`). Dipakai Task 2 (implementasi), Task 3 (resolver), Task 4 (action), Task 5+ (test).

- [ ] **Step 1: Tulis failing test**

Buat `tests/Unit/Context/ContextContractTest.php`:

```php
<?php

namespace Tests\Unit\Context;

use Core\Contracts\OrganizationContext;
use Core\Contracts\OrganizationalUnitContext;
use PHPUnit\Framework\TestCase;

class ContextContractTest extends TestCase
{
    public function test_organization_context_defines_expected_methods(): void
    {
        $this->assertTrue(interface_exists(OrganizationContext::class));
        $this->assertSame([
            'organization',
            'organizationId',
            'set',
            'clear',
            'has',
        ], get_class_methods(OrganizationContext::class));
    }

    public function test_organizational_unit_context_defines_expected_methods(): void
    {
        $this->assertTrue(interface_exists(OrganizationalUnitContext::class));
        $this->assertSame([
            'current',
            'currentId',
            'set',
            'clear',
            'has',
        ], get_class_methods(OrganizationalUnitContext::class));
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor\bin\pest tests\Unit\Context\ContextContractTest.php`
Expected: FAIL — `Class "Core\Contracts\OrganizationContext" not found`.

- [ ] **Step 3: Tulis implementasi**

Buat `core/Contracts/OrganizationContext.php`:

```php
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
```

Buat `core/Contracts/OrganizationalUnitContext.php`:

```php
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
```

- [ ] **Step 4: Run test — pastikan pass**

Run: `vendor\bin\pest tests\Unit\Context\ContextContractTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add core/Contracts/OrganizationContext.php core/Contracts/OrganizationalUnitContext.php tests/Unit/Context/ContextContractTest.php
git commit -m "feat: add context contracts (TODO 5.1)"
```

---

### Task 2: `ContextResolver` — baca session + fallback

**Files:**
- Create: `core/Context/ContextResolver.php`
- Test: `tests/Unit/Context/ContextResolverTest.php`

**Interfaces:**
- Consumes: Task 1 (kontrak — tidak dipakai langsung, hanya referensi), `App\Models\User` (via `Auth::user()`, `Illuminate\Support\Facades\Auth`), `Core\Organization\Models\OrganizationalUnit`, `Core\Organization\Models\Organization`.
- Produces: `Core\Context\ContextResolver` dengan method:
  - `resolveCurrentUnit(?User $user): ?OrganizationalUnit` — session `unit_id` (valid) → primary unit → unit pertama di-assign → null. Session basi di-clear.
  - `resolveOrganization(?User $user): ?Organization` — dari current unit (unit → organization) → pivot `organization_user` pertama → null.
  - `sessionKey(): string` — `config('core.context.session_key', 'context.unit_id')`.
  Dipakai Task 3 (manager), Task 4 (action), Task 6 (controller).

- [ ] **Step 1: Tulis failing test**

Buat `tests/Unit/Context/ContextResolverTest.php`:

```php
<?php

namespace Tests\Unit\Context;

use App\Models\User;
use Core\Context\ContextResolver;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class ContextResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_key_uses_config_default(): void
    {
        $resolver = app(ContextResolver::class);

        $this->assertSame('context.unit_id', $resolver->sessionKey());
    }

    public function test_session_key_reads_config(): void
    {
        config(['core.context.session_key' => 'custom.key']);
        $resolver = app(ContextResolver::class);

        $this->assertSame('custom.key', $resolver->sessionKey());
    }

    public function test_returns_null_when_user_has_no_units(): void
    {
        $user = User::factory()->create();

        $this->assertNull(app(ContextResolver::class)->resolveCurrentUnit($user));
    }

    public function test_uses_primary_unit_when_no_session(): void
    {
        $user = User::factory()->create();
        $primary = OrganizationalUnit::factory()->create();
        $other = OrganizationalUnit::factory()->create();
        $user->units()->attach([$primary->id, $other->id]);
        $user->update(['primary_organizational_unit_id' => $primary->id]);

        $this->assertSame($primary->id, app(ContextResolver::class)->resolveCurrentUnit($user)->id);
    }

    public function test_uses_first_assigned_unit_when_no_primary(): void
    {
        $user = User::factory()->create();
        $first = OrganizationalUnit::factory()->create();
        $second = OrganizationalUnit::factory()->create();
        $user->units()->attach([$first->id, $second->id]);

        $this->assertSame($first->id, app(ContextResolver::class)->resolveCurrentUnit($user)->id);
    }

    public function test_uses_session_unit_when_valid(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);
        Session::put('context.unit_id', $unit->id);

        $this->assertSame($unit->id, app(ContextResolver::class)->resolveCurrentUnit($user)->id);
    }

    public function test_clears_stale_session_and_falls_back(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);
        $stale = OrganizationalUnit::factory()->create();
        Session::put('context.unit_id', $stale->id);

        $resolved = app(ContextResolver::class)->resolveCurrentUnit($user);

        $this->assertSame($unit->id, $resolved->id);
        $this->assertFalse(Session::has('context.unit_id'));
    }

    public function test_resolve_organization_from_current_unit(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $unit = OrganizationalUnit::factory()->create(['organization_id' => $organization->id]);
        $user->units()->attach($unit->id);

        $this->assertSame($organization->id, app(ContextResolver::class)->resolveOrganization($user)->id);
    }

    public function test_resolve_organization_from_pivot_when_no_units(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization);

        $this->assertSame($organization->id, app(ContextResolver::class)->resolveOrganization($user)->id);
    }

    public function test_resolve_organization_null_when_no_units_and_no_orgs(): void
    {
        $user = User::factory()->create();

        $this->assertNull(app(ContextResolver::class)->resolveOrganization($user));
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor\bin\pest tests\Unit\Context\ContextResolverTest.php`
Expected: FAIL — `Class "Core\Context\ContextResolver" not found`.

- [ ] **Step 3: Tulis implementasi**

Buat `core/Context/ContextResolver.php`:

```php
<?php

namespace Core\Context;

use App\Models\User;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

final class ContextResolver
{
    public function sessionKey(): string
    {
        return (string) config('core.context.session_key', 'context.unit_id');
    }

    public function resolveCurrentUnit(?User $user): ?OrganizationalUnit
    {
        if ($user === null) {
            return null;
        }

        $sessionKey = $this->sessionKey();

        if (Session::has($sessionKey)) {
            $unit = OrganizationalUnit::find(Session::get($sessionKey));

            if ($unit !== null && $user->units()->where('organizational_units.id', $unit->id)->exists()) {
                return $unit;
            }

            Session::forget($sessionKey);
        }

        if ($user->primary_organizational_unit_id !== null) {
            $primary = OrganizationalUnit::find($user->primary_organizational_unit_id);

            if ($primary !== null && $user->units()->where('organizational_units.id', $primary->id)->exists()) {
                return $primary;
            }
        }

        return $user->units()->first();
    }

    public function resolveOrganization(?User $user): ?Organization
    {
        if ($user === null) {
            return null;
        }

        $unit = $this->resolveCurrentUnit($user);

        if ($unit !== null) {
            return $unit->organization;
        }

        return $user->organizations()->first();
    }
}
```

> Catatan implementasi: `Auth::user()` di-resolve di dalam manager (Task 3) dan dipakai oleh controller (Task 6) — `ContextResolver` menerima `?User` dari caller agar tetap testable dan bisa dipakai di CLI/queue (di mana session/Auth tak tersedia → caller mengirim `null`).

- [ ] **Step 4: Run test — pastikan pass**

Run: `vendor\bin\pest tests\Unit\Context\ContextResolverTest.php`
Expected: PASS (10 tests).

- [ ] **Step 5: Commit**

```bash
git add core/Context/ContextResolver.php tests/Unit/Context/ContextResolverTest.php
git commit -m "feat: add ContextResolver with session + fallback resolution (TODO 5.2)"
```

---

### Task 3: Manager `OrganizationContextManager` & `OrganizationalUnitContextManager`

**Files:**
- Create: `core/Context/OrganizationContextManager.php`
- Create: `core/Context/OrganizationalUnitContextManager.php`
- Test: `tests/Unit/Context/ContextManagerTest.php`

**Interfaces:**
- Consumes: Task 1 (kontrak), Task 2 (`ContextResolver`), `Core\Organization\Models\Organization`, `Core\Organization\Models\OrganizationalUnit`, `App\Models\User` (via `Auth::user()`).
- Produces: `Core\Context\OrganizationContextManager` (implements `Core\Contracts\OrganizationContext`), `Core\Context\OrganizationalUnitContextManager` (implements `Core\Contracts\OrganizationalUnitContext`). Keduanya singleton di container (dibind Task 4 via `ContextServiceProvider`). Manager memakai `Auth::user()`; `Auth::user()` null (CLI/queue/tidak login) → `has()=false`, `current()/organization()=null`. `set()` hanya untuk current unit manager yang menyimpan `unit_id` ke session; `OrganizationContextManager::set()` menyimpan state in-memory (per-request), tidak ke session (organization di-derive, bukan disimpan — spec §3.3).

- [ ] **Step 1: Tulis failing test**

Buat `tests/Unit/Context/ContextManagerTest.php`:

```php
<?php

namespace Tests\Unit\Context;

use App\Models\User;
use Core\Context\OrganizationContextManager;
use Core\Context\OrganizationalUnitContextManager;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class ContextManagerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): User
    {
        $user = User::factory()->create();

        Auth::login($user);

        return $user;
    }

    public function test_organization_context_empty_when_unauthenticated(): void
    {
        $manager = app(OrganizationContextManager::class);

        $this->assertFalse($manager->has());
        $this->assertNull($manager->organization());
        $this->assertNull($manager->organizationId());
    }

    public function test_organization_context_has_organization_from_unit(): void
    {
        $user = $this->actingAsUser();
        $organization = Organization::factory()->create();
        $unit = OrganizationalUnit::factory()->create(['organization_id' => $organization->id]);
        $user->units()->attach($unit->id);

        $manager = app(OrganizationContextManager::class);

        $this->assertTrue($manager->has());
        $this->assertSame($organization->id, $manager->organizationId());
    }

    public function test_organization_context_from_pivot_when_no_units(): void
    {
        $user = $this->actingAsUser();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization);

        $manager = app(OrganizationContextManager::class);

        $this->assertTrue($manager->has());
        $this->assertSame($organization->id, $manager->organizationId());
    }

    public function test_unit_context_uses_primary_unit_by_default(): void
    {
        $user = $this->actingAsUser();
        $primary = OrganizationalUnit::factory()->create();
        $other = OrganizationalUnit::factory()->create();
        $user->units()->attach([$primary->id, $other->id]);
        $user->update(['primary_organizational_unit_id' => $primary->id]);

        $manager = app(OrganizationalUnitContextManager::class);

        $this->assertTrue($manager->has());
        $this->assertSame($primary->id, $manager->currentId());
    }

    public function test_unit_context_reads_session(): void
    {
        $user = $this->actingAsUser();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);
        Session::put('context.unit_id', $unit->id);

        $manager = app(OrganizationalUnitContextManager::class);

        $this->assertSame($unit->id, $manager->currentId());
    }

    public function test_unit_context_set_writes_session(): void
    {
        $user = $this->actingAsUser();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);

        app(OrganizationalUnitContextManager::class)->set($unit);

        $this->assertSame($unit->id, Session::get('context.unit_id'));
        $this->assertSame($unit->id, app(OrganizationalUnitContextManager::class)->currentId());
    }

    public function test_unit_context_clear_removes_session(): void
    {
        $user = $this->actingAsUser();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);
        Session::put('context.unit_id', $unit->id);

        app(OrganizationalUnitContextManager::class)->clear();

        $this->assertFalse(Session::has('context.unit_id'));
        $this->assertFalse(app(OrganizationalUnitContextManager::class)->has());
    }

    public function test_unit_context_returns_null_when_unauthenticated(): void
    {
        $manager = app(OrganizationalUnitContextManager::class);

        $this->assertFalse($manager->has());
        $this->assertNull($manager->current());
        $this->assertNull($manager->currentId());
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor\bin\pest tests\Unit\Context\ContextManagerTest.php`
Expected: FAIL — `Class "Core\Context\OrganizationContextManager" not found`.

- [ ] **Step 3: Tulis implementasi**

Buat `core/Context/OrganizationContextManager.php`:

```php
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
```

Buat `core/Context/OrganizationalUnitContextManager.php`:

```php
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
```

> Catatan implementasi: `app(ContextResolver::class)` dipakai langsung (bukan constructor injection) karena kedua manager adalah singleton dan resolver harus dibaca per-request agar `Auth::user()` selalu segar — mengikuti pola `CoreServiceProviderTest` yang sudah memakai `app(...)` di test. `set()` pada `OrganizationalUnitContextManager` menyimpan `unit_id` ke session (source of truth per-device, spec §3.3).

- [ ] **Step 4: Run test — pastikan pass**

Run: `vendor\bin\pest tests\Unit\Context\ContextManagerTest.php`
Expected: PASS (8 tests).

- [ ] **Step 5: Commit**

```bash
git add core/Context/OrganizationContextManager.php core/Context/OrganizationalUnitContextManager.php tests/Unit/Context/ContextManagerTest.php
git commit -m "feat: add context managers implementing contracts (TODO 5.2)"
```

---

### Task 4: `ContextServiceProvider` + config + binding

**Files:**
- Modify: `core/Config/core.php`
- Create: `core/Context/ContextServiceProvider.php`
- Test: `tests/Unit/Core/CoreServiceProviderTest.php` (tambah 1 method)

**Interfaces:**
- Consumes: Task 1 (kontrak), Task 3 (manager), `Core\CoreServiceProvider` (sudah ada).
- Produces: `Core\Context\ContextServiceProvider` (extends `Illuminate\Support\ServiceProvider`) yang di `register()` membind singleton `Core\Contracts\OrganizationContext` → `Core\Context\OrganizationContextManager` dan `Core\Contracts\OrganizationalUnitContext` → `Core\Context\OrganizationalUnitContextManager`. Didaftarkan via entry `'providers'` di `core/Config/core.php`. Menambah blok config `'context' => ['session_key' => 'context.unit_id']`. Dipakai Task 5+ (`app(OrganizationContext::class)`).

- [ ] **Step 1: Tulis failing test**

Tambahkan method berikut ke `tests/Unit/Core/CoreServiceProviderTest.php` (di dalam class `CoreServiceProviderTest`):

```php
    public function test_context_provider_is_registered_in_config(): void
    {
        $this->assertContains(
            Core\Context\ContextServiceProvider::class,
            config('core.providers')
        );
    }

    public function test_context_contracts_are_bound_to_managers(): void
    {
        $this->assertInstanceOf(
            Core\Context\OrganizationContextManager::class,
            app(Core\Contracts\OrganizationContext::class)
        );
        $this->assertInstanceOf(
            Core\Context\OrganizationalUnitContextManager::class,
            app(Core\Contracts\OrganizationalUnitContext::class)
        );
    }

    public function test_context_config_has_session_key(): void
    {
        $this->assertSame('context.unit_id', config('core.context.session_key'));
    }
```

(File sudah ada — jangan menimpa; tambahkan method di dalam class. Pastikan import `use Core\Context\ContextServiceProvider;` bila memakai FQCN singkat.)

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor\bin\pest tests\Unit\Core\CoreServiceProviderTest.php`
Expected: FAIL — `config('core.providers')` tidak mengandung `ContextServiceProvider`, dan `app(Core\Contracts\OrganizationContext::class)` throw `Target ... is not instantiable`.

- [ ] **Step 3: Tulis implementasi**

Modifikasi `core/Config/core.php` menjadi:

```php
<?php

return [
    'providers' => [
        Core\Context\ContextServiceProvider::class,
    ],
    'organization' => [
        'max_depth' => 10,
    ],
    'context' => [
        'session_key' => 'context.unit_id',
    ],
];
```

Buat `core/Context/ContextServiceProvider.php`:

```php
<?php

namespace Core\Context;

use Core\Contracts\OrganizationContext;
use Core\Contracts\OrganizationalUnitContext;
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
```

- [ ] **Step 4: Run test — pastikan pass**

Run: `vendor\bin\pest tests\Unit\Core\CoreServiceProviderTest.php`
Expected: PASS (semua method, termasuk 3 yang baru).

- [ ] **Step 5: Commit**

```bash
git add core/Config/core.php core/Context/ContextServiceProvider.php tests/Unit/Core/CoreServiceProviderTest.php
git commit -m "feat: register ContextServiceProvider and bind context contracts (TODO 5.2, ADR-009)"
```

---

### Task 5: `SwitchUnitAction`

**Files:**
- Create: `core/Context/Actions/SwitchUnitAction.php`
- Test: `tests/Unit/Context/SwitchUnitActionTest.php`

**Interfaces:**
- Consumes: Task 3 (`OrganizationalUnitContextManager` via `Core\Contracts\OrganizationalUnitContext`), `Core\Exceptions\OrganizationException`, `App\Models\User` (query assignment), `Core\Organization\Models\OrganizationalUnit`.
- Produces: `Core\Context\Actions\SwitchUnitAction` (final, method `handle(string $userId, string $unitId): void`). Validasi: unit harus di-assign ke user (query `units()` via `OrganizationalUnit::whereHas` + pivot) → valid: `set()` current unit; tidak valid: throw `OrganizationException::invalidAssignment`. Dipakai Task 6 (controller).

- [ ] **Step 1: Tulis failing test**

Buat `tests/Unit/Context/SwitchUnitActionTest.php`:

```php
<?php

namespace Tests\Unit\Context;

use App\Models\User;
use Core\Context\Actions\SwitchUnitAction;
use Core\Exceptions\OrganizationException;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class SwitchUnitActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_switches_to_assigned_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);

        app(SwitchUnitAction::class)->handle($user->id, $unit->id);

        $this->assertSame($unit->id, Session::get('context.unit_id'));
    }

    public function test_throws_when_unit_not_assigned(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();

        $this->expectException(OrganizationException::class);
        app(SwitchUnitAction::class)->handle($user->id, $unit->id);
    }

    public function test_throws_when_user_does_not_exist(): void
    {
        $unit = OrganizationalUnit::factory()->create();

        $this->expectException(OrganizationException::class);
        app(SwitchUnitAction::class)->handle('non-existent-user-id', $unit->id);
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor\bin\pest tests\Unit\Context\SwitchUnitActionTest.php`
Expected: FAIL — `Class "Core\Context\Actions\SwitchUnitAction" not found`.

- [ ] **Step 3: Tulis implementasi**

Buat `core/Context/Actions/SwitchUnitAction.php`:

```php
<?php

namespace Core\Context\Actions;

use Core\Contracts\OrganizationalUnitContext;
use Core\Exceptions\OrganizationException;
use Core\Organization\Models\OrganizationalUnit;

final class SwitchUnitAction
{
    public function __construct(
        private readonly OrganizationalUnitContext $context,
    ) {
    }

    public function handle(string $userId, string $unitId): void
    {
        $assigned = OrganizationalUnit::query()
            ->where('organizational_units.id', $unitId)
            ->whereHas('users', fn ($query) => $query->where('users.id', $userId))
            ->exists();

        if (! $assigned) {
            throw OrganizationException::invalidAssignment(
                'Unit tujuan harus merupakan unit yang di-assign ke pengguna.'
            );
        }

        $unit = OrganizationalUnit::findOrFail($unitId);

        $this->context->set($unit);
    }
}
```

> Catatan implementasi: `whereHas('users', ...)` memakai relasi `users()` yang **belum ada** di model `OrganizationalUnit` (M3 hanya punya `organization()`, `parent()`, `children()`). Tambahkan relasi berikut ke `core/Organization/Models/OrganizationalUnit.php` (relasi `users(): BelongsToMany` ke `App\Models\User` via pivot `organizational_unit_user`):

```php
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// di dalam class OrganizationalUnit:
public function users(): BelongsToMany
{
    return $this->belongsToMany(User::class);
}
```

> Penting: relasi ini mengimpor `App\Models\User` dari dalam `core/` — melanggar ADR-005 (`Core` tidak boleh mengimpor `App`). **Alternatif yang benar:** jangan pakai `whereHas('users')`. Gunakan query pivot langsung via `DB::table('organizational_unit_user')`:

```php
<?php

namespace Core\Context\Actions;

use Core\Contracts\OrganizationalUnitContext;
use Core\Exceptions\OrganizationException;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Support\Facades\DB;

final class SwitchUnitAction
{
    public function __construct(
        private readonly OrganizationalUnitContext $context,
    ) {
    }

    public function handle(string $userId, string $unitId): void
    {
        $assigned = DB::table('organizational_unit_user')
            ->where('organizational_unit_id', $unitId)
            ->where('user_id', $userId)
            ->exists();

        if (! $assigned) {
            throw OrganizationException::invalidAssignment(
                'Unit tujuan harus merupakan unit yang di-assign ke pengguna.'
            );
        }

        $unit = OrganizationalUnit::findOrFail($unitId);

        $this->context->set($unit);
    }
}
```

**Gunakan implementasi kedua** (query pivot via `DB::table`). Jangan menambah relasi `users()` ke model Core — Core tidak boleh mengimpor `App\Models\User` (arch test `tests/Arch/CoreArchTest.php`). Relasi `users()` di sisi `App\Models\User` (`units()`) sudah ada dan cukup.

- [ ] **Step 4: Run test — pastikan pass**

Run: `vendor\bin\pest tests\Unit\Context\SwitchUnitActionTest.php`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add core/Context/Actions/SwitchUnitAction.php tests/Unit/Context/SwitchUnitActionTest.php
git commit -m "feat: add SwitchUnitAction with assignment validation (TODO 5.3)"
```

---

### Task 6: Route + Controller `SwitchUnitController`

**Files:**
- Create: `app/Http/Controllers/SwitchUnitController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Context/SwitchUnitTest.php`

**Interfaces:**
- Consumes: Task 5 (`Core\Context\Actions\SwitchUnitAction`), `App\Models\User`, `Core\Contracts\OrganizationalUnitContext`, `Filament\Notifications\Notification`, `Illuminate\Http\RedirectResponse`, `Illuminate\Http\Request`.
- Produces: `App\Http\Controllers\SwitchUnitController` (single-action `__invoke(Request): RedirectResponse`): ambil `auth()->user()`, baca `unit_id` dari request, panggil `SwitchUnitAction`, kirim notifikasi sukses (Filament `Notification::make()->title(...)->success()->send()`), `redirect()->back()`. Bila `unit_id` kosong → redirect back + error notification. Route `POST /context/switch-unit` bernama `context.switch-unit` di `routes/web.php` (middleware `web` default → CSRF + session aktif).

- [ ] **Step 1: Tulis failing test**

Buat `tests/Feature/Context/SwitchUnitTest.php`:

```php
<?php

namespace Tests\Feature\Context;

use App\Models\User;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SwitchUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_switch_unit_redirects_back_with_success(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);

        $this->actingAs($user)
            ->post(route('context.switch-unit'), ['unit_id' => $unit->id])
            ->assertRedirect();
    }

    public function test_switch_unit_sets_session_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);

        $this->actingAs($user)->post(route('context.switch-unit'), ['unit_id' => $unit->id]);

        $this->assertSame($unit->id, session('context.unit_id'));
    }

    public function test_switch_unit_rejects_unassigned_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();

        $this->actingAs($user)
            ->post(route('context.switch-unit'), ['unit_id' => $unit->id])
            ->assertRedirect()
            ->assertSessionHas('errors');
    }

    public function test_switch_unit_requires_authentication(): void
    {
        $unit = OrganizationalUnit::factory()->create();

        $this->post(route('context.switch-unit'), ['unit_id' => $unit->id])
            ->assertRedirect('/login');
    }

    public function test_switch_unit_rejects_missing_unit_id(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('context.switch-unit'))
            ->assertRedirect()
            ->assertSessionHas('errors');
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor\bin\pest tests\Feature\Context\SwitchUnitTest.php`
Expected: FAIL — route `context.switch-unit` not defined (atau 404).

- [ ] **Step 3: Tulis implementasi**

Buat `app/Http/Controllers/SwitchUnitController.php`:

```php
<?php

namespace App\Http\Controllers;

use Core\Context\Actions\SwitchUnitAction;
use Core\Exceptions\OrganizationException;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SwitchUnitController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $unitId = (string) $request->input('unit_id');

        if ($unitId === '') {
            Notification::make()
                ->title('Unit tidak dipilih.')
                ->danger()
                ->send();

            return redirect()->back();
        }

        try {
            app(SwitchUnitAction::class)->handle((string) $request->user()->id, $unitId);

            Notification::make()
                ->title('Unit berhasil diganti.')
                ->success()
                ->send();

            return redirect()->back();
        } catch (OrganizationException) {
            Notification::make()
                ->title('Anda tidak memiliki akses ke unit tersebut.')
                ->danger()
                ->send();

            return redirect()->back();
        }
    }
}
```

Modifikasi `routes/web.php` — tambahkan route di bawah route `'/'` yang sudah ada:

```php
<?php

use App\Http\Controllers\SwitchUnitController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('context/switch-unit', SwitchUnitController::class)
    ->name('context.switch-unit');
```

- [ ] **Step 4: Run test — pastikan pass**

Run: `vendor\bin\pest tests\Feature\Context\SwitchUnitTest.php`
Expected: PASS (5 tests).

> Catatan: `assertRedirect('/login')` mengharapkan redirect ke route login Laravel default. Di app ini login Filament ada di `admin/login`; jika redirect aktual berbeda, sesuaikan assertion — yang penting route butuh auth (bukan 200). Test `test_switch_unit_requires_authentication` bisa diverifikasi manual dengan melihat output pest.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/SwitchUnitController.php routes/web.php tests/Feature/Context/SwitchUnitTest.php
git commit -m "feat: add switch-unit route and controller (TODO 5.3)"
```

---

### Task 7: Unit Switcher di Filament (render hook + blade)

**Files:**
- Create: `resources/views/panel/unit-switcher.blade.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Test: `tests/Feature/Context/ContextFilamentTest.php`

**Interfaces:**
- Consumes: Task 6 (route `context.switch-unit`), `App\Models\User` (`units()`), `Core\Contracts\OrganizationalUnitContext` (via `app(...)`), `Core\Contracts\OrganizationContext`, `Filament\View\PanelsRenderHook`, `Filament\Panel`.
- Produces: Blade view `resources/views/panel/unit-switcher.blade.php` (dropdown `x-filament::dropdown` dengan daftar unit user, unit aktif ditandai, submit POST ke `route('context.switch-unit')` via form per item); render hook `PanelsRenderHook::USER_MENU_BEFORE` di `AdminPanelProvider::panel()` (hanya render jika `auth()->user()->units()->count() > 1`). Dipakai manual di panel admin.

- [ ] **Step 1: Tulis failing test**

Buat `tests/Feature/Context/ContextFilamentTest.php`:

```php
<?php

namespace Tests\Feature\Context;

use App\Models\User;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContextFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_switcher_renders_when_user_has_multiple_units(): void
    {
        $user = User::factory()->create();
        $unitA = OrganizationalUnit::factory()->create();
        $unitB = OrganizationalUnit::factory()->create();
        $user->units()->attach([$unitA->id, $unitB->id]);
        session(['context.unit_id' => $unitA->id]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertSee($unitA->name)
            ->assertSee($unitB->name);
    }

    public function test_switcher_hidden_when_user_has_single_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);

        $this->actingAs($user)
            ->get('/admin')
            ->assertDontSee('unit-switcher');
    }

    public function test_switcher_marks_active_unit(): void
    {
        $user = User::factory()->create();
        $unitA = OrganizationalUnit::factory()->create();
        $unitB = OrganizationalUnit::factory()->create();
        $user->units()->attach([$unitA->id, $unitB->id]);
        session(['context.unit_id' => $unitA->id]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertSee('unit-switcher-active');
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor\bin\pest tests\Feature\Context\ContextFilamentTest.php`
Expected: FAIL — `/admin` belum menampilkan switcher (assertSee gagal).

> Catatan: `/admin` adalah dashboard Filament; test ini butuh auth + mungkin butuh `Livewire`/panel boot. Jika `GET /admin` gagal (mis. redirect ke login karena panel auth middleware), sesuaikan dengan `->actingAs($user, 'web')` (sudah) dan pastikan user punya role/permission akses dashboard. Verifikasi manual bila perlu.

- [ ] **Step 3: Tulis implementasi**

Buat `resources/views/panel/unit-switcher.blade.php` (mengikuti pola dropdown language switcher, tapi POST form):

```blade
@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $units = $user->units()->get();
    $currentUnitId = app(\Core\Contracts\OrganizationalUnitContext::class)->currentId();
@endphp

@if ($units->count() > 1)
    <x-filament::dropdown placement="bottom-end" maxHeight="36rem" teleport>
        <x-slot name="trigger">
            <x-filament::icon-button
                icon="heroicon-o-building-office-2"
                label="Ganti unit"
                tooltip="Ganti unit"
            />
        </x-slot>

        <x-filament::dropdown.list>
            @foreach ($units as $unit)
                <form method="POST" action="{{ route('context.switch-unit') }}">
                    @csrf
                    <input type="hidden" name="unit_id" value="{{ $unit->id }}">
                    <button
                        type="submit"
                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-white/5 flex items-center gap-2 {{ $unit->id === $currentUnitId ? 'unit-switcher-active font-semibold' : '' }}"
                    >
                        <span class="flex-1">{{ $unit->name }}</span>
                        @if ($unit->id === $currentUnitId)
                            <x-filament::icon icon="heroicon-o-check" class="h-4 w-4 text-primary-600" />
                        @endif
                    </button>
                </form>
            @endforeach
        </x-filament::dropdown.list>
    </x-filament::dropdown>
@endif
```

Modifikasi `app/Providers/Filament/AdminPanelProvider.php` — di dalam method `panel(Panel $panel)` (setelah `->plugins([...])` atau sebelum `->sidebarWidth(...)`), tambahkan:

```php
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): \Illuminate\View\View => view('panel.unit-switcher'),
            )
```

(Pastikan `use Filament\View\PanelsRenderHook;` sudah ada di bagian atas file — sudah, dipakai plugin language switcher.)

> Catatan: render hook `USER_MENU_BEFORE` sudah dipakai language switcher (plugin) — render hook Filament mendukung banyak callback pada hook yang sama; keduanya akan tampil. Urutan render mengikuti urutan registrasi (language switcher dulu via plugin, lalu switcher unit). Jika ingin switcher unit tampil duluan, pindahkan `->renderHook(...)` ke atas plugin atau gunakan scoping — tidak wajib untuk M4.

- [ ] **Step 4: Run test — pastikan pass**

Run: `vendor\bin\pest tests\Feature\Context\ContextFilamentTest.php`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add resources/views/panel/unit-switcher.blade.php app/Providers/Filament/AdminPanelProvider.php tests/Feature/Context/ContextFilamentTest.php
git commit -m "feat: add Filament unit switcher in user menu (TODO 5.3)"
```

---

### Task 8: Non-Filament usage (Services/Actions/Policies/Jobs/Commands) + docs

**Files:**
- Create: `tests/Unit/Context/ContextNonFilamentTest.php`
- Create: `docs/superpowers/specs/2026-08-16-organizational-context-design.md` (append section "Non-Filament Usage Examples" — opsional, lihat langkah)
- (Opsional) Create: `core/Context/README.md` — dokumentasi cara pakai

**Interfaces:**
- Consumes: Task 1 (kontrak), Task 3 (manager), Task 4 (binding).
- Produces: Dokumentasi + test yang membuktikan context bisa dipakai dari Service/Action/Policy/Job/Command tanpa session (CLI/queue → `null`), dan cara `set()` eksplisit di Job/Command. Tidak ada file produksi baru — hanya test + doc.

- [ ] **Step 1: Tulis failing test**

Buat `tests/Unit/Context/ContextNonFilamentTest.php`:

```php
<?php

namespace Tests\Unit\Context;

use App\Models\User;
use Core\Contracts\OrganizationContext;
use Core\Contracts\OrganizationalUnitContext;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ContextNonFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_context_null_in_queue_without_auth(): void
    {
        // Simulasi queue/CLI: tidak ada Auth::user(), tidak ada session.
        $this->assertNull(app(OrganizationContext::class)->organization());
        $this->assertNull(app(OrganizationalUnitContext::class)->current());
    }

    public function test_service_can_use_current_unit(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->units()->attach($unit->id);
        Auth::login($user);

        // Contoh pola Service/Action: baca current unit.
        $currentUnitId = app(OrganizationalUnitContext::class)->currentId();

        $this->assertSame($unit->id, $currentUnitId);
    }

    public function test_job_sets_context_explicitly(): void
    {
        $unit = OrganizationalUnit::factory()->create();

        // Contoh pola Job: set eksplisit tanpa session.
        app(OrganizationalUnitContext::class)->set($unit);

        $this->assertSame($unit->id, app(OrganizationalUnitContext::class)->currentId());
    }

    public function test_policy_can_check_has_and_current_id(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $context = app(OrganizationalUnitContext::class);

        // Contoh pola Policy: cek has() + currentId().
        $this->assertIsBool($context->has());
        $this->assertIsString($context->currentId());
    }
}
```

- [ ] **Step 2: Run test — pastikan gagal**

Run: `vendor\bin\pest tests\Unit\Context\ContextNonFilamentTest.php`
Expected: FAIL — karena `OrganizationContext`/`OrganizationalUnitContext` belum dibind ke manager (Task 4 belum dilakukan) → `Target ... is not instantiable`.

> Catatan: Test ini sebenarnya bisa pass setelah Task 4 (binding aktif). Karena plan dieksekusi berurutan, Task 8 sudah punya binding — maka test ini **pass dari awal**. Ini test dokumentasi perilaku, bukan red-green ketat; jalankan untuk memverifikasi perilaku yang didokumentasikan.

- [ ] **Step 3: Tulis dokumentasi**

Tambahkan section berikut ke akhir `docs/superpowers/specs/2026-08-16-organizational-context-design.md` (atau buat file baru `docs/superpowers/plans/2026-08-16-organizational-context-design.md` jika ingin doc terpisah — pilih salah satu, jangan duplikasi):

```markdown
## Non-Filament Usage (Praktik)

- **Services/Actions**: `app(OrganizationalUnitContext::class)->current()` — tersedia jika session ada; di CLI/queue, `null`.
- **Policies**: cek `$context->has()` + `$context->currentId()` sebelum otorisasi scope.
- **Jobs**: set context eksplisit di `handle()` via `app(OrganizationalUnitContext::class)->set($unit)` atau terima `unit_id` di constructor; jangan andalkan session.
- **Console Commands**: opsi `--unit=` untuk men-set context, atau `set()` manual.
- **Model scoping** (Data Scope, §6) adalah milestone terpisah — jangan implementasikan di M4.
```

- [ ] **Step 4: Run test — pastikan pass**

Run: `vendor\bin\pest tests\Unit\Context\ContextNonFilamentTest.php`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add tests/Unit/Context/ContextNonFilamentTest.php docs/superpowers/specs/2026-08-16-organizational-context-design.md
git commit -m "docs: document non-Filament context usage and add tests (TODO 5.4)"
```

---

### Task 9: Verifikasi akhir — `composer check` + manual smoke test

**Files:**
- (Verifikasi saja, tidak ada file baru)

**Interfaces:**
- Consumes: seluruh task di atas.

- [ ] **Step 1: Jalankan seluruh test suite**

Run: `vendor\bin\pest`
Expected: PASS — seluruh suite (Unit + Feature + Arch). Perhatikan: `tests/Arch/CoreArchTest.php` memastikan `Core` tidak mengimpor `App`/`Filament` — implementasi Task 2–5 harus lolos.

- [ ] **Step 2: Jalankan Pint**

Run: `vendor\bin\pint --dirty`
Expected: tidak ada perubahan (atau auto-fix formatting).

- [ ] **Step 3: Jalankan PHPStan**

Run: `vendor\bin\phpstan analyse --memory-limit=2G`
Expected: 0 errors.

- [ ] **Step 4: Smoke test manual (opsional, bila env lokal jalan)**

1. `php artisan serve` + login ke `/admin`.
2. Assign user ke 2+ unit (via UI admin atau `php artisan tinker`).
3. Buka dashboard → user menu → dropdown unit muncul dengan checkmark pada unit aktif.
4. Klik unit lain → redirect back + notifikasi "Unit berhasil diganti".
5. Reload → unit aktif tetap (session).
6. Buka tinker: `app(\Core\Contracts\OrganizationalUnitContext::class)->currentId()` → unit dari session.

- [ ] **Step 5: Commit (bila ada perubahan dari Pint/PHPStan)**

```bash
git add -A
git commit -m "chore: apply formatting/static analysis fixes from M4 verification"
```

> Jika tidak ada perubahan, lewati step ini.

---

## Self-Review

**Spec coverage:**
- §3.2 Kontrak → Task 1 (interface + method exact).
- §3.1 Struktur file (`core/Config/core.php`, `Contracts/`, `Context/`, `Actions/`, `ContextServiceProvider`) → Task 1–5.
- §3.3 Alur data (session hanya unit_id, org di-derive) → Task 2–3 (resolver + manager).
- §4.1/4.2 Default & current resolution (primary → first unit → pivot → null; stale session clear) → Task 2 (tests cover semua cabang).
- §4.3 Lifecycle (session source of truth, per-request re-resolve) → Task 2–3.
- §5.1 `SwitchUnitAction` (primitif, validasi, throw `invalidAssignment`) → Task 5.
- §5.2 Unit switcher Filament (USER_MENU_BEFORE, dropdown, checkmark, POST, >1 unit) → Task 7.
- §5.3 Route & controller (POST `/context/switch-unit`, CSRF, redirect back + notifikasi) → Task 6.
- §5.4 Persistence (session key config) → Task 2, 4 (config block).
- §6 Non-Filament usage → Task 8 (test + doc).
- §7 Konfigurasi → Task 4.
- §8 Error handling → Task 2 (stale clear), 5 (invalidAssignment), 6 (controller catch).
- §9 Testing (5 file test) → Task 1–8 (ContextContractTest, ContextResolverTest, ContextManagerTest, SwitchUnitTest, ContextFilamentTest, ContextNonFilamentTest, SwitchUnitActionTest).

**Placeholder scan:** Tidak ada TBD/TODO placeholder di langkah implementasi; setiap langkah punya kode lengkap. Satu catatan test (`test_switch_unit_requires_authentication` redirect path) diberi instruksi verifikasi eksplisit.

**Type consistency:**
- `organization(): ?Organization` / `organizationId(): ?string` / `set(Organization)` / `clear()` / `has(): bool` — konsisten di Task 1, 3.
- `current(): ?OrganizationalUnit` / `currentId(): ?string` / `set(OrganizationalUnit)` / `clear()` / `has(): bool` — konsisten di Task 1, 3.
- `ContextResolver::resolveCurrentUnit(?User)` / `resolveOrganization(?User)` / `sessionKey()` — dipakai Task 2, 3, 6.
- `SwitchUnitAction::handle(string $userId, string $unitId)` — dipakai Task 5, 6.
- Session key selalu `config('core.context.session_key', 'context.unit_id')` — konsisten di Task 2, 3, 4, 6, 7.
- Nama route `context.switch-unit` — konsisten di Task 6, 7.

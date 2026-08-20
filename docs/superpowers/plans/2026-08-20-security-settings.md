# Security Settings Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a System-tier Security Settings page that stores 2FA and password policies via the existing Settings registry, following the Application settings precedent (storage + UI only; runtime application deferred).

**Architecture:** Add 3 `security`-group keys to `core/Config/core.php` registry definitions (System scope only), then create a Filament Page `SecuritySettings.php` + blade view mirroring `ApplicationSettings` (mount via `keysInGroup`, save via `SettingsRepository::set(..., SettingScope::System)`, permissions `view:settings`/`update:settings`). The panel auto-discovers pages via `discoverPages` — no registration needed. No repository/registry core changes; `bool`/`int` casts already supported.

**Tech Stack:** PHP 8.3, Laravel 13, Filament 5, PHPUnit class-style tests, Laravel Pint, PHPStan.

## Global Constraints

- Follow `docs/conventions/settings.md` — key registered in `core/Config/core.php['settings']['definitions']`; field names use underscores (`str_replace('.', '_', $key)`).
- Permission format `action:subject` — `view:settings` / `update:settings` (no new permissions).
- Scope: `SettingScope::System` only for all security keys (instance-wide policy).
- `docs/TODO.md` §9.2 style: checkbox items with `[x]` + `(**file paths**)` and deferral notes.
- Environment docs format: `docs/conventions/environment.md` table with columns `Variabel | Tipe | Default | Deskripsi | Sumber`.
- Test conventions: PHPUnit class-style, `RefreshDatabase`, namespace `Tests\Feature\Settings`.
- Quality gate: `composer check` (pint `--dirty`, `php artisan test`, `phpstan analyse --memory-limit=2G`).
- No runtime application — middleware `ForceSuperAdminTwoFactor` and password rules keep using `config('core.auth.*')`.
- Commit messages: conventional commits; end with `Co-authored-by: CommandCodeBot <noreply@commandcode.ai>`.

---

### Task 1: Register security group keys

**Files:**
- Modify: `core/Config/core.php:23-45` (inside `'settings' => ['definitions' => [...]]`)
- Modify: `tests/Unit/Settings/SettingsRegistryTest.php` (add security keys to `registry()` helper, add group test)

**Interfaces:**
- Produces: registry keys `security.two_factor_required` (bool, default `(bool) env('AUTH_2FA_FORCE', false)`), `security.password_min_length` (int, default 8), `security.password_require_complexity` (bool, default true); all `scopes => [SettingScope::System]`, `group => 'security'`. Consumed by Task 2 (page) and Task 3 (tests).

> Note on test strategy: `SettingsRegistryTest` builds its registry from a self-contained
> helper, so it cannot exercise the config wiring. The end-to-end verification that the
> config definitions actually load is the feature test in Task 3 (page `mount()` iterates
> `keysInGroup('security')` from the real config-loaded registry, `save()` persists). Task 1
> guards the registry grouping mechanics and verifies config wiring via tinker.

- [ ] **Step 1: Update the registry unit test**

In `tests/Unit/Settings/SettingsRegistryTest.php`, add the security keys to the `registry()` helper:

```php
$registry->register([
    // ... existing app.* keys ...
    'security.two_factor_required' => [
        'type' => 'bool',
        'default' => false,
        'scopes' => [SettingScope::System],
        'group' => 'security',
    ],
    'security.password_min_length' => [
        'type' => 'int',
        'default' => 8,
        'scopes' => [SettingScope::System],
        'group' => 'security',
    ],
    'security.password_require_complexity' => [
        'type' => 'bool',
        'default' => true,
        'scopes' => [SettingScope::System],
        'group' => 'security',
    ],
]);
```

Replace the existing `test_keys_in_group_returns_empty_for_unknown_group` body so it targets a group that will never exist:

```php
public function test_keys_in_group_returns_empty_for_unknown_group(): void
{
    $this->assertSame([], $this->registry()->keysInGroup('mail'));
}
```

Add a new test for the security group:

```php
public function test_keys_in_group_security(): void
{
    $this->assertSame(
        ['security.two_factor_required', 'security.password_min_length', 'security.password_require_complexity'],
        $this->registry()->keysInGroup('security')
    );
}
```

- [ ] **Step 2: Run test to verify it passes**

Run: `./vendor/bin/phpunit tests/Unit/Settings/SettingsRegistryTest.php`
Expected: PASS — the helper is self-contained, so this confirms the registry grouping behaves correctly (this is a regression guard; the config wiring itself is verified in Step 4).

- [ ] **Step 3: Add the security definitions to config**

In `core/Config/core.php`, inside `'settings' => ['definitions' => [...]]`, after the `app.timezone` entry:

```php
'security.two_factor_required' => [
    'type' => 'bool',
    'default' => (bool) env('AUTH_2FA_FORCE', false),
    'scopes' => [SettingScope::System],
    'group' => 'security',
],
'security.password_min_length' => [
    'type' => 'int',
    'default' => 8,
    'scopes' => [SettingScope::System],
    'group' => 'security',
],
'security.password_require_complexity' => [
    'type' => 'bool',
    'default' => true,
    'scopes' => [SettingScope::System],
    'group' => 'security',
],
```

- [ ] **Step 4: Verify config wiring loads the keys**

Run: `php artisan tinker --execute="dump(app(\Core\Settings\SettingsRegistry::class)->keysInGroup('security'))"`
Expected: prints `array:3` with the three `security.*` keys — proving `SettingsServiceProvider::boot()` registered the new definitions from config into the live registry.

- [ ] **Step 5: Commit**

```bash
git add core/Config/core.php tests/Unit/Settings/SettingsRegistryTest.php
git commit -m "feat: register security settings keys in registry (TODO 9.2)" -m "Co-authored-by: CommandCodeBot <noreply@commandcode.ai>"
```

---

### Task 2: Create SecuritySettings Filament page

**Files:**
- Create: `app/Filament/Pages/Settings/SecuritySettings.php`
- Create: `resources/views/filament/pages/settings/security-settings.blade.php`

**Interfaces:**
- Consumes: `Core\Contracts\SettingsRepository`, `Core\Settings\Enums\SettingScope`, `Core\Settings\SettingsRegistry` (Task 1), `Filament\Forms\Components\Toggle`, `Filament\Forms\Components\TextInput`, `Filament\Notifications\Notification`, `Filament\Pages\Page`, `Filament\Schemas\Schema`, `Illuminate\Support\Facades\Auth`.
- Produces: page class `App\Filament\Pages\Settings\SecuritySettings` with `canAccess()` (view:settings), `mount()`, `form()`, `save()` (update:settings). Auto-discovered by panel — no provider changes. Consumed by Task 3 (tests) and Task 4 (docs).

- [ ] **Step 1: Create the page class**

Create `app/Filament/Pages/Settings/SecuritySettings.php`:

```php
<?php

namespace App\Filament\Pages\Settings;

use Core\Contracts\SettingsRepository;
use Core\Settings\Enums\SettingScope;
use Core\Settings\SettingsRegistry;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

/**
 * @property-read Schema $form
 */
class SecuritySettings extends Page
{
    protected string $view = 'filament.pages.settings.security-settings';

    protected static ?int $navigationSort = 52;

    protected static ?string $title = 'Security Settings';

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

        foreach ($registry->keysInGroup('security') as $key) {
            $field = str_replace('.', '_', $key);
            $this->data[$field] = $repository->getForScope($key, SettingScope::System)
                ?? $registry->definition($key)['default'];
        }

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('security_two_factor_required')
                    ->label('Wajibkan 2FA')
                    ->helperText('Super admin selalu wajib 2FA.'),
                TextInput::make('security_password_min_length')
                    ->label('Panjang Minimum Password')
                    ->numeric()
                    ->minValue(6)
                    ->maxValue(128),
                Toggle::make('security_password_require_complexity')
                    ->label('Wajib Password Kompleks')
                    ->helperText('Membutuhkan huruf besar/kecil, angka, dan simbol.'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        abort_unless(Auth::user()?->can('update:settings'), 403);

        $repository = app(SettingsRepository::class);
        $registry = app(SettingsRegistry::class);
        $state = $this->form->getState();

        foreach ($registry->keysInGroup('security') as $key) {
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

- [ ] **Step 2: Create the blade view**

Create `resources/views/filament/pages/settings/security-settings.blade.php`:

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

- [ ] **Step 3: Add a smoke render test**

Create `tests/Feature/Settings/SecuritySettingsPageSmokeTest.php`:

```php
<?php

namespace Tests\Feature\Settings;

use App\Filament\Pages\Settings\SecuritySettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecuritySettingsPageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_with_view_permission(): void
    {
        $role = Role::create(['name' => 'security-smoke-tester-'.uniqid()]);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'view:settings']));
        $user = User::factory()->create()->assignRole([$role, Role::firstOrCreate(['name' => 'panel_user'])]);

        $this->actingAs($user)
            ->get(SecuritySettings::getUrl())
            ->assertSuccessful();
    }
}
```

Run: `./vendor/bin/phpunit tests/Feature/Settings/SecuritySettingsPageSmokeTest.php`
Expected: PASS — proves the page class, blade view, and registry wiring (`mount()` iterates `keysInGroup('security')`) all resolve together. This smoke test is superseded by the fuller suite in Task 3; delete this file in Task 3 Step 2 once the real tests pass.

- [ ] **Step 4: Run formatting**

Run: `./vendor/bin/pint --dirty`
Expected: no style violations (or auto-fixed).

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Pages/Settings/SecuritySettings.php resources/views/filament/pages/settings/security-settings.blade.php tests/Feature/Settings/SecuritySettingsPageSmokeTest.php
git commit -m "feat: add Security Settings page (TODO 9.2)" -m "Co-authored-by: CommandCodeBot <noreply@commandcode.ai>"
```

---

### Task 3: Add SecuritySettings page tests

**Files:**
- Test: `tests/Feature/Settings/SecuritySettingsPageTest.php`

**Interfaces:**
- Consumes: page `SecuritySettings` (Task 2), `Core\Contracts\SettingsRepository`, `SettingScope::System` (Task 1), permissions `view:settings`/`update:settings`.
- Produces: passing feature tests proving access control and persistence with correct bool/int casting.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Settings/SecuritySettingsPageTest.php`:

```php
<?php

namespace Tests\Feature\Settings;

use App\Filament\Pages\Settings\SecuritySettings;
use App\Models\User;
use Core\Contracts\SettingsRepository;
use Core\Settings\Enums\SettingScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecuritySettingsPageTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPermissions(array $permissions): User
    {
        $role = Role::create(['name' => 'security-settings-tester-'.uniqid()]);

        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission]));
        }

        $panelRole = Role::firstOrCreate(['name' => 'panel_user']);

        return User::factory()->create()->assignRole([$role, $panelRole]);
    }

    public function test_page_accessible_with_view_permission(): void
    {
        $user = $this->userWithPermissions(['view:settings']);

        $this->actingAs($user)
            ->get(SecuritySettings::getUrl())
            ->assertSuccessful();
    }

    public function test_page_forbidden_without_view_permission(): void
    {
        $user = $this->userWithPermissions([]);

        $this->actingAs($user)
            ->get(SecuritySettings::getUrl())
            ->assertForbidden();
    }

    public function test_save_persists_values_via_settings_repository(): void
    {
        $user = $this->userWithPermissions(['view:settings', 'update:settings']);
        $this->actingAs($user);

        Livewire::test(SecuritySettings::class)
            ->fillForm([
                'security_two_factor_required' => true,
                'security_password_min_length' => 10,
                'security_password_require_complexity' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $repository = app(SettingsRepository::class);

        $this->assertSame(true, $repository->getForScope('security.two_factor_required', SettingScope::System));
        $this->assertSame(10, $repository->getForScope('security.password_min_length', SettingScope::System));
        $this->assertSame(false, $repository->getForScope('security.password_require_complexity', SettingScope::System));
    }

    public function test_save_forbidden_without_update_permission(): void
    {
        $user = $this->userWithPermissions(['view:settings']);
        $this->actingAs($user);

        Livewire::test(SecuritySettings::class)
            ->fillForm([
                'security_two_factor_required' => true,
                'security_password_min_length' => 10,
                'security_password_require_complexity' => false,
            ])
            ->call('save')
            ->assertForbidden();

        $repository = app(SettingsRepository::class);
        $this->assertNull($repository->getForScope('security.two_factor_required', SettingScope::System));
    }
}
```

- [ ] **Step 2: Run the tests to see them fail**

Run: `./vendor/bin/phpunit tests/Feature/Settings/SecuritySettingsPageTest.php`
Expected: the `save` tests FAIL (page's `save()` persists nothing yet — Task 2 page was created before these tests, so access-control tests may already pass). The point is to confirm the persistence assertions actually exercise the repository.

Note: the page from Task 2 already implements `save()`, so if all tests pass immediately that is also acceptable — the persistence assertions in `test_save_persists_values_via_settings_repository` are the real gate.

- [ ] **Step 3: Run the full security settings test suite**

Run: `./vendor/bin/phpunit tests/Feature/Settings/SecuritySettingsPageTest.php tests/Feature/Settings/SecuritySettingsPageSmokeTest.php`
Expected: all pass (4 page tests + 1 smoke test).

Delete the smoke test file now that the full suite covers it:

```bash
rm tests/Feature/Settings/SecuritySettingsPageSmokeTest.php
```

- [ ] **Step 4: Run the full settings test suite**

Run: `./vendor/bin/phpunit tests/Feature/Settings tests/Unit/Settings`
Expected: all pass, including existing ApplicationSettingsPageTest and registry tests.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Settings/SecuritySettingsPageTest.php
git rm tests/Feature/Settings/SecuritySettingsPageSmokeTest.php
git commit -m "test: add SecuritySettings page tests (TODO 9.2)" -m "Co-authored-by: CommandCodeBot <noreply@commandcode.ai>"
```

---

### Task 4: Update documentation and run quality gate

**Files:**
- Modify: `docs/TODO.md:308` (§9.2 Security settings line)
- Modify: `docs/conventions/environment.md:203` (AUTH_2FA_FORCE row)
- Modify: `docs/conventions/settings.md` (optional: add security group example in "Menambah Field Baru")

**Interfaces:**
- Consumes: nothing new. Produces: updated TODO/docs reflecting Security settings as done with deferred runtime application.

- [ ] **Step 1: Update TODO.md**

In `docs/TODO.md` §9.2, replace line 308:

```markdown
- [ ] Security settings
```

with:

```markdown
- [x] Security settings — storage + UI (`app/Filament/Pages/Settings/SecuritySettings.php`), keys `security.two_factor_required`/`security.password_min_length`/`security.password_require_complexity`; runtime application (2FA force middleware, password rules) is deferred to a follow-up task, not part of this plan
```

- [ ] **Step 2: Update environment.md**

In `docs/conventions/environment.md` §Authentication, update the `AUTH_2FA_FORCE` row (line 203):

```markdown
| `AUTH_2FA_FORCE` | optional | false | Wajibkan 2FA untuk semua user; default registry `security.two_factor_required` | config/core.php |
```

- [ ] **Step 3: Update settings.md (optional)**

In `docs/conventions/settings.md`, in the "Menambah Field Baru" section, add after the `app.timezone` example:

```php
// Grup security (System tier) — lihat spec 2026-08-20-security-settings-design.md
'security.two_factor_required' => [
    'type' => 'bool',
    'default' => (bool) env('AUTH_2FA_FORCE', false),
    'scopes' => [SettingScope::System],
    'group' => 'security',
],
```

- [ ] **Step 4: Run the full quality gate**

Run: `composer check`
Expected: Pint clean, all tests pass (including new SecuritySettingsPageTest and registry tests), PHPStan clean.

- [ ] **Step 5: Commit**

```bash
git add docs/TODO.md docs/conventions/environment.md docs/conventions/settings.md
git commit -m "docs: mark Security settings done, note deferred runtime (TODO 9.2)" -m "Co-authored-by: CommandCodeBot <noreply@commandcode.ai>"
```

---

## Self-Review

**Spec coverage:**
- §3.1 Registry (3 keys, group security, System scope) → Task 1 ✓ (config + unit regression guard + tinker wiring check)
- §3.2 Page SecuritySettings.php (mount/form/save, nav sort 52, permissions) → Task 2 ✓
- §3.3 Blade view → Task 2 ✓ (smoke render test)
- §5 Testing (4 tests incl. bool/int cast assertions) → Task 3 ✓
- §6 Dokumentasi (TODO.md, environment.md, settings.md) → Task 4 ✓
- §7 Out of Scope (no runtime application, no repo changes) → respected: no middleware/config wiring touched ✓

**Placeholder scan:** No TBD/TODO/placeholder patterns; every step has concrete code and commands. Task 3 Step 2 honestly documents the TDD nuance (page already exists from Task 2). No dangling references — the smoke test created in Task 2 is explicitly removed in Task 3.

**Type consistency:**
- Key names consistent across all tasks: `security.two_factor_required`, `security.password_min_length`, `security.password_require_complexity`.
- Field names (underscore form) consistent: `security_two_factor_required`, `security_password_min_length`, `security_password_require_complexity` — used in both page form and tests.
- Permission strings `view:settings`/`update:settings` consistent.
- `SettingScope::System` used everywhere.

# Authentication & Security (M5) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement TODO §7 Authentication & Security — login/logout, password reset, email verification, 2FA (TOTP + recovery codes), passkeys (WebAuthn), browser session management, and security events — using the already-installed Filament Breezy v3.2 as the account/security UX foundation, integrated with the Core security_events table.

**Architecture:** Breezy 3.2 (already installed) provides 2FA, passkeys, browser sessions, and password rules via `AdminPanelProvider` plugin configuration. Email verification comes from Filament `emailVerification()` + `MustVerifyEmail`. Security events live in a new `Core\Security` domain (enum + model + event + recorder); application listeners + extended Breezy Livewire components dispatch `SecurityEventOccurred` — no vendor modification. Auth policy (2FA force, passkey RP, password rules) lives in `core/Config/core.php` with env overrides, documented as the future migration path to the M7 Settings System.

**Tech Stack:** Laravel 13, Filament 5, Jeffgreco13/filament-breezy v3.2, Filament Shield v4.2, Spatie Permission, Pest/PHPUnit, UUIDv7 (`UsesUuid`).

## Global Constraints

- **Architecture (ADR-005):** `Core\` must NOT use `App\` or `Modules\`; Core non-UI must NOT use Filament. Verified by `tests/Arch/CoreArchTest.php`.
- **No vendor modification (PRD §55):** All Breezy hooks go through official extension points — `myProfileComponents([...])` overrides that `extends` Breezy classes and call parent, or Laravel/Filament event listeners. Never edit `vendor/`.
- **No custom cryptography (PRD §24, §54):** TOTP = pragmarx/google2fa; passkeys = web-auth/webauthn-lib (both Breezy deps). Never implement crypto ourselves.
- **No external identity provider (PRD §6, §21):** Everything must work offline/LAN.
- **Permissions format:** `action:subject`, separator `:`, snake case (Shield v4.3.1 default) — e.g. `view:users`.
- **Bypass role:** Role named exactly `super_admin` (Spatie). Super admin bypasses *resource* gates via Shield intercept `before`, but **never bypasses 2FA** (user decision).
- **2FA policy:** Optional by default, force via `AUTH_2FA_FORCE` env; **super admin always forced** regardless of env.
- **Email verification:** Required (`MustVerifyEmail`); user must verify before entering the panel.
- **Session driver:** Must stay `database` (Breezy browser sessions requirement). It already is.
- **Naming:** PSR-4, `final class` for helpers/services, backed enum with singular name, snake_case columns.
- **Test conventions:** PHPUnit class-style (`class XxxTest extends TestCase`), `use RefreshDatabase;`, namespaces `Tests\Unit\Security` / `Tests\Feature\Auth`.
- **Quality gate:** `composer check` = Pint → Pest → PHPStan. Run after each task.
- **Env vars:** New vars `AUTH_2FA_FORCE`, `AUTH_PASSKEY_RP_ID` must be added to `.env.example` and documented in `docs/conventions/environment.md` (project policy).

---

### Task 1: Security events Core domain

**Files:**
- Create: `core/Security/Enums/SecurityEventType.php`
- Create: `core/Security/Models/SecurityEvent.php`
- Create: `core/Security/Events/SecurityEventOccurred.php`
- Create: `core/Security/Services/SecurityEventRecorder.php`
- Test: `tests/Unit/Security/SecurityEventTypeTest.php`
- Test: `tests/Unit/Security/SecurityEventRecorderTest.php`

**Interfaces:**
- Produces:
  - `Core\Security\Enums\SecurityEventType` — backed string enum, cases with `->value` = `login_succeeded`, `login_failed`, `password_changed`, `two_factor_enabled`, `two_factor_disabled`, `passkey_registered`, `passkey_revoked`, `session_revoked`.
  - `Core\Security\Models\SecurityEvent` — Eloquent model for `security_events` table; UUID PK; casts `metadata`→array, `occurred_at`→datetime.
  - `Core\Security\Events\SecurityEventOccurred` — `__construct(SecurityEventType $type, ?string $userId = null, array $metadata = [], ?\DateTimeInterface $occurredAt = null)`; readonly public props.
  - `Core\Security\Services\SecurityEventRecorder` — `record(SecurityEventType $type, ?string $userId = null, array $metadata = [], ?\DateTimeInterface $occurredAt = null): void`. `final class`, no constructor.

- [ ] **Step 1: Write the failing tests**

`tests/Unit/Security/SecurityEventTypeTest.php`:

```php
<?php

namespace Tests\Unit\Security;

use Core\Security\Enums\SecurityEventType;
use PHPUnit\Framework\TestCase;

class SecurityEventTypeTest extends TestCase
{
    public function test_enum_has_expected_cases_and_values(): void
    {
        $this->assertSame('login_succeeded', SecurityEventType::LoginSucceeded->value);
        $this->assertSame('login_failed', SecurityEventType::LoginFailed->value);
        $this->assertSame('password_changed', SecurityEventType::PasswordChanged->value);
        $this->assertSame('two_factor_enabled', SecurityEventType::TwoFactorEnabled->value);
        $this->assertSame('two_factor_disabled', SecurityEventType::TwoFactorDisabled->value);
        $this->assertSame('passkey_registered', SecurityEventType::PasskeyRegistered->value);
        $this->assertSame('passkey_revoked', SecurityEventType::PasskeyRevoked->value);
        $this->assertSame('session_revoked', SecurityEventType::SessionRevoked->value);
    }
}
```

`tests/Unit/Security/SecurityEventRecorderTest.php`:

```php
<?php

namespace Tests\Unit\Security;

use Core\Security\Enums\SecurityEventType;
use Core\Security\Models\SecurityEvent;
use Core\Security\Services\SecurityEventRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityEventRecorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_creates_security_event(): void
    {
        $recorder = new SecurityEventRecorder();

        $recorder->record(SecurityEventType::PasswordChanged, 'user-123', ['ip_address' => '127.0.0.1']);

        $this->assertDatabaseHas('security_events', [
            'event' => 'password_changed',
            'user_id' => 'user-123',
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_record_allows_null_user_for_anonymous_events(): void
    {
        $recorder = new SecurityEventRecorder();

        $recorder->record(SecurityEventType::LoginFailed, null, []);

        $this->assertDatabaseHas('security_events', [
            'event' => 'login_failed',
            'user_id' => null,
        ]);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Unit/Security/SecurityEventTypeTest.php tests/Unit/Security/SecurityEventRecorderTest.php`
Expected: FAIL — `Class "Core\Security\Enums\SecurityEventType" not found`

- [ ] **Step 3: Write the implementation**

`core/Security/Enums/SecurityEventType.php`:

```php
<?php

namespace Core\Security\Enums;

enum SecurityEventType: string
{
    case LoginSucceeded = 'login_succeeded';
    case LoginFailed = 'login_failed';
    case PasswordChanged = 'password_changed';
    case TwoFactorEnabled = 'two_factor_enabled';
    case TwoFactorDisabled = 'two_factor_disabled';
    case PasskeyRegistered = 'passkey_registered';
    case PasskeyRevoked = 'passkey_revoked';
    case SessionRevoked = 'session_revoked';
}
```

`core/Security/Models/SecurityEvent.php`:

```php
<?php

namespace Core\Security\Models;

use Core\Support\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    use UsesUuid;

    protected $table = 'security_events';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];
}
```

> `Model::unguard()` is global (AppServiceProvider) — no `$guarded` needed. `UsesUuid` generates UUIDv7 ids (ADR-011).

`core/Security/Events/SecurityEventOccurred.php`:

```php
<?php

namespace Core\Security\Events;

use Core\Security\Enums\SecurityEventType;

class SecurityEventOccurred
{
    public function __construct(
        public readonly SecurityEventType $type,
        public readonly ?string $userId = null,
        public readonly array $metadata = [],
        public readonly ?\DateTimeInterface $occurredAt = null,
    ) {}
}
```

`core/Security/Services/SecurityEventRecorder.php`:

```php
<?php

namespace Core\Security\Services;

use Core\Security\Enums\SecurityEventType;
use Core\Security\Models\SecurityEvent;

final class SecurityEventRecorder
{
    public function record(
        SecurityEventType $type,
        ?string $userId = null,
        array $metadata = [],
        ?\DateTimeInterface $occurredAt = null,
    ): void {
        SecurityEvent::query()->create([
            'event' => $type->value,
            'user_id' => $userId,
            'ip_address' => $metadata['ip_address'] ?? request()->ip(),
            'user_agent' => $metadata['user_agent'] ?? request()->userAgent(),
            'metadata' => $metadata,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Unit/Security/SecurityEventTypeTest.php tests/Unit/Security/SecurityEventRecorderTest.php`
Expected: PASS (5 passed)

- [ ] **Step 5: Commit**

```bash
git add core/Security tests/Unit/Security
git commit -m "feat: add security events core domain (TODO 7.5)"
```

---

### Task 2: Auth policy config + env vars

**Files:**
- Modify: `core/Config/core.php`
- Modify: `.env.example`
- Modify: `docs/conventions/environment.md`

**Interfaces:**
- Produces:
  - `config('core.auth.two_factor.enabled')` → bool (true)
  - `config('core.auth.two_factor.force')` → bool, from `env('AUTH_2FA_FORCE', false)`
  - `config('core.auth.two_factor.super_admin_forced')` → bool (true)
  - `config('core.auth.passkey.enabled')` → bool (true)
  - `config('core.auth.passkey.relying_party_id')` → ?string, from `env('AUTH_PASSKEY_RP_ID')`
  - `config('core.auth.passkey.relying_party_name')` → string, from `env('APP_NAME', 'Mitra White Label')`
  - `config('core.auth.password.rules')` → array of strings `['min:8', 'mixedCase', 'numbers', 'symbols', 'uncompromised(3)']`

- [ ] **Step 1: Add the `auth` block to `core/Config/core.php`**

Append before the closing `];` of `core/Config/core.php`:

```php
    'auth' => [
        'two_factor' => [
            'enabled' => true,
            'force' => (bool) env('AUTH_2FA_FORCE', false),
            'super_admin_forced' => true,
        ],
        'passkey' => [
            'enabled' => true,
            'relying_party_id' => env('AUTH_PASSKEY_RP_ID'),
            'relying_party_name' => env('APP_NAME', 'Mitra White Label'),
        ],
        'password' => [
            'rules' => ['min:8', 'mixedCase', 'numbers', 'symbols', 'uncompromised(3)'],
        ],
    ],
```

- [ ] **Step 2: Add env vars to `.env.example`**

Append inside the Auth section (after the existing `AUTH_PASSWORD_TIMEOUT` line):

```dotenv
AUTH_2FA_FORCE=false
AUTH_PASSKEY_RP_ID=
```

- [ ] **Step 3: Document env vars in `docs/conventions/environment.md`**

In the **Auth** table, add two rows:

```markdown
| `AUTH_2FA_FORCE` | optional | false | Wajibkan 2FA untuk semua user | config/core.php |
| `AUTH_PASSKEY_RP_ID` | optional | — | Relying party ID passkey (default: request host) | config/core.php |
```

- [ ] **Step 4: Verify**

Run: `php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); var_dump(config('core.auth.two_factor.force'));"`
Expected: `bool(false)` (or whatever `.env` AUTH_2FA_FORCE is set to)

- [ ] **Step 5: Commit**

```bash
git add core/Config/core.php .env.example docs/conventions/environment.md
git commit -m "feat: add auth policy config with env overrides (TODO 7)"
```

---

### Task 3: Publish Breezy migrations

**Files:**
- Create: `database/migrations/<timestamp>_create_breezy_sessions_table.php`
- Create: `database/migrations/<timestamp>_alter_breezy_sessions_table.php`
- Create: `database/migrations/<timestamp>_create_passkeys_table.php`

**Interfaces:**
- Produces: tables `breezy_sessions` (2FA secrets/recovery codes, encrypted casts) and `passkeys` (WebAuthn credentials), published from Breezy package.

- [ ] **Step 1: Publish Breezy migrations**

Run: `php artisan vendor:publish --tag=filament-breezy-migrations`
Expected: 3 migration files created under `database/migrations/` with current timestamps.

- [ ] **Step 2: Verify migration files exist**

Run: `ls database/migrations`
Expected: `*_create_breezy_sessions_table.php`, `*_alter_breezy_sessions_table.php`, `*_create_passkeys_table.php` present.

- [ ] **Step 3: Run migrations**

Run: `php artisan migrate`
Expected: tables `breezy_sessions` and `passkeys` created (already-migrated tables skipped).

- [ ] **Step 4: Commit**

```bash
git add database/migrations
git commit -m "chore: publish breezy 2fa and passkey migrations (TODO 7.3, 7.4)"
```

---

### Task 4: User model — MustVerifyEmail + TwoFactorAuthenticatable

**Files:**
- Modify: `app/Models/User.php`

**Interfaces:**
- Consumes: `Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable`, `Illuminate\Contracts\Auth\MustVerifyEmail`.
- Produces: `User` implements `MustVerifyEmail`; has `securityEvents(): HasMany` relation to `Core\Security\Models\SecurityEvent` via `user_id`.

- [ ] **Step 1: Modify `app/Models/User.php`**

Current content:

```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Core\Organization\Models\Organization;
use Core\Organization\Models\OrganizationalUnit;
use Core\Support\Concerns\UsesUuid;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasPanelShield;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;
    use UsesUuid;

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class);
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(OrganizationalUnit::class);
    }

    public function primaryOrganizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'primary_organizational_unit_id');
    }
}
```

Apply these edits:

1. Uncomment/change the import line:
```php
use Illuminate\Contracts\Auth\MustVerifyEmail;
```
2. Add the Breezy trait import:
```php
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
```
3. Change the class declaration:
```php
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
```
4. Add the trait to the `use` list:
```php
    use TwoFactorAuthenticatable;
```
5. Add the relation method (after `primaryOrganizationalUnit()`):
```php
    public function securityEvents(): HasMany
    {
        return $this->hasMany(SecurityEvent::class, 'user_id');
    }
```
6. Add the two imports used by the relation:
```php
use Core\Security\Models\SecurityEvent;
use Illuminate\Database\Eloquent\Relations\HasMany;
```

- [ ] **Step 2: Verify model loads**

Run: `php artisan tinker --execute="dump(class_implements(App\Models\User::class));"`
Expected: array contains `Illuminate\Contracts\Auth\MustVerifyEmail`.

- [ ] **Step 3: Commit**

```bash
git add app/Models/User.php
git commit -m "feat: add email verification and 2FA to user model (TODO 7.1, 7.3)"
```

---

### Task 5: Configure AdminPanelProvider — email verification, password reset, Breezy 2FA/passkey/browser sessions

**Files:**
- Modify: `app/Providers/Filament/AdminPanelProvider.php`

**Interfaces:**
- Consumes: `config('core.auth.*')` from Task 2; Breezy 3.2 `BreezyCore` plugin.
- Produces: Panel with `->passwordReset()`, `->emailVerification()`, and Breezy with `enableTwoFactorAuthentication(force:)`, `enablePasskeys(...)`, `enableBrowserSessions()`.

- [ ] **Step 1: Modify `AdminPanelProvider`**

Current Breezy block:

```php
                BreezyCore::make()
                    ->myProfile(),
```

Replace with:

```php
                BreezyCore::make()
                    ->myProfile()
                    ->enableTwoFactorAuthentication(
                        force: config('core.auth.two_factor.force'),
                    )
                    ->enablePasskeys(
                        relyingPartyName: config('core.auth.passkey.relying_party_name'),
                        relyingPartyId: config('core.auth.passkey.relying_party_id') ?: null,
                    )
                    ->enableBrowserSessions(),
```

And after the `->login()` line, add:

```php
            ->passwordReset()
            ->emailVerification()
```

- [ ] **Step 2: Verify panel boots**

Run: `php artisan about`
Expected: no exceptions; admin panel registered.

- [ ] **Step 3: Run existing test suite to catch regressions**

Run: `php artisan test`
Expected: PASS (or pre-existing failures only — note which).

- [ ] **Step 4: Commit**

```bash
git add app/Providers/Filament/AdminPanelProvider.php
git commit -m "feat: enable 2fa passkey browser sessions email verification on panel (TODO 7.1-7.4)"
```

---

### Task 6: Super admin 2FA enforcement middleware

**Files:**
- Create: `app/Http/Middleware/ForceSuperAdminTwoFactor.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`

**Interfaces:**
- Consumes: `config('core.auth.two_factor.super_admin_forced')`; `Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable` on User; `BreezyCore::shouldForceTwoFactor()` and `BreezyCore::getTwoFactorRouteAction()`.
- Produces: middleware `ForceSuperAdminTwoFactor` — registered in `AdminPanelProvider` before Breezy's `MustTwoFactor`. Redirects a logged-in `super_admin` without confirmed 2FA to the Breezy 2FA setup page (My Profile) unless already there.

- [ ] **Step 1: Write the middleware**

`app/Http/Middleware/ForceSuperAdminTwoFactor.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Symfony\Component\HttpFoundation\Response;

class ForceSuperAdminTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if (
            config('core.auth.two_factor.super_admin_forced') &&
            $user &&
            $user->hasRole('super_admin') &&
            ! $user->hasConfirmedTwoFactor()
        ) {
            /** @var BreezyCore $breezy */
            $breezy = filament('filament-breezy');

            $myProfileRouteName = 'filament.'.Filament::getCurrentOrDefaultPanel()->getId().'.pages.'.$breezy->slug();

            if (! $request->routeIs($myProfileRouteName)) {
                return redirect()->route($myProfileRouteName);
            }
        }

        return $next($request);
    }
}
```

- [ ] **Step 2: Register middleware before Breezy's MustTwoFactor**

In `AdminPanelProvider::panel()`, change the `authMiddleware` block:

```php
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\ForceSuperAdminTwoFactor::class,
            ])
```

> Breezy's `MustTwoFactor` middleware is appended by `BreezyCore::register()` when 2FA is enabled — it runs after our `authMiddleware`. Our middleware runs first, so a super admin without confirmed 2FA is redirected to My Profile before the normal 2FA challenge check.

- [ ] **Step 3: Verify panel boots**

Run: `php artisan about`
Expected: no exceptions.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Middleware/ForceSuperAdminTwoFactor.php app/Providers/Filament/AdminPanelProvider.php
git commit -m "feat: enforce 2fa for super admin (TODO 7.3)"
```

---

### Task 7: Application security listeners + Livewire component overrides

**Files:**
- Create: `app/Listeners/Security/RecordSecurityEvent.php`
- Create: `app/Listeners/Security/RecordLoginSucceeded.php`
- Create: `app/Listeners/Security/RecordLoginFailed.php`
- Create: `app/Listeners/Security/RecordPasskeyUsed.php`
- Create: `app/Livewire/Security/UpdatePassword.php`
- Create: `app/Livewire/Security/TwoFactorAuthentication.php`
- Create: `app/Livewire/Security/Passkeys.php`
- Create: `app/Livewire/Security/BrowserSessions.php`
- Modify: `app/Providers/AppServiceProvider.php`

**Interfaces:**
- Consumes:
  - `Core\Security\Events\SecurityEventOccurred`, `Core\Security\Enums\SecurityEventType`, `Core\Security\Services\SecurityEventRecorder` (Task 1).
  - Breezy events `Jeffgreco13\FilamentBreezy\Events\LoginSuccess`, `Jeffgreco13\FilamentBreezy\Events\PasskeyUsedToAuthenticate`.
  - Breezy Livewire components `UpdatePassword`, `TwoFactorAuthentication`, `Passkeys`, `BrowserSessions` (all in `Jeffgreco13\FilamentBreezy\Livewire\`).
- Produces:
  - Listener `RecordSecurityEvent` for `SecurityEventOccurred` → calls `SecurityEventRecorder::record`.
  - Listeners `RecordLoginSucceeded`, `RecordLoginFailed` for `Illuminate\Auth\Events\Login`/`Failed` → dispatch `SecurityEventOccurred`.
  - Listener `RecordPasskeyUsed` for Breezy `PasskeyUsedToAuthenticate` → dispatch `SecurityEventOccurred` (LoginSucceeded, metadata `method=passkey`).
  - Livewire overrides (each `extends` the Breezy class, calls parent, then dispatches `SecurityEventOccurred`):
    - `App\Livewire\Security\UpdatePassword` — override `submit()`, dispatch `PasswordChanged`.
    - `App\Livewire\Security\TwoFactorAuthentication` — override `enableAction()`, `disableAction()`, `regenerateCodesAction()`, dispatch `TwoFactorEnabled` / `TwoFactorDisabled`.
    - `App\Livewire\Security\Passkeys` — override `storePasskey()` and the delete action, dispatch `PasskeyRegistered` / `PasskeyRevoked`.
    - `App\Livewire\Security\BrowserSessions` — override `logoutOtherBrowserSessions()`, dispatch `SessionRevoked`.

- [ ] **Step 1: Write the listeners**

`app/Listeners/Security/RecordSecurityEvent.php`:

```php
<?php

namespace App\Listeners\Security;

use Core\Security\Events\SecurityEventOccurred;
use Core\Security\Services\SecurityEventRecorder;

class RecordSecurityEvent
{
    public function __construct(private readonly SecurityEventRecorder $recorder) {}

    public function handle(SecurityEventOccurred $event): void
    {
        $this->recorder->record(
            $event->type,
            $event->userId,
            $event->metadata,
            $event->occurredAt,
        );
    }
}
```

`app/Listeners/Security/RecordLoginSucceeded.php`:

```php
<?php

namespace App\Listeners\Security;

use Core\Security\Enums\SecurityEventType;
use Core\Security\Events\SecurityEventOccurred;
use Illuminate\Auth\Events\Login;

class RecordLoginSucceeded
{
    public function handle(Login $event): void
    {
        SecurityEventOccurred::dispatch(
            SecurityEventType::LoginSucceeded,
            method_exists($event->user, 'getKey') ? $event->user->getKey() : null,
        );
    }
}
```

`app/Listeners/Security/RecordLoginFailed.php`:

```php
<?php

namespace App\Listeners\Security;

use Core\Security\Enums\SecurityEventType;
use Core\Security\Events\SecurityEventOccurred;
use Illuminate\Auth\Events\Failed;

class RecordLoginFailed
{
    public function handle(Failed $event): void
    {
        SecurityEventOccurred::dispatch(
            SecurityEventType::LoginFailed,
            $event->user?->getKey(),
        );
    }
}
```

`app/Listeners/Security/RecordPasskeyUsed.php`:

```php
<?php

namespace App\Listeners\Security;

use Core\Security\Enums\SecurityEventType;
use Core\Security\Events\SecurityEventOccurred;
use Jeffgreco13\FilamentBreezy\Events\PasskeyUsedToAuthenticate;

class RecordPasskeyUsed
{
    public function handle(PasskeyUsedToAuthenticate $event): void
    {
        SecurityEventOccurred::dispatch(
            SecurityEventType::LoginSucceeded,
            $event->passkey->authenticatable_id,
            ['method' => 'passkey', 'passkey_name' => $event->passkey->name],
        );
    }
}
```

- [ ] **Step 2: Write the Livewire overrides**

`app/Livewire/Security/UpdatePassword.php`:

```php
<?php

namespace App\Livewire\Security;

use Core\Security\Enums\SecurityEventType;
use Core\Security\Events\SecurityEventOccurred;
use Jeffgreco13\FilamentBreezy\Livewire\UpdatePassword as BreezyUpdatePassword;

class UpdatePassword extends BreezyUpdatePassword
{
    public function submit(): void
    {
        parent::submit();

        SecurityEventOccurred::dispatch(
            SecurityEventType::PasswordChanged,
            $this->user->getKey(),
        );
    }
}
```

`app/Livewire/Security/TwoFactorAuthentication.php`:

```php
<?php

namespace App\Livewire\Security;

use Core\Security\Enums\SecurityEventType;
use Core\Security\Events\SecurityEventOccurred;
use Jeffgreco13\FilamentBreezy\Livewire\TwoFactorAuthentication as BreezyTwoFactorAuthentication;

class TwoFactorAuthentication extends BreezyTwoFactorAuthentication
{
    public function enableAction(): \Filament\Actions\Action
    {
        $action = parent::enableAction();

        return $action->action(function () {
            $this->user->enableTwoFactorAuthentication();
            SecurityEventOccurred::dispatch(
                SecurityEventType::TwoFactorEnabled,
                $this->user->getKey(),
            );
        });
    }

    public function disableAction(): \Filament\Actions\Action
    {
        $action = parent::disableAction();

        return $action->action(function () {
            $this->user->disableTwoFactorAuthentication();
            SecurityEventOccurred::dispatch(
                SecurityEventType::TwoFactorDisabled,
                $this->user->getKey(),
            );
        });
    }
}
```

`app/Livewire/Security/Passkeys.php`:

```php
<?php

namespace App\Livewire\Security;

use Core\Security\Enums\SecurityEventType;
use Core\Security\Events\SecurityEventOccurred;
use Filament\Actions\DeleteAction;
use Jeffgreco13\FilamentBreezy\Livewire\Passkeys as BreezyPasskeys;

class Passkeys extends BreezyPasskeys
{
    protected function getTableActions(): array
    {
        return [
            parent::getTableActions()[0],   // edit action
            DeleteAction::make()
                ->iconButton()
                ->action(function ($record) {
                    SecurityEventOccurred::dispatch(
                        SecurityEventType::PasskeyRevoked,
                        $this->user->getKey(),
                        ['passkey_name' => $record->name],
                    );
                    $record->delete();
                }),
        ];
    }

    public function storePasskey(string $passkey): void
    {
        parent::storePasskey($passkey);

        SecurityEventOccurred::dispatch(
            SecurityEventType::PasskeyRegistered,
            $this->user->getKey(),
            ['passkey_name' => $this->name],
        );
    }
}
```

`app/Livewire/Security/BrowserSessions.php`:

```php
<?php

namespace App\Livewire\Security;

use Core\Security\Enums\SecurityEventType;
use Core\Security\Events\SecurityEventOccurred;
use Jeffgreco13\FilamentBreezy\Livewire\BrowserSessions as BreezyBrowserSessions;

class BrowserSessions extends BreezyBrowserSessions
{
    public static function logoutOtherBrowserSessions($password): void
    {
        parent::logoutOtherBrowserSessions($password);

        SecurityEventOccurred::dispatch(
            SecurityEventType::SessionRevoked,
            auth()->id(),
        );
    }
}
```

> Note: the Passkeys override replaces the delete action's closure to record the event before deleting. `parent::getTableActions()[0]` preserves the edit action. If Breezy's action layout changes, the `record` still fires from `storePasskey`.

- [ ] **Step 3: Register listeners and overrides**

In `app/Providers/AppServiceProvider.php`, add a `boot()` call (inside existing `boot()`):

```php
        $this->configureSecurityEvents();
```

And add the method:

```php
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
```

Add imports at the top of the file for the classes used.

- [ ] **Step 4: Register the Livewire overrides in the panel**

In `AdminPanelProvider`, change the Breezy block to register overrides:

```php
                BreezyCore::make()
                    ->myProfile()
                    ->myProfileComponents([
                        'update_password' => \App\Livewire\Security\UpdatePassword::class,
                        'two_factor_authentication' => \App\Livewire\Security\TwoFactorAuthentication::class,
                        'passkeys' => \App\Livewire\Security\Passkeys::class,
                        'browser_sessions' => \App\Livewire\Security\BrowserSessions::class,
                    ])
                    ->enableTwoFactorAuthentication(
                        force: config('core.auth.two_factor.force'),
                    )
                    ->enablePasskeys(
                        relyingPartyName: config('core.auth.passkey.relying_party_name'),
                        relyingPartyId: config('core.auth.passkey.relying_party_id') ?: null,
                    )
                    ->enableBrowserSessions(),
```

- [ ] **Step 5: Verify listeners registered**

Run: `php artisan tinker --execute="dump(app('events')->getListeners(Core\Security\Events\SecurityEventOccurred::class));"`
Expected: array with 1 entry (the `RecordSecurityEvent` listener).

- [ ] **Step 6: Commit**

```bash
git add app/Listeners/Security app/Livewire/Security app/Providers/AppServiceProvider.php app/Providers/Filament/AdminPanelProvider.php
git commit -m "feat: record security events from login 2fa passkey session actions (TODO 7.5)"
```

---

### Task 8: Roles & permissions seeder

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php`

**Interfaces:**
- Consumes: `Spatie\Permission\Models\Role`, `Spatie\Permission\Models\Permission`.
- Produces: Default roles `super_admin`, `administrator`, `manager`, `supervisor`, `staff`, `viewer` (PRD §19); `super_admin` role gets `*` wildcard permission (Shield convention).

- [ ] **Step 1: Modify `DatabaseSeeder`**

Current content:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Core\Database\Seeders\OrganizationSeeder;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'super_admin']);

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ])->assignRole('super_admin');

        $this->call(OrganizationSeeder::class);
    }
}
```

Replace the `Role::firstOrCreate(['name' => 'super_admin']);` line with:

```php
        $roles = ['super_admin', 'administrator', 'manager', 'supervisor', 'staff', 'viewer'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
```

- [ ] **Step 2: Verify roles seed**

Run: `php artisan db:seed`
Expected: seed runs; roles table now has 6 roles; `super_admin` assigned to admin@example.com.

- [ ] **Step 3: Commit**

```bash
git add database/seeders/DatabaseSeeder.php
git commit -m "feat: seed default roles (TODO 7, PRD 19)"
```

---

### Task 9: Feature tests — login, password reset, email verification

**Files:**
- Create: `tests/Feature/Auth/LoginTest.php`
- Create: `tests/Feature/Auth/PasswordResetTest.php`
- Create: `tests/Feature/Auth/EmailVerificationTest.php`

**Interfaces:**
- Consumes: Filament panel routes `filament.admin.auth.login`, `filament.admin.auth.request-password-reset`, `filament.admin.auth.reset-password`, `filament.admin.auth.email-verification.prompt`; `App\Models\User` with `MustVerifyEmail`; Breezy `enableTwoFactorAuthentication(force: false)`.

- [ ] **Step 1: Write the tests**

`tests/Feature/Auth/LoginTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible(): void
    {
        $this->get(route('filament.admin.auth.login'))
            ->assertSuccessful();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $this->post(route('filament.admin.auth.login'), [
            'email' => 'user@example.com',
            'password' => 'password',
        ])->assertRedirect(route('filament.admin.pages.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $this->post(route('filament.admin.auth.login'), [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors();

        $this->assertGuest();
    }

    public function test_logout_logs_user_out(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('filament.admin.auth.logout'))
            ->assertRedirect(route('filament.admin.auth.login'));

        $this->assertGuest();
    }
}
```

`tests/Feature/Auth/PasswordResetTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_password_reset_page_is_accessible(): void
    {
        $this->get(route('filament.admin.auth.request-password-reset'))
            ->assertSuccessful();
    }

    public function test_user_receives_reset_link(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->post(route('filament.admin.auth.request-password-reset'), [
            'email' => 'user@example.com',
        ])->assertSessionHasNoErrors();
    }

    public function test_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $token = Password::createToken($user);

        $this->post(route('filament.admin.auth.reset-password'), [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasNoErrors();
    }
}
```

`tests/Feature/Auth/EmailVerificationTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_is_redirected_to_verification_prompt(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertRedirect(route('filament.admin.auth.email-verification.prompt'));
    }

    public function test_verified_user_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertSuccessful();
    }
}
```

- [ ] **Step 2: Run the tests**

Run: `php artisan test tests/Feature/Auth/LoginTest.php tests/Feature/Auth/PasswordResetTest.php tests/Feature/Auth/EmailVerificationTest.php`
Expected: PASS (9 passed). If route names differ from the assumed slugs (`filament.admin.auth.*`), run `php artisan route:list` and adjust the test route names.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Auth
git commit -m "test: add auth feature tests (TODO 7.1)"
```

---

### Task 10: Feature tests — 2FA + super admin enforcement

**Files:**
- Create: `tests/Feature/Auth/TwoFactorTest.php`
- Create: `tests/Feature/Auth/SuperAdminTwoFactorTest.php`

**Interfaces:**
- Consumes: `App\Models\User` with `TwoFactorAuthenticatable`; `Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable` methods (`enableTwoFactorAuthentication`, `confirmTwoFactorAuthentication`, `hasConfirmedTwoFactor`); Breezy `filament('filament-breezy')->verify()`; roles from Task 8.

- [ ] **Step 1: Write the tests**

`tests/Feature/Auth/TwoFactorTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_enable_two_factor(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $user->enableTwoFactorAuthentication();

        $this->assertTrue($user->hasEnabledTwoFactor());
        $this->assertNotNull($user->breezySession->two_factor_recovery_codes);
    }

    public function test_user_can_confirm_two_factor(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->enableTwoFactorAuthentication();

        $user->confirmTwoFactorAuthentication();

        $this->assertTrue($user->hasConfirmedTwoFactor());
    }

    public function test_user_can_disable_two_factor(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->enableTwoFactorAuthentication();
        $user->confirmTwoFactorAuthentication();

        $user->disableTwoFactorAuthentication();

        $this->assertFalse($user->hasEnabledTwoFactor());
    }

    public function test_two_factor_page_requires_challenge_after_login(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->enableTwoFactorAuthentication();
        $user->confirmTwoFactorAuthentication();

        // Logged in but no valid 2FA session → redirected to 2FA challenge
        $this->actingAs($user)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertRedirect(route('filament.admin.auth.two-factor'));
    }
}
```

`tests/Feature/Auth/SuperAdminTwoFactorTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_without_2fa_is_redirected_to_profile(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertRedirect(route('filament.admin.pages.my-profile'));
    }

    public function test_super_admin_with_confirmed_2fa_can_access_dashboard(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);
        $user->enableTwoFactorAuthentication();
        $user->confirmTwoFactorAuthentication();

        $this->actingAs($user)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertRedirect(route('filament.admin.auth.two-factor')); // 2FA challenge, not profile
    }
}
```

> Note: Breezy routes a confirmed-but-not-session-authenticated user to the 2FA challenge page. The test asserts we do NOT redirect to profile (which would mean our middleware failed). Route names may need adjustment after `php artisan route:list`.

- [ ] **Step 2: Run the tests**

Run: `php artisan test tests/Feature/Auth/TwoFactorTest.php tests/Feature/Auth/SuperAdminTwoFactorTest.php`
Expected: PASS. If the my-profile slug differs (`my-profile` default), adjust route name.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Auth/TwoFactorTest.php tests/Feature/Auth/SuperAdminTwoFactorTest.php
git commit -m "test: add 2fa and super admin enforcement tests (TODO 7.3)"
```

---

### Task 11: Feature tests — security events recording

**Files:**
- Create: `tests/Feature/Auth/SecurityEventRecordingTest.php`

**Interfaces:**
- Consumes: listeners from Task 7; `Core\Security\Models\SecurityEvent`; `Illuminate\Auth\Events\Login`/`Failed`.

- [ ] **Step 1: Write the tests**

`tests/Feature/Auth/SecurityEventRecordingTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Core\Security\Models\SecurityEvent;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityEventRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_success_records_security_event(): void
    {
        Event::fake([Login::class]);

        $user = User::factory()->create(['email_verified_at' => now()]);

        Event::dispatch(new Login('web', $user, false));

        $this->assertDatabaseHas('security_events', [
            'event' => 'login_succeeded',
            'user_id' => $user->getKey(),
        ]);
    }

    public function test_login_failure_records_security_event(): void
    {
        Event::fake([Failed::class]);

        Event::dispatch(new Failed('web', null, []));

        $this->assertDatabaseHas('security_events', [
            'event' => 'login_failed',
            'user_id' => null,
        ]);
    }

    public function test_password_change_records_security_event(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Security\UpdatePassword::class)
            ->fillForm([
                'current_password' => 'password',
                'new_password' => 'new-password-123',
                'new_password_confirmation' => 'new-password-123',
            ])
            ->call('submit');

        $this->assertDatabaseHas('security_events', [
            'event' => 'password_changed',
            'user_id' => $user->getKey(),
        ]);
    }

    public function test_two_factor_enable_records_security_event(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Security\TwoFactorAuthentication::class)
            ->callAction('enable');

        $this->assertDatabaseHas('security_events', [
            'event' => 'two_factor_enabled',
            'user_id' => $user->getKey(),
        ]);
    }
}
```

> Livewire testing: `fillForm` and `callAction` are Filament testing helpers
> (`Tests\Livewire\...`). If the exact helper names differ in Filament 5,
> use `Livewire::test(...)->fillForm([...])->call('submit')` or
> `->callAction('enable')` per the Filament testing docs. The assertion is
> the contract: an event row must exist after the action.

- [ ] **Step 2: Run the tests**

Run: `php artisan test tests/Feature/Auth/SecurityEventRecordingTest.php`
Expected: PASS (4 passed).

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Auth/SecurityEventRecordingTest.php
git commit -m "test: add security event recording tests (TODO 7.5)"
```

---

### Task 12: Arch test update + final quality gate

**Files:**
- Modify: `tests/Arch/CoreArchTest.php`

**Interfaces:**
- Consumes: everything from Tasks 1-11.

- [ ] **Step 1: Update arch test to cover Security domain**

`tests/Arch/CoreArchTest.php` — add:

```php
arch('Core Security must not use App or Filament')
    ->expect('Core\Security')
    ->not->toUse(['App', 'Filament']);
```

- [ ] **Step 2: Run arch tests**

Run: `php artisan test tests/Arch/CoreArchTest.php`
Expected: PASS.

- [ ] **Step 3: Run full quality gate**

Run: `composer check`
Expected: Pint passes, all tests pass, PHPStan passes.

> If PHPStan flags the Livewire override return types, fix them (e.g., add proper return type hints matching parent).

- [ ] **Step 4: Update TODO.md**

In `docs/TODO.md`, check off §7.1–§7.5 items that are now complete (base auth, account security, 2FA, passkeys, security events) and add a note referencing the spec/plan.

- [ ] **Step 5: Commit**

```bash
git add tests/Arch/CoreArchTest.php docs/TODO.md
git commit -m "test: enforce core security arch boundaries (TODO 7)"
```

---

## Self-Review Notes

- **Spec §3.3 → Tasks 1, 7:** Core security events (enum/model/event/recorder) and app listeners + Livewire overrides. All 8 event types from the enum are wired: LoginSucceeded/Failed (listeners), PasswordChanged (UpdatePassword override), TwoFactorEnabled/Disabled (override), PasskeyRegistered/Revoked (override), SessionRevoked (BrowserSessions override).
- **Spec §3.2 → Task 2:** Auth policy config + env vars + docs.
- **Spec §3.4 → Task 4:** User model MustVerifyEmail + TwoFactorAuthenticatable + HasMany relation.
- **Spec §3.5 → Tasks 5, 6, 7:** Panel config, super admin middleware, component overrides.
- **Spec §3.6 → Task 8:** Default roles seeder.
- **Spec §3.7 → Tasks 9, 10:** Login flow tests.
- **Spec §4 (Error Handling) → Tasks 9-11:** Covered by test cases.
- **Spec §5 (Testing) → Tasks 9-12:** All test categories mapped.
- **Spec §6 (Out of Scope):** Sanctum tokens UI, RBAC detail, Settings UI — correctly not in any task.
- **Spec §7 (Future M7):** Config keys structured as settings defaults; migration path documented in spec (not implemented here).

## Verification Checklist

- [ ] `composer check` passes (Pint, Pest, PHPStan)
- [ ] `php artisan migrate` runs clean (breezy_sessions, passkeys)
- [ ] `php artisan db:seed` creates 6 roles
- [ ] Login → email verification → 2FA challenge → dashboard flow works
- [ ] Super admin without 2FA redirected to profile
- [ ] Security events recorded for login success/failure, password change, 2FA enable/disable, passkey register/revoke, session revocation

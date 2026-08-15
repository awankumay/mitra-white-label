---
name: project-console
description: >
  Use this skill when working with project-specific Artisan commands in Laravel Filament Starter Kit —
  scaffolding resources/modules, initializing/deploying/updating the project, or refreshing caches.
  Covers make:project-resource, make:module-resource, make:project-module, project:init, project:deploy,
  project:update, and project:cache.
---

## Project Console Commands

These custom Artisan commands streamline development in this Laravel Filament Starter Kit.

For the standard `app-modules/*` folder taxonomy these commands scaffold into, see `docs/ARCHITECTURE.md`.

### Resource scaffolding

There are **two** resource-scaffolding commands, for two different module folder conventions — flat (`Filament/Resources` / `Filament/AdminResources`) and nested (`Filament/Tenant/Resources` / `Filament/Admin/Resources`). Don't guess or go by a module's age — check `app-modules/{module}/src/Filament/` before scaffolding (or see the Migration status table in `docs/ARCHITECTURE.md`, kept as a convenience summary of the same check):

- `Tenant/`/`Admin/` subfolders present, or no `Filament/` folder yet at all → nested → `make:module-resource`.
- `Resources/`/`AdminResources/` present directly → flat → `make:project-resource`.

Both commands guard against generating into a module whose `{Name}Plugin` doesn't yet discover the target directory on the requested panel, and abort with the exact snippet to add rather than silently misplacing files (a real Filament generator quirk — see each command's source for details).

#### `make:project-resource` — flat convention

```bash
# Scaffold a Filament resource + policy + lang files (en/id), app-level (no module)
php artisan make:project-resource {ModelName}

# With module support (default panel: tenant)
php artisan make:project-resource {ModelName} --module={module-name}

# Cross-tenant / admin-only resource
php artisan make:project-resource {ModelName} --module={module-name} --panel=admin
```

`--panel` controls which module folder/namespace the resource is generated into:
- `tenant` (default) → `Filament/Resources`, auto-discovered by the `tenant` panel. Use for ordinary per-company operational data.
- `admin` → `Filament/AdminResources`, auto-discovered by the `admin` panel. Rare — only for cross-tenant resources (e.g. `Company`, `Role`, `Bank`) that must be manageable without an active tenant.

#### `make:module-resource` — nested convention, the standard going forward

```bash
# Scaffold into Modules\{Module}\Filament\Tenant\Resources (default panel: tenant)
php artisan make:module-resource {ModelName} {module-name}

# Scaffold into Modules\{Module}\Filament\Admin\Resources
php artisan make:module-resource {ModelName} {module-name} --panel=admin
```

Always requires a module (positional argument, not `--module=`) — there is no app-level form. `--panel` maps to a namespace/folder segment: `tenant` (default) → `Filament/Tenant/Resources`; `admin` → `Filament/Admin/Resources`. Both are auto-discovered out of the box by any module created via `make:project-module` (its generated `{Name}Plugin` wires up both panels' nested `Resources`/`Pages` directories from the start — see `stubs/project/module-plugin.stub`).

Both commands generate:
- Filament resource via `make:filament-resource`
- Policy via `make:policy` (auto-registered in module ServiceProvider or AppServiceProvider)
- `lang/{en,id}/{model_singular}.php` from `stubs/project/lang.stub`

Post-scaffold checklist (printed by the command):
- Register policy in appropriate service provider
- Add `HasTranslatedLabels` trait to the resource
- Fill in `field` and `column` keys in lang files
- Translate `lang/id/` file
- Run `php artisan project:update` to regenerate Shield permissions

### Module scaffolding

```bash
# Scaffold a ready-to-use module: module + composer link + Filament plugin + smoke test
php artisan make:project-module {module-name}
```

What it generates:
- Module via `make:module` with default namespace
- `composer update modules/{kebab}` (auto-links the module)
- Filament plugin from `stubs/project/module-plugin.stub` — wires up the nested `Filament/Admin/Resources`+`Pages` / `Filament/Tenant/Resources`+`Pages` convention on both panels, ready for `make:module-resource` immediately
- Service provider from `stubs/project/module-service-provider.stub` (overwrites default)
- Smoke test from `stubs/project/module-smoke-test.stub`

Post-scaffold:
- `make:module-resource {Model} {kebab}` for resources (the nested convention this module's plugin is already wired for)
- Use `Modules\Core\Models\Concerns\BelongsToCompany` for company-scoped models
- Register policies in the module's ServiceProvider
- If the module has seed data, add a `Modules\{Studly}\Database\Seeders\{Studly}DatabaseSeeder` calling its individual seeders — the root `DatabaseSeeder` auto-discovers and calls it (same pattern as Filament plugin auto-discovery), no manual wiring needed
- Run `php artisan project:update`

### Project lifecycle

```bash
# Initialize dev environment (DESTRUCTIVE — drops all tables)
php artisan project:init

# Safe deploy (non-destructive)
php artisan project:deploy

# Update existing install (migrate + re-gen permissions + clear cache)
php artisan project:update

# Refresh cache only
php artisan project:cache
```

#### `project:init`
Use for fresh development setups only. Runs in order:
1. `migrate:fresh` — drops all tables
2. `shield:generate --all --panel=admin` — generates all permissions
3. `db:seed` — runs database seeder
4. `shield:super-admin --user=1` — assigns super admin to user ID 1
5. Clears Filament + Laravel caches
6. Runs Pint code style check

#### `project:deploy`
Safe for production-like environments (non-destructive). Runs:
1. `migrate` (not fresh)
2. `shield:generate --all --panel=admin`
3. Clears + re-optimizes Filament and Laravel caches

#### `project:update`
Quick update for existing installs. Runs:
1. `migrate`
2. `shield:generate --all --panel=admin`
3. Clears Filament + Laravel caches

#### `project:cache`
Cache refresh only — does not touch DB or permissions:
1. `filament:optimize-clear`
2. `optimize:clear`
3. `optimize`
4. `filament:optimize`

### Typical workflow

1. `php artisan make:project-module sales` — create new module (plugin wired for the nested convention)
2. `php artisan make:module-resource Invoice sales` — scaffold resources inside it
3. Fill in lang files, policies, and resource schema
4. `php artisan project:update` — regenerate Shield permissions
5. `php artisan project:cache` — optimize before testing

### Related file structure

```
app/Console/Commands/
├── MakeProjectResource.php    # make:project-resource (flat convention)
├── MakeModuleResource.php     # make:module-resource (nested convention)
├── Concerns/
│   └── ScaffoldsModuleResources.php  # shared logic: moduleNamespace(), lang files, plugin-discovery guard
├── MakeProjectModule.php      # make:project-module
├── ProjectInitialize.php      # project:init
├── ProjectDeploy.php          # project:deploy
├── ProjectUpdate.php          # project:update
└── Recache.php                # project:cache

stubs/project/
├── lang.stub                  # Lang file template
├── module-plugin.stub         # Filament plugin template (nested Admin/Tenant convention)
├── module-service-provider.stub  # Module service provider template
└── module-smoke-test.stub     # Module smoke test template
```

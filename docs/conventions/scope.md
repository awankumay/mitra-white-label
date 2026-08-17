# Scope Conventions

> Data Scope Architecture — lihat spec `docs/superpowers/specs/2026-08-16-data-scope-architecture-design.md`.

## Kategori Scope

| Kategori | Kolom | Contoh |
|---|---|---|
| Global | — | System config, Feature definitions |
| Organization | `organization_id` | Organization settings, branding |
| Unit | `organization_id` + `organizational_unit_id` | Operational transactions, branch records |

Enum: `Core\Enums\DataScope` (`global`, `organization`, `unit`).

## Kolom pada Model Scoped

- Model scoped (Organization/Unit) punya dua kolom nullable: `organization_id` (FK `organizations`) dan `organizational_unit_id` (FK `organizational_units`).
- Invariant: jika `organizational_unit_id` terisi → `organization_id` terisi dan konsisten (unit → organization).
- Model non-scoped (Global) tidak punya kolom ini.
- Implementasikan `Core\Contracts\ScopedModel` (marker) pada model scoped.

## Helper `Core\Support\Scope`

```php
Scope::unit($query, ?string $unitId);            // where organizational_unit_id = ?
Scope::organization($query, ?string $orgId);     // where organization_id = ?
Scope::userUnits($query, $user);                 // unit IN units user
Scope::userOrganizations($query, $user);         // org IN org user
Scope::userScope($query, $user);                 // units user ATAU org user
Scope::can($user, ?string $unitId);              // super_admin ATAU unit di-assign
Scope::isSuperAdmin($user);                      // role super_admin
```

- `null` id → no-op (tidak memfilter).
- Bebas Filament & `App\Models\User` — hanya terima primitif atau `Authenticatable`.

## Pola Query

- **Context-driven** (session aktif): local scope convenience membaca context.

```php
Product::query()->inCurrentUnit()->get();
```

- **User-driven** (data user / bypass):

```php
if (! Scope::isSuperAdmin($user)) {
    $query = Scope::userScope($query, $user);
}
```

## Policy

Permission dulu, lalu scope record:

```php
public function view(User $authUser, Product $product): bool
{
    return $authUser->can('view:product')
        && Scope::can($authUser, $product->organizational_unit_id);
}
```

## Filament Resource

```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->when(
            ! Scope::isSuperAdmin(auth()->user()),
            fn (Builder $q) => Scope::userScope($q, auth()->user())
        );
}
```

## Bypass

| Kondisi | Perilaku |
|---|---|
| `super_admin` | Lolos semua scope |
| User biasa | Scope sesuai unit/org di-assign |
| Tanpa unit/org | Hanya data global |
| CLI/queue tanpa session | Scope no-op; caller set context eksplisit |

## Role Hierarchy

- Kolom `parent_role_id` (self-ref) pada tabel `roles` — arah child→parent.
- Inheritance top-down: permission role + semua ancestor (via `App\Services\RoleService`).
- Default: `administrator` (tanpa parent) → `manager` → `supervisor` → `staff` → `viewer`.
- `super_admin` bukan bagian hierarchy — bypass gate & scope (invariant).
- `panel_user` bukan bagian hierarchy (role teknis akses panel).
- Cegah cycle via `RoleService::wouldCreateCycle`.

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

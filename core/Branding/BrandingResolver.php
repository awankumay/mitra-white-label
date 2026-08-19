<?php

namespace Core\Branding;

use Core\Contracts\OrganizationContext;
use Core\Contracts\SettingsRepository;
use Core\Organization\Models\Organization;
use Core\Settings\Enums\SettingScope;
use Core\Settings\SettingsRegistry;
use Illuminate\Support\Facades\Storage;

final class BrandingResolver
{
    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly SettingsRegistry $registry,
        private readonly OrganizationContext $context,
    ) {}

    public function get(string $key): mixed
    {
        $orgId = $this->context->organizationId() ?? Organization::query()->value('id');

        if ($orgId !== null) {
            $value = $this->settings->getForScope($key, SettingScope::Organization, $orgId);

            if ($value !== null) {
                return $value;
            }
        }

        return $this->settings->getForScope($key, SettingScope::System)
            ?? $this->registry->definition($key)['default'];
    }

    public function url(string $key): ?string
    {
        $path = $this->get($key);

        return $path ? Storage::disk(config('core.branding.disk'))->url($path) : null;
    }
}

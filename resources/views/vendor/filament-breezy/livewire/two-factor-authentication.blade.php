<x-filament::section :heading="__('filament-breezy::default.profile.2fa.title')" :description="__('filament-breezy::default.profile.2fa.description')">
    @if ($this->showRequiresTwoFactorAlert())
        <x-filament::callout color="danger" icon="heroicon-s-shield-exclamation">
            <x-slot name="heading">
                {{ __('filament-breezy::default.profile.2fa.must_enable') }}
            </x-slot>
        </x-filament::callout>
    @endif

    @unless ($user->hasEnabledTwoFactor())
        <div class="space-y-4">
            <div>
                <h3 class="flex items-center gap-2 text-lg font-medium">
                    <x-filament::icon icon="heroicon-o-exclamation-circle" class="w-6 h-6" />
                    {{ __('filament-breezy::default.profile.2fa.not_enabled.title') }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('filament-breezy::default.profile.2fa.not_enabled.description') }}
                </p>
            </div>

            <div class="flex justify-between">
                {{ $this->enableAction }}
            </div>
        </div>
    @else
        @if ($user->hasConfirmedTwoFactor())
            <div class="space-y-4">
                <div>
                    <h3 class="flex items-center gap-2 text-lg font-medium">
                        <x-filament::icon icon="heroicon-o-shield-check" class="w-6 h-6" />
                        {{ __('filament-breezy::default.profile.2fa.enabled.title') }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('filament-breezy::default.profile.2fa.enabled.description') }}
                    </p>
                </div>

                @if ($showRecoveryCodes)
                    <div class="space-y-3">
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            {{ __('filament-breezy::default.profile.2fa.enabled.store_codes') }}
                        </p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($this->recoveryCodes->toArray() as $code)
                                <x-filament::badge color="gray">
                                    {{ $code }}
                                </x-filament::badge>
                            @endforeach
                        </div>
                        <div class="inline-block text-xs">
                            <x-filament-breezy::clipboard-link :data="$this->recoveryCodes->join(',')" />
                        </div>
                    </div>
                @endif

                <div class="flex justify-between">
                    {{ $this->regenerateCodesAction }}
                    {{ $this->disableAction }}
                </div>
            </div>
        @else
            <div class="space-y-4">
                <div>
                    <h3 class="flex items-center gap-2 text-lg font-medium">
                        <x-filament::icon icon="heroicon-o-question-mark-circle" class="w-6 h-6" />
                        {{ __('filament-breezy::default.profile.2fa.finish_enabling.title') }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('filament-breezy::default.profile.2fa.finish_enabling.description') }}
                    </p>
                </div>

                <div class="flex flex-col gap-4 sm:flex-row">
                    <div class="space-y-2">
                        {!! $this->getTwoFactorQrCode() !!}
                        <p class="pt-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('filament-breezy::default.profile.2fa.setup_key') }}
                            {{ $this->two_factor_secret }}
                        </p>
                    </div>
                    <div class="space-y-3">
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            {{ __('filament-breezy::default.profile.2fa.enabled.store_codes') }}
                        </p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($this->recoveryCodes->toArray() as $code)
                                <x-filament::badge color="gray">
                                    {{ $code }}
                                </x-filament::badge>
                            @endforeach
                        </div>
                        <div class="inline-block text-xs">
                            <x-filament-breezy::clipboard-link :data="$this->recoveryCodes->join(',')" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-between">
                    {{ $this->confirmAction }}
                    {{ $this->disableAction }}
                </div>
            </div>
        @endif
    @endunless

    <x-filament-actions::modals />
</x-filament::section>

<x-filament::section :heading="__('filament-breezy::default.profile.sanctum.title')" :description="__('filament-breezy::default.profile.sanctum.description')">
    @if ($plainTextToken)
        <x-filament::callout color="primary" icon="heroicon-s-key" class="mb-4">
            <x-slot name="heading">
                {{ __('filament-breezy::default.profile.sanctum.create.message') }}
            </x-slot>

            <x-slot name="footer">
                <x-filament::input.wrapper class="w-full">
                    <x-filament::input type="text" :value="$plainTextToken" readonly />
                </x-filament::input.wrapper>

                <div class="flex items-center justify-between gap-4">
                    <div class="inline-block text-xs">
                        <x-filament-breezy::clipboard-link :data="$plainTextToken" />
                    </div>
                    <x-filament::button icon="heroicon-s-clipboard-document-check" size="sm" type="button" wire:click="$set('plainTextToken',null)">
                        {{ __('filament-breezy::default.profile.sanctum.copied.label') }}
                    </x-filament::button>
                </div>
            </x-slot>
        </x-filament::callout>
    @endif

    <div @style(['display: none' => $plainTextToken])>
        {{ $this->table }}
    </div>
</x-filament::section>

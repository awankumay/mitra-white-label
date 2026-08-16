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

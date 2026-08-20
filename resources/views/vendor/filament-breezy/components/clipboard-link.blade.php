@props([
    'data',
])

<a
    x-data="{}"
    x-on:click.prevent="window.navigator.clipboard.writeText(@js($data));"
    x-tooltip="{
        content: @js(__('filament-breezy::default.clipboard.tooltip')),
        theme: $store.theme,
    }"
    href="#"
    class="inline-flex items-center gap-2"
>
    <x-filament::icon icon="heroicon-s-clipboard-document" class="w-4 h-4" />
    <span>{{ __('filament-breezy::default.clipboard.link') }}</span>
</a>

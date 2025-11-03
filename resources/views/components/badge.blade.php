@props([
    'icon' => null,
])

@php
    $baseContentClasses = 'text-light';
    $iconClasses = 'w-6 h-6 lg:w-8 lg:h-8';
    $textClasses = 'font-semibold text-xl lg:text-2xl leading-none';
@endphp

<div {{ $attributes->merge([
    'class' => 'flex items-center justify-center p-3 bg-elevation-surface/16 border border-elevation-surface/16 rounded-sm w-fit'
]) }}>

    @if ($icon)
        <x-filament::icon
            :icon="$icon"
            @class([$baseContentClasses, $iconClasses])
        />
    @else
        <span @class([$baseContentClasses, $textClasses])>
            {{ $slot }}
        </span>
    @endif

</div>

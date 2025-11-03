@props([
    'icon' => null,
    'size' => 'sm',
    'color' => 'neutral'
])

@php
    $baseContentClasses = 'text-light';

    $colorClasses = match($color) {
        'primary' => 'bg-gradient-to-br from-brand-primary to-brand-secondary',
        'neutral' => 'bg-elevation-surface/16 border border-icon-light/32'
    };

    $sizeClasses = match($size) {
        'md' => [
            'padding' => 'p-3',
            'icon' => 'w-6 h-6 lg:w-8 lg:h-8',
            'text' => 'font-semibold text-2xl leading-none',
        ],
        'lg' => [
            'padding' => 'p-4',
            'icon' => 'w-10 h-10',
            'text' => 'font-semibold text-3xl leading-none',
        ],
        default => [
            'padding' => 'p-3',
            'icon' => 'w-6 h-6',
            'text' => 'font-semibold text-base leading-none',
        ],
    };
@endphp

<div {{ $attributes->merge([
    'class' => 'flex items-center justify-center rounded-sm w-fit ' . $sizeClasses['padding'] . ' ' . $colorClasses
]) }}>

    @if ($icon)
        <x-filament::icon
            :icon="$icon"
            @class([$baseContentClasses, $sizeClasses['icon']])
        />
    @else
        <span @class([$baseContentClasses, $sizeClasses['text']])>
            {{ $slot }}
        </span>
    @endif

</div>

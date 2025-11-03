@props([
    'icon' => null,
    'size' => 'sm',
])

@php
    $baseContentClasses = 'text-light';

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
    'class' => 'flex items-center justify-center bg-elevation-surface/16 border border-elevation-surface/16 rounded-sm w-fit ' . $sizeClasses['padding']
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

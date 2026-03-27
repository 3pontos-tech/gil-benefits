@props([
    'reversed' => false,
    'textColor' => 'light',
    'buttonVariant' => 'solid',
    'title' => '',
    'description' => '',
    'icon' => '',
    'iconColor' => 'white',
    'imgPath' => 'img/man-look-confused.webp'
])

@php
    $textLight = $textColor === 'light' ? 'text-light!' : '';
@endphp

<div {{ $attributes->class([
    'grid grid-cols-1 lg:grid-cols-[1fr_2fr] gap-12',
    'lg:[&>*:first-child]:order-2 lg:[&>*:last-child]:order-1' => $reversed,
    ])
    }}>
    <div class="relative overflow-hidden h-[25vh] lg:h-auto rounded-lg">
        <img
            src="{{ asset($imgPath) }}"
            alt=""
            class="absolute inset-0 w-full h-full object-cover object-center"
            loading="lazy"
            decoding="async"
        />
    </div>

    <x-headline class="{{ $textLight }} max-w-full!" align="left">
        @if($icon === 'flamma-icon')
            <x-slot:badge>
                <x-logo :color="$iconColor" />
            </x-slot:badge>
        @elseif($icon)
            <x-slot:badge>
                <x-filament::icon :icon="$icon" class="w-12 h-12" />
            </x-slot:badge>
        @endif
        @if($title)
            <x-slot:title>
                {{ $title }}
            </x-slot:title>
        @endif
        <x-slot:description class="{{ $textLight }}">
            {{ $description }}
        </x-slot:description>
        <x-slot:actions>
            <x-button :variant="$buttonVariant" rel="noopener noreferrer" target="_blank" href="https://wa.me/5511976205711?text=Flamma">
                Entrar em contato
            </x-button>
        </x-slot:actions>
    </x-headline>
</div>

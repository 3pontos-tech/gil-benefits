@props([
    'as' => 'button',
    'href' => null,
    'type' => 'button',
    'color' => 'primary',
    'variant' => 'solid',
    'size' => 'lg',       // xs|sm|md|lg|xl|xl-tight
    'rounded' => 'sm',  // full|lg|md|sm
    'block' => false,
    'disabled' => false,
    'loading' => false,
    'iconOnly' => false,
    'icon' => null,
    'iconPosition' => 'leading',
])

@aware(['interactive' => false])

@php
    $isLink = filled($href);
    $tag = $isLink ? 'a' : $as;
    $isBusy = (bool) $loading;
    $isDisabled = (bool) $disabled || $isBusy;

    $hasLeading = isset($leading) || (filled($icon) && $iconPosition === 'leading');
    $hasTrailing = isset($trailing) || (filled($icon) && $iconPosition === 'trailing');

    // O peso sai do base e passa a vir do tamanho: o CTA do site institucional é bold,
    // os botões de interface seguem medium.
    $base = 'relative inline-flex items-center justify-center whitespace-nowrap transition-all duration-200 group/button active:scale-95 cursor-pointer';

    $roundCls = [
        'full' => 'rounded-full', 'lg' => 'rounded-xl', 'md' => 'rounded-lg', 'sm' => 'rounded-md',
        'none' => 'rounded-none',
    ][$rounded] ?? 'rounded-sm';

    // xl é a medida do CTA no Figma: 16px de padding vertical + 24px de linha = 56 de altura,
    // e 32 na horizontal (o botão do hero tem 221 de largura para um rótulo de 157).
    $sizeCls = [
        'xs' => ['pad'=>'px-2.5 py-1.5','text'=>'text-xs','icon'=>'h-4 w-4','iconOnly'=>'p-1.5','weight'=>'font-medium'],
        'sm' => ['pad'=>'px-3.5 py-2','text'=>'text-sm','icon'=>'h-4 w-4','iconOnly'=>'p-2','weight'=>'font-medium'],
        'md' => ['pad'=>'px-4 py-2.5','text'=>'text-sm','icon'=>'h-5 w-5','iconOnly'=>'p-2.5','weight'=>'font-medium'],
        'lg' => ['pad'=>'px-5 py-3','text'=>'text-base','icon'=>'h-5 w-5','iconOnly'=>'p-3','weight'=>'font-medium'],
        'xl' => ['pad'=>'px-8 py-4','text'=>'text-base','icon'=>'h-5 w-5','iconOnly'=>'p-4','weight'=>'font-bold'],
        // Mesma altura do xl, mas com 16 de padding horizontal: é o botão da página do
        // colaborador no Figma (184 = rótulo de 152 + 2×16), contra 32 nas outras duas.
        'xl-tight' => ['pad'=>'px-4 py-4','text'=>'text-base','icon'=>'h-5 w-5','iconOnly'=>'p-4','weight'=>'font-bold'],
    ][$size] ?? ['pad'=>'px-4 py-2.5','text'=>'text-sm','icon'=>'h-5 w-5','iconOnly'=>'p-2.5','weight'=>'font-medium'];

    // Os CTAs do site usam o laranja forte da marca (--orange-primary, #E2410A) — o mesmo
    // do topo e do rodapé. O vermelho segue nos títulos e nos destaques de texto.
    $colors = [
        'primary' => [
            'solid' => [
                'bg' => 'bg-gradient-to-br from-orange-primary to-orange-400',
                'text' => 'text-light',
                'border' => '',
                'hoverBg' => '',
                'hoverText' => '',
            ],
            'outline' => [
                'bg' => 'bg-transparent',
                'text' => 'text-orange-primary',
                'border' => 'border-orange-primary',
                'hoverBg' => 'hover:bg-orange-primary',
                'hoverText' => 'hover:text-light',
            ],
            'white' => [
                'bg' => 'bg-white',
                'text' => 'text-orange-primary',
                'border' => 'none',
                'hoverBg' => '',
                'hoverText' => '',
            ],
            // Chapado, sem gradiente: é o CTA da página para empresas no design.
            'flat' => [
                'bg' => 'bg-orange-primary',
                'text' => 'text-light',
                'border' => 'border-outline-light',
                'hoverBg' => 'hover:bg-orange-600',
                'hoverText' => '',
            ],
            // Botão claro sobre bloco colorido. O design usa duas versões, que diferem
            // só no rótulo: escuro sobre o gradiente do hero, laranja sobre bloco colorido.
            'light' => [
                'bg' => 'bg-icon-light',
                'text' => 'text-dark',
                'border' => 'border-outline-light',
                'hoverBg' => 'hover:bg-white',
                'hoverText' => '',
            ],
            'light-brand' => [
                'bg' => 'bg-icon-light',
                'text' => 'text-orange-primary',
                'border' => 'border-[#f1785a]',
                'hoverBg' => 'hover:bg-white',
                'hoverText' => '',
            ],
        ],
    ][$color] ?? $colors['brand'];

    $variantColors = $colors[$variant] ?? $colors['solid'];

    $variantCls = match ($variant) {
        'solid' => "{$variantColors['bg']} {$variantColors['text']} {$variantColors['border']} {$variantColors['hoverBg']} hover:scale-[1.02] transition-all duration-300",
        'outline' => "{$variantColors['bg']} {$variantColors['text']} {$variantColors['border']} {$variantColors['hoverBg']} {$variantColors['hoverText']} border hover:scale-[1.02] transition-all duration-300",
        'white' => "{$variantColors['bg']} {$variantColors['text']} {$variantColors['border']} {$variantColors['hoverBg']} {$variantColors['hoverText']} hover:scale-[1.02] transition-all duration-300",
        'flat', 'light', 'light-brand' => "{$variantColors['bg']} {$variantColors['text']} {$variantColors['border']} {$variantColors['hoverBg']} border transition-all duration-300",
        default => "{$variantColors['bg']} {$variantColors['text']} {$variantColors['border']}",
    };

    $linkedHoverCls = $interactive ? match ($variant) {
        'solid' => 'group-hover/card:shadow-md',
        'outline' => 'group-hover/card:bg-gray-50',
        default => '',
    } : '';

    $classes = implode(' ', [
        $base,
        $roundCls,
        $sizeCls['text'],
        $sizeCls['weight'],
        $variantCls,
        $block ? 'w-full' : 'w-full lg:w-auto',
        $isDisabled ? 'opacity-60 pointer-events-none' : '',
        ($hasLeading || $hasTrailing) && !$iconOnly ? 'gap-2' : 'gap-0',
        $iconOnly ? $sizeCls['iconOnly'] : $sizeCls['pad'],
        $linkedHoverCls,
    ]);
@endphp

<{{ $tag }}
    @if (!$isLink)
    type="{{ $type }}"
@if ($isDisabled)
    disabled
@endif
@else
    href="{{ $href }}"
    @if ($isDisabled)
        aria-disabled="true" tabindex="-1"
    @endif
@endif
@if ($isBusy)
    aria-busy="true"
@endif
{{ $attributes->class($classes) }}
>

{{-- Leading Icon or Slot --}}
@if ($hasLeading)
    <span class="{{ $sizeCls['icon'] }} shrink-0">
            @if ($icon)
            <x-dynamic-component :component="$icon" class="w-full h-full"/>
        @else
            {{ $leading }}
        @endif
        </span>
@endif

{{-- Label Slot --}}
@unless($iconOnly)
    <span class="{{ $isBusy ? 'opacity-0' : 'opacity-100' }}">
            {{ $slot }}
        </span>
@endunless

{{-- Trailing Icon or Slot --}}
@if ($hasTrailing)
    <span class="{{ $sizeCls['icon'] }} shrink-0">
            @if ($icon)
            <x-dynamic-component :component="$icon" class="w-full h-full"/>
        @else
            {{ $trailing }}
        @endif
        </span>
@endif

{{-- Loading Spinner --}}
@if ($isBusy)
    <span class="absolute inline-flex items-center justify-center">
            <svg class="animate-spin {{ $sizeCls['icon'] }}" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4"
                      stroke-linecap="round"/>
            </svg>
        </span>
@endif
</{{ $tag }}>

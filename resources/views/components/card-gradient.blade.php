@props([
    'as' => 'div',
    'href' => null,
    'interactive' => true,
    'disabled' => false,
    'density' => 'normal',
    'target' => null,
    'rel' => null,
    'variant' => '',
])

@php
    $isInteractive = $interactive && !$disabled;
    $tag = $href ? 'a' : $as;

    [
        $borderGradientClass,
        $backgroundGradient,
        $iconColor,
        $interactiveBorderClasses,
        $interactiveBackgroundClasses,
    ] = match ($variant) {
        'blue' => [
            'bg-gradient-to-b from-blue-primary/25 to-transparent rounded-xl p-0.5',
            'bg-gradient-to-b from-blue-primary/20 to-transparent',
            'text-blue-900',
            $isInteractive
                ? 'transition hover:from-blue-primary/50 hover:to-blue-primary/20'
                : '',
            $isInteractive
                ? 'transition group-hover/card:bg-blue-primary/10 focus-visible:ring-2 focus-visible:ring-blue-primary focus-visible:ring-offset-2 ring-offset-zinc-900'
                : '',
        ],
        'pink' => [
            'bg-gradient-to-b from-pink-primary/25 to-transparent rounded-xl p-0.5',
            'bg-gradient-to-b from-pink-primary/20 to-transparent',
            'text-pink-900',
            $isInteractive
                ? 'transition hover:from-pink-primary/50 hover:to-pink-primary/20'
                : '',
            $isInteractive
                ? 'transition group-hover/card:bg-pink-primary/10 focus-visible:ring-2 focus-visible:ring-pink-primary focus-visible:ring-offset-2 ring-offset-zinc-900'
                : '',
        ],
        'neutral' => [
            'bg-gradient-to-b from-outline-light/25 to-outline-light rounded-xl p-0.5',
            'bg-elevation-surface',
            'text-orange-primary',
            '',
            '',
        ],
        default => [
            'bg-gradient-to-b from-primary-900/25 to-transparent rounded-xl p-0.5',
            'bg-gradient-to-b from-primary-900/20 to-transparent',
            'text-primary-900',
            $isInteractive
                ? 'transition hover:from-primary-900/50 hover:to-primary-900/20'
                : '',
            $isInteractive
                ? 'transition group-hover/card:bg-primary-900/10 focus-visible:ring-2 focus-visible:ring-primary-900 focus-visible:ring-offset-2 ring-offset-zinc-900'
                : '',
        ],
    };

    $backgroundColor = 'bg-elevation-surface';
    $paddingClass = $density === 'compact' ? 'p-4' : 'p-6';
    $disabledClasses = $disabled ? 'pointer-events-none cursor-not-allowed opacity-60' : '';

    $classes = trim(implode(' ', [
        'relative z-20 rounded-xl transition flex flex-col gap-y-3 h-full w-full',
        $backgroundGradient,
        $paddingClass,
        $interactiveBackgroundClasses,
        $disabledClasses,
    ]));

    $linkAttrs = [];
    if ($href) {
        $linkAttrs['href'] = $href;
        if ($target === '_blank' && is_null($rel)) {
            $linkAttrs['rel'] = 'noopener noreferrer';
        }
        if ($target) $linkAttrs['target'] = $target;
        if ($rel) $linkAttrs['rel'] = $rel;
    }
    if ($disabled) {
        $linkAttrs['aria-disabled'] = 'true';
        $linkAttrs['tabindex'] = '-1';
    }
@endphp

<div class="relative {{ $isInteractive ? 'group/card hover:scale-[1.02] ease-in-out duration-500' : '' }} {{ $borderGradientClass }} {{ $interactiveBorderClasses }} h-full">
    <div class="absolute inset-0.5 rounded-[calc(0.75rem-1px)] z-10 {{ $backgroundColor }}"></div>

    <{{ $tag }} {{ $attributes->merge(['class' => $classes])->merge($linkAttrs) }}>
    @isset($icon)
        <div class="{{ $iconColor }}" {{ $icon->attributes }}>
            {{ $icon }}
        </div>
    @endisset

    @isset($title)
        <div {{ $title->attributes->class('text-lg font-semibold tracking-tight text-high') }}>
            {{ $title }}
        </div>
    @endisset

    @isset($description)
        <p {{ $description->attributes->class('text-sm text-medium') }}>
            {{ $description }}
        </p>
    @endisset

    @isset($actions)
        <div {{ $actions->attributes->class('mt-2') }}>
            {{ $actions }}
        </div>
    @endisset

    @isset($footer)
        <div {{ $footer->attributes->class('mt-4 pt-4 border-t border-black/5') }}>
            {{ $footer }}
        </div>
    @endisset
</{{ $tag }}>
</div>

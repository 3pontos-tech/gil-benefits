@props([
    'as' => 'div',
    'href' => null,
    'interactive' => false,
    'disabled' => false,
    'density' => 'normal',
    'target' => null,
    'rel' => null,
    'textBox' => 'flex flex-col gap-y-1',
    'color' => 'primary',
    'variant' => 'neutral',
])

@php
    $isInteractive = $interactive && !$disabled;
    $tag = $href ? 'a' : $as;

    $styleVariants = [
        'primary' => [
            'solid' => [
                'base' => 'bg-gradient-to-br from-brand-primary to-brand-secondary',
                'title' => 'text-light',
                'description' => 'text-light',
                'hover' => 'hover:brightness-110',
                'titleHover' => '',
                'descriptionHover' => '',
            ],
            'light' => [
                'base' => 'bg-elevation-01dp border-outline-light dark:border-outline-dark',
                'title' => 'text-text-high',
                'description' => 'text-text-medium',
                'hover' => 'hover:border-brand-primary hover:bg-brand-primary',
                'titleHover' => 'group-hover/card:text-light',
                'descriptionHover' => 'group-hover/card:text-light',
            ],
        ],
    ];

    $colorSet = $styleVariants[$color] ?? $styleVariants['neutral'];
    $variantSet = $colorSet[$variant] ?? $colorSet['solid'];

    $baseClasses = $variantSet['base'];
    $titleClasses = $variantSet['title'];
    $descriptionClasses = $variantSet['description'];

    $interactiveClasses = '';
    $titleInteractiveClasses = '';
    $descriptionInteractiveClasses = '';
    $groupClass = '';

    if ($isInteractive) {
        $groupClass = 'group/card';
        $interactiveClasses = $variantSet['hover'];
        $titleInteractiveClasses = $variantSet['titleHover'];
        $descriptionInteractiveClasses = $variantSet['descriptionHover'];
    }

    $paddingClass = $density === 'compact' ? 'p-4' : 'p-6';
    $disabledClasses = $disabled ? 'pointer-events-none cursor-not-allowed opacity-60' : '';

    $classes = trim(implode(' ', [
        'rounded-xl transition flex flex-col gap-y-3',
        $paddingClass,
        $groupClass,
        $baseClasses,
        $interactiveClasses,
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

<{{ $tag }} {{ $attributes->merge(['class' => $classes])->merge($linkAttrs) }}>

@isset($icon)
    <div {{ $icon->attributes }}>
        {{ $icon }}
    </div>
@endisset

<div class="{{ $textBox }}">
    @isset($title)
        <h3 {{ $title->attributes->class([
                'text-md lg:text-xl font-semibold tracking-tight',
                $titleClasses,
                $titleInteractiveClasses
            ]) }}>
            {{ $title }}
        </h3>
    @endisset

    @isset($description)
        <p {{ $description->attributes->class([
                'text-sm',
                $descriptionClasses,
                $descriptionInteractiveClasses
            ]) }}>
            {{ $description }}
        </p>
    @endisset
</div>

@isset($actions)
    <div {{ $actions->attributes->class('mt-2') }}>
        {{ $actions }}
    </div>
@endisset

@isset($footer)
    <div {{ $footer->attributes->class('mt-4 pt-4 border-t border-outline-light dark:border-outline-dark') }}>
        {{ $footer }}
    </div>
@endisset

</{{ $tag }}>

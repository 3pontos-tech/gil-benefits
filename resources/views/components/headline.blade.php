@props([
    'as' => 'div',
    'align' => 'center', // center|left
    'size' => 'lg',      // sm|md|lg|xl|2xl|3xl|4xl
    'animate' => true,
    'keywords' => [],
    'keywordColor' => 'primary'
])

@php
    $tag = $as;

    $alignCls = match($align) {
        'left' => 'sm:text-left text-center items-center sm:items-start flex flex-col',
        default => 'text-center flex flex-col items-center',
    };

    $actionsAlignCls = match($align) {
        'left' => 'sm:justify-start justify-center',
        default => 'justify-center',
    };

    $animateCls = $animate ? 'animate-fade-in' : '';

    $highlightClass = match ($keywordColor) {
        'primary' => 'text-brand-primary'
    };

    $sizes = [
        'sm' => ['h' => 'text-lg sm:text-xl md:text-2xl lg:text-3xl', 'p' => 'text-sm sm:text-base'],
        'md' => ['h' => 'text-xl sm:text-2xl md:text-3xl lg:text-4xl', 'p' => 'text-sm sm:text-base md:text-lg'],
        'lg' => ['h' => 'text-2xl sm:text-4xl md:text-3xl lg:text-4xl xl:text-5xl', 'p' => 'text-sm md:text-md lg:text-base'],
        'xl' => ['h' => 'text-2xl sm:text-3xl md:text-3xl lg:text-4xl xl:text-5xl', 'p' => 'text-sm sm:text-md md:text-base lg:text-lg'],
        '2xl' => ['h' => 'text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl', 'p' => 'text-base md:text-lg lg:text-xl xl:text-2xl'],
        '3xl' => ['h' => 'text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl', 'p' => 'text-base md:text-lg lg:text-xl xl:text-2xl'],
        '4xl' => ['h' => 'text-4xl sm:text-5xl md:text-6xl lg:text-7xl xl:text-8xl', 'p' => 'text-xl md:text-2xl lg:text-3xl xl:text-4xl'],
    ][$size] ?? ['h' => 'text-2xl md:text-3xl', 'p' => 'text-base'];
@endphp

<div {{ $attributes->class(($align === 'center' ? 'mx-auto' : '') . ' max-w-2xl md:max-w-3xl lg:max-w-7xl') }}>
    <{{ $tag }} class="{{ $animateCls }} {{ $alignCls }}">

    @isset($badge)
        <div {{ $badge->attributes->class('mb-4') }}>
            {{ $badge }}
        </div>
    @endisset

    @isset($title)
        @php
            $words = str($title)->explode(' ');
        @endphp

        <h1 {{ $title->attributes->class('font-bold drop-shadow-lg leading-tight mb-4 sm:mb-6 ' . $sizes['h']) }}>
            @foreach($words as $word)
                @if(in_array(trim($word), $keywords))
                    <span class="{{ $highlightClass }}">{{ $word }}</span>
                @else
                    {{ $word }}
                @endif
                @if(!$loop->last)
                    {{ ' ' }}
                @endif
            @endforeach
        </h1>
    @endisset

    @isset($description)
        <p {{ $description->attributes->class('text-medium font-medium delay-200 max-w-full mb-4 sm:mb-6 ' . $sizes['p']) }}>
            {{ $description }}
        </p>
    @endisset

    @isset($actions)
        <div {{ $actions->attributes->class('flex w-full flex-col sm:flex-row gap-6 sm:gap-x-4 items-center ' . $actionsAlignCls) }}>
            {{ $actions }}
        </div>
    @endisset

</{{ $tag }}>
</div>

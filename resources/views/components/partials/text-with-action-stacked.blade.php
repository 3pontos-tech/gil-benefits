@props([
    'text' => '',
])

<div {{ $attributes->class(['flex flex-col items-center justify-center gap-8 sm:max-w-[60%]']) }}>
    <p class="text-medium font-medium delay-200 max-w-full text-sm md:text-md lg:text-base text-center">
        {{ $text }}
    </p>
    <x-button>
        Lorem Ipsum
    </x-button>
</div>

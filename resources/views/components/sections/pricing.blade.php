@php
    $title = 'Conheça nossos planos';
    $description = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. In efficitur velit vitae enim sodales
        sodales. Donec lectus nisi, aliquam eu ante at, blandit laoret...';
@endphp

<section class="flex flex-col mx-auto mb-28 sm:mb-44 items-center justify-center space-y-8 lg:space-y-16 w-full">
    <x-headline class="max-w-3xl!">
        <x-slot:title>
            {{ $title }}
        </x-slot:title>
        <x-slot:description>
            {{ $description }}
        </x-slot:description>
    </x-headline>

    <livewire:pricing-calculator />
</section>

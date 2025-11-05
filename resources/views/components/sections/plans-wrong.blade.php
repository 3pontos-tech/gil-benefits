@php
    $title = 'Conheça nossos planos';
    $description = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. In efficitur velit vitae enim sodales
        sodales. Donec lectus nisi, aliquam eu ante at, blandit laoret...';

    $benefitTitles = [
        'titulo beneficio 1',
        'titulo beneficio 2',
        'titulo beneficio 3',
        'titulo beneficio 4',
        'titulo beneficio 5',
        'titulo beneficio 6',
        'titulo beneficio 7',
    ];

    $plansData = [
        [
            'title' => 'Plano Bronze',
            'benefits' => ['beneficio 1'],
        ],
        [
            'title' => 'Plano Prata',
            'benefits' => ['beneficio 1', 'beneficio 2'],
        ],
        [
            'title' => 'Plano Ouro',
            'benefits' => ['beneficio 1', 'beneficio 2', 'beneficio 3', 'beneficio 4'],
        ],
        [
            'title' => 'Plano Platina',
            'benefits' => ['beneficio 1', 'beneficio 2', 'beneficio 3', 'beneficio 4', 'beneficio 5', 'beneficio 6', 'beneficio 7'],
        ],
    ];
@endphp

<section class="flex flex-col mx-auto mb-28 sm:mb-44 items-center justify-center space-y-8 lg:space-y-16">
    <x-headline class="max-w-full!" align="left" contentLayout="grid grid-cols-1 lg:grid-cols-[1fr_2fr] gap-4 lg:gap-12 items-center">
        <x-slot:title>
            {{ $title }}
        </x-slot:title>
        <x-slot:description>
            {{ $description }}
        </x-slot:description>
    </x-headline>

    <x-partials.pricing-table :benefitTitles="$benefitTitles" :plans="$plansData" />
</section>

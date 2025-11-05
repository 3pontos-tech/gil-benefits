@props([
    'reversed' => false,
    'textColor' => 'light',
    'buttonVariant' => 'white'
])

@php
    $title = 'O Desafio do RH: Como o Estresse Financeiro Afeta Sua Empresa';
    $description = 'A Flamma surge como um benefício corporativo inovador, desenhado para empoderar seus colaboradores
        com educação financeira de alta qualidade. Somos pioneiros em oferecer uma consultoria financeira personalizada,
        que vai além do básico, promovendo uma verdadeira evolução na relação das pessoas com o dinheiro. Nosso
        propósito é disseminar conhecimento e ferramentas para que seus funcionários alcancem a tão desejada liberdade
        financeira, refletindo diretamente no sucesso da sua organização.';

    $textLight = $textColor === 'light' ? 'text-light!' : '';
@endphp

<div {{ $attributes->class([
    'grid grid-cols-1 lg:grid-cols-[1fr_2fr] gap-12',
    'lg:[&>*:first-child]:order-2 lg:[&>*:last-child]:order-1' => $reversed,
    ])
    }}>
    <div class="relative overflow-hidden h-[25vh] lg:h-auto rounded-lg">
        <img
            src="{{ asset('img/woman-bg.png') }}"
            alt=""
            class="absolute inset-0 w-full h-full object-cover object-center"
            loading="eager"
            decoding="async"
        />
    </div>

    <x-headline class="{{ $textLight }} max-w-full!" align="left">
        <x-slot:title>
            {{ $title }}
        </x-slot:title>
        <x-slot:description class="{{ $textLight }}">
            {{ $description }}
        </x-slot:description>
        <x-slot:actions>
            <x-button :variant="$buttonVariant">
                Saiba mais
            </x-button>
        </x-slot:actions>
    </x-headline>
</div>

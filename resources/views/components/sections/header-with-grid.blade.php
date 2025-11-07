@php
    $title = 'Como funciona?';
    $description = 'A empresa contrata um pacote de horas de atendimento (mensal, semestral ou anual). Os colaboradores
        agendam seus atendimentos diretamente pela plataforma Flamma. Você tem acesso à atendimentos individuais com
        até 60 minutos, online ou presenciais.  Relatórios consolidados de uso para acompanhamento da adesão e
        impacto.';

    $cards = [
        [
            'title' => 'Lorem Ipsum',
            'description' => 'A empresa define o pacote ideal de horas e inicia o acompanhamento personalizado pela Flamma.'
        ],
        [
            'title' => 'Lorem Ipsum',
            'description' => 'Os colaboradores agendam seus atendimentos diretamente pela plataforma Flamma.'
        ],
        [
            'title' => 'Lorem Ipsum',
            'description' => 'Atendimentos individuais com até 60 minutos, online ou presenciais.'
        ],
        [
            'title' => 'Lorem Ipsum',
            'description' => 'Relatórios consolidados de uso para acompanhamento da adesão e impacto.'
        ],
    ];
@endphp

<section class="flex flex-col mx-auto mb-28 sm:mb-44 space-y-8 lg:space-y-16 scroll-mt-28" id="how-it-works">
    <x-headline class="max-w-full!" align="left" contentLayout="grid grid-cols-1 lg:grid-cols-[1fr_2fr] gap-4 lg:gap-12">
        <x-slot:title>
            {{ $title }}
        </x-slot:title>
        <x-slot:description>
            {{ $description }}
        </x-slot:description>
    </x-headline>

    <x-partials.card-grid>
        @foreach ($cards as $card)
            <x-card variant="light" density="compact">
                <x-slot:icon>
                    <x-badge color="primary">
                        {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                    </x-badge>
                </x-slot:icon>
                <x-slot:title>
                    {{ $card['title'] }}
                </x-slot:title>
                <x-slot:description>
                    {{ $card['description'] }}
                </x-slot:description>
            </x-card>
        @endforeach
    </x-partials.card-grid>
</section>

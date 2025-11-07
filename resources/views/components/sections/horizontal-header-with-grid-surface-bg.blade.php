@php
    $title = 'Pronto para transformar o bem-estar financeiro da sua equipe?';
    $keywords = ['bem-estar', 'financeiro', 'da', 'sua', 'equipe?'];
    $description = 'A empresa contrata um pacote de horas de atendimento (mensal, semestral ou anual). Os colaboradores
        agendam seus atendimentos diretamente pela plataforma Flamma. Você tem acesso à atendimentos individuais com
        até 60 minutos, online ou presenciais.  Relatórios consolidados de uso para acompanhamento da adesão e
        impacto.';

    $cards = [
        [
            'title' => 'Lorem Ipsum',
            'description' => 'A empresa define o pacote ideal de horas e inicia o acompanhamento personalizado pela Flamma.',
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
        [
            'title' => 'Lorem Ipsum',
            'description' => 'Relatórios consolidados de uso para acompanhamento da adesão e impacto.'
        ],
        [
            'title' => 'Lorem Ipsum',
            'description' => 'A empresa define o pacote ideal de horas e inicia o acompanhamento personalizado pela Flamma.',
        ],
    ];
@endphp

<div class="relative w-screen left-1/2 -ml-[50vw] mb-28 sm:mb-44 bg-elevation-01dp border border-tb border-outline-light" >
    <div class="mx-auto max-w-screen-2xl px-4 py-16 lg:px-8">
        <section class="flex flex-col space-y-8 lg:space-y-16 items-center justify-center">
            <x-headline
                class="max-w-full!"
                align="left"
                contentLayout="grid grid-cols-1 lg:grid-cols-[1.5fr_2fr] gap-4 lg:gap-12 items-center"
                :keywords="$keywords"
            >
                <x-slot:title>
                    {{ $title }}
                </x-slot:title>
                <x-slot:description>
                    {{ $description }}
                </x-slot:description>
            </x-headline>

            <x-partials.card-grid class="lg:grid-cols-3!">
                <x-card class="items-center justify-center" variant="primary">
                    <x-slot:title>
                        {{ $cards[0]['title'] }}
                    </x-slot:title>
                    <x-slot:description>
                        {{ $cards[0]['description'] }}
                    </x-slot:description>
                </x-card>

                @foreach (array_slice($cards, 1) as $card)
                    <x-card variant="white" density="compact">
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

            <x-partials.text-with-action-stacked
                text="Nosso propósito é disseminar conhecimento e ferramentas para que seus
                funcionários alcancem a tão desejada liberdade financeira,
                refletindo diretamente no sucesso da sua organização"
            />
        </section>
    </div>
</div>

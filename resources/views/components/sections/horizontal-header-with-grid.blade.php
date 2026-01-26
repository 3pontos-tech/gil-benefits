@php
    $title = 'A consultoria financeira que transforma vidas e impulsiona sua empresa';
    $keywords = ['impulsiona', 'sua', 'empresa'];
    $description = '';

    $cards = [
        [
            'title' => 'Para o RH e a Empresa',
            'description' => 'Mudanças visíveis na vida dos seus colaboradores:',
        ],
        [
            'title' => 'Aumento da Produtividade',
            'description' => 'Colaboradores com finanças organizadas são mais focados, engajados e produtivos.'
        ],
        [
            'title' => 'Redução do Absenteísmo e Turnover',
            'description' => 'Diminua as faltas e a rotatividade, criando um ambiente de trabalho mais estável e motivador.'
        ],
        [
            'title' => 'Melhora do Clima Organizacional',
            'description' => 'Funcionários mais seguros financeiramente contribuem para um ambiente de trabalho positivo e colaborativo.'
        ],
        [
            'title' => 'Atração e Retenção de Talentos',
            'description' => 'Ofereça um diferencial competitivo que valoriza o bem-estar integral dos seus colaboradores, atraindo e mantendo os melhores profissionais.'
        ],
        [
            'title' => 'Inovação e Responsabilidade Social',
            'description' => 'Posicione sua empresa como líder em benefícios e engajada com o desenvolvimento pessoal de sua equipe.',
        ],
    ];
@endphp

<section class="flex flex-col mx-auto mb-28 sm:mb-44 space-y-8 lg:space-y-16 items-center justify-center scroll-mt-28" id="assessment">
    <x-headline
        class="max-w-full!"
        align="center"
        contentLayout="grid grid-cols-1 justify-items-center text-center gap-4"
        :keywords="$keywords"
    >
        <x-slot:title>
            {{ $title }}
        </x-slot:title>
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

    <x-partials.text-with-action-stacked
        text="Nosso propósito é disseminar conhecimento e ferramentas para que seus funcionários alcancem a tão desejada liberdade financeira, refletindo diretamente no sucesso da sua organização."
    />
</section>

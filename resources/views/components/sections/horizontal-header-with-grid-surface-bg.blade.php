@php
    $title = 'Pronto para transformar o bem-estar financeiro da sua equipe?';
    $keywords = ['bem-estar', 'financeiro', 'da', 'sua', 'equipe?'];
    $description = 'Com a Flamma, você não oferece apenas um benefício; você oferece uma transformação. Estamos comprometidos em levar a educação financeira e a evolução da relação das pessoas com o dinheiro a um novo nível, disseminando algo bom e duradouro para todos.';

    $cards = [
        [
            'title' => 'Para os colaboradores',
            'description' => 'Mudanças visíveis na vida financeira de seus colaboradores, pois eles merecem estabilidade e tranquilidade.',
        ],
        [
            'title' => 'Educação Financeira Personalizada',
            'description' => 'Acesso a consultores especializados para sessões individuais de 1 hora, focadas nas necessidades específicas de cada um.'
        ],
        [
            'title' => 'Controle e Planejamento',
            'description' => 'Ferramentas e conhecimentos para gerenciar dívidas, criar orçamentos, planejar investimentos e alcançar objetivos financeiros.'
        ],
        [
            'title' => 'Redução do Estresse',
            'description' => 'Alívio da ansiedade e preocupação com dinheiro, promovendo maior bem-estar e qualidade de vida.'
        ],
        [
            'title' => 'Empoderamento Financeiro',
            'description' => 'Capacitação para tomar decisões financeiras mais conscientes e construir um futuro financeiro sólido.'
        ],
        [
            'title' => 'Melhora da Qualidade de Vida',
            'description' => 'Impacto positivo em todas as áreas da vida, desde a saúde mental até os relacionamentos pessoais.',
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
                text="Estamos comprometidos em levar a educação financeira e a evolução da relação das pessoas com o dinheiro a um novo nível, disseminando algo bom e duradouro para todos."
            />
        </section>
    </div>
</div>

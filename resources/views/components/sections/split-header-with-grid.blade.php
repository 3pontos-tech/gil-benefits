@php
    $cards = [
        [
            'title' => 'Queda na Produtividade',
            'description' => 'Colaboradores preocupados com dívidas ou com a falta de planejamento financeiro têm
                dificuldade em manter o foco e a performance no trabalho.'
        ],
        [
            'title' => 'Aumento do Absenteísmo e Turnover:',
            'description' => 'O estresse financeiro pode causar faltas no trabalho e até aumentar a rotatividade,
                gerando custos extras com recrutamento e treinamento.'
        ],
        [
            'title' => 'Clima Organizacional Afetado',
            'description' => 'A ansiedade e a insegurança financeira podem gerar um ambiente de trabalho tenso,
                impactando a colaboração e a satisfação geral da equipe.'
        ],
        [
            'title' => 'Dificuldade na Retenção de Talentos',
            'description' => 'Sem apoio ao bem-estar financeiro, empresas correm risco de perder talentos para concorrentes
                com benefícios mais atrativos.'
        ],
    ];
@endphp

<section class="flex flex-col mx-auto mb-28 sm:mb-44 gap-12 bg-gradient-to-r from-brand-primary to-brand-secondary p-8 lg:p-16 rounded-xl">
    <x-partials.split-image-with-headline class="lg:grid-cols-[1.5fr_2fr]!" />

    <x-partials.card-grid class="sm:grid-cols-1! lg:grid-cols-4! lg:col-span-2">
        @foreach ($cards as $card)
            <x-card variant="transparent" density="compact">
                <x-slot:icon>
                    <x-badge color="neutral">
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

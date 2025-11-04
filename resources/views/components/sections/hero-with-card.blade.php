@php
    $title = 'Ofereça educação financeira pessoal como benefício corporativo';
    $keywords = ['educação', 'financeira', 'benefício', 'corporativo'];
    $description = 'Em um mercado cada vez mais competitivo, reter talentos e garantir a produtividade da sua equipe são
        desafios constantes. Mas você já parou para pensar em como o bem-estar financeiro dos seus colaboradores impacta
        diretamente esses resultados?  O estresse com dinheiro é uma das principais causas de absenteísmo, baixa
        produtividade e rotatividade nas empresas. É aqui que a Flamma entra, oferecendo uma solução inovadora que beneficia
        a todos.';

    $cardTitle = 'Consultoria Financeira que se Torna o Benefício Mais Valioso para Seus Funcionários';
@endphp

<section class="flex flex-col w-full mx-auto mb-28 sm:mb-44">
    <div class="grid grid-cols-1 lg:grid-cols-[3fr_1fr] h-full gap-12 lg:gap-16">
        <x-headline class="lg:order-1 max-w-full!" align="left" :keywords="$keywords">
            <x-slot:title>
                {{ $title }}
            </x-slot:title>
            <x-slot:description>
                {{ $description }}
            </x-slot:description>
            <x-slot:actions>
                <x-button>
                    Saiba mais
                </x-button>
            </x-slot:actions>
        </x-headline>
        <div class="flex flex-col h-full overflow-hidden order-3 lg:order-2">
            <x-card class="h-full flex flex-col justify-between p-8">
                <x-slot:icon>
                    <x-badge size="md" icon="heroicon-o-check-circle" />
                </x-slot:icon>
                <x-slot:title>
                    {{ $cardTitle }}
                </x-slot:title>
            </x-card>
        </div>
        <div class="relative overflow-hidden lg:col-span-2 lg:order-3 h-[25vh] lg:h-[40vh] rounded-lg">
            <img
                src="{{ asset('img/woman-bg.png') }}"
                alt=""
                class="w-full h-full object-cover object-center"
                loading="eager"
                decoding="async"
            />
            <div class="absolute z-2 inset-0 bg-gradient-to-br from-brand-primary/32 to-brand-secondary/16"></div>
        </div>
    </div>
</section>

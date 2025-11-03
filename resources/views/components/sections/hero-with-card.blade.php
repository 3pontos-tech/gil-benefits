@php
    $title = 'Ofereça educação financeira pessoal como benefício corporativo';
    $keywords = ['educação', 'financeira', 'benefício', 'corporativo'];
    $description = 'Em um mercado cada vez mais competitivo, reter talentos e garantir a produtividade da sua equipe são
        desafios constantes. Mas você já parou para pensar em como o bem-estar financeiro dos seus colaboradores impacta
        diretamente esses resultados?  O estresse com dinheiro é uma das principais causas de absenteísmo, baixa
        produtividade e rotatividade nas empresas. É aqui que a Flamma entra, oferecendo uma solução inovadora que beneficia
        a todos.'
@endphp

<section class="w-full mx-auto mb-28 sm:mb-44 grid grid-cols-1 lg:grid-cols-[2fr_1fr] h-fit">
    <x-headline align="left" :keywords="$keywords">
        <x-slot:title>
            {{ $title }}
        </x-slot:title>
        <x-slot:description>
            {{ $description }}
        </x-slot:description>
    </x-headline>
</section>

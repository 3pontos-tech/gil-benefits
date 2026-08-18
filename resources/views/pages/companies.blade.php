<x-layouts.site title="Para Empresas" description="Consultoria financeira como benefício corporativo: menos estresse financeiro no time, mais produtividade e retenção.">
    {{--
        overflow-x-clip contém as faixas que sangram de ponta a ponta (`w-screen`) sem gerar
        barra de rolagem horizontal. O espaçamento vertical não é uniforme: o design vai de
        ~16px entre o hero e a seção seguinte até ~490px antes do simulador, então cada
        seção carrega a sua própria margem. Os valores de lg vêm das coordenadas do Figma:
        hero→investir 16 · investir→fluxo 0 · fluxo→consultor 0 · consultor→privacidade 80 ·
        privacidade→plano 80 · plano→contratar 215.

        O container é `max-w-[1800px]` com 62px de padding, e não 1676 direto: no Figma o
        CONTEÚDO tem 1676px, com 122px de margem lateral numa prancheta de 1920. 1800 − 2×62
        = 1676 de conteúdo, e (1920 − 1800)/2 + 62 = 122 de margem. Escrever `max-w-[1676px]`
        aqui deixaria o conteúdo com 1612px, ou seja ~4% mais estreito que o design.
    --}}
    <div class="overflow-x-clip">
        <div class="mx-auto max-w-[1800px] px-5 sm:px-8 lg:px-[62px]">
            <x-sections.companies.hero />
        </div>

        <div class="mx-auto mt-4 max-w-[1800px] px-5 sm:px-8 lg:px-[62px]">
            <x-sections.companies.why-invest />
        </div>

        <x-sections.companies.journey class="mt-16 lg:mt-0" />

        <div class="mx-auto mt-20 max-w-[1800px] px-5 sm:px-8 lg:mt-0 lg:px-[62px]">
            <x-sections.companies.real-consultant />
        </div>

        <div class="mx-auto mt-20 max-w-[1800px] px-5 sm:px-8 lg:mt-20 lg:px-[62px]">
            <x-sections.companies.privacy />
        </div>

        <div class="mx-auto mt-20 max-w-[1800px] px-5 sm:px-8 lg:mt-20 lg:px-[62px]">
            <x-sections.companies.plan />
        </div>

        <div class="mx-auto mt-20 max-w-[1800px] px-5 sm:px-8 lg:mt-[215px] lg:px-[62px]">
            <x-sections.companies.final-cta />
        </div>

        {{-- Onda em degraus que faz a transição para o rodapé (Vector 21 no design) --}}
        <x-sections.footer-wave />
    </div>
</x-layouts.site>

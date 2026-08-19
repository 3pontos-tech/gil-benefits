{{--
    Hero. No design: texto de 822px à esquerda, foto de 654x912 à direita, com três
    decorativos — seta rosa sangrando na margem esquerda, bloco em gradiente atrás do topo
    da foto e asterisco sobre a parte de baixo dela. No mobile o design não exibe a foto.
--}}
<section class="relative pt-6 lg:pt-10" id="empresas">
    {{-- Seta apontando para ↘, sangrando na esquerda (image 8 no design). Só no desktop:
         no mobile o título ocupa a largura toda e ela caía sobre as primeiras linhas. --}}
    <x-graphism
        type="arrow"
        data-fm-static
        class="absolute -left-24 -top-2 -z-10 hidden rotate-180 lg:block lg:w-[274px]"
    />

    {{-- Bloco em gradiente atrás do topo da foto --}}
    <div
        class="absolute right-0 top-0 -z-10 hidden h-[241px] w-[610px] lg:block"
        aria-hidden="true"
        style="background: linear-gradient(126.25deg, #F1785A 0.36%, #FD0342 34.87%, #FF7B33 69.62%)"
    ></div>

    <div class="relative grid grid-cols-1 items-center gap-8 lg:grid-cols-[1fr_654px] lg:gap-12">
        <div class="flex flex-col gap-4 lg:max-w-[822px] lg:gap-8">
            <h1 class="text-[32px] font-bold leading-[1.5] text-dark lg:text-5xl">
                O <span class="text-brand-primary">bem-estar financeiro</span> do seu time em forma de
                <span class="text-brand-primary">benefício</span>
            </h1>

            <p class="text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal">
                Se seu colaborador está organizado financeiramente, a empresa tem menos turnover, mais
                produtividade e uma cultura mais forte.
            </p>

            <x-button
                class="mt-2 w-full! sm:w-fit!"
                variant="flat"
                rounded="none"
                href="#simulador"
            >
                Simular contratação
            </x-button>
        </div>

        <div class="relative hidden lg:block">
            <div class="aspect-[654/912] w-full overflow-hidden">
                <img
                    src="{{ asset('img/companies/hero.webp') }}"
                    alt="Consultora financeira em atendimento a uma colaboradora"
                    class="size-full object-cover object-center"
                    loading="eager"
                    fetchpriority="high"
                    decoding="async"
                />
            </div>

            {{-- Asterisco sobre a parte de baixo da foto, transbordando à direita --}}
            <x-graphism data-fm-static class="absolute -right-16 bottom-8 w-[200px] xl:w-[280px]" />
        </div>
    </div>
</section>

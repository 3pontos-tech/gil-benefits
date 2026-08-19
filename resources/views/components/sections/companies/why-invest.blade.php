{{--
    "Por que investir na saúde financeira". Foto de 706x981 à esquerda com dois recortes
    retangulares na cor do fundo mordendo os cantos (motivo do design), texto de 822px à
    direita e asterisco rosa sangrando embaixo. No mobile o design mostra apenas o texto.
--}}
<section class="relative scroll-mt-28" id="por-que-investir">
    <div class="grid grid-cols-1 items-center gap-8 lg:grid-cols-[706px_1fr] lg:gap-[154px]">
        <div class="relative hidden aspect-[706/981] w-full lg:block">
            <img
                src="{{ asset('img/companies/why-invest.webp') }}"
                alt="Equipe de colaboradores trabalhando em um escritório"
                class="size-full object-cover object-center"
                loading="lazy"
                decoding="async"
            />

            {{-- Recortes na cor do fundo, como no design --}}
            <div class="absolute right-0 top-0 h-[14.5%] w-[52%] bg-elevation-surface" aria-hidden="true"></div>
            <div class="absolute bottom-0 left-0 h-[15%] w-[52%] bg-elevation-surface" aria-hidden="true"></div>
        </div>

        <div class="relative flex flex-col gap-4 lg:max-w-[822px]">
            <h2 class="text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                Por que investir na <span class="text-brand-primary">saúde financeira</span> dos seus
                colaboradores?
            </h2>

            <p class="text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal">
                O estresse financeiro reduz foco, memória e capacidade de decisão, e isso aparece direto nos
                indicadores de RH, com mais erros, menos produtividade, mais faltas e mais pedidos de demissão.
            </p>

            {{-- Asterisco abaixo do texto; a faixa em gradiente logo depois corta a metade de baixo, como no design --}}
            <x-graphism
                data-fm-static
                class="absolute -bottom-[340px] right-0 -z-10 hidden w-[347px] xl:block"
            />
        </div>
    </div>
</section>

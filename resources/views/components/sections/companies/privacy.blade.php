{{--
    "Privacidade em primeiro lugar" — Frame 20377 no Figma.

    A foto sangra de ponta a ponta e traz o recorte em degrau no alpha do arquivo, que é
    o mesmo do bloco de privacidade da /colaborador (img/privacy.webp): um bloco estreito no topo
    à direita, o bloco largo no meio e, embaixo, a faixa que atravessa a tela inteira.
    O texto fica sobre a parte vazada do recorte, à esquerda.

    Medidas do arquivo (1920 × 711), que são as que ancoram os grafismos:

        bloco estreito   x ≥ 78,2%    até y 13,5%
        bloco largo      x ≥ 57,55%   até y 80,31%
        faixa cheia      x ≥ 0        de  y 80,31%

    A foto começa 100px acima do texto (no Figma, foto em y=50 e título em y=150) e
    passa por baixo da seção — daí o min-h, que reserva a altura dela.

    ESCALA — a foto (e com ela a seta) acompanha a largura da tela, então o texto tem
    de acompanhar junto: com tamanhos fixos, abaixo de 1920 o parágrafo passava por
    cima da diagonal da seta. As medidas saem em vw da prancheta de 1920 (44px de
    título = 44/19.2 = 2.2917vw), a mesma referência da foto — em cqw da seção elas
    cairiam sobre os 1676px do conteúdo e o título ficava com 38px em vez de 44. O
    min()/clamp() congela nos valores do Figma a partir de 1920 e mantém um piso de
    legibilidade nas larguras baixas.
--}}
<section class="relative scroll-mt-28 lg:min-h-[611px]" id="privacidade">
    {{-- Bloco de mídia: full-bleed, atrás do conteúdo. --}}
    <div
        data-privacy-media
        class="pointer-events-none absolute -top-[100px] left-1/2 -z-10 -ml-[50vw] hidden w-screen lg:block"
        aria-hidden="true"
    >
        <div class="relative">
            <img
                src="{{ asset('img/privacy.webp') }}"
                alt=""
                class="block aspect-[1920/711] w-full"
                loading="lazy"
                decoding="async"
            />

            {{--
                A seta se encaixa no vértice do degrau: a base dela encosta no topo da
                faixa cheia (80,31%) e a lateral direita avança 1% para dentro do bloco
                largo (57,55%). Ancorada por baixo e pela direita, o encaixe se mantém
                em qualquer largura. Não flutua (`data-fm-static`) para não sair do lugar.

                Abaixo de xl ela sai de cena: com a foto já bem baixa, o parágrafo (que
                tem piso de 16px) chegaria a encostar no braço de baixo da seta.
            --}}
            <x-graphism
                type="arrow"
                data-fm-static
                class="absolute bottom-[19.69%] right-[41.5%] hidden w-[17.4%] rotate-180 xl:block"
            />
        </div>
    </div>

    <div
        data-privacy-text
        class="flex flex-col items-center gap-4 text-center
            lg:w-[min(42.8125vw,822px)] lg:items-start lg:gap-[min(1.6667vw,32px)] lg:pt-[min(2.6042vw,50px)] lg:text-left"
    >
        <h2 class="text-[32px] font-bold leading-[1.5] text-dark lg:text-[clamp(28px,2.2917vw,44px)]">
            <span class="text-orange-primary">Privacidade</span> em primeiro lugar.
        </h2>

        <p class="text-base font-medium leading-[1.5] text-medium lg:text-[clamp(16px,1.0417vw,20px)] lg:font-normal lg:text-dark">
            A empresa contrata o benefício, mas quem decide o que compartilhar em cada sessão é o
            colaborador, e essa informação fica só entre ele e o consultor.
        </p>

        <x-button
            class="w-full! sm:w-fit! lg:w-[clamp(250px,17.3958vw,334px)]!"
            variant="flat"
            rounded="none"
            href="#simulador"
        >
            Simular contratação
        </x-button>
    </div>
</section>

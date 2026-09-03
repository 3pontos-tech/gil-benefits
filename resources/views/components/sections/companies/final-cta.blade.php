{{--
    CTA de fechamento — Frame 20220 no Figma, 1676 × 655.

    As colunas não são 50/50: o design usa 64 / 754,139 / 32 / 761,861 / 64. E a foto
    não respeita o padding vertical do card — ela tem 556,746 de altura num card cujo
    miolo teria 527, então estoura ~15px para cima e para baixo. Por isso as duas
    colunas são posicionadas em % do card em vez de resolvidas por flex:

        texto  left 3,8186%   centralizado na vertical   44,9964% de largura
        foto   left 50,7243%  top 7,5003%                45,4571% × 85,0009%

    O card tem aspect-ratio fixo, então encolhe com a tela; a tipografia precisa
    encolher junto, daí sair em cqw da prancheta de 1920 (48px de título = 2,5cqw)
    com max() de piso. Antes, com tamanhos fixos, o conteúdo não caber na coluna de
    altura fixa esmagava o botão — em 1440 ele ficava com 26px de altura.

    No mobile o design ordena texto → foto → botão, e aí o fluxo normal resolve.
--}}
<section class="scroll-mt-28 @container" id="contratar">
    <div
        class="flex flex-col gap-4 border border-outline-light bg-elevation-surface p-4
            lg:relative lg:block lg:aspect-[1676/655] lg:p-0"
    >
        <div
            class="flex flex-col gap-4
                lg:absolute lg:left-[3.8186%] lg:top-1/2 lg:w-[44.9964%] lg:-translate-y-1/2 lg:gap-[1.6667cqw]"
        >
            <div class="flex flex-col gap-4 lg:gap-[1.8229cqw]">
                <h2 class="text-[32px] font-bold leading-[1.5] text-dark lg:text-[max(28px,2.5cqw)]">
                    Leve <span class="text-brand-primary">saúde financeira</span> para dentro da sua empresa.
                </h2>

                <p class="text-base font-medium leading-[1.5] text-medium lg:text-[max(16px,1.0417cqw)] lg:font-normal lg:text-dark">
                    Menos turnover, mais produtividade, e um time que sente que a empresa se importa de verdade.
                </p>
            </div>

            {{-- No mobile a foto entra entre o texto e o botão --}}
            <div data-cta-photo class="aspect-[762/557] w-full overflow-hidden lg:hidden">
                <img
                    src="{{ asset('img/companies/cta.webp') }}"
                    alt="Pessoa depositando uma moeda em um cofre de porquinho"
                    class="size-full object-cover object-center"
                    loading="lazy"
                    decoding="async"
                />
            </div>

            <x-button
                class="w-full! shrink-0 sm:w-fit! lg:w-[max(250px,17.4%)]!"
                variant="flat"
                size="xl"
                href="#simulador"
            >
                Simular contratação
            </x-button>
        </div>

        <div
            data-cta-photo
            class="hidden overflow-hidden
                lg:absolute lg:left-[50.7243%] lg:top-[7.5003%] lg:block lg:h-[85.0009%] lg:w-[45.4571%]"
        >
            <img
                src="{{ asset('img/companies/cta.webp') }}"
                alt="Pessoa depositando uma moeda em um cofre de porquinho"
                class="size-full object-cover object-center"
                loading="lazy"
                decoding="async"
            />
        </div>
    </div>
</section>

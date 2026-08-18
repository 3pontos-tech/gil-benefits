{{--
    CTA de fechamento — Frame 20220 no Figma, 1676 × 655.

    As colunas não são 50/50: o design usa 64 / 754,139 / 32 / 761,861 / 64. E a foto
    não respeita o padding vertical do card — ela tem 556,746 de altura num card cujo
    miolo teria 527, então estoura ~15px para cima e para baixo. Por isso as duas
    colunas são posicionadas em % do card em vez de resolvidas por flex:

        texto  left 3,8186%   top 25,0382%   44,9964% × 49,9237%
        foto   left 50,7243%  top 7,5003%    45,4571% × 85,0009%

    No mobile o design ordena texto → foto → botão, e aí o fluxo normal resolve.
--}}
<section class="scroll-mt-28" id="contratar">
    <div
        class="flex flex-col gap-4 border border-outline-light bg-elevation-01dp p-4
            lg:relative lg:block lg:aspect-[1676/655] lg:p-0"
    >
        <div
            class="flex flex-col gap-4
                lg:absolute lg:left-[3.8186%] lg:top-[25.0382%] lg:h-[49.9237%] lg:w-[44.9964%] lg:gap-8"
        >
            <div class="flex flex-col gap-4 lg:gap-[35px]">
                <h2 class="text-[32px] font-bold leading-[1.5] text-dark lg:text-5xl">
                    Leve <span class="text-orange-primary">saúde financeira</span> para dentro da sua empresa.
                </h2>

                <p class="text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal lg:text-dark">
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
                class="w-full! sm:w-fit! lg:w-[334px]!"
                variant="flat"
                rounded="none"
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

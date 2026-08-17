{{--
    "Privacidade em primeiro lugar" — Frame 20377 no Figma.

    O palco é o container (1676 × 732) e tudo dentro dele é posicionado em %:

        texto   left 0        top 10,2459% (75/732)    width 49,0453% (822/1676)
        foto    top 0         width 67,2984% (1127,921/1676)   height 100%
        seta    left -0,0443% top 41,2568% (302/732)   width 29,8252% da FOTO

    A foto sangra para fora do container à direita: no Figma ela termina em 1799,42
    dentro de uma prancheta de 1920, ou seja 1,42px para fora. Ancorá-la pela direita
    com --companies-gutter mantém esse sangramento constante em qualquer largura;
    a aproximação anterior (-mr-16/-mr-32/-mr-56 por breakpoint) mudava a cada faixa.

    A seta é ancorada na foto, não no palco — por isso ela mora dentro do bloco de mídia.
    No mobile o design centraliza o texto e não usa a foto.
--}}
<section class="relative scroll-mt-28" id="privacidade">
    <div class="flex flex-col gap-8 lg:relative lg:block lg:aspect-[1676/732]">
        <div
            data-privacy-text
            class="flex flex-col items-center gap-4 text-center
                lg:absolute lg:left-0 lg:top-[10.2459%] lg:w-[49.0453%] lg:items-start lg:gap-8 lg:text-left"
        >
            <h2 class="text-[32px] font-bold leading-[1.5] text-dark lg:text-[44px]">
                <span class="text-brand-primary">Privacidade</span> em primeiro lugar.
            </h2>

            <p class="text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal lg:text-dark">
                A empresa contrata o benefício, mas quem decide o que compartilhar em cada sessão é o
                colaborador, e essa informação fica só entre ele e o consultor.
            </p>

            <x-button
                class="w-full! sm:w-fit! lg:w-[334px]!"
                variant="flat"
                rounded="none"
                href="#simulador"
            >
                Simular contratação
            </x-button>
        </div>

        {{-- Bloco de mídia = bounds do Vector 20, ancorado pela direita para manter o sangramento. --}}
        <div
            data-privacy-media
            class="pointer-events-none absolute top-0 hidden h-full w-[67.2984%] lg:block"
            style="right: calc(-1.42px - var(--companies-gutter))"
        >
            <img
                src="{{ asset('img/companies/privacy.webp') }}"
                alt="Colaborador em uma sessão de consultoria financeira por vídeo"
                class="absolute inset-0 size-full"
                loading="lazy"
                decoding="async"
            />

            <x-graphism
                type="arrow"
                class="absolute left-[-0.0443%] top-[41.2568%] h-[45.9601%] w-[29.8252%] rotate-180"
            />
        </div>
    </div>
</section>

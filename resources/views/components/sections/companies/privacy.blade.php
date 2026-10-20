<section class="relative scroll-mt-28 lg:min-h-[min(38.125vw,732px)]" id="privacidade">
    <div
        data-privacy-media
        class="pointer-events-none absolute top-0 right-[calc(50%-50vw)] -z-10 hidden w-[min(60.156vw,1155px)] lg:block"
        aria-hidden="true"
    >
        <img
            src="{{ asset('img/companies/privacy.webp') }}"
            alt=""
            class="block aspect-[1155/732] w-full"
            loading="lazy"
            decoding="async"
        />

        <x-graphism
            type="collaborator-arrow"
            data-fm-static
            class="absolute left-0 top-[36.2%] hidden w-[28.05%] xl:block"
        />
    </div>

    <div
        data-privacy-text
        class="flex flex-col items-center gap-4 text-center
            lg:w-[min(42.8125vw,822px)] lg:items-start lg:gap-[min(1.6667vw,32px)] lg:pt-[min(3.9063vw,75px)] lg:text-left"
    >
        <h2 class="text-[32px] font-bold leading-[1.5] text-dark lg:text-[clamp(28px,2.2917vw,44px)]">
            <span class="text-brand-primary">Privacidade</span> em primeiro lugar.
        </h2>

        <p class="text-base font-medium leading-[1.5] text-medium lg:text-[clamp(16px,1.0417vw,20px)] lg:font-normal lg:text-dark">
            A vida financeira é pessoal. A Flamma respeita a confidencialidade das informações dos
            colaboradores e a proteção dos dados individuais.
        </p>

        <x-button
            class="w-full! sm:w-fit! lg:w-[clamp(250px,17.3958vw,334px)]!"
            variant="flat"
            href="#proposta"
        >
            Simular contratação
        </x-button>
    </div>
</section>

<section class="relative scroll-mt-28 lg:min-h-[calc(37.0313vw-30px)] xl:min-h-[calc(37.0313vw-100px)]" id="privacidade">
    <div
        data-privacy-media
        class="pointer-events-none absolute -top-[30px] left-1/2 -z-10 -ml-[50vw] hidden w-screen lg:block xl:-top-[100px]"
        aria-hidden="true"
    >
        <img
            src="{{ asset('img/privacy.webp') }}"
            alt=""
            class="block aspect-[1920/711] w-full"
            loading="lazy"
            decoding="async"
        />
    </div>

    <div
        data-privacy-text
        class="flex flex-col items-center gap-4 text-center
            lg:w-[calc(41vw-62px)] lg:items-start lg:gap-[min(1.6667vw,32px)] lg:pt-[min(2.6042vw,50px)] lg:text-left
            xl:w-[min(42.8125vw,822px)]"
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

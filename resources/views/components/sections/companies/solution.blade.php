<section class="relative scroll-mt-28" id="proposta">
    <svg
        class="pointer-events-none absolute left-1/2 top-[240px] -z-10 -ml-[50vw] hidden h-[1001px] w-screen lg:block"
        viewBox="0 0 1919 1001"
        fill="none"
        preserveAspectRatio="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
    >
        <path
            d="M1660 458.92H957.5V498.575H231.5V711.5H111V794.5H43V946.5H-6V360.36H166V396.55H231.5L1469.72 394.308H1635.76V0.77L1919 0V1001H1660V458.92Z"
            fill="#39393A"
        />
    </svg>
    <x-graphism type="asterisk" class="absolute left-1/2 top-[565px] -z-10 ml-[calc(50vw-346px)] hidden size-[340px] lg:block" />

    <div class="flex flex-col items-center gap-8 border border-outline-light bg-elevation-01dp p-6 lg:flex-row lg:p-16">
        <div class="flex flex-col gap-8 lg:flex-1">
            <div class="flex flex-col gap-4 lg:gap-[35px]">
                <h2 class="text-[32px] font-bold leading-[1.5] text-dark lg:text-5xl">
                    Uma <span class="text-brand-primary">solução</span> para diferentes realidades
                    <span class="text-brand-primary">corporativas</span>.
                </h2>
                <p class="text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal lg:text-dark">
                    Converse com nosso time e descubra como incorporar a saúde financeira à estratégia de saúde e
                    bem-estar da sua organização.
                </p>
            </div>

            <x-button
                class="w-full! sm:w-fit! lg:w-[334px]!"
                variant="flat"
                size="xl"
                :href="config('services.whatsapp.url')"
                target="_blank"
                rel="noopener noreferrer"
            >
                Solicitar uma proposta
            </x-button>
        </div>

        <div data-solution-photo class="aspect-[758/527] w-full overflow-hidden lg:flex-1">
            <img
                src="{{ asset('img/companies/solution.webp') }}"
                alt="Colegas conversando em volta de uma mesa com notebooks"
                class="size-full object-cover"
                loading="lazy"
                decoding="async"
            />
        </div>
    </div>
</section>

{{--
    "O plano acompanha o crescimento do seu time". O design traz o título centralizado e, abaixo,
    um bloco de 1670x804 com foto, apoiado numa massa cinza-escura em degraus (Vector 11) que
    sangra de ponta a ponta. O simulador funcional do projeto entra logo depois, porque a cópia
    promete "simule o valor para o seu time abaixo".
--}}
<section class="relative scroll-mt-28" id="simulador">
    {{-- Massa escura em degraus atrás do bloco da foto, sangrando de ponta a ponta --}}
    <div class="absolute inset-x-0 bottom-0 left-1/2 -z-10 -mx-[50vw] hidden h-[70%] w-screen lg:block" aria-hidden="true">
        <svg
            class="size-full"
            viewBox="0 0 1925 1300"
            fill="none"
            preserveAspectRatio="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                d="M1666 771.5L1666 682L1666 596H1564.56H1263H963.5L963.5 647.5H481H0V468H90.7357H172L172 515H237.5L1475.72 512.088H1641.76V393L1641.76 1L1925 0L1925 1300H1843.79H1825H1802.68H1666L1666 830V809.5L1666 771.5Z"
                fill="#39393A"
            />
        </svg>
    </div>

    <div class="relative flex flex-col gap-8 lg:gap-20">
        <header class="flex flex-col gap-4 text-center lg:gap-8">
            <h2 class="text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                O plano acompanha o <span class="text-brand-primary">crescimento</span> do seu time
            </h2>

            <p class="text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal">
                Simule o valor para o seu time abaixo. Ficou com alguma dúvida? É só falar com a gente.
            </p>
        </header>

        <div class="aspect-[1670/804] w-full overflow-hidden">
            <img
                src="{{ asset('img/companies/plan.webp') }}"
                alt="Colaboradora conversando com uma consultora financeira"
                class="size-full object-cover object-center"
                loading="lazy"
                decoding="async"
            />
        </div>

        <livewire:pricing-calculator />
    </div>
</section>

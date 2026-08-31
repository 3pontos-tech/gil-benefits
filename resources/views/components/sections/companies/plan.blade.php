{{--
    "O plano acompanha o crescimento do seu time". A foto de 1670x804 do design saiu:
    o título centralizado emenda direto no simulador. A massa cinza-escura em degraus
    (Vector 11) ficou, reancorada no fim da seção — a coluna da direita sobe ao lado
    do card do simulador e a faixa da esquerda cai no vão antes do CTA de fechamento,
    descendo por trás dele.
--}}
<section class="relative scroll-mt-28" id="simulador">
    {{-- Seta ↖ encostada na borda esquerda, na altura do título --}}
    <x-graphism type="arrow" data-fm-static class="absolute -top-16 left-1/2 -z-10 -ml-[50vw] hidden w-[270px] lg:block" />

    {{--
        Massa escura em degraus. O palco tem altura fixa em px (o preserveAspectRatio="none"
        estica só na horizontal): o topo fica 450px acima do fim da seção, de modo que a
        faixa esquerda do desenho (y 468–647 do palco) cai no vão de 215px entre o simulador
        e o CTA de fechamento, e a coluna da direita (x ≥ 1641) sobe ao lado do card —
        que é opaco e esconde o miolo. A base (1300 − 450 = 850px abaixo da seção) termina
        rente ao fim do CTA, que passa por cima dela como um card claro.
    --}}
    <div
        class="pointer-events-none absolute left-1/2 top-[calc(100%-450px)] -z-10 -ml-[50vw] hidden h-[1300px] w-screen lg:block"
        aria-hidden="true"
    >
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

        <livewire:pricing-calculator />
    </div>
</section>

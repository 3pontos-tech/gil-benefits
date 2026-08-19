@php
    $highlights = [
        [
            'title' => 'Consultor humano',
            'description' => 'Cada sessão é conduzida por um especialista que escuta antes de orientar.',
        ],
        [
            'title' => 'Personalização real',
            'description' => 'O ponto de partida é sempre a realidade financeira do colaborador.',
        ],
        [
            'title' => 'Acompanhamento contínuo',
            'description' => 'O foco é construir uma mudança de comportamento duradoura.',
        ],
    ];
@endphp

{{--
    "Mais que uma ferramenta".

    No desktop esta seção não é um grid: o design monta tudo sobre um palco que é o
    próprio grafismo escuro em degraus (Vector 20 no Figma), 1309 × 763 ancorado na
    borda esquerda da prancheta de 1920 — ou seja, 68,18% da largura, sangrando para
    fora do container. Cada peça é posicionada em % desse palco:

        painel translúcido 1   left 34,4%   top 25,8%   527 × 176
        painel translúcido 2   left 34,4%   top 57,4%   527 × 173
        box do título          left 18,6%   top 31,7%   700 × 184
        box do texto           left 18,6%   top 60,0%   700 × 112

    Os painéis translúcidos não são um simples deslocamento dos boxes: ficam à direita
    e ACIMA deles, e é isso que dá o escalonamento do design.

    Os pilares ficam fora do palco, à direita (x 1098 de 1920 = 57,19%), sobre o fundo
    claro — daí o texto escuro. No mobile nada disso se aplica: os grafismos somem e o
    conteúdo volta a empilhar, e as regras absolutas só entram no lg.

    A ordem no DOM é a ordem de pintura (grafismos → painéis → boxes → pilares), por
    isso não há z-index aqui.

    ESCALA — as massas e os boxes estão em % do palco, então acompanham a largura da
    tela; a tipografia, os paddings e os gaps precisam acompanhar junto, senão em
    qualquer viewport abaixo de 1920 o título ganha uma linha extra e vaza para fora
    do box branco. Por isso a seção é um container (`@container`) e essas medidas
    estão em cqw da prancheta de 1920: 40px de título = 40/1920 = 2.0833cqw, e assim
    por diante. Em 1920 os valores caem exatamente nos do Figma.

    Os `max()` são o piso de legibilidade: puro cqw levaria o corpo a 8px numa tela de
    1024. Quando o piso morde, os boxes crescem em vez de cortar o texto — daí eles
    serem `min-h` e não `h`.
--}}
<section
    class="relative scroll-mt-28 @container lg:left-1/2 lg:-mx-[50vw] lg:w-screen"
    id="consultor"
>
    <div class="flex flex-col gap-8 lg:relative lg:block lg:aspect-[1309/763] lg:w-[68.18%]">
        {{-- Massa escura em degraus: é o próprio palco. --}}
        <svg
            class="absolute inset-0 hidden size-full lg:block"
            viewBox="0 0 1309 763"
            fill="none"
            preserveAspectRatio="none"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <path
                d="M0 763L0.000222181 0H413.664H1309V178.419H1157.75V240.577H1043.22V676.764H918.105V763H0Z"
                fill="#39393A"
            />
        </svg>

        {{-- Massa em degradê por cima da escura, espelhada na vertical (Vector 9: 1041 × 727). --}}
        <svg
            class="absolute left-0 top-0 hidden h-[95.3%] w-[79.5%] -scale-y-100 lg:block"
            viewBox="0 0 1041 727"
            fill="none"
            preserveAspectRatio="none"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <path
                d="M211.481 0V121.774H368.385V446.263H810.166V635.5H1041V727H810.166H789.507H0L3.41098 0H211.481Z"
                fill="url(#consultantBlockGradient)"
            />
            <defs>
                <linearGradient
                    id="consultantBlockGradient"
                    x1="0" y1="-46.2636" x2="1163.42" y2="424.349"
                    gradientUnits="userSpaceOnUse"
                >
                    <stop offset="0.154200" stop-color="#FD0342"/>
                    <stop offset="0.845800" stop-color="#FF803C"/>
                </linearGradient>
            </defs>
        </svg>

        <div
            class="absolute left-[34.4%] top-[25.8%] hidden h-[23.1%] w-[40.3%] bg-white/32 lg:block"
            aria-hidden="true"
        ></div>
        <div
            class="absolute left-[34.4%] top-[57.4%] hidden h-[22.7%] w-[40.3%] bg-white/32 lg:block"
            aria-hidden="true"
        ></div>

        <div
            data-tool-box
            class="bg-elevation-01dp p-4
                lg:absolute lg:left-[18.6%] lg:top-[31.7%] lg:flex lg:min-h-[24.1%] lg:w-[53.5%] lg:items-center lg:p-[1.6667cqw]"
        >
            <h2 class="text-[32px] font-bold leading-[1.5] text-dark lg:text-[max(24px,2.0833cqw)]">
                Mais que uma ferramenta, um
                <span class="text-brand-primary">consultor de verdade</span>
            </h2>
        </div>

        <div
            data-tool-box
            class="bg-elevation-01dp p-4
                lg:absolute lg:left-[18.6%] lg:top-[60%] lg:flex lg:min-h-[14.7%] lg:w-[53.5%] lg:items-center lg:p-[1.6667cqw]"
        >
            <p class="text-base font-medium leading-[1.5] text-dark lg:text-[max(14px,0.8333cqw)]">
                Cada colaborador tem uma conversa real com alguém que entende sua situação financeira,
                muito além do que qualquer planilha consegue oferecer.
            </p>
        </div>
    </div>

    {{--
        Pilares: x 1098 de 1920 (57,19%) até a borda do conteúdo em 1798, com 32px de
        recuo interno — Frame 20265 no Figma tem 700 de largura e o conteúdo começa em 32.
        O topo fica em 344/763 = 45,1% do palco, não centralizado: no design o bloco
        pende para baixo. A borda direita acompanha o container, e não um breakpoint —
        ver --companies-gutter.
    --}}
    <ul
        class="mt-8 flex flex-col gap-6
            lg:absolute lg:left-[57.19%] lg:top-[45.1%] lg:mt-0 lg:gap-[1.6667cqw] lg:px-[1.6667cqw]"
        style="right: var(--companies-gutter)"
    >
        @foreach ($highlights as $highlight)
            {{-- Mobile empilha o grafismo acima do texto; no desktop ele fica ao lado. --}}
            {{-- No Figma o marcador ocupa uma caixa de 46px com o grafismo de 38 centralizado. --}}
            <li class="flex flex-col gap-4 lg:flex-row lg:items-center lg:gap-[0.8333cqw]">
                <span class="flex shrink-0 items-center justify-center lg:w-[max(28px,2.3958cqw)]">
                    <x-graphism class="w-[38px] shrink-0 lg:w-[max(23px,1.9792cqw)]" />
                </span>

                <div class="flex flex-col gap-1">
                    <h3 class="text-base font-bold leading-[1.5] text-high lg:text-[max(18px,1.25cqw)] lg:text-dark">
                        {{ $highlight['title'] }}
                    </h3>
                    <p class="text-base font-medium leading-[1.5] text-medium lg:text-[max(14px,0.8333cqw)] lg:text-dark">
                        {{ $highlight['description'] }}
                    </p>
                </div>
            </li>
        @endforeach
    </ul>
</section>

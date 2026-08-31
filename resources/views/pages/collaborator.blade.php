<x-layouts.site title="Para Colaboradores" description="Consultoria financeira individual, pensada para quem quer organizar as finanças e planejar o futuro com apoio de especialistas.">
{{--
    Página do colaborador — Figma node 8298:3441 ("Pagina colaborador")
    Ordem e copy conforme o Figma. Classes fm-* animadas por flamma-motion.

    ATENÇÃO — reuso: as seções 4 (cards do fluxo), 5 (consultor + pilares) e
    6 (privacidade) são os MESMOS blocos da página /para-empresas, com copy
    ligeiramente diferente. Depois que as duas páginas estiverem no ar, valem
    ser extraídas para componentes Blade. Veja o HANDOFF-colaborador.md.

    Assets novos em public/img/colaborador/ e public/svg/colaborador/.
    Assets reaproveitados de public/img/companies/ e public/svg/companies/.
--}}

<div class="fm-page overflow-x-clip">

    {{-- ══════════════════════════════════════════════════════════════
         1. HERO — 8298:3487 sobre Vector 17 (8298:3486)
         O Vector 17 é uma foto 1920×1156 com recorte em degrau no alpha
         do PNG — mesma técnica do bloco de privacidade da /para-empresas.
         ══════════════════════════════════════════════════════════════ --}}
    {{-- A altura acompanha a foto de fundo (1920 × 1136 = 59,1667vw), que é w-full/h-auto:
         com a altura fixa em px, abaixo de 1920 sobrava fundo claro sob a imagem. --}}
    <section id="colaborador" class="relative scroll-mt-28 lg:left-1/2 lg:-mx-[50vw] lg:h-[59.1667vw] lg:w-screen">
        {{-- -top-1.5: as primeiras ~4 linhas de pixels do arquivo são brancas e abriam
             um fio entre o topo da foto e a navbar — subir 6px as esconde sob ela. --}}
        <div class="absolute inset-x-0 -top-1.5 -z-10 hidden lg:block" aria-hidden="true">
            <img src="{{ asset('img/colaborador/hero-bg.webp') }}" alt=""
                 class="h-auto w-full" fetchpriority="high" decoding="async">

            {{--
                Grafismo do Figma: banda vermelha de 30px contornando o degrau entre o
                painel claro e a foto, mais o bloco solto ao lado da faixa inferior direita.
                As coordenadas vêm do recorte medido no próprio hero-bg.webp (palco
                1920×1136): borda em x=1174 (y 0–382), x=1036 (y 382–758), full-bleed em
                y=758 e o bloco da direita entre y 1002–1112. As bordas externas avançam
                6px para dentro da foto: cravadas na fronteira exata, o anti-aliasing do
                arquivo deixava um fio claro entre o vermelho e a imagem. Como o SVG usa
                o mesmo palco da foto, o encaixe acompanha qualquer largura.
            --}}
            <svg class="absolute inset-0 h-auto w-full text-brand-primary" viewBox="0 0 1920 1136"
                 fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1180 0V388H1042V764H0V728H1006V352H1144V0Z" fill="currentColor"/>
                <path d="M1144 996H1180V1112H1144V996Z" fill="currentColor"/>
            </svg>
        </div>

        {{-- O texto acompanha a foto, que escala com a viewport: em px fixos ele descia
             sobre a imagem nas larguras menores. Medidas do nó: recuo de 143, título 48,
             corpo 20, coluna de 826. --}}
        <div class="mx-auto max-w-[1800px] px-5 pb-16 pt-10 sm:px-8 lg:px-[62px] lg:pb-0 lg:pt-[min(7.4479vw,143px)]">
            <div class="flex flex-col gap-8 lg:max-w-[min(43.0208vw,826px)]">
                <div class="flex flex-col gap-4">
                    <h1 class="fm-reveal fm-in text-[32px] font-bold leading-[1.5] text-dark lg:text-[clamp(28px,2.5vw,48px)]">
                        <span class="text-brand-primary">Cuide do seu dinheiro</span> com a ajuda de um especialista.
                    </h1>
                    <p class="fm-reveal fm-in text-base font-medium leading-[1.5] text-medium lg:max-w-[min(30.1042vw,578px)] lg:text-[clamp(16px,1.0417vw,20px)]"
                       data-fm-delay="1">
                        Consultoria financeira individual, pensada para quem quer organizar suas
                        finanças e planejar o futuro com apoio de quem realmente entende do assunto.
                    </p>
                </div>

                <div class="fm-reveal fm-in flex flex-col gap-4 sm:flex-row sm:gap-8" data-fm-delay="2">
                    <x-button
                        class="fm-btn w-full! sm:w-[273px]!"
                        variant="light-brand"
                        size="xl-tight"
                        href="#planos"
                    >
                        Conhecer planos
                    </x-button>
                    <x-button
                        class="fm-btn w-full! sm:w-[273px]!"
                        variant="flat"
                        size="xl-tight"
                        href="https://wa.me/5511976205711?text=Flamma"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Indicar minha empresa
                    </x-button>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         2. O QUE É O FLAMMA? — 8298:3443
         ══════════════════════════════════════════════════════════════ --}}
    <section id="o-que-e" class="relative mx-auto mt-16 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[75px] lg:px-[62px]">
        {{--
            Grafismo: seta ↘ (Group 31818, 324 × 337), encostada na borda direita da
            viewport e começando 48px acima do título — no Figma ela acompanha este
            bloco, e não a seção seguinte, onde estava. Estática: encaixada numa
            composição, flutuar só a tiraria do lugar.

            Só a partir de xl: abaixo disso a coluna de texto (822px fixos) chega perto
            demais da diagonal da seta.
        --}}
        <x-graphism
            type="collaborator-arrow"
            data-fm-static
            class="absolute -top-12 right-1/2 -z-10 -mr-[50vw] hidden w-[324px] xl:block"
        />

        <div class="flex flex-col gap-4 lg:max-w-[822px] lg:gap-7">
            <h2 class="fm-reveal text-center text-[32px] font-bold leading-[1.5] text-high lg:text-left lg:text-[64px]">
                O que é o <span class="text-brand-primary">Flamma</span>?
            </h2>
            <p class="fm-reveal text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal" data-fm-delay="1">
                O Flamma é um benefício corporativo de saúde financeira, oferecido pela sua
                empresa, que conecta você a consultores especializados para organizar suas
                finanças, sair das dívidas e planejar o futuro com mais segurança.
            </p>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         3. DO ACESSO À PRIMEIRA SESSÃO — 8451:1039
         Mesmos 3 cards da /para-empresas (mesmas fotos), com rótulos
         e textos próprios do colaborador.
         ══════════════════════════════════════════════════════════════ --}}
    <section id="como-funciona" class="relative mx-auto mt-20 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[103px] lg:px-[62px]">
        {{--
            Vector 13 (8298:3468) — massa escura de 1927×1302 que no Figma começa em
            y=1916, ou seja 319px dentro desta seção, passa por trás do rodapé dos cards
            e desce até depois dos pilares. Os três painéis translúcidos (Rectangle
            1253/1254/1255) moram sobre ela, em % do próprio palco.
        --}}
        <div class="absolute left-1/2 top-[319px] -z-10 -ml-[50vw] hidden w-screen lg:block"
             aria-hidden="true">
            <div class="relative aspect-[1927/1302] w-full">
                <svg class="absolute inset-0 size-full" viewBox="0 0 1927 1302" fill="none"
                     preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1685.75 317.824H1779.88V246.186L1779.88 204.681H1809.31H1927V586.184H1199.36V641.619H1027.23V711.836H910.5V1204.58H746.017V1302H0V0H127.17V98.3607H224.418V350.801V388.326H336.232H1685.85C1685.85 368.426 1686.04 354.915 1685.75 317.824Z"
                          fill="#39393A"/>
                </svg>
                {{-- Só o painel do alto mora na massa; os outros dois acompanham os
                     boxes da seção "Mais que uma ferramenta". --}}
                <div class="absolute left-[73.28%] top-[20.43%] h-[21.35%] w-[27.19%] bg-white/32"></div>
            </div>
        </div>

        {{--
            O botão "Fazer meu cadastro" que o Figma punha abaixo do título migrou para
            a seção "Mais que uma ferramenta", abaixo do card da citação.
        --}}
        <div class="flex flex-col items-center gap-8 text-center">
            <h2 class="fm-reveal w-full text-[32px] font-bold leading-[1.5] text-high lg:text-[64px]">
                Do acesso à primeira sessão
            </h2>
        </div>

        {{--
            Mesmos cards da /para-empresas: conferido no Figma que a geometria é idêntica
            (Group 31943 em 33,33 com 482,228 × 327,671, Ellipse 114 em 95,85/309,38,
            produto em 51,53/65,08 com 428,746 × 278,836). Só mudam ícone, título e texto —
            aqui é Contratação / Ativação / Consultoria.
        --}}
        <div class="mt-8 grid grid-cols-1 gap-8 lg:mt-14 lg:grid-cols-3">
            @foreach ([
                [
                    'icon' => 'handshake.svg',
                    'image' => 'card-contract.webp',
                    'title' => 'Contratação',
                    'description' => 'Sua empresa contrata o pacote de horas disponível pro time.',
                    'stage' => [
                        'shadow' => ['shape' => 'ellipse', 'left' => 62.85, 'top' => 276.38, 'width' => 340.109, 'height' => 26.601],
                        'prod' => ['left' => 18.53, 'top' => 32.08, 'width' => 428.746, 'height' => 278.836],
                    ],
                ],
                [
                    'icon' => 'check-circle.svg',
                    'image' => 'card-reality.webp',
                    'title' => 'Ativação',
                    'description' => 'Você recebe seu acesso e agenda no horário que preferir.',
                    'stage' => [
                        'shadow' => ['shape' => 'ellipse', 'left' => 145.85, 'top' => 284.32, 'width' => 190.523, 'height' => 26.601],
                        'prod' => ['left' => 57.45, 'top' => 0, 'width' => 376.992, 'height' => 327.671],
                    ],
                ],
                [
                    'icon' => 'users.svg',
                    'image' => 'card-secrecy.webp',
                    'title' => 'Consultoria',
                    'description' => 'Sessões individuais e sigilosas, só entre você e o consultor.',
                    'stage' => [
                        'shadow' => [
                            'shape' => 'polygon',
                            'left' => 134.01,
                            'top' => 227.87,
                            'width' => 297.171,
                            'height' => 80.73,
                            'clipPath' => 'polygon(0% 37.2196%, 56.6631% 0%, 100% 67.2519%, 44.2263% 100%)',
                        ],
                        'prod' => ['left' => 34.61, 'top' => 0, 'width' => 396.575, 'height' => 327.671],
                    ],
                ],
            ] as $i => $card)
                <x-partials.illustration-card
                    class="fm-card fm-reveal"
                    :data-fm-delay="$i + 1"
                    :icon="$card['icon']"
                    :image="$card['image']"
                    :stage="$card['stage']"
                    :title="$card['title']"
                    :description="$card['description']"
                />
            @endforeach
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         4. MAIS QUE UMA FERRAMENTA — 8298:3472 + pilares 8298:3653
         Mesmo bloco da /para-empresas.
         ══════════════════════════════════════════════════════════════ --}}
    {{--
        A composição é a mesma da /para-empresas, mas aqui a massa escura em degraus vem
        da seção anterior (Vector 13). Medidas do Figma, em px de 1920:

            box do título     123 → 829 (706 de largura), 184 de altura
            box do texto      123 → 829, 116 de altura, 30 abaixo do primeiro
            painel 1          524 × 179, 216 à direita e 56 acima do box do título
            painel 2          526 × 177, 71 à esquerda e 35 abaixo do topo do box do texto
            pilares           583 de largura (1140 → 1723), 89 abaixo do topo do box

        Os painéis translúcidos ficam à direita e ACIMA / à esquerda e ABAIXO dos boxes —
        é esse desencontro que dá o escalonamento do design; por isso são ancorados a
        cada box e não à massa, onde antes estavam (e desalinhavam quando a altura do
        conteúdo acima mudava).

        As medidas saem em vw, e não em % da seção, porque é assim que a massa escala:
        ela é full-bleed (w-screen), então abaixo de 1800 — onde a seção deixa de ser
        1800 e passa a acompanhar a tela — medir pela seção fazia os boxes crescerem em
        relação ao palco. O min()/clamp() congela tudo nos valores do Figma a partir de
        1920 e mantém um piso de legibilidade nas larguras baixas.

        O mesmo motivo explica o mt em calc(): o degrau da massa que abre este palco
        desce 0,3695 por px de largura (ela é aspect-[1927/1302] e o degrau está a
        54,68% da altura), enquanto o topo dos boxes só desce 0,184 — o resto do
        caminho é conteúdo de altura fixa. Sem compensar essa diferença, o vão entre o
        degrau e o box do título ia de 52px em 1920 para 170px em 1280, que é o
        "cinza sobrando acima dos cards". calc(18.55vw - 69px) dá 287px em 1920 (a
        medida do Figma) e acompanha a massa nas outras larguras.
    --}}
    <section id="consultor" class="relative mx-auto mt-20 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[calc(18.55vw-69px)] lg:px-[62px]">
        <div class="flex flex-col items-start gap-10 lg:flex-row lg:justify-between lg:gap-16">
            <div class="flex w-full flex-col gap-8 lg:w-[min(36.7708vw,706px)] lg:gap-[min(1.5625vw,30px)]">
                <div class="relative">
                    {{-- painel translúcido, à direita e acima do box --}}
                    <div
                        class="absolute hidden bg-white/32 lg:block
                            lg:left-[min(11.25vw,216px)] lg:top-[calc(min(2.9167vw,56px)*-1)] lg:h-[min(9.3229vw,179px)] lg:w-[min(27.2917vw,524px)]"
                        aria-hidden="true"
                    ></div>

                    <div
                        data-tool-box
                        class="fm-reveal-left relative bg-elevation-01dp p-6
                            lg:flex lg:min-h-[min(9.5833vw,184px)] lg:items-center lg:p-[min(1.6667vw,32px)]"
                    >
                        <h2 class="text-[32px] font-bold leading-[1.5] text-dark lg:text-[clamp(24px,2.0833vw,40px)]">
                            Mais que uma ferramenta, um <span class="text-brand-primary">consultor de verdade</span>
                        </h2>
                    </div>
                </div>

                <div class="relative">
                    {{-- painel translúcido, à esquerda e abaixo do box --}}
                    <div
                        class="absolute hidden bg-white/32 lg:block
                            lg:left-[calc(min(3.6979vw,71px)*-1)] lg:top-[min(1.8229vw,35px)] lg:h-[min(9.2188vw,177px)] lg:w-[min(27.3958vw,526px)]"
                        aria-hidden="true"
                    ></div>

                    <div
                        data-tool-box
                        class="fm-reveal-left relative bg-elevation-01dp p-6
                            lg:flex lg:min-h-[min(6.0417vw,116px)] lg:items-center lg:p-[min(1.6667vw,32px)]"
                        data-fm-delay="1"
                    >
                        <p class="text-base font-medium leading-[1.5] text-dark lg:text-[clamp(14px,0.8333vw,16px)]">
                            Cada colaborador tem uma conversa real com alguém que entende sua situação
                            financeira, muito além do que qualquer planilha consegue oferecer.
                        </p>
                    </div>
                </div>

                {{-- Veio da seção "Do acesso à primeira sessão", onde ficava abaixo do título.
                     border-0!: sobre a massa escura, a borda outline-light do flat vira um
                     contorno branco — aqui o botão é só o vermelho chapado. --}}
                <x-button
                    class="fm-reveal-left fm-btn border-0! w-full! sm:w-[273px]!"
                    data-fm-delay="2"
                    variant="flat"
                    size="xl-tight"
                    href="/app/register"
                >
                    Fazer meu cadastro
                </x-button>
            </div>

            <ul class="fm-reveal-right flex w-full flex-col gap-8
                lg:me-[min(3.9062vw,75px)] lg:mt-[min(4.6354vw,89px)] lg:w-[min(30.3646vw,583px)] lg:gap-[min(2.6042vw,50px)]">
                @foreach ([
                    ['Consultor humano', 'Cada sessão é conduzida por um especialista que escuta antes de orientar.'],
                    ['Personalização real', 'O ponto de partida é sempre a realidade financeira do colaborador.'],
                    ['Acompanhamento contínuo', 'O foco é construir uma mudança de comportamento duradoura.'],
                ] as $pilar)
                    <li class="fm-pillar flex flex-col gap-4 lg:flex-row lg:items-center lg:gap-[min(1.25vw,24px)]">
                        <x-graphism class="w-[46px] lg:w-[clamp(28px,2.1354vw,41px)]" />

                        <div class="flex flex-col gap-1">
                            <h3 class="text-xl font-bold leading-[1.5] text-high lg:text-[clamp(18px,1.25vw,24px)]">{{ $pilar[0] }}</h3>
                            <p class="text-base font-medium leading-[1.5] text-medium lg:text-[clamp(14px,0.8333vw,16px)]">{{ $pilar[1] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         5. PRIVACIDADE EM PRIMEIRO LUGAR — 8298:3446
         Mesmo bloco da /para-empresas, com o mesmo arquivo (img/privacy.webp) e as
         mesmas medidas de recorte:

             bloco estreito   x ≥ 78,2%    até y 13,5%
             bloco largo      x ≥ 57,55%   até y 80,31%
             faixa cheia      x ≥ 0        de  y 80,31%

         A foto sangra de ponta a ponta e traz o recorte em degrau no alpha, e a seta se
         encaixa no vértice onde a foto se alarga — por isso ela mora dentro do bloco de
         mídia, ancorada por baixo e pela direita, e não flutua (`data-fm-static`).

         A tipografia sai em vw porque a foto (e com ela a seta) escala pela viewport: com
         tamanhos fixos, o parágrafo passava por cima da diagonal.
         ══════════════════════════════════════════════════════════════ --}}
    <section id="privacidade" class="relative mx-auto mt-20 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[295px] lg:min-h-[calc(37.0313vw-91px)] lg:px-[62px]">
        <div class="pointer-events-none absolute -top-[91px] left-1/2 -z-10 -ml-[50vw] hidden w-screen lg:block"
             aria-hidden="true">
            <div class="relative">
                <img src="{{ asset('img/privacy.webp') }}" alt=""
                     class="block aspect-[1920/711] w-full" loading="lazy" decoding="async">

                {{-- Base 0,4% abaixo do topo da faixa (19,69%), como na /para-empresas:
                     encostar exato deixava um fio branco de arredondamento. O right-[42.3%]
                     é o limite à esquerda: deixa a lateral 0,15% sobre o bloco da foto
                     (x ≥ 57,55%) — qualquer valor maior abre um vão branco entre eles. --}}
                <x-graphism
                    type="collaborator-arrow-alt"
                    data-fm-static
                    class="absolute bottom-[19.29%] right-[42.3%] hidden w-[17.4%] xl:block"
                />
            </div>
        </div>

        <div class="flex flex-col gap-8 lg:ml-[min(6.4063vw,123px)] lg:w-[min(36.3021vw,697px)] lg:max-w-none">
            <div class="flex flex-col gap-4">
                <h2 class="fm-reveal-left text-[32px] font-bold leading-[1.5] text-dark lg:text-[clamp(28px,2.2917vw,44px)]">
                    <span class="text-brand-primary">Privacidade</span> em primeiro lugar.
                </h2>
                <p class="fm-reveal-left text-base font-medium leading-[1.5] text-medium lg:text-[clamp(14px,0.8333vw,16px)] lg:font-normal"
                   data-fm-delay="1">
                    A empresa contrata o benefício, mas quem decide o que compartilhar em cada
                    sessão é o colaborador, e essa informação fica só entre ele e o consultor.
                </p>
            </div>
            <x-button
                class="fm-reveal-left fm-btn w-full! shrink-0 sm:w-[189px]!"
                data-fm-delay="2"
                variant="flat"
                size="xl-tight"
                href="#planos"
            >
                Simular contratação
            </x-button>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         6. ESCOLHA SEU PLANO — 8298:3587
         Bloco NOVO. Preço em gradiente rose→orange via bg-clip-text.
         ══════════════════════════════════════════════════════════════ --}}
    {{-- O design pedia 440px de respiro após a Privacidade; encurtado para 160. --}}
    <section id="planos" class="relative mx-auto mt-20 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[160px] lg:px-[62px]">
        {{--
            Vector 15 (8298:3477): 1920×419. Ancorado pela base da seção (= base dos
            cards), como no Figma: a faixa desce 78px além dos cards, o bloco alto da
            direita sobe ao lado do segundo card e os degraus da esquerda cortam o
            terço de baixo do primeiro. Altura fixa em px (o preserveAspectRatio="none"
            estica só na horizontal): com aspect-ratio, abaixo de 1920 a faixa encolhia
            e terminava no meio dos cards. O painel translúcido do canto inferior
            direito é o Rectangle do frame, em % do palco.
        --}}
        <div class="absolute left-1/2 top-[calc(100%-341px)] -z-10 -ml-[50vw] hidden h-[419px] w-screen lg:block"
             aria-hidden="true">
            <svg class="absolute inset-0 size-full" viewBox="0 0 1920 419" fill="none"
                 preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 419V341.461V271.675H88V300.106H215.5V341.461H297H571.947V279.34H758.593V250.029H945.739V176.751H1129.88V116.691H1336.54V26.4208H1467H1591V0H1920V419H0Z"
                      fill="url(#colabPlansGradient)"/>
                <defs>
                    <linearGradient id="colabPlansGradient" x1="0" y1="192.987" x2="418.405" y2="985.625"
                                    gradientUnits="userSpaceOnUse">
                        <stop stop-color="#FD0342"/>
                        <stop offset="1" stop-color="#FF803C"/>
                    </linearGradient>
                </defs>
            </svg>

            <div class="absolute left-[72.7%] top-[58.7%] h-[41.3%] w-[27.3%] bg-white/32"></div>
        </div>
        <header class="mx-auto flex flex-col gap-4 text-center lg:max-w-[1418px]">
            <h2 class="fm-reveal text-[32px] font-bold leading-[1.5] text-dark lg:text-5xl">
                Escolha <span class="text-brand-primary">seu</span> plano
            </h2>
            <p class="fm-reveal mx-auto text-base font-medium leading-[1.5] text-medium lg:max-w-[1418px] lg:text-xl"
               data-fm-delay="1">
                Cada pacote foi pensado para acompanhar você no seu ritmo, unindo sessões com um
                consultor financeiro, materiais exclusivos e suporte personalizado via IA para
                tornar suas decisões financeiras mais simples.
            </p>
        </header>

        <div class="mx-auto mt-10 grid grid-cols-1 gap-8 lg:mt-16 lg:max-w-[1418px] lg:grid-cols-2">
            @foreach ([
                ['preco' => 'R$ 179,90', 'sessoes' => '1 Sessão/mês'],
                ['preco' => 'R$ 279,90', 'sessoes' => '2 Sessões/mês'],
            ] as $i => $plano)
                <div class="fm-card fm-reveal flex flex-col items-center justify-center gap-8 overflow-hidden border border-outline-light bg-elevation-01dp p-8"
                     data-fm-delay="{{ $i + 1 }}">
                    <div class="flex w-full flex-col text-center leading-[1.5]">
                        <p class="text-base font-medium text-dark">Mensal</p>
                        {{-- gradiente do Figma: 165.6deg, #fd0342 15.42% → #ff803c 84.58% --}}
                        <p class="bg-gradient-to-br from-brand-primary to-brand-secondary bg-clip-text text-[36px] font-bold leading-[1.5] text-transparent lg:text-5xl">
                            {{ $plano['preco'] }}
                        </p>
                    </div>
                    <ul class="flex w-full flex-col gap-8">
                        @foreach ([
                            ['calendar-check', $plano['sessoes']],
                            ['book-open-text', 'Materiais exclusivos'],
                            ['robot', 'Suporte personalizado via IA'],
                        ] as $item)
                            <li class="flex items-center gap-2.5 rounded">
                                <img src="{{ asset('svg/colaborador/' . $item[0] . '.svg') }}" alt=""
                                     class="size-6 shrink-0" loading="lazy" decoding="async">
                                <span class="flex-1 text-base font-medium leading-[1.5] text-dark">{{ $item[1] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         7. SUA EMPRESA JÁ TEM O FLAMMA? — 8451:1092
         Foto (Vector 16) sangra à direita, 700×587.
         ══════════════════════════════════════════════════════════════ --}}
    <section id="cadastro" class="relative mx-auto mt-20 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[159px] lg:px-[62px]">
        {{--
            A foto tem 700 × 587 e encosta na borda direita do conteúdo; o recorte em
            degrau (17,43% × 36,97% do arquivo, canto inferior esquerdo) vem no alpha.
            As colunas e a tipografia saem em vw porque a foto é medida em px do design:
            com a coluna de texto fixa em 822px, abaixo de 1800 a foto era empurrada
            para fora da tela — em 1440 ela terminava em 1648, com o conteúdo em 1378.
        --}}
        <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-[1fr_min(36.4583vw,700px)] lg:gap-[min(3.3333vw,64px)]">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col gap-4">
                    <h2 class="fm-reveal-left text-[32px] font-bold leading-[1.5] text-high lg:max-w-[min(42.8125vw,822px)] lg:text-[clamp(28px,2.2917vw,44px)]">
                        Sua empresa já tem o <span class="text-brand-primary">Flamma</span>?
                    </h2>
                    <p class="fm-reveal-left text-base font-medium leading-[1.5] text-medium lg:max-w-[min(30.1042vw,578px)] lg:text-[clamp(14px,0.8333vw,16px)] lg:font-normal"
                       data-fm-delay="1">
                        Se o benefício já está disponível na sua empresa, cadastre-se agora e
                        marque seu primeiro atendimento com um consultor!
                    </p>
                </div>
                <x-button
                    class="fm-reveal-left fm-btn w-full! shrink-0 sm:w-[184px]!"
                    data-fm-delay="2"
                    variant="flat"
                    size="xl-tight"
                    href="/app/register"
                >
                    Fazer meu cadastro
                </x-button>
            </div>

            <div class="fm-reveal-right fm-zoom hidden aspect-[700/587] w-full lg:ml-auto lg:block">
                <img src="{{ asset('img/colaborador/cadastro.webp') }}"
                     alt="Colaborador acessando a plataforma"
                     class="size-full object-cover" loading="lazy" decoding="async">
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         8. VOCÊ E SEUS COLEGAS — 8451:1101
         Foto à ESQUERDA (708×588), texto à direita.
         ══════════════════════════════════════════════════════════════ --}}
    <section id="indicar" class="relative mx-auto mt-20 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[80px] lg:px-[62px]">
        {{--
            Como nas outras seções desta página, colunas e tipografia saem em vw: com a
            foto e a coluna de texto em px fixos, abaixo de 1800 a soma não cabia mais no
            conteúdo. Medidas do nó, em px de 1920: foto 708 × 589, vão de 142 entre as
            colunas, título 44, corpo 16.
        --}}
        <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-[min(36.875vw,708px)_1fr] lg:gap-[min(7.3958vw,142px)]">
            <div class="fm-zoom-scope relative">
                <div class="fm-reveal-left fm-zoom aspect-[708/589] w-full overflow-hidden">
                    <img src="{{ asset('img/colaborador/colegas.webp') }}"
                         alt="Colegas de trabalho conversando sobre finanças"
                         class="size-full object-cover" loading="lazy" decoding="async">
                </div>

                {{--
                    Grafismo: a mesma seta grossa da marca espelhada na vertical (aponta ↙),
                    apoiada no canto inferior esquerdo da foto — 230px no Figma, 23 para fora
                    dela à esquerda — e passando POR CIMA da imagem (por isso vem depois dela
                    no DOM). O fm-zoom-follow a amplia junto com o zoom da foto no hover.
                    O bottom de 2,5vw (eram os 55px/2,8646vw do Figma) deixa o braço
                    horizontal ~4px sobreposto à base da foto: com o valor original ele
                    começava 3px abaixo dela, abrindo um fio branco.
                --}}
                <x-graphism
                    type="arrow"
                    data-fm-static
                    class="fm-zoom-follow absolute bottom-[calc(min(2.5vw,48px)*-1)] left-[calc(min(1.1979vw,23px)*-1)] hidden w-[min(11.9792vw,230px)] -scale-y-100 lg:block"
                />
            </div>

            <div class="flex flex-col gap-8">
                <div class="flex flex-col gap-8">
                    <h2 class="fm-reveal-right text-[32px] font-bold leading-[1.5] text-high lg:text-[clamp(28px,2.2917vw,44px)]">
                        Você e seus colegas com mais <span class="text-brand-primary">organização financeira</span>.
                    </h2>
                    <p class="fm-reveal-right text-base font-medium leading-[1.5] text-medium lg:max-w-[min(30.1042vw,578px)] lg:text-[clamp(14px,0.8333vw,16px)] lg:font-normal"
                       data-fm-delay="1">
                        Indique o Flamma para o RH da sua empresa e dê o primeiro passo para seu
                        bem-estar financeiro.
                    </p>
                </div>
                <x-button
                    class="fm-reveal-right fm-btn w-full! sm:w-[207px]!"
                    data-fm-delay="2"
                    variant="flat"
                    size="xl-tight"
                    href="https://wa.me/5511976205711?text=Flamma"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Indicar minha empresa
                </x-button>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         9. COMECE AGORA — 8451:1110
         Card final; foto 761×556 à direita. Mesma foto da CTA da
         /para-empresas (magnific_a-photorealistic-cinemati).
         ══════════════════════════════════════════════════════════════ --}}
    <section id="comecar" class="relative mx-auto mt-20 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[109px] lg:px-[62px]">
        <div class="fm-reveal-scale flex flex-col items-center gap-8 border border-outline-light bg-elevation-01dp p-6 lg:flex-row lg:gap-8 lg:p-16">
            <div class="flex flex-col gap-8 lg:flex-1">
                <div class="flex flex-col gap-4 lg:gap-[22px]">
                    <h2 class="text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                        Comece <span class="text-brand-primary">agora</span>
                    </h2>
                    <p class="text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal">
                        Marque sua primeira sessão com um consultor Flamma e comece a organizar
                        suas finanças com quem entende do assunto!
                    </p>
                </div>
                <x-button
                    class="fm-btn w-full! sm:w-[248px]!"
                    variant="flat"
                    size="xl-tight"
                    href="/app/register"
                >
                    Agende sua primeira sessão
                </x-button>
            </div>

            <div class="fm-zoom aspect-[762/557] w-full overflow-hidden lg:max-w-[762px] lg:flex-1">
                <img src="{{ asset('img/companies/cta.webp') }}"
                     alt="Pessoa depositando uma moeda em um cofre de porquinho"
                     class="size-full object-cover" loading="lazy" decoding="async">
            </div>
        </div>
    </section>


    {{-- Onda em degraus que faz a transição para o rodapé (Vector 20 no design),
         encurtada como na home para o card final ficar mais perto do rodapé
         (o design pedia 317px de respiro + onda de 329px) --}}
    <x-sections.footer-wave top-margin="mt-12 lg:mt-10" height="h-[80px] lg:h-[180px]" />
</div>
</x-layouts.site>

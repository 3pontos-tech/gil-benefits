<x-filament-panels::page full-height="true" class="scroll-smooth">
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
    <section id="colaborador" class="relative scroll-mt-28 lg:left-1/2 lg:-mx-[50vw] lg:h-[1156px] lg:w-screen">
        <div class="absolute inset-x-0 top-0 -z-10 hidden lg:block" aria-hidden="true">
            <img src="{{ asset('img/colaborador/hero-bg.webp') }}" alt=""
                 class="h-auto w-full" fetchpriority="high" decoding="async">
        </div>

        <div class="mx-auto max-w-[1800px] px-5 pb-16 pt-10 sm:px-8 lg:px-[62px] lg:pb-0 lg:pt-[143px]">
            <div class="flex flex-col gap-8 lg:max-w-[826px]">
                <div class="flex flex-col gap-4">
                    <h1 class="fm-reveal fm-in text-[32px] font-bold leading-[1.5] text-dark lg:text-5xl">
                        <span class="text-brand-primary">Cuide do seu dinheiro</span> com a ajuda de um especialista.
                    </h1>
                    <p class="fm-reveal fm-in text-base font-medium leading-[1.5] text-medium lg:max-w-[578px] lg:text-xl"
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
                        rounded="none"
                        href="#planos"
                    >
                        Conhecer planos
                    </x-button>
                    <x-button
                        class="fm-btn w-full! sm:w-[273px]!"
                        variant="flat"
                        size="xl-tight"
                        rounded="none"
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
        <div class="flex flex-col gap-4 lg:max-w-[822px] lg:gap-7">
            <h2 class="fm-reveal text-[32px] font-bold leading-[1.5] text-high lg:text-[64px]">
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
        {{-- Grafismo: seta (Group 31818), canto superior direito --}}
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
                <div class="absolute left-[73.28%] top-[20.43%] h-[21.35%] w-[27.19%] bg-white/32"></div>
                <div class="absolute left-[17.4%] top-[52.69%] h-[13.52%] w-[27.19%] bg-white/32"></div>
                <div class="absolute left-[2.66%] top-[75.81%] h-[13.52%] w-[27.19%] bg-white/32"></div>
            </div>
        </div>

        <img src="{{ asset('svg/colaborador/deco-arrow-1.svg') }}" alt="" aria-hidden="true"
             class="fm-bob pointer-events-none absolute -top-24 right-0 -z-10 hidden w-[303px] lg:block"
             loading="lazy" decoding="async">

        {{--
            No Figma o título ocupa a largura toda (1676, centralizado) e o botão fica
            ABAIXO dele, também centralizado — Frame 20222 em x=701,5 com 273 de largura
            dá centro 838, que é 1676/2. O Blade original punha os dois lado a lado.
        --}}
        <div class="flex flex-col items-center gap-8 text-center">
            <h2 class="fm-reveal w-full text-[32px] font-bold leading-[1.5] text-high lg:text-[64px]">
                Do acesso à primeira sessão
            </h2>
            <x-button
                class="fm-reveal fm-btn w-full! sm:w-[273px]!"
                data-fm-delay="1"
                variant="flat"
                size="xl-tight"
                rounded="none"
                href="/app/register"
            >
                Fazer meu cadastro
            </x-button>
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
    <section id="consultor" class="relative mx-auto mt-20 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[287px] lg:px-[62px]">
        <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-2 lg:gap-16">
            <div class="flex flex-col gap-8">
                <div class="fm-reveal-left relative bg-elevation-01dp p-6 lg:p-8">
                    <h2 class="text-[32px] font-bold leading-[1.5] text-dark lg:text-[40px]">
                        Mais que uma ferramenta, um <span class="text-brand-primary">consultor de verdade</span>
                    </h2>
                </div>
                <div class="fm-reveal-left relative bg-elevation-01dp p-6 lg:p-8" data-fm-delay="1">
                    <p class="text-base font-medium leading-[1.5] text-dark">
                        Cada colaborador tem uma conversa real com alguém que entende sua situação
                        financeira, muito além do que qualquer planilha consegue oferecer.
                    </p>
                </div>
            </div>

            <ul class="fm-reveal-right flex flex-col gap-8 lg:ml-auto lg:w-[700px] lg:px-8 lg:py-[39px]">
                @foreach ([
                    ['Consultor humano', 'Cada sessão é conduzida por um especialista que escuta antes de orientar.'],
                    ['Personalização real', 'O ponto de partida é sempre a realidade financeira do colaborador.'],
                    ['Acompanhamento contínuo', 'O foco é construir uma mudança de comportamento duradoura.'],
                ] as $pilar)
                    <li class="fm-pillar flex flex-col gap-4 lg:flex-row lg:items-center lg:gap-6">
                        <svg aria-hidden="true" viewBox="0 0 347 347" fill="none" xmlns="http://www.w3.org/2000/svg"
                             class="pointer-events-none w-[46px] shrink-0 text-brand-primary">
                            <g stroke="currentColor" stroke-width="61.0935">
                                <path d="M173.444 0V347"></path>
                                <path d="M347 173.438H0"></path>
                                <path d="M296.133 50.7803L50.7661 296.147"></path>
                                <path d="M296.22 296.155L50.854 50.7891"></path>
                            </g>
                        </svg>
                        <div class="flex flex-col gap-1 lg:w-[574px]">
                            <h3 class="text-xl font-bold leading-[1.5] text-high lg:text-2xl">{{ $pilar[0] }}</h3>
                            <p class="text-base font-medium leading-[1.5] text-medium lg:text-xl">{{ $pilar[1] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         5. PRIVACIDADE EM PRIMEIRO LUGAR — 8298:3446
         Mesmo bloco da /para-empresas, texto em coluna de 693px.
         ══════════════════════════════════════════════════════════════ --}}
    <section id="privacidade" class="relative mx-auto mt-20 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[295px] lg:px-[62px]">
        {{-- Grafismo: seta (Group 31819) --}}
        {{--
            Vector 21 (8451:1090) — a foto desta seção sangra de ponta a ponta e traz o
            recorte em degrau no alpha do PNG, como o bloco de privacidade da /para-empresas.
            Começa 91px acima do texto (Figma: foto em 3315, texto em 3406).
        --}}
        <div class="absolute left-1/2 -top-[91px] -z-10 -ml-[50vw] hidden w-screen lg:block"
             aria-hidden="true">
            <img src="{{ asset('img/colaborador/privacidade.webp') }}" alt=""
                 class="block aspect-[1920/711] w-full" loading="lazy" decoding="async">
        </div>

        <img src="{{ asset('svg/colaborador/deco-arrow-2.svg') }}" alt="" aria-hidden="true"
             class="fm-bob pointer-events-none absolute -bottom-20 left-1/2 -z-10 hidden w-[300px] lg:block"
             loading="lazy" decoding="async">

        <div class="flex flex-col gap-8 lg:ml-32 lg:w-[693px] lg:max-w-none">
            <div class="flex flex-col gap-4">
                <h2 class="fm-reveal-left text-[32px] font-bold leading-[1.5] text-dark lg:text-[44px]">
                    <span class="text-brand-primary">Privacidade</span> em primeiro lugar.
                </h2>
                <p class="fm-reveal-left text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal"
                   data-fm-delay="1">
                    A empresa contrata o benefício, mas quem decide o que compartilhar em cada
                    sessão é o colaborador, e essa informação fica só entre ele e o consultor.
                </p>
            </div>
            <x-button
                class="fm-reveal-left fm-btn w-full! sm:w-[189px]!"
                data-fm-delay="2"
                variant="flat"
                size="xl-tight"
                rounded="none"
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
    <section id="planos" class="relative mx-auto mt-20 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[440px] lg:px-[62px]">
        {{-- Vector 15 (8298:3477): 1920×419, começa 200px dentro desta seção. --}}
        <div class="absolute left-1/2 top-[200px] -z-10 -ml-[50vw] hidden w-screen lg:block"
             aria-hidden="true">
            <svg class="block aspect-[1920/419] w-full" viewBox="0 0 1920 419" fill="none"
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
        <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-[822px_1fr] lg:gap-16">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col gap-4">
                    <h2 class="fm-reveal-left text-[32px] font-bold leading-[1.5] text-high lg:text-[44px]">
                        Sua empresa já tem o <span class="text-brand-primary">Flamma</span>?
                    </h2>
                    <p class="fm-reveal-left text-base font-medium leading-[1.5] text-medium lg:max-w-[578px] lg:text-xl lg:font-normal"
                       data-fm-delay="1">
                        Se o benefício já está disponível na sua empresa, cadastre-se agora e
                        marque seu primeiro atendimento com um consultor!
                    </p>
                </div>
                <x-button
                    class="fm-reveal-left fm-btn w-full! sm:w-[184px]!"
                    data-fm-delay="2"
                    variant="flat"
                    size="xl-tight"
                    rounded="none"
                    href="/app/register"
                >
                    Fazer meu cadastro
                </x-button>
            </div>

            <div class="fm-reveal-right fm-zoom hidden aspect-[700/587] w-full overflow-hidden lg:ml-auto lg:block lg:w-[700px]">
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
        <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-[708px_1fr] lg:gap-[142px]">
            <div class="fm-reveal-left fm-zoom aspect-[708/589] w-full overflow-hidden">
                <img src="{{ asset('img/colaborador/colegas.webp') }}"
                     alt="Colegas de trabalho conversando sobre finanças"
                     class="size-full object-cover" loading="lazy" decoding="async">
            </div>

            <div class="flex flex-col gap-8">
                <div class="flex flex-col gap-8">
                    <h2 class="fm-reveal-right text-[32px] font-bold leading-[1.5] text-high lg:text-[44px]">
                        Você e seus colegas com mais <span class="text-brand-primary">organização financeira</span>.
                    </h2>
                    <p class="fm-reveal-right text-base font-medium leading-[1.5] text-medium lg:max-w-[578px] lg:text-xl lg:font-normal"
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
                    rounded="none"
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
    <section id="comecar" class="relative mx-auto mt-20 max-w-[1800px] scroll-mt-28 px-5 pb-20 sm:px-8 lg:mt-[109px] lg:px-[62px] lg:pb-0">
        {{-- Grafismo: Group 31820, canto inferior esquerdo --}}
        <img src="{{ asset('svg/colaborador/deco-star.svg') }}" alt="" aria-hidden="true"
             class="fm-spin pointer-events-none absolute -left-16 bottom-0 -z-10 hidden w-[225px] lg:block"
             loading="lazy" decoding="async">

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
                    rounded="none"
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


    {{-- Onda em degraus que faz a transição para o rodapé (Vector 20 no design) --}}
    <x-sections.footer-wave top-margin="mt-20 lg:mt-[317px]" />
</div>
</x-filament-panels::page>

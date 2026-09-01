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
    <section id="colaborador" class="relative scroll-mt-28">
        <div class="absolute inset-x-0 top-0 -z-10 hidden lg:block" aria-hidden="true">
            <img src="{{ asset('img/colaborador/hero-bg.png') }}" alt=""
                 class="h-auto w-full" fetchpriority="high" decoding="async">
        </div>

        <div class="mx-auto max-w-[1800px] px-5 pb-16 pt-10 sm:px-8 lg:px-[62px] lg:pb-32 lg:pt-[204px]">
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
                    <a href="#planos"
                       class="fm-btn inline-flex items-center justify-center border border-outline-light p-4 text-base font-bold leading-[1.5] text-brand-primary sm:w-[273px]">
                        Conhecer planos
                    </a>
                    <a href="#indicar"
                       class="fm-btn inline-flex items-center justify-center border border-brand-primary bg-brand-primary p-4 text-base font-bold leading-[1.5] text-light sm:w-[273px]">
                        Indicar minha empresa
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         2. O QUE É O FLAMMA? — 8298:3443
         ══════════════════════════════════════════════════════════════ --}}
    <section id="o-que-e" class="relative mx-auto mt-16 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-24 lg:px-[62px]">
        <div class="flex flex-col gap-4 lg:max-w-[822px] lg:gap-7">
            <h2 class="fm-reveal text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
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
    <section id="como-funciona" class="relative mx-auto mt-20 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-32 lg:px-[62px]">
        {{-- Grafismo: seta (Group 31818), canto superior direito --}}
        <img src="{{ asset('svg/colaborador/deco-arrow-1.svg') }}" alt="" aria-hidden="true"
             class="fm-bob pointer-events-none absolute -top-24 right-0 -z-10 hidden w-[303px] lg:block"
             loading="lazy" decoding="async">

        <div class="flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
            <h2 class="fm-reveal text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                Do acesso à primeira sessão
            </h2>
            <a href="#cadastro"
               class="fm-reveal fm-btn inline-flex w-full items-center justify-center border border-brand-primary bg-brand-primary p-4 text-base font-bold leading-[1.5] text-light sm:w-[273px]"
               data-fm-delay="1">
                Fazer meu cadastro
            </a>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-8 lg:mt-14 lg:grid-cols-3">
            @foreach ([
                ['img' => 'card-contract.webp', 'icon' => 'handshake',    'title' => 'Contratação', 'text' => 'Sua empresa contrata o pacote de horas disponível pro time.', 'alt' => 'Aperto de mãos'],
                ['img' => 'card-reality.webp',  'icon' => 'check-circle', 'title' => 'Ativação',    'text' => 'Você recebe seu acesso e agenda no horário que preferir.',   'alt' => 'Molho de chaves'],
                ['img' => 'card-secrecy.webp',  'icon' => 'users',        'title' => 'Consultoria', 'text' => 'Sessões individuais e sigilosas, só entre você e o consultor.', 'alt' => 'Calculadora'],
            ] as $i => $card)
                <article class="fm-card fm-reveal flex flex-col gap-6 border border-outline-light bg-elevation-surface p-6 lg:p-8"
                         data-fm-delay="{{ $i + 1 }}">
                    <div class="fm-zoom aspect-[482/328] w-full overflow-hidden"
                         style="background: linear-gradient(180deg, #FE4A39 0%, #FF7B33 31.8%, #FBBFDE 100%)">
                        <img src="{{ asset('img/companies/' . $card['img']) }}" alt="{{ $card['alt'] }}"
                             class="fm-card-prod size-full object-contain p-[6%]" loading="lazy" decoding="async">
                    </div>
                    <div class="flex flex-col gap-4 p-4">
                        <div class="flex items-center gap-4">
                            {{-- handshake e users já existem em /svg/companies/; check-circle é novo --}}
                            <img src="{{ $card['icon'] === 'check-circle'
                                            ? asset('svg/colaborador/check-circle.svg')
                                            : asset('svg/companies/' . $card['icon'] . '.svg') }}"
                                 alt="" class="fm-card-icon size-[31px] shrink-0" loading="lazy" decoding="async">
                            <h3 class="text-2xl font-bold text-high">{{ $card['title'] }}</h3>
                        </div>
                        <p class="text-base font-medium leading-[1.5] text-medium">{{ $card['text'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         4. MAIS QUE UMA FERRAMENTA — 8298:3472 + pilares 8298:3653
         Mesmo bloco da /para-empresas.
         ══════════════════════════════════════════════════════════════ --}}
    <section id="consultor" class="relative mx-auto mt-20 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-32 lg:px-[62px]">
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

            <ul class="fm-reveal-right flex flex-col gap-8">
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
                        <div class="flex flex-col gap-1">
                            <h3 class="text-xl font-bold leading-[1.5] text-high lg:text-2xl">{{ $pilar[0] }}</h3>
                            <p class="text-base font-medium leading-[1.5] text-medium">{{ $pilar[1] }}</p>
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
    <section id="privacidade" class="relative mx-auto mt-20 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-32 lg:px-[62px]">
        {{-- Grafismo: seta (Group 31819) --}}
        <img src="{{ asset('svg/colaborador/deco-arrow-2.svg') }}" alt="" aria-hidden="true"
             class="fm-bob pointer-events-none absolute -bottom-20 left-1/2 -z-10 hidden w-[300px] lg:block"
             loading="lazy" decoding="async">

        <div class="flex flex-col gap-8 lg:max-w-[693px] lg:pl-32">
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
            <a href="#planos"
               class="fm-reveal-left fm-btn inline-flex w-full items-center justify-center border border-brand-primary bg-brand-primary p-4 text-base font-bold leading-[1.5] text-light sm:w-[189px]"
               data-fm-delay="2">
                Simular contratação
            </a>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         6. ESCOLHA SEU PLANO — 8298:3587
         Bloco NOVO. Preço em gradiente rose→orange via bg-clip-text.
         ══════════════════════════════════════════════════════════════ --}}
    <section id="planos" class="relative mx-auto mt-20 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-32 lg:px-[62px]">
        <header class="flex flex-col gap-4 text-center">
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

        <div class="mt-10 grid grid-cols-1 gap-8 lg:mt-16 lg:grid-cols-2">
            @foreach ([
                ['preco' => 'R$ 179,90', 'sessoes' => '1 Sessão/mês'],
                ['preco' => 'R$ 279,90', 'sessoes' => '2 Sessões/mês'],
            ] as $i => $plano)
                <div class="fm-card fm-reveal flex flex-col items-center justify-center gap-8 overflow-hidden border border-outline-light bg-elevation-01dp p-8"
                     data-fm-delay="{{ $i + 1 }}">
                    <div class="flex w-full flex-col text-center leading-[1.5]">
                        <p class="text-base font-medium text-dark">Mensal</p>
                        {{-- gradiente do Figma: 165.6deg, #fd0342 15.42% → #ff803c 84.58% --}}
                        <p class="bg-gradient-to-br from-brand-primary to-brand-secondary bg-clip-text text-[36px] font-bold text-transparent lg:text-5xl">
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
    <section id="cadastro" class="relative mx-auto mt-20 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-32 lg:px-[62px]">
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
                <a href="#cadastro"
                   class="fm-reveal-left fm-btn inline-flex w-full items-center justify-center border border-brand-primary bg-brand-primary p-4 text-base font-bold leading-[1.5] text-light sm:w-[184px]"
                   data-fm-delay="2">
                    Fazer meu cadastro
                </a>
            </div>

            <div class="fm-reveal-right fm-zoom hidden aspect-[700/587] w-full overflow-hidden lg:block">
                <img src="{{ asset('img/colaborador/cadastro.png') }}"
                     alt="Colaborador acessando a plataforma"
                     class="size-full object-cover" loading="lazy" decoding="async">
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         8. VOCÊ E SEUS COLEGAS — 8451:1101
         Foto à ESQUERDA (708×588), texto à direita.
         ══════════════════════════════════════════════════════════════ --}}
    <section id="indicar" class="relative mx-auto mt-20 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-32 lg:px-[62px]">
        <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-[708px_1fr] lg:gap-[142px]">
            <div class="fm-reveal-left fm-zoom aspect-[708/589] w-full overflow-hidden">
                <img src="{{ asset('img/colaborador/colegas.png') }}"
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
                <a href="#indicar"
                   class="fm-reveal-right fm-btn inline-flex w-full items-center justify-center border border-brand-primary bg-brand-primary p-4 text-base font-bold leading-[1.5] text-light sm:w-[207px]"
                   data-fm-delay="2">
                    Indicar minha empresa
                </a>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         9. COMECE AGORA — 8451:1110
         Card final; foto 761×556 à direita. Mesma foto da CTA da
         /para-empresas (magnific_a-photorealistic-cinemati).
         ══════════════════════════════════════════════════════════════ --}}
    <section id="comecar" class="relative mx-auto mt-20 max-w-[1800px] scroll-mt-28 px-5 pb-20 sm:px-8 lg:mt-32 lg:px-[62px] lg:pb-32">
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
                <a href="#cadastro"
                   class="fm-btn inline-flex w-full items-center justify-center border border-brand-primary bg-brand-primary p-4 text-base font-bold leading-[1.5] text-light sm:w-[248px]">
                    Agende sua primeira sessão
                </a>
            </div>

            <div class="fm-zoom aspect-[762/557] w-full overflow-hidden lg:max-w-[762px] lg:flex-1">
                <img src="{{ asset('img/companies/cta.webp') }}"
                     alt="Pessoa depositando uma moeda em um cofre de porquinho"
                     class="size-full object-cover" loading="lazy" decoding="async">
            </div>
        </div>
    </section>

</div>

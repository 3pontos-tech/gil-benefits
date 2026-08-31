<x-layouts.site title="Clube de Benefícios" description="O benefício que cuida do bem-estar financeiro do seu time: consultoria financeira individual para cada colaborador.">
{{--
    Home institucional — Figma node 8298:404 ("Página institucional")
    Ordem e copy conforme o Figma. As classes fm-* são animadas por
    flamma-motion.css / flamma-motion.js.

    Assets esperados em public/img/home/ e public/svg/home/ — veja o
    bloco de curl no HANDOFF-home.md.
--}}

<div class="fm-page overflow-x-clip">

    {{-- ══════════════════════════════════════════════════════════════
         1. HERO — 8298:497
         Full-bleed com gradiente rose→orange. Três retângulos brancos
         a 32% compõem o fundo (Rectangle 1242/1243/1244 no Figma).
         ══════════════════════════════════════════════════════════════ --}}
    <section id="home"
             class="relative left-1/2 -mx-[50vw] w-screen overflow-hidden scroll-mt-28"
             style="background: linear-gradient(106deg, #FD0342 15.42%, #FF803C 84.58%)">

        {{-- Faixa inferior de 80px atravessando a largura toda --}}
        <div class="absolute inset-x-0 bottom-0 hidden h-[80px] bg-white/32 lg:block" aria-hidden="true"></div>
        {{-- Bloco atrás da foto, 701×494 a partir de x=1220 de 1921 --}}
        <div class="absolute right-0 top-[34%] hidden h-[60%] w-[36.5%] bg-white/32 lg:block" aria-hidden="true"></div>
        {{-- Degrau à esquerda da faixa, 219×73 --}}
        <div class="absolute bottom-[80px] right-[36.5%] hidden h-[73px] w-[11.4%] bg-white/32 lg:block" aria-hidden="true"></div>

        <div class="relative mx-auto flex max-w-[1800px] flex-col gap-10 px-5 py-16 sm:px-8 lg:h-[822px] lg:flex-row lg:items-center lg:gap-[42px] lg:px-[62px] lg:py-20">
            <div class="flex flex-col gap-6 lg:w-[891px] lg:gap-8">
                <h1 class="fm-reveal fm-in text-[32px] font-bold leading-[1.5] text-light lg:text-5xl">
                    O benefício que cuida do bem-estar financeiro do seu time.
                </h1>
                <p class="fm-reveal fm-in text-base font-medium leading-[1.5] text-light lg:text-xl lg:font-normal" data-fm-delay="1">
                    Levamos equilíbrio ao bolso (e à vida) de quem faz sua empresa acontecer,
                    com planejamento financeiro, acompanhamento individual e sigilo absoluto.
                </p>
                <x-button
                    class="fm-reveal fm-in fm-btn w-full! sm:w-fit!"
                    data-fm-delay="2"
                    variant="light"
                    size="xl"
                    href="https://wa.me/5511976205711?text=Flamma"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Fazer cotação gratuita
                </x-button>
            </div>

            <div class="fm-reveal-scale fm-in fm-zoom relative w-full overflow-hidden lg:h-[584px] lg:w-[674px] lg:shrink-0">
                <img src="{{ asset('img/home/hero.webp') }}"
                     alt="Equipe reunida em frente ao computador durante uma sessão de consultoria"
                     class="size-full object-cover"
                     fetchpriority="high" decoding="async">
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         2. POR QUE CONFIAR NO FLAMMA? — 8298:522
         ══════════════════════════════════════════════════════════════ --}}
    <section id="por-que-confiar" class="relative mx-auto mt-24 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[113px] lg:px-[62px]">
        <header class="flex flex-col gap-6 text-center">
            <h2 class="fm-reveal text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                Por que confiar no <span class="text-brand-primary">Flamma</span>?
            </h2>
            <p class="fm-reveal text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal" data-fm-delay="1">
                Cuidar do financeiro dos seus colaboradores é cuidar do resultado da sua empresa,
                e poucos tratam isso com a atenção que merece.
            </p>
        </header>

        <div class="mt-10 grid grid-cols-1 gap-8 lg:mt-[65px] lg:grid-cols-3">
            @foreach ([
                ['img' => 'card-especialistas.webp', 'icon' => 'users',    'title' => 'Especialistas', 'text' => 'Consultores especializados oferecem sessões individuais de até 60 minutos, focadas nas necessidades de cada colaborador.', 'alt' => 'Consultora atendendo uma colaboradora'],
                ['img' => 'card-realidade.webp',     'icon' => 'target',   'title' => 'Realidade',     'text' => '42% dos brasileiros apontam o dinheiro como principal fonte de preocupação, reforçando a importância do cuidado financeiro.', 'alt' => 'Pessoa organizando as contas'],
                ['img' => 'card-sigilo.webp',        'icon' => 'lock-key', 'title' => 'Sigilo',        'text' => 'O colaborador pode compartilhar seus planos e dificuldades com liberdade, sabendo que suas informações permanecem em sigilo.', 'alt' => 'Sessão individual e sigilosa'],
            ] as $i => $card)
                <article class="fm-card fm-reveal flex flex-col gap-6 border border-outline-light bg-elevation-01dp p-6 lg:p-8"
                         data-fm-delay="{{ $i + 1 }}">
                    <div class="fm-zoom aspect-[478/327] w-full overflow-hidden">
                        <img src="{{ asset('img/home/' . $card['img']) }}" alt="{{ $card['alt'] }}"
                             class="size-full object-cover" loading="lazy" decoding="async">
                    </div>
                    <div class="flex flex-col gap-6 p-4">
                        <div class="flex items-center gap-6">
                            <img src="{{ asset('svg/home/icon-' . $card['icon'] . '.svg') }}" alt=""
                                 class="fm-card-icon size-11 shrink-0" loading="lazy" decoding="async">
                            <h3 class="text-2xl font-bold text-dark">{{ $card['title'] }}</h3>
                        </div>
                        <p class="text-base font-medium leading-[1.5] text-medium">{{ $card['text'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         Vector 8 — faixa em degraus que sobe pela esquerda e passa por trás
         do rodapé dos cards. No Figma vai de y 1517 a 2015,5 numa página em
         que os cards terminam em 1765: ou seja, ela invade 248px do bloco de
         cima e ainda sobra 80px até o título seguinte. Daí a margem negativa
         aqui e o mt reduzido na seção abaixo.
         ══════════════════════════════════════════════════════════════ --}}
    <div class="relative left-1/2 -mx-[50vw] -z-10 hidden w-screen lg:block lg:-mt-[248px]"
         aria-hidden="true">
        <svg class="block aspect-[1920/499] w-full text-brand-primary" viewBox="0 0 1920 499" fill="none"
             preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M122.5 0H0.5L-1.5 498.5H1922V390H334.5V306H214.5V83.5H122.5V0Z"
                  fill="currentColor"/>
        </svg>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         3. COMO O FLAMMA CHEGA ATÉ O SEU TIME? — 8430:376
         Números 01/02/03 em 64px rose.
         ══════════════════════════════════════════════════════════════ --}}
    <section id="how-it-works" class="relative mx-auto mt-24 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[80px] lg:px-[62px]">
        {{-- Grafismo: seta do Figma (Frame 20324), canto superior direito.
             O -top-20 é o mesmo valor do lg:mt-[80px] desta seção, e é o que faz a
             seta encostar na base da faixa em degraus acima em vez de sobrar um vão. --}}
        <x-graphism type="home-arrow" data-fm-static class="absolute -top-20 right-0 -z-10 hidden w-[295px] lg:block" />

        <header class="flex flex-col gap-8 lg:max-w-[1531px]">
            <h2 class="fm-reveal text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                Como o Flamma chega até o seu time?
            </h2>
            {{-- O max-w segura o subtítulo em duas linhas, longe da seta que sangra
                 no canto direito da seção. --}}
            <p class="fm-reveal text-base font-medium leading-[1.5] text-medium lg:max-w-5xl lg:text-xl lg:font-normal" data-fm-delay="1">
                Da contratação à primeira sessão, cuidamos de cada detalhe para que o processo
                seja simples para você e acolhedor para o seu time.
            </p>
        </header>

        <div class="mt-9 grid grid-cols-1 gap-8 lg:grid-cols-3">
            @foreach ([
                ['n' => '01', 'title' => 'Contratação', 'text' => 'A empresa escolhe o pacote ideal para o tamanho do time.'],
                ['n' => '02', 'title' => 'Agendamento', 'text' => 'Cada colaborador agenda sua sessão no horário que preferir.'],
                ['n' => '03', 'title' => 'Consultoria', 'text' => 'O colaborador recebe atendimento especializado e contínuo.'],
            ] as $i => $step)
                <div class="fm-card fm-reveal flex flex-col gap-8 border border-outline-light bg-elevation-01dp p-8"
                     data-fm-delay="{{ $i + 1 }}">
                    <p class="fm-step-n text-[48px] font-bold leading-[1.5] text-brand-primary lg:text-[64px]"
                       aria-hidden="true">{{ $step['n'] }}</p>
                    <div class="flex flex-col gap-4">
                        <h3 class="text-2xl font-bold text-high lg:text-[32px]">{{ $step['title'] }}</h3>
                        <p class="text-base font-medium leading-[1.5] text-medium">{{ $step['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         Vector 7 (8298:453) + Imagem (8298:584) — bloco de respiro do Figma:
         massa escura em degraus de ponta a ponta, com uma foto de 1675×940
         apoiada nela. A foto fica em 123/2712 num palco de 1920×1094, ou seja
         left 6,406% · top 5,027% · 87,24% × 85,92%.

         Este bloco não vinha no Blade do handoff — a foto nem estava na lista
         de download, foi exportada do nó 8298:584.
         ══════════════════════════════════════════════════════════════ --}}
    {{-- Bloco desativado por ora, a pedido — some junto o grafismo cinza em degraus.
    <div class="relative left-1/2 -mx-[50vw] mt-16 hidden w-screen lg:block lg:mt-[118px]">
        <div class="relative aspect-[1920/1094] w-full">
            <svg class="absolute inset-0 size-full" viewBox="0 0 1920 1094" fill="none"
                 preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M0 0V468.782L123.532 470.357V680.338H820.214V754.356H1455.88V1094H1921V0H0Z"
                      fill="#39393A"/>
            </svg>

            <div class="fm-zoom fm-reveal-scale absolute left-[6.406%] top-[5.027%] h-[85.92%] w-[87.24%] overflow-hidden">
                <img src="{{ asset('img/home/story.webp') }}"
                     alt="Colaboradora sorrindo durante uma conversa no escritório"
                     class="size-full object-cover" loading="lazy" decoding="async">
            </div>
        </div>
    </div>
    --}}

    {{-- ══════════════════════════════════════════════════════════════
         4. O ESTRESSE FINANCEIRO CUSTA CARO — 8298:415
         Duas colunas: rose (empresa) e clara (colaborador).
         A foto 577×760 que o Figma punha à direita saiu — os cards
         ocupam a largura toda da seção.
         ══════════════════════════════════════════════════════════════ --}}
    {{-- O lg:mt-24 é temporário: quando o bloco de imagem acima voltar, o valor
         original era lg:-mt-[19px], que encostava a seção na base da foto. --}}
    <section id="challenge" class="relative mx-auto mt-24 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-24 lg:px-[62px]">
        <div class="flex flex-col gap-8">
            <header class="flex flex-col gap-4">
                <h2 class="fm-reveal text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                    O estresse financeiro <span class="text-brand-primary">custa caro</span>
                </h2>
                <p class="fm-reveal text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal" data-fm-delay="1">
                    Colaboradores endividados faltam mais, produzem menos e saem primeiro.
                    É uma das causas mais subestimadas de turnover no Brasil e também uma
                    das mais fáceis de endereçar.
                </p>
            </header>

            <div class="grid grid-cols-1 gap-0 md:grid-cols-2">
                {{-- Coluna rose — para a empresa --}}
                <div class="fm-reveal-left flex flex-col gap-8 border border-outline-light bg-brand-primary p-8">
                    <div class="flex flex-col gap-4">
                        <h3 class="text-2xl font-bold leading-[1.5] text-light lg:text-[32px]">Para sua empresa</h3>
                        <div class="h-px w-full bg-icon-light"></div>
                    </div>
                    <div class="flex flex-col gap-8">
                        @foreach ([
                            ['Mais produtividade', 'Colaboradores com finanças organizadas trabalham mais focados e engajados.'],
                            ['Menos turnover', 'Diminuição de faltas e menos rotatividade, com um time mais estável.'],
                            ['Retenção de talento', 'Um diferencial competitivo que valoriza o bem-estar de quem trabalha com você.'],
                        ] as $item)
                            <div class="flex flex-col gap-3">
                                <p class="text-xl font-bold leading-[1.5] text-light">{{ $item[0] }}</p>
                                <p class="text-base font-medium leading-[1.5] text-light">{{ $item[1] }}</p>
                            </div>
                        @endforeach
                    </div>
                    <x-button
                        class="fm-btn w-full!"
                        variant="light-brand"
                        size="xl"
                        href="https://wa.me/5511976205711?text=Flamma"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Fale com a gente
                    </x-button>
                </div>

                {{-- Coluna clara — para o colaborador --}}
                <div class="fm-reveal-right flex flex-col gap-8 border border-outline-light bg-elevation-01dp p-8">
                    <div class="flex flex-col gap-4">
                        <h3 class="text-2xl font-bold leading-[1.5] text-high lg:text-[32px]">Para seu colaborador</h3>
                        <div class="h-px w-full bg-outline-light"></div>
                    </div>
                    <div class="flex flex-col gap-8">
                        @foreach ([
                            ['Consultoria personalizada', 'Sessões individuais, focadas na realidade financeira de cada pessoa.'],
                            ['Controle financeiro', 'Ferramentas para organizar dívidas, criar orçamento e planejar o futuro.'],
                            ['Menos ansiedade', 'Mais tranquilidade no dia a dia, com uma relação mais saudável com o dinheiro.'],
                        ] as $item)
                            <div class="flex flex-col gap-3">
                                <p class="text-xl font-bold leading-[1.5] text-high">{{ $item[0] }}</p>
                                <p class="text-base font-medium leading-[1.5] text-medium">{{ $item[1] }}</p>
                            </div>
                        @endforeach
                    </div>
                    <x-button
                        class="fm-btn w-full!"
                        variant="flat"
                        size="xl"
                        href="https://wa.me/5511976205711?text=Flamma"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Fale com a gente
                    </x-button>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         5. O PLANO ACOMPANHA O CRESCIMENTO — 8298:551
         A foto do frame saiu: no lugar entra o simulador de preço, o mesmo
         da página Para Empresas. Os grafismos (Vector 9 + estrela + seta)
         desceram para a seção do CTA, logo abaixo.
         ══════════════════════════════════════════════════════════════ --}}
    <section id="pricing" class="relative mx-auto mt-24 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[73px] lg:px-[62px]">
        <header class="flex flex-col gap-8 text-center">
            <h2 class="fm-reveal text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                O plano acompanha o <span class="text-brand-primary">crescimento</span> do seu time
            </h2>
            <p class="fm-reveal text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal" data-fm-delay="1">
                Faça a simulação, descubra o melhor plano para sua empresa e, se ficar com
                alguma dúvida, entre em contato.
            </p>
        </header>

        <div class="fm-reveal mt-10 lg:mt-20">
            <livewire:pricing-calculator />
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         6. CTA — DÊ AO SEU TIME O CUIDADO QUE ELE MERECE — 8298:406
         O lg:mt-[330px] abre espaço para a faixa esquerda do Vector 9 (222px
         de altura no palco) passar entre o simulador e o headline sem escurecer
         o fundo do texto.
         ══════════════════════════════════════════════════════════════ --}}
    <section id="contratar" class="relative mx-auto mt-24 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[330px] lg:px-[62px]">
        {{--
            Vector 9 (8298:516) — massa escura de 1920×1254, agora ancorada nesta seção:
            o topo fica 650px acima dela, de modo que a faixa esquerda do desenho
            (y 393–615 do palco) cai no vão entre o simulador e o headline, a coluna da
            direita sobe atrás do card do simulador e a base termina junto da foto.
            A estrela (Group 31834) mora sobre ela: left 74,58% · top 28,07% ·
            28,16% × 43,86% do palco.
        --}}
        {{-- Altura fixa em px (não aspect-ratio): o vão acima e a posição da faixa são
             medidas fixas do layout, então o palco não pode escalar com a viewport —
             o preserveAspectRatio="none" estica os degraus só na horizontal. --}}
        <div class="pointer-events-none absolute -top-[650px] left-1/2 -z-10 -ml-[50vw] hidden w-screen lg:block"
             aria-hidden="true">
            <div class="relative h-[1254px] w-full">
                <svg class="absolute inset-0 size-full" viewBox="0 0 1920 1254" fill="none"
                     preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M980 615H0V393H1486.5V380H1637.5V1L1920 0V1253.77L1798 1254V862L1487 857.5V664H980V615Z"
                          fill="#39393A"/>
                </svg>

                <x-graphism type="home-asterisk"
                            data-fm-static
                            class="absolute left-[74.58%] top-[28.07%] h-[43.86%] w-[28.16%]" />
            </div>
        </div>

        {{-- Grafismo: seta ↖ encostada na borda esquerda, sobre o topo da faixa escura --}}
        <x-graphism type="arrow" data-fm-static class="absolute -top-[330px] left-1/2 -z-10 -ml-[50vw] hidden w-[270px] lg:block" />
        <div class="flex flex-col items-center justify-between gap-10 lg:flex-row lg:gap-16">
            <div class="flex flex-col gap-8 lg:w-[822px]">
                <h2 class="fm-reveal-left text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                    Dê ao seu time o <span class="text-brand-primary">cuidado</span> financeiro que ele merece
                </h2>
                <p class="fm-reveal-left text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal" data-fm-delay="1">
                    Mais que um benefício, uma mudança real na relação com o dinheiro,
                    com reflexo direto nos resultados da empresa.
                </p>
                <x-button
                    class="fm-reveal-left fm-btn w-full! sm:w-fit!"
                    data-fm-delay="2"
                    variant="flat"
                    size="xl"
                    href="https://wa.me/5511976205711?text=Flamma"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Cotação gratuita
                </x-button>
            </div>

            <div class="fm-reveal-right fm-zoom aspect-[677/596] w-full overflow-hidden lg:w-[677px] lg:shrink-0">
                <img src="{{ asset('img/home/cta.webp') }}"
                     alt="Time comemorando resultados financeiros"
                     class="size-full object-cover" loading="lazy" decoding="async">
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         7. PERGUNTAS FREQUENTES — 8298:556
         Accordion com Alpine, no mesmo padrão que a home já usava.
         ══════════════════════════════════════════════════════════════ --}}
    <section id="faq" class="relative mx-auto mt-24 max-w-[1800px] scroll-mt-28 px-5 pb-24 sm:px-8 lg:mt-[74px] lg:px-[62px] lg:pb-0">
        <div class="grid grid-cols-1 items-center gap-8 lg:grid-cols-[825px_1fr]">
            <div class="flex flex-col justify-center gap-8 px-0 py-4 lg:gap-16 lg:px-8">
                <div class="flex flex-col gap-6 lg:gap-[27px]">
                    <h2 class="fm-reveal-left text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                        Perguntas frequentes
                    </h2>
                    <p class="fm-reveal-left text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal" data-fm-delay="1">
                        Separamos as respostas para as perguntas que mais recebemos.
                    </p>
                </div>
                <x-button
                    class="fm-reveal-left fm-btn w-full! sm:w-fit!"
                    data-fm-delay="2"
                    variant="flat"
                    size="xl"
                    href="https://wa.me/5511976205711?text=Flamma"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Fale com a gente
                </x-button>
            </div>

            <div class="flex flex-col justify-center gap-8">
                @foreach ([
                    ['Como funciona o processo de atendimento?', 'Cada colaborador agenda uma sessão individual de até 60 minutos com um consultor especializado, no horário que preferir. O atendimento é contínuo e acompanha a evolução de cada pessoa.'],
                    ['Posso alterar minhas informações cadastrais?', 'Sim. As informações cadastrais podem ser atualizadas a qualquer momento pelo próprio colaborador, direto na plataforma.'],
                    ['Quais são as formas de pagamento aceitas?', 'O plano empresarial é faturado mensalmente conforme o número de colaboradores. Fale com a gente para conhecer as condições disponíveis.'],
                    ['Existe um prazo de cancelamento?', 'O cancelamento pode ser solicitado a qualquer momento, respeitando o ciclo de faturamento vigente.'],
                    ['Como posso entrar em contato com o suporte?', 'Pelo canal de atendimento da plataforma ou pelo e-mail de contato no rodapé. Respondemos em até um dia útil.'],
                ] as $i => $faq)
                    <div x-data="{ open: false }"
                         class="fm-reveal-right fm-faq group rounded border border-outline-light bg-elevation-01dp transition-colors duration-300 hover:border-brand-primary"
                         :class="{ 'border-brand-primary': open }"
                         data-fm-delay="{{ min($i + 1, 3) }}">
                        <h3 class="flex">
                            <button type="button"
                                    @click="open = !open"
                                    :aria-expanded="open"
                                    aria-controls="faq-panel-{{ $i }}"
                                    class="flex flex-1 items-center justify-between gap-2 px-6 py-4 text-left text-base font-bold leading-[1.5] text-medium transition-colors lg:px-8 lg:text-xl"
                                    :class="{ 'text-brand-primary': open }">
                                {{ $faq[0] }}
                                <svg class="size-6 shrink-0 text-brand-primary transition-transform duration-300"
                                     :class="{ 'rotate-180': open }"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>
                        </h3>
                        {{-- grid-template-rows 0fr→1fr anima a altura sem precisar medir o conteúdo --}}
                        <div id="faq-panel-{{ $i }}" class="fm-faq-panel grid" :class="{ 'is-open': open }">
                            <div class="overflow-hidden">
                                <p class="px-6 pb-4 text-base font-medium leading-[1.5] text-medium lg:px-8">
                                    {{ $faq[1] }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>


    {{-- Onda em degraus que faz a transição para o rodapé (Vector 10 no design) --}}
    <x-sections.footer-wave top-margin="mt-20 lg:mt-[10px]" />
</div>
</x-layouts.site>

<x-layouts.site title="Saúde financeira corporativa" description="A Flamma é uma solução de saúde financeira que ajuda empresas a cuidar do bem-estar dos colaboradores, com orientação especializada para organizar as finanças e planejar o futuro.">
<div class="fm-page overflow-x-clip">

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
                    Saúde financeira também é saúde corporativa.
                </h1>
                <p class="fm-reveal fm-in text-base font-medium leading-[1.5] text-light lg:text-xl lg:font-normal" data-fm-delay="1">
                    A Flamma é uma solução de saúde financeira que ajuda empresas a cuidar do bem-estar
                    dos colaboradores, oferecendo orientação especializada para organizar as finanças,
                    tomar decisões com mais segurança e planejar o futuro.
                </p>
                <x-button
                    class="fm-reveal fm-in fm-btn w-full! sm:w-fit!"
                    data-fm-delay="2"
                    variant="light"
                    size="xl"
                    href="#expediente"
                >
                    Conheça a Flamma
                </x-button>
            </div>

            <div class="fm-reveal-scale fm-in fm-zoom relative aspect-[674/584] w-full overflow-hidden lg:w-[674px] lg:shrink-0">
                <img src="{{ asset('img/home/hero.webp') }}"
                     alt="Equipe reunida em frente ao computador durante uma sessão de consultoria"
                     class="size-full object-cover"
                     fetchpriority="high" decoding="async">
            </div>
        </div>
    </section>

    <section id="expediente" class="relative mx-auto mt-24 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[71px] lg:px-[62px]">
        <header class="flex flex-col gap-6 text-center">
            <h2 class="fm-reveal text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                A <span class="text-brand-primary">preocupação financeira</span> entra no expediente.
            </h2>
            <div class="fm-reveal flex flex-col text-base font-medium leading-[1.5] text-medium" data-fm-delay="1">
                <p>As preocupações com dinheiro não ficam em casa. Elas podem afetar o sono, a saúde, a concentração e a energia de quem faz sua empresa acontecer.</p>
                <p>Quando a vida financeira está em desequilíbrio, seus impactos podem ultrapassar a esfera pessoal e chegar ao ambiente de trabalho.</p>
            </div>
        </header>

        <div class="mt-10 grid grid-cols-1 gap-8 lg:mt-[65px] lg:grid-cols-3">
            @foreach ([
                ['icon' => 'wallet',      'title' => 'Pouca margem para imprevistos', 'text' => '87% dos trabalhadores pesquisados possuem menos de um salário guardado como reserva financeira.', 'source' => 'SalaryFits / Serasa Experian (2025) e Icatu e Onze (2025)'],
                ['icon' => 'brain',       'title' => 'A preocupação afeta o trabalho', 'text' => '60% dos profissionais pesquisados afirmam que as preocupações financeiras afetam diretamente seu rendimento profissional.', 'source' => 'Icatu e Onze — Raio-X da Saúde Financeira dos Brasileiros 2025, via InfoMoney'],
                ['icon' => 'heart-pulse', 'title' => 'Do bolso para a saúde', 'text' => '8 em cada 10 pessoas inadimplentes pesquisadas relatam efeitos na saúde física ou mental.', 'source' => 'SalaryFits / Serasa Experian (2025); CNDL / SPC Brasil'],
            ] as $i => $card)
                <article class="fm-card fm-reveal flex flex-col gap-6 border border-outline-light bg-elevation-01dp p-6 lg:p-8"
                         data-fm-delay="{{ $i + 1 }}">
                    <div class="flex flex-col gap-6 p-4">
                        <img src="{{ asset('svg/home/icon-' . $card['icon'] . '.svg') }}" alt=""
                             class="fm-card-icon size-11 shrink-0" loading="lazy" decoding="async">
                        <h3 class="text-2xl font-bold text-high">{{ $card['title'] }}</h3>
                        <p class="text-base font-medium leading-[1.5] text-medium">{{ $card['text'] }}</p>
                    </div>
                    <p class="text-sm leading-[1.3] text-medium"><strong>Fonte:</strong> {{ $card['source'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <div class="relative left-1/2 -mx-[50vw] -z-10 hidden w-screen lg:block lg:-mt-[calc(26vw-39px)]"
         aria-hidden="true">
        <svg class="block aspect-[1920/499] w-full text-brand-primary" viewBox="0 0 1920 499" fill="none"
             preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M122.5 0H0.5L-1.5 498.5H1922V390H334.5V306H214.5V83.5H122.5V0Z"
                  fill="currentColor"/>
        </svg>
    </div>

    <section id="how-it-works" class="relative mx-auto mt-24 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[143px] lg:px-[62px]">
        <x-graphism type="home-arrow" data-fm-static class="absolute -top-[115px] right-0 -z-10 hidden w-[295px] lg:block" />
        <x-graphism type="asterisk" class="absolute left-1/2 top-[614px] -z-10 -ml-[calc(50vw+111px)] hidden size-[270px] lg:block" />

        <div class="flex flex-col items-center gap-8 border border-outline-light bg-elevation-01dp p-6 lg:flex-row lg:p-8">
            <div class="flex flex-col gap-8 lg:flex-1">
                <h2 class="fm-reveal text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                    Cuidar da <span class="text-brand-primary">saúde do colaborador</span> também é olhar para a vida financeira.
                </h2>
                <div class="fm-reveal flex flex-col text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal" data-fm-delay="1">
                    <p>Saúde física, mental e financeira estão conectadas. Quando uma dessas dimensões está em desequilíbrio, as outras também podem ser afetadas.</p>
                    <p>Incorporar a saúde financeira à estratégia de cuidado é ampliar o olhar sobre o bem-estar das pessoas, considerando desafios que fazem parte da vida dentro e fora do trabalho.</p>
                </div>
            </div>

            <div class="fm-reveal-scale fm-zoom aspect-[741/642] w-full overflow-hidden lg:w-[46%] lg:shrink-0">
                <img src="{{ asset('img/home/care.webp') }}"
                     alt="Colegas conversando em volta de uma mesa com notebooks"
                     class="size-full object-cover" loading="lazy" decoding="async">
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-8 lg:mt-[84px] lg:grid-cols-3">
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

    <section id="challenge" class="relative mx-auto mt-24 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-20 lg:px-[62px]">
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
                <div class="fm-reveal-left flex flex-col gap-8 border border-outline-light bg-brand-primary p-8">
                    <div class="flex flex-col gap-4">
                        <h3 class="text-2xl font-bold leading-[1.5] text-light lg:text-[32px]">Para sua empresa</h3>
                        <div class="h-px w-full bg-icon-light"></div>
                    </div>
                    <div class="flex flex-col gap-8">
                        @foreach ([
                            ['Cuidado mais completo', 'Inclua a saúde financeira na estratégia de saúde e bem-estar corporativo.'],
                            ['Prevenção', 'Ofereça orientação antes que as dificuldades financeiras se tornem problemas maiores.'],
                            ['Bem-estar no trabalho', 'Ajude a reduzir uma fonte de preocupação que pode acompanhar o colaborador durante o expediente.'],
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
                        href="#diferenciais"
                    >
                        Conheça nossa solução
                    </x-button>
                </div>

                <div class="fm-reveal-right flex flex-col gap-8 border border-outline-light bg-elevation-01dp p-8">
                    <div class="flex flex-col gap-4">
                        <h3 class="text-2xl font-bold leading-[1.5] text-high lg:text-[32px]">Para seu colaborador</h3>
                        <div class="h-px w-full bg-outline-light"></div>
                    </div>
                    <div class="flex flex-col gap-8">
                        @foreach ([
                            ['Mais clareza', 'Compreenda melhor a própria realidade financeira e organize prioridades.'],
                            ['Mais autonomia', 'Tome decisões financeiras com informação, orientação e segurança.'],
                            ['Mais possibilidades', 'Planeje conquistas, prepare-se para imprevistos e construa objetivos para o futuro.'],
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
                        href="#diferenciais"
                    >
                        Conheça nossa solução
                    </x-button>
                </div>
            </div>
        </div>
    </section>

    <section id="pricing" class="relative mx-auto mt-24 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-20 lg:px-[62px]">
        <header class="flex flex-col gap-8 text-center">
            <h2 class="fm-reveal text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                O plano acompanha o <span class="text-brand-primary">crescimento</span> do seu time
            </h2>
            <p class="fm-reveal text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal" data-fm-delay="1">
                Simule o valor para o seu time abaixo. Ficou com alguma dúvida? É só falar com a gente.
            </p>
        </header>

        <div class="fm-reveal mt-10 lg:mt-8">
            <livewire:pricing-calculator />
        </div>
    </section>

    <section id="momentos" class="relative mx-auto mt-24 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[383px] lg:px-[62px]">
        <svg class="pointer-events-none absolute -top-[498px] left-1/2 -z-10 -ml-[50vw] hidden h-[570px] w-screen lg:block"
             viewBox="0 0 1920 570" fill="none" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M1926 246.5H-6V0H69.5V92H151H334.616V166.999H393.603H1046.54V200.926H1489.79H1576.83H1670.43H1766.18V130.5H1846.5V50.5H1926V246.5Z"
                  fill="url(#momentosBandTop)"/>
            <path transform="translate(0 241)"
                  d="M1932 0H-12V133.448H238.594V94H417.3V133.448H1047.08V76.5H1493.08H1580.66H1674.85H1771.18L1770.17 174H1843.41V329H1932V0Z"
                  fill="url(#momentosBandBottom)"/>
            <defs>
                <linearGradient id="momentosBandTop" x1="-6" y1="148.5" x2="89.04" y2="566.34" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#FF803C"/>
                    <stop offset="1" stop-color="#FD0342"/>
                </linearGradient>
                <linearGradient id="momentosBandBottom" x1="-12" y1="164.5" x2="232.68" y2="-480.32" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#FF803C"/>
                    <stop offset="1" stop-color="#FD0342"/>
                </linearGradient>
            </defs>
        </svg>
        <x-graphism type="asterisk" class="absolute left-1/2 top-[722px] -z-10 -ml-[calc(50vw+134px)] hidden size-[270px] lg:block" />

        <header class="flex flex-col gap-8 text-center">
            <h2 class="fm-reveal text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                <span class="text-brand-primary">Saúde financeira</span> não começa nem termina nas dívidas.
            </h2>
            <div class="fm-reveal flex flex-col text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal" data-fm-delay="1">
                <p>Cada pessoa vive um momento financeiro diferente. Algumas precisam reorganizar o orçamento ou lidar com dívidas. Outras querem construir uma reserva, realizar uma conquista ou começar a investir.</p>
                <p>A Flamma entende que saúde financeira não é apenas resolver problemas. É também criar condições para tomar decisões melhores e construir o futuro.</p>
            </div>
        </header>

        <div class="mt-10 grid grid-cols-1 gap-8 lg:mt-[70px] lg:grid-cols-2">
            @foreach ([
                ['icon' => 'chart-pie',         'title' => 'Organização financeira',  'text' => 'Mais clareza sobre o orçamento, os gastos e as prioridades.'],
                ['icon' => 'shield-check',      'title' => 'Reserva e proteção',      'text' => 'Planejamento para lidar com imprevistos e desenvolver maior segurança financeira.'],
                ['icon' => 'arrows-clockwise',  'title' => 'Dívidas e reorganização', 'text' => 'Orientação para compreender compromissos financeiros e construir caminhos possíveis.'],
                ['icon' => 'target',            'title' => 'Sonhos e objetivos',      'text' => 'Organização para transformar planos pessoais em metas financeiras.'],
                ['icon' => 'trend-up',          'title' => 'Investimentos e futuro',  'text' => 'Orientação para tomar decisões mais conscientes sobre patrimônio e planejamento de longo prazo.'],
            ] as $i => $card)
                <article @class([
                             'fm-card fm-reveal flex flex-col gap-4 border border-outline-light bg-elevation-01dp p-8',
                             'lg:col-span-2 lg:mx-auto lg:w-[832px]' => $loop->last,
                         ])
                         data-fm-delay="{{ $i % 2 + 1 }}">
                    <img src="{{ asset('svg/home/icon-' . $card['icon'] . '.svg') }}" alt=""
                         class="fm-card-icon size-11 shrink-0" loading="lazy" decoding="async">
                    <h3 class="text-2xl font-bold text-high lg:text-[32px]">{{ $card['title'] }}</h3>
                    <p class="text-base font-medium leading-[1.5] text-medium">{{ $card['text'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section id="diferenciais" class="relative mx-auto mt-24 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[242px] lg:px-[62px]">
        <svg class="pointer-events-none absolute -top-[162px] left-1/2 -z-10 -ml-[50vw] hidden h-[895px] w-screen lg:block"
             viewBox="0 0 1920 895" fill="none" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M579.544 0H-7V895H284.819V825H782.74V760.5H942.614V190.5H1142.94V124.5H1495.43V36H1919.5V0H579.544Z"
                  fill="#39393A"/>
            <path transform="translate(1 0)"
                  d="M199.415 796H80.4813V873H0V0H1321.93L1319 68H1013.62V132H901.838V325H494.513V726.77H199.415V796Z"
                  fill="url(#diferenciaisBand)"/>
            <defs>
                <linearGradient id="diferenciaisBand" x1="0" y1="928.56" x2="1708.05" y2="-22.38" gradientUnits="userSpaceOnUse">
                    <stop offset="0.62" stop-color="#E2410A"/>
                    <stop offset="1" stop-color="#FD0342"/>
                </linearGradient>
            </defs>
        </svg>

        <div class="relative flex flex-col items-center gap-8 lg:flex-row">
            <div class="absolute -left-[47px] top-[358px] hidden h-[176px] w-[527px] bg-white/32 lg:block" aria-hidden="true"></div>
            <div class="absolute left-[286px] top-0 hidden h-[176px] w-[527px] bg-white/32 lg:block" aria-hidden="true"></div>

            <div class="relative flex flex-col gap-8 lg:w-[47%] lg:shrink-0">
                <h2 class="fm-reveal-left bg-elevation-surface px-8 py-4 text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                    Uma solução de saúde financeira para <span class="text-brand-primary">pessoas reais.</span>
                </h2>
                <div class="fm-reveal-left flex flex-col bg-elevation-surface px-8 py-4 text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal" data-fm-delay="1">
                    <p>Não existe uma única realidade financeira dentro de uma empresa. Cada colaborador possui desafios, prioridades e objetivos diferentes.</p>
                    <p>A Flamma oferece orientação financeira especializada para ajudar pessoas a compreender melhor sua situação, organizar decisões e desenvolver uma relação mais saudável com o dinheiro.</p>
                    <p>Tudo isso com uma abordagem que considera a realidade individual, respeita a privacidade e valoriza o cuidado humano.</p>
                </div>
            </div>

            <div class="flex flex-1 flex-col gap-4 lg:px-8 lg:py-4">
                <h3 class="fm-reveal-right text-center text-[32px] font-bold leading-[1.5] text-high lg:text-[44px]">Diferenciais</h3>
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
                    @foreach ([
                        ['Orientação especializada', 'Apoio para compreender desafios e tomar decisões financeiras mais conscientes.'],
                        ['Olhar individualizado', 'Cada realidade financeira exige prioridades e caminhos diferentes.'],
                        ['Cuidado sem julgamentos', 'Uma abordagem que acolhe diferentes situações, sem culpabilizar o colaborador por suas dificuldades.'],
                        ['Foco na evolução', 'Saúde financeira é uma construção que envolve organização, prevenção e planejamento ao longo da vida.'],
                    ] as $i => $item)
                        <article class="fm-card fm-reveal-right flex flex-col gap-3 rounded border border-outline-light bg-elevation-surface px-8 py-4"
                                 data-fm-delay="{{ $i % 2 + 1 }}">
                            <x-graphism type="asterisk" class="fm-card-icon m-0.5 size-10" />
                            <h4 class="text-xl font-bold leading-[1.5] text-high">{{ $item[0] }}</h4>
                            <p class="text-base font-medium leading-[1.5] text-medium">{{ $item[1] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section id="jornada" class="relative mx-auto mt-24 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-[263px] lg:px-[62px]">
        <x-graphism type="asterisk" class="absolute left-1/2 top-[429px] -z-10 ml-[calc(50vw-343px)] hidden size-[640px] 2xl:block" />

        <header class="flex flex-col gap-8">
            <h2 class="fm-reveal text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                Saúde financeira de um jeito mais <span class="text-brand-primary">simples e acessível</span>.
            </h2>
            <p class="fm-reveal text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal" data-fm-delay="1">
                A Flamma conecta empresas e colaboradores a uma jornada de cuidado financeiro, com orientação para diferentes necessidades e momentos da vida.
            </p>
        </header>

        <ol class="mt-10 flex flex-col lg:mt-12">
            @foreach ([
                ['n' => '01', 'title' => 'A empresa incorpora a Flamma', 'text' => 'A saúde financeira passa a fazer parte da estratégia de cuidado com os colaboradores.'],
                ['n' => '02', 'title' => 'O colaborador acessa a solução', 'text' => 'Cada pessoa encontra orientação de acordo com suas necessidades e objetivos financeiros.'],
                ['n' => '03', 'title' => 'A jornada financeira começa', 'text' => 'Com apoio especializado, o colaborador pode desenvolver mais clareza sobre suas finanças e planejar os próximos passos.'],
            ] as $i => $step)
                <li class="fm-reveal flex gap-6 lg:gap-8" data-fm-delay="{{ $i + 1 }}">
                    <div class="flex w-20 shrink-0 flex-col items-center gap-4 lg:w-[125px]">
                        <span class="fm-step-n flex size-20 items-center justify-center rounded-full border border-outline-light bg-white/32 text-[32px] font-bold leading-[1.5] text-brand-primary lg:size-[121px] lg:text-5xl"
                              aria-hidden="true">{{ $step['n'] }}</span>
                        @unless ($loop->last)
                            <span class="mb-4 min-h-12 w-1 flex-1 bg-brand-primary lg:min-h-[68px]" aria-hidden="true"></span>
                        @endunless
                    </div>
                    <div class="flex flex-col gap-4 pb-8 lg:p-8">
                        <h3 class="text-2xl font-bold text-high lg:text-[32px]">{{ $step['title'] }}</h3>
                        <p class="text-base font-medium leading-[1.5] text-medium">{{ $step['text'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>

        <x-button
            class="fm-reveal fm-btn mt-4 w-full! sm:w-fit! lg:mt-12"
            variant="flat"
            size="xl"
            href="https://wa.me/5511976205711?text=Flamma"
            target="_blank"
            rel="noopener noreferrer"
        >
            Saiba como implementar
        </x-button>
    </section>

    <section id="historia" class="relative mx-auto mt-24 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-20 lg:px-[62px]">
        <div class="flex flex-col items-center gap-8 rounded border border-outline-light bg-white/32 p-6 backdrop-blur-[16px] lg:flex-row lg:p-16">
            <div class="flex flex-col gap-8 lg:flex-1 lg:px-4">
                <h2 class="fm-reveal-left text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                    Por trás de cada <span class="text-brand-primary">decisão financeira</span>, existe uma história.
                </h2>
                <div class="fm-reveal-left flex flex-col text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal" data-fm-delay="1">
                    <p>Dinheiro não é apenas uma questão de números. Envolve escolhas, responsabilidades, preocupações e sonhos.</p>
                    <p>Por isso, a Flamma valoriza o acompanhamento humano e a orientação especializada, considerando que cada pessoa possui uma realidade financeira diferente.</p>
                    <p>Mais do que oferecer informações, queremos ajudar colaboradores a compreender suas possibilidades e tomar decisões com mais confiança.</p>
                </div>
                <p class="fm-reveal-left text-xl font-bold leading-[1.5] text-high lg:text-2xl" data-fm-delay="2">
                    Informação orienta. Acompanhamento ajuda a transformar conhecimento em decisão.
                </p>
            </div>

            <div class="fm-reveal-right fm-zoom aspect-[741/642] w-full overflow-hidden lg:w-[46%] lg:shrink-0">
                <img src="{{ asset('img/home/historia.webp') }}"
                     alt="Colaborador em videochamada com uma consultora sorridente na tela do notebook"
                     class="size-full object-cover" loading="lazy" decoding="async">
            </div>
        </div>
    </section>

    <section id="implementacao" class="relative mx-auto mt-24 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-12 lg:px-[62px]">
        <div class="flex flex-col-reverse items-center gap-8 rounded border border-outline-light bg-white/32 p-6 backdrop-blur-[16px] lg:flex-row lg:p-16">
            <div class="fm-reveal-left fm-zoom aspect-[741/642] w-full overflow-hidden lg:w-[46%] lg:shrink-0">
                <img src="{{ asset('img/home/implementacao.webp') }}"
                     alt="Colaborador sorrindo enquanto usa o celular no escritório"
                     class="size-full object-cover" loading="lazy" decoding="async">
            </div>

            <div class="flex flex-col gap-8 lg:flex-1">
                <h2 class="fm-reveal-right text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                    Uma solução que se <span class="text-brand-primary">adapta à realidade</span> da sua empresa.
                </h2>
                <div class="fm-reveal-right flex flex-col text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal" data-fm-delay="1">
                    <p>Cada organização possui uma estrutura, uma cultura e diferentes necessidades de cuidado com seus colaboradores.</p>
                    <p>Converse com nosso time para conhecer as possibilidades de implementação da Flamma e entender como incorporar a saúde financeira à sua estratégia de saúde e bem-estar.</p>
                </div>
                <x-button
                    class="fm-reveal-right fm-btn w-full! sm:w-fit!"
                    data-fm-delay="2"
                    variant="flat"
                    size="xl"
                    href="https://wa.me/5511976205711?text=Flamma"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Converse com nosso time
                </x-button>
            </div>
        </div>
    </section>

    <section id="contratar" class="relative mx-auto mt-24 max-w-[1800px] scroll-mt-28 px-5 sm:px-8 lg:mt-12 lg:px-[62px]">
        <x-graphism type="asterisk" class="absolute -top-[45px] left-1/2 -z-10 -ml-[calc(50vw+150px)] hidden size-[330px] lg:block" />
        <x-graphism type="home-arrow" data-fm-static class="absolute left-1/2 top-[242px] -z-10 ml-[calc(50vw-279px)] hidden w-[295px] lg:block" />

        <div class="flex flex-col items-center gap-8 rounded border border-outline-light bg-white/16 p-6 text-center backdrop-blur-[8px] lg:p-16">
            <div class="fm-reveal-scale fm-zoom aspect-[1434/580] w-full overflow-hidden lg:w-[1434px] lg:max-w-full">
                <img src="{{ asset('img/home/cta.webp') }}"
                     alt="Duas colegas conversando e rindo durante um café no escritório"
                     class="size-full object-cover" loading="lazy" decoding="async">
            </div>
            <h2 class="fm-reveal text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                Amplie o cuidado com <span class="text-brand-primary">quem faz</span> sua empresa acontecer.
            </h2>
            <p class="fm-reveal text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal" data-fm-delay="1">
                A saúde financeira faz parte do bem-estar das pessoas. Com a Flamma, sua empresa pode incorporar essa dimensão à estratégia de cuidado e oferecer mais apoio para que os colaboradores lidem com o presente e planejem o futuro.
            </p>
            <x-button
                class="fm-reveal fm-btn w-full! sm:w-fit!"
                data-fm-delay="2"
                variant="flat"
                size="xl"
                href="https://wa.me/5511976205711?text=Flamma"
                target="_blank"
                rel="noopener noreferrer"
            >
                Conheça a Flamma
            </x-button>
        </div>
    </section>

    <section id="faq" class="relative mx-auto mt-24 max-w-[941px] scroll-mt-28 px-5 sm:px-8 lg:mt-20 lg:px-0">
        <div class="flex flex-col gap-8">
            <div class="flex flex-col gap-6 lg:gap-[27px] lg:px-8 lg:py-4">
                <h2 class="fm-reveal text-[32px] font-bold leading-[1.5] text-high lg:text-5xl">
                    Perguntas frequentes
                </h2>
                <p class="fm-reveal text-base font-medium leading-[1.5] text-medium lg:text-xl lg:font-normal" data-fm-delay="1">
                    Entenda como a Flamma pode fazer parte da estratégia de saúde e bem-estar da sua empresa.
                </p>
            </div>

            <div class="flex flex-col justify-center gap-4">
                @foreach ([
                    ['O que é a Flamma?', 'A Flamma é uma solução de saúde financeira para empresas. Ela oferece aos colaboradores orientação financeira especializada, em sessões individuais, para organizar as finanças, tomar decisões com mais segurança e planejar o futuro.'],
                    ['Por que a saúde financeira é importante para as empresas?', 'Preocupações com dinheiro afetam o sono, a saúde, a concentração e a energia das pessoas, e esses impactos chegam ao trabalho. Cuidar da saúde financeira amplia a estratégia de bem-estar da empresa e ajuda a prevenir que dificuldades se tornem problemas maiores.'],
                    ['A Flamma é apenas para pessoas endividadas?', 'Não. A Flamma apoia diferentes momentos financeiros: organizar o orçamento, lidar com dívidas, construir uma reserva, realizar objetivos e começar a investir.'],
                    ['Como a Flamma funciona para empresas?', 'A empresa contrata o benefício com valor por colaborador, e cada pessoa do time passa a ter acesso à orientação financeira, agendando suas sessões no horário que preferir.'],
                    ['Como funciona a privacidade das informações dos colaboradores?', 'A empresa contrata o benefício, mas quem decide o que compartilhar em cada sessão é o colaborador, e essa informação fica só entre ele e o consultor.'],
                    ['Como implementar a Flamma na minha empresa?', 'Fale com o nosso time. Vamos entender a estrutura e as necessidades da sua empresa e apresentar as possibilidades de implementação.'],
                ] as $i => $faq)
                    <div x-data="{ open: false }"
                         class="fm-reveal fm-faq group rounded border border-outline-light bg-elevation-01dp transition-colors duration-300 hover:border-brand-primary"
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

            <x-button
                class="fm-reveal fm-btn w-full! sm:w-fit!"
                variant="flat"
                size="xl"
                href="https://wa.me/5511976205711?text=Flamma"
                target="_blank"
                rel="noopener noreferrer"
            >
                Fale com a gente
            </x-button>
        </div>
    </section>


    {{-- Onda em degraus que faz a transição para o rodapé (Vector 10 no design),
         encurtada aqui para o FAQ ficar mais perto do rodapé --}}
    <x-sections.footer-wave top-margin="mt-12 lg:mt-[10px]" height="h-[80px] lg:h-[180px]" />
</div>
</x-layouts.site>

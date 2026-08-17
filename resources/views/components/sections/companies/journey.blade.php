@php
    /*
     * A arte dos três cards é posicionada em px absolutos, no palco de
     * 482,229 × 327,671 do Figma — o CSS de .fcard-stage escala esse palco
     * inteiro para a largura real do card. Sombra e foto têm left/top/width/height
     * próprios; nada aqui é centralizado.
     *
     * As sombras são vetores borrados: elipse nos dois primeiros cards e um
     * paralelogramo no "Sigilo" (Rectangle1226 no Figma), cujos vértices viram
     * o clip-path abaixo, em % da caixa da sombra.
     */
    $steps = [
        [
            'icon' => 'handshake.svg',
            'image' => 'card-contract.webp',
            'title' => 'Contratação',
            'description' => 'Você escolhe o pacote ideal para o tamanho do seu time.',
            'stage' => [
                'shadow' => ['shape' => 'ellipse', 'left' => 62.85, 'top' => 276.38, 'width' => 340.109, 'height' => 26.601],
                'prod' => ['left' => 18.53, 'top' => 32.08, 'width' => 428.746, 'height' => 278.836],
            ],
        ],
        [
            'icon' => 'target.svg',
            'image' => 'card-reality.webp',
            'title' => 'Realidade',
            'description' => 'Seu time recebe acesso e agenda no horário que preferir.',
            'stage' => [
                'shadow' => ['shape' => 'ellipse', 'left' => 145.85, 'top' => 284.32, 'width' => 190.523, 'height' => 26.601],
                'prod' => ['left' => 57.45, 'top' => 0, 'width' => 376.992, 'height' => 327.671],
            ],
        ],
        [
            'icon' => 'lock-key.svg',
            'image' => 'card-secrecy.webp',
            'title' => 'Sigilo',
            'description' => 'Sessões individuais e sigilosas, sem que a empresa precise acompanhar ou intermediar.',
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
    ];
@endphp

{{--
    Faixa em gradiente, sangrando de ponta a ponta. No mobile os cards viram um carrossel
    horizontal com snap (como no design, cards de 284px); no desktop, grid de três colunas.
--}}
<section
    {{ $attributes->class([
        'relative left-1/2 -mx-[50vw] w-screen scroll-mt-28 overflow-hidden py-12 lg:pb-[116px] lg:pt-[149px]',
    ]) }}
    style="background: linear-gradient(116.67deg, #E2410A 61.49%, #FD0342 100%)"
    id="como-funciona"
>
    {{-- Barra translúcida no topo à direita e asterisco branco na margem esquerda, como no design --}}
    <div
        class="absolute right-0 top-0 hidden h-[80px] w-[448px] bg-white/32 lg:block"
        aria-hidden="true"
    ></div>

    <x-graphism
        color="light"
        class="absolute bottom-[8%] -left-20 w-40 lg:-left-24 lg:w-[347px]"
    />

    <div class="relative mx-auto flex max-w-[1800px] flex-col gap-8 px-5 sm:px-8 lg:gap-[74px] lg:px-[62px]">
        <header class="flex flex-col items-center gap-4 text-center lg:gap-8">
            <h2 class="text-[32px] font-bold leading-[1.5] text-light lg:text-[64px]">
                Do fechamento à primeira sessão
            </h2>

            <p class="text-base font-medium leading-[1.5] text-light lg:text-xl lg:font-normal">
                Depois da contratação o processo roda sozinho, sem gerar trabalho extra para o RH. Cada
                colaborador recebe o acesso, escolhe o horário e é atendido por um consultor especializado.
            </p>
        </header>

        <div
            class="-mx-4 flex snap-x snap-mandatory gap-3 overflow-x-auto px-4 pb-4 sm:-mx-6 sm:px-6
                lg:mx-0 lg:grid lg:grid-cols-3 lg:gap-8 lg:overflow-visible lg:px-0 lg:pb-0"
        >
            @foreach ($steps as $step)
                <x-partials.illustration-card
                    class="w-[284px] shrink-0 snap-start lg:w-auto lg:shrink"
                    :icon="$step['icon']"
                    :image="$step['image']"
                    :stage="$step['stage']"
                    :title="$step['title']"
                    :description="$step['description']"
                />
            @endforeach
        </div>
    </div>
</section>

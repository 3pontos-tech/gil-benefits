@props([
    'title' => '',
    'description' => '',
    'icon' => '',    // nome do svg em public/svg/companies
    'image' => '',   // ilustração 3D em public/img/companies
    'stage' => [],   // geometria das camadas, em px do palco 482,229 × 327,671 do Figma
])

@php
    $shadow = $stage['shadow'] ?? null;
    $prod = $stage['prod'] ?? null;

    $px = fn (array $box, array $keys) => collect($keys)
        ->map(fn ($k) => "{$k}: {$box[$k]}px")
        ->implode('; ');
@endphp

<article
    {{ $attributes->class([
        'flex flex-col gap-6',
        'bg-elevation-surface border border-outline-light',
        'p-[33px]',
    ]) }}
>
    {{--
        O palco reproduz as camadas do Figma: o fundo em gradiente, a sombra do
        produto e a foto, cada uma com as suas próprias coordenadas.
    --}}
    <div class="fcard-stage-wrap">
        <div class="fcard-stage">
            {{--
                O gradiente é filho do palco, e não fundo do wrapper, de propósito: o
                transform do palco cria um contexto de empilhamento, e a máscara em
                mix-blend-mode só enxerga o que é pintado dentro dele. Com o gradiente
                por fora, o `lighten` blendava contra transparência e a máscara saía
                como uma silhueta rosa chapada em vez de sumir sobre a arte.
            --}}
            <div class="fcard-bg" aria-hidden="true"></div>

            @if ($shadow)
                <div
                    class="fcard-shadow"
                    style="{{ $px($shadow, ['left', 'top', 'width', 'height']) }};
                        @if (($shadow['shape'] ?? 'ellipse') === 'ellipse') border-radius: 50%;
                        @else clip-path: {{ $shadow['clipPath'] }}; @endif"
                    aria-hidden="true"
                ></div>
            @endif

            @if ($prod)
                <div
                    class="fcard-prod fm-card-prod"
                    data-card-prod
                    style="{{ $px($prod, ['left', 'top', 'width', 'height']) }}"
                >
                    {{--
                        Não há camada de máscara aqui de propósito. No Figma o grupo do
                        produto é `imagem` + `Mask group`, e dentro do Mask group está a
                        MESMA imagem outra vez, recortada por Rectangle 1136/1137/1138 —
                        ou seja, a arte sobreposta a si mesma, visualmente inerte. Não é
                        uma máscara rosa. Implementá-la como preenchimento rosa em
                        mix-blend-mode tingia os cards de magenta e afastava do Figma:
                        medido contra o render do Figma, o erro médio dos três cards
                        caiu de 5,98 para 5,58 (em 255) ao remover a camada.
                        Os assets card-*-mask.webp continuam no repositório sem uso.
                    --}}
                    <img
                        src="{{ asset('img/companies/' . $image) }}"
                        alt=""
                        loading="lazy"
                        decoding="async"
                    />
                </div>
            @endif
        </div>
    </div>

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center gap-6">
            <img
                src="{{ asset('svg/companies/' . $icon) }}"
                alt=""
                class="fm-card-icon size-[31px] shrink-0"
                loading="lazy"
                decoding="async"
            />
            <h3 class="text-2xl font-bold leading-[1.5] text-high">
                {{ $title }}
            </h3>
        </div>

        <p class="text-base font-medium leading-[1.5] text-medium">
            {{ $description }}
        </p>
    </div>
</article>

@props([
    // O respiro antes da onda muda entre as duas páginas: na Para Empresas o Figma
    // deixa 104px depois do CTA, na home a onda quase encosta no FAQ (10px).
    'topMargin' => 'mt-20 lg:mt-28',
])

{{--
    Transição em degraus para o rodapé, usada pela home (Vector 10) e pela Para Empresas
    (Vector 21) — é a mesma silhueta nos dois frames, espelhada na horizontal.
    Sangra de ponta a ponta e encosta no rodapé, com o mesmo gradiente da marca — daí o
    <linearGradient> em vez de currentColor: o SVG é esticado por preserveAspectRatio, e
    userSpaceOnUse mantém o eixo do gradiente no sistema do viewBox.
    `preserveAspectRatio="none"` deixa a silhueta esticar com a largura, como no Figma.
--}}
<div class="relative left-1/2 -mx-[50vw] w-screen {{ $topMargin }}" aria-hidden="true">
    <svg
        class="block h-[80px] w-full -scale-x-100 lg:h-[329px]"
        viewBox="0 0 1920 329"
        fill="none"
        preserveAspectRatio="none"
        xmlns="http://www.w3.org/2000/svg"
    >
        <path
            d="M1920 329H0V195.552H338.5H397.121H1046V252.5H1486.5H1573H1666.02V0H1920V329Z"
            fill="url(#footerWaveGradient)"
        />

        <defs>
            {{--
                Eixo invertido (de 1920 para 0) de propósito: a silhueta é espelhada por
                `-scale-x-100`, e sem isso o gradiente saía laranja→vermelho, contra o
                sentido do rodapé logo abaixo.
            --}}
            <linearGradient
                id="footerWaveGradient"
                x1="1920" y1="0" x2="0" y2="329"
                gradientUnits="userSpaceOnUse"
            >
                <stop offset="0.1542" stop-color="#FD0342"/>
                <stop offset="0.8458" stop-color="#FF803C"/>
            </linearGradient>
        </defs>
    </svg>
</div>

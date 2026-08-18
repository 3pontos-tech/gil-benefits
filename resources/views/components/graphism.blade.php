@props([
    'type' => 'asterisk', // veja $glyphs abaixo
    'color' => 'primary', // primary|light
])

{{--
    Grafismos da marca. O design usa dois glifos — o asterisco de quatro traços e a seta
    grossa — sempre em escalas e recortes diferentes.

    `asterisk` e `arrow` são os glifos inteiros, para uso livre (o tamanho vem das classes
    de quem chama; a seta aponta para ↖ na origem, gire com `rotate-*`). Os demais são os
    recortes exatos das composições do Figma, onde o glifo entra cortado pela borda da
    peça: reproduzem o viewBox e o traçado do nó original.

    Todos são SVG inline: as versões exportadas do Figma vinham com o fundo da prancheta
    (um <rect> #FBFBFF) embutido, que aparecia como um quadrado branco por cima das faixas
    escuras. Desenhando na mão, sobra só o traço.
--}}

@php
    // stroke: espessura dos traços do nó · paths: traços · fills: as diagonais que o
    // Figma achatou em vetor preenchido em vez de manter como stroke.
    $glyphs = [
        'asterisk' => [
            'viewBox' => '0 0 347 347',
            'stroke' => '61.0935',
            'paths' => [
                'M173.444 0V347',
                'M347 173.438H0',
                'M296.133 50.7803L50.7661 296.147',
                'M296.22 296.155L50.854 50.7891',
            ],
        ],

        /*
         * O viewBox começa em y=1,464 de propósito: o traço horizontal está em y=31,46
         * com 60 de espessura, ou seja a arte começa em 1,464 — e o nó do Figma vinha
         * com essa folga no topo. Como a seta é usada girada em 180°, a folga virava
         * uma lasca de ~1,5px na base do desenho. Recortando o viewBox, o traçado
         * encosta nas quatro bordas e a caixa fica quadrada (270,23 × 270,23).
         */
        'arrow' => [
            'viewBox' => '0 1.464 270.23 270.23',
            'stroke' => '60',
            'preserveAspectRatio' => 'none',
            'paths' => [
                'M30 0L30 271.693',
                'M270.23 31.46L26.9516 31.4608',
                'M199.943 201.308L28.7506 25.0799',
            ],
        ],

        // Home — seta ↗ do canto superior direito da seção "Como o Flamma chega
        // até o seu time?" (Frame 20324).
        'home-arrow' => [
            'viewBox' => '0 0 295 296',
            'stroke' => '64',
            'paths' => [
                'M297.992 32H-0.000280005',
                'M263.484 295.482L263.484 28.6559',
                'M77.2092 218.395L270.496 30.6312',
            ],
        ],

        // Home — asterisco sobre a massa escura do "plano" (Group 31834), cortado
        // à direita: o traço horizontal segue até 540 num viewBox de 488.
        'home-asterisk' => [
            'viewBox' => '0 0 488 550',
            'stroke' => '61.0935',
            'paths' => [
                'M270.248 0V550',
                'M540.671 274.9H0',
                'M461.412 80.4873L79.0996 469.396',
                'M461.548 469.41L79.2363 80.501',
            ],
        ],

        // Colaborador — seta ↘ ao lado do hero (Group 31818).
        'collaborator-arrow' => [
            'viewBox' => '0 0 324 337',
            'stroke' => '77.8678',
            'paths' => [
                'M298.324 336.428L298.324 -1.07115e-05',
                'M0 297.473L302.111 297.472',
            ],
            'fills' => [
                'M87.279 87.1675L59.3123 114.257L271.905 332.474L299.872 305.384L327.838 278.295L115.246 60.078L87.279 87.1675Z',
            ],
        ],

        // Colaborador — a mesma seta na seção de privacidade (Group 31819), com
        // alguns pixels a mais de folga à direita.
        'collaborator-arrow-alt' => [
            'viewBox' => '0 0 335 337',
            'stroke' => '77.8678',
            'paths' => [
                'M295.23 336.428L295.23 -1.07115e-05',
                'M0 297.473L298.988 297.472',
            ],
            'fills' => [
                'M86.3658 87.1675L58.5444 114.398L268.94 332.614L296.761 305.384L324.583 278.154L114.187 59.9372L86.3658 87.1675Z',
            ],
        ],
    ];

    $glyph = $glyphs[$type] ?? $glyphs['asterisk'];
@endphp

<svg
    aria-hidden="true"
    viewBox="{{ $glyph['viewBox'] }}"
    fill="none"
    @if (isset($glyph['preserveAspectRatio'])) preserveAspectRatio="{{ $glyph['preserveAspectRatio'] }}" @endif
    xmlns="http://www.w3.org/2000/svg"
    {{ $attributes->class([
        'pointer-events-none shrink-0',
        'text-brand-primary' => $color === 'primary',
        'text-icon-light' => $color === 'light',
    ]) }}
>
    <g stroke="currentColor" stroke-width="{{ $glyph['stroke'] }}">
        @foreach ($glyph['paths'] as $path)
            <path d="{{ $path }}"/>
        @endforeach
    </g>

    @foreach ($glyph['fills'] ?? [] as $fill)
        <path d="{{ $fill }}" fill="currentColor"/>
    @endforeach
</svg>

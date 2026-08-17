@props([
    'type' => 'asterisk', // asterisk|arrow
    'color' => 'primary', // primary|light
])

{{--
    Grafismos da marca. O design usa dois glifos, sempre em escalas e recortes diferentes:
    o asterisco de quatro traços e a seta grossa (que aponta para ↖ na origem — gire com
    `rotate-*` para as outras direções). O tamanho vem das classes de quem chama.
--}}
@if ($type === 'arrow')
    <svg
        aria-hidden="true"
        viewBox="0 0 270.23 271.694"
        fill="none"
        preserveAspectRatio="none"
        xmlns="http://www.w3.org/2000/svg"
        {{ $attributes->class([
            'pointer-events-none shrink-0',
            'text-brand-primary' => $color === 'primary',
            'text-icon-light' => $color === 'light',
        ]) }}
    >
        <g stroke="currentColor" stroke-width="60">
            <path d="M30 0L30 271.693"/>
            <path d="M270.23 31.46L26.9516 31.4608"/>
            <path d="M199.943 201.308L28.7506 25.0799"/>
        </g>
    </svg>
@else
    <svg
        aria-hidden="true"
        viewBox="0 0 347 347"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        {{ $attributes->class([
            'pointer-events-none shrink-0',
            'text-brand-primary' => $color === 'primary',
            'text-icon-light' => $color === 'light',
        ]) }}
    >
        <g stroke="currentColor" stroke-width="61.0935">
            <path d="M173.444 0V347"/>
            <path d="M347 173.438H0"/>
            <path d="M296.133 50.7803L50.7661 296.147"/>
            <path d="M296.22 296.155L50.854 50.7891"/>
        </g>
    </svg>
@endif

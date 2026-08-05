{{--
    As duas frases sob o avatar, na ordem do layout. Ficam aqui em vez de no
    helperText do campo para eu controlar ordem, tamanho e destaque — o helperText
    sairia sempre logo após o campo, antes desta frase.

    A frase de upload é duplicada de propósito: o FilePond a mostra dentro do
    círculo, mas só enquanto está vazio. Repetida aqui, ela continua visível
    depois que o avatar é enviado. Este texto é informativo, não clicável — quem
    abre o seletor de arquivos é o próprio círculo.
--}}
@php /** @var \Illuminate\Support\HtmlString $hint */ @endphp
@php /** @var string $helper */ @endphp

<div class="w-full">
    <p class="text-center text-[20px] leading-normal text-gray-950 dark:text-white">
        {{ $hint }}
    </p>

    <p class="mt-3 text-center text-[16px] text-gray-500 dark:text-gray-400">
        {{ $helper }}
    </p>
</div>

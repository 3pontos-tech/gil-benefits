@props([
    'title' => null,
    'description' => null,
])

{{--
    Layout do site institucional (home, /para-empresas, /colaborador).

    Estas páginas viviam no painel guest do Filament e herdavam o template dele —
    inclusive o padding do <main> (16/24/32px) e os 32px verticais do cabeçalho da
    página, que empurravam o conteúdo para dentro. Aqui o <main> é cru: cada seção
    controla a própria largura com o container de 1800px, e as faixas full-bleed
    encostam de fato na borda da viewport.

    A casca (topo, fundo, tipografia) reproduz as medidas do painel — ver
    resources/css/site.css.
--}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ filled($title) ? $title . ' - ' : '' }}Flamma</title>

    @if (filled($description))
        <meta name="description" content="{{ $description }}">
    @endif

    {{-- Camada de movimento das três páginas (classes fm-*), mais o bundle do site. --}}
    @vite(['resources/css/site.css', 'resources/js/flamma-motion.js'])

    @livewireStyles
</head>
<body>
    <x-site.header />

    <main class="w-full">
        {{ $slot }}
    </main>

    <x-guest-footer />

    @livewireScripts
</body>
</html>

{{-- @param string $total @param string $completed  polyline points --}}
{{-- @param string $area  area path @param array<int, array{label: string, x: float}> $labels --}}
<svg viewBox="0 0 600 200" class="h-44 w-full" style="overflow: visible">
    <line x1="10" y1="46" x2="590" y2="46" class="stroke-gray-200 dark:stroke-white/10" stroke-width="1" />
    <line x1="10" y1="100" x2="590" y2="100" class="stroke-gray-200 dark:stroke-white/10" stroke-width="1" />
    <line x1="10" y1="154" x2="590" y2="154" class="stroke-gray-200 dark:stroke-white/10" stroke-width="1" />

    @if (filled($area))
        <path d="{{ $area }}" class="fill-emerald-500/15" />
    @endif

    @if (filled($total))
        <polyline points="{{ $total }}" fill="none" class="stroke-primary-500" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round" opacity="0.9" />
    @endif

    @if (filled($completed))
        <polyline points="{{ $completed }}" fill="none" class="stroke-emerald-500" stroke-width="2.6" stroke-linejoin="round" stroke-linecap="round" />
    @endif

    @foreach ($labels as $label)
        <text x="{{ $label['x'] }}" y="194" text-anchor="middle" class="fill-gray-400" font-size="10">{{ $label['label'] }}</text>
    @endforeach
</svg>

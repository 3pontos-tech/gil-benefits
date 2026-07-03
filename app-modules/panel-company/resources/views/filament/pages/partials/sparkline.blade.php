{{-- @param string $points  polyline points --}}
{{-- @param string $stroke  literal stroke-* class --}}
{{-- @param int $width @param int $height --}}
<svg viewBox="0 0 100 32" preserveAspectRatio="none" style="width: {{ $width ?? 84 }}px; height: {{ $height ?? 30 }}px; overflow: visible">
    @if (filled($points))
        <polyline
            points="{{ $points }}"
            fill="none"
            class="{{ $stroke }}"
            stroke-width="2.4"
            stroke-linejoin="round"
            stroke-linecap="round"
        />
    @endif
</svg>

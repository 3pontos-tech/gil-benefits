{{-- @param string $background @param string $value  gauge arc paths --}}
{{-- @param string $score  formatted average @param string $caption --}}
<svg viewBox="0 0 200 116" style="width: 190px; height: 110px">
    <path d="{{ $background }}" class="stroke-gray-200 dark:stroke-white/10" stroke-width="15" fill="none" stroke-linecap="round" />
    <path d="{{ $value }}" class="stroke-primary-500" stroke-width="15" fill="none" stroke-linecap="round" />
    <text x="100" y="92" text-anchor="middle" class="fill-gray-950 dark:fill-white" font-size="34" font-weight="700">{{ $score }}</text>
    <text x="100" y="108" text-anchor="middle" class="fill-gray-400" font-size="11">{{ $caption }}</text>
</svg>

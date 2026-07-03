{{-- @param array<int, array{path: string, fill: string}> $slices --}}
{{-- @param string $centerValue @param string $centerLabel --}}
<svg viewBox="0 0 120 120" class="shrink-0" style="width: 104px; height: 104px">
    @foreach ($slices as $slice)
        <path d="{{ $slice['path'] }}" class="{{ $slice['fill'] }}" />
    @endforeach
    <text x="60" y="56" text-anchor="middle" class="fill-gray-950 dark:fill-white" font-size="20" font-weight="700">{{ $centerValue }}</text>
    <text x="60" y="71" text-anchor="middle" class="fill-gray-400" font-size="9">{{ $centerLabel }}</text>
</svg>

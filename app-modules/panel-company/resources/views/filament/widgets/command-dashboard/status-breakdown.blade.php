@use('TresPontosTech\PanelCompany\Support\MetricsNumber')

@php
    $dot = [
        'primary' => 'bg-primary-500', 'emerald' => 'bg-emerald-500', 'blue' => 'bg-blue-500',
        'violet' => 'bg-violet-500', 'pink' => 'bg-pink-500', 'amber' => 'bg-amber-500',
        'teal' => 'bg-teal-500', 'red' => 'bg-red-500', 'orange' => 'bg-orange-500', 'neutral' => 'bg-gray-400',
    ];
    $card = 'rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900';
    $label = 'text-sm font-semibold text-gray-500 dark:text-gray-400';
    $num = 'font-mono tabular-nums tracking-tight';
    $muted = 'text-gray-400 dark:text-gray-500';
    $br = fn (int|float $n): string => MetricsNumber::integer($n);
    $pct = fn (float $n): string => MetricsNumber::percent($n);
@endphp

<x-filament-widgets::widget>
    <section class="{{ $card }} h-full">
        <p class="{{ $label }} mb-3">{{ __('panel-company::resources.pages.command_dashboard.status.heading') }}</p>
        @if ($data->segments === [])
            <p class="py-8 text-center text-sm {{ $muted }}">{{ __('panel-company::resources.pages.command_dashboard.status.empty') }}</p>
        @else
            <div class="mb-4 flex h-3.5 overflow-hidden rounded-full">
                @foreach ($data->segments as $segment)
                    @php $width = max(0, min(100, (float) $segment->percent)); @endphp
                    <div class="h-full {{ $dot[$segment->color] ?? 'bg-gray-400' }}" style="width: {{ $width }}%"></div>
                @endforeach
            </div>
            <div class="flex flex-col gap-2.5">
                @foreach ($data->segments as $segment)
                    <div class="flex items-center gap-2.5">
                        <span class="size-2.5 rounded-sm {{ $dot[$segment->color] ?? 'bg-gray-400' }}"></span>
                        <span class="flex-1 text-xs text-gray-600 dark:text-gray-300">{{ $segment->label }}</span>
                        <span
                            class="{{ $num }} text-xs font-semibold text-gray-900 dark:text-white">{{ $br($segment->value) }}</span>
                        <span
                            class="{{ $num }} w-10 text-right text-xs {{ $muted }}">{{ $pct($segment->percent) }}%</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</x-filament-widgets::widget>

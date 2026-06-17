@use('TresPontosTech\PanelCompany\Support\MetricsNumber')

@php
    $card = 'rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900';
    $label = 'text-sm font-semibold text-gray-500 dark:text-gray-400';
    $num = 'font-mono tabular-nums tracking-tight';
    $br = fn (int|float $n): string => MetricsNumber::integer($n);
    $pct = fn (float $n): string => MetricsNumber::percent($n);
@endphp

<x-filament-widgets::widget>
    <section class="{{ $card }}">
        <div class="flex items-center justify-between">
            <p class="{{ $label }}">{{ __('panel-company::resources.pages.command_dashboard.trend.heading') }}</p>
            <div class="flex gap-4 text-xs">
                <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="size-2 rounded-sm bg-emerald-500"></span>{{ __('panel-company::resources.pages.command_dashboard.trend.completed') }}</span>
                <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="size-2 rounded-sm bg-primary-500"></span>{{ __('panel-company::resources.pages.command_dashboard.trend.scheduled') }}</span>
            </div>
        </div>

        <div class="mt-1 flex items-baseline gap-2">
            <span class="{{ $num }} text-2xl font-bold text-gray-900 dark:text-white">{{ $br($completedTotal) }}</span>
            @if ($growthFactor !== null)
                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">▲ {{ $pct($growthFactor) }}× {{ __('panel-company::resources.pages.command_dashboard.trend.growth') }}</span>
            @endif
        </div>

        @include('panel-company::filament.pages.partials.trend', [
            'total' => $totalPolyline,
            'completed' => $completedPolyline,
            'area' => $completedArea,
            'labels' => $labels,
        ])
    </section>
</x-filament-widgets::widget>

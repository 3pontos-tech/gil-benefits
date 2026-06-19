@use('TresPontosTech\PanelCompany\Support\MetricsNumber')

@php
    $card = 'rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900';
    $label = 'text-sm font-semibold text-gray-500 dark:text-gray-400';
    $num = 'font-mono tabular-nums tracking-tight';
    $muted = 'text-gray-400 dark:text-gray-500';
    $br = fn (int|float $n): string => MetricsNumber::integer($n);
    $pct = fn (float $n): string => MetricsNumber::percent($n);
@endphp

<x-filament-widgets::widget>
    <section class="{{ $card }} flex flex-col items-center">
        <p class="{{ $label }} mb-1 self-start">{{ __('panel-company::resources.pages.command_dashboard.satisfaction.heading') }}</p>
        @if ($data->total === 0)
            <p class="py-10 text-center text-sm {{ $muted }}">{{ __('panel-company::resources.pages.command_dashboard.satisfaction.empty') }}</p>
        @else
            @include('panel-company::filament.pages.partials.gauge', [
                'background' => $gaugeBackground,
                'value' => $gaugeValue,
                'score' => MetricsNumber::decimal($data->avg),
                'caption' => __('panel-company::resources.pages.command_dashboard.satisfaction.out_of', ['count' => $br($data->total)]),
            ])
            <div class="mt-2 flex gap-6">
                <div class="text-center">
                    <p class="{{ $num }} text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ $data->nps }}</p>
                    <p class="text-xs {{ $muted }}">{{ __('panel-company::resources.pages.command_dashboard.satisfaction.nps') }}</p>
                </div>
                <div class="text-center">
                    <p class="{{ $num }} text-xl font-bold text-gray-900 dark:text-white">{{ $pct($data->recommend) }}%</p>
                    <p class="text-xs {{ $muted }}">{{ __('panel-company::resources.pages.command_dashboard.satisfaction.recommend') }}</p>
                </div>
            </div>
        @endif
    </section>
</x-filament-widgets::widget>

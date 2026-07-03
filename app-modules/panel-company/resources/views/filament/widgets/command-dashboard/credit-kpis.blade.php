@use('TresPontosTech\PanelCompany\Support\MetricsNumber')

@php
    $stroke = [
        'primary' => 'stroke-primary-500', 'success' => 'stroke-emerald-500',
        'info' => 'stroke-blue-500', 'neutral' => 'stroke-gray-400',
    ];
    $capTone = [
        'primary' => 'text-gray-500 dark:text-gray-400', 'success' => 'text-emerald-600 dark:text-emerald-400',
        'info' => 'text-gray-500 dark:text-gray-400', 'neutral' => 'text-gray-500 dark:text-gray-400',
    ];
    $card = 'rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900';
    $label = 'text-sm font-semibold text-gray-500 dark:text-gray-400';
    $num = 'font-mono tabular-nums tracking-tight';
    $muted = 'text-gray-400 dark:text-gray-500';
    $br = fn (int|float $n): string => MetricsNumber::integer($n);
@endphp

<x-filament-widgets::widget>
    <div class="flex h-full flex-col gap-2">
        <div class="grid flex-1 grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($kpis as $kpi)
                <div class="{{ $card }} flex flex-col justify-between">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="{{ $label }}">{{ $kpi->label }}</p>
                            <p class="{{ $num }} mt-1 text-3xl font-bold leading-none text-gray-900 dark:text-white">{{ $br($kpi->value) }}</p>
                        </div>
                        @include('panel-company::filament.pages.partials.sparkline', [
                            'points' => $kpi->sparkline,
                            'stroke' => $stroke[$kpi->tone] ?? 'stroke-gray-400',
                            'width' => 84,
                            'height' => 30,
                        ])
                    </div>
                    <p class="mt-3 text-xs font-medium {{ $capTone[$kpi->tone] ?? $muted }}">{{ $kpi->caption }}</p>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>

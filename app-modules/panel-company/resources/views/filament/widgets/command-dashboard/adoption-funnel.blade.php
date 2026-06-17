@use('TresPontosTech\PanelCompany\Support\MetricsNumber')

@php
    $card = 'rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900';
    $label = 'text-sm font-semibold text-gray-500 dark:text-gray-400';
    $num = 'font-mono tabular-nums tracking-tight';
    $muted = 'text-gray-400 dark:text-gray-500';
    $funnelBar = ['bg-primary-500/30', 'bg-primary-500/60', 'bg-primary-500'];
    $br = fn (int|float $n): string => MetricsNumber::integer($n);
    $pct = fn (float $n): string => MetricsNumber::percent($n);
@endphp

<x-filament-widgets::widget>
    <section class="{{ $card }}">
        <div class="flex items-start justify-between">
            <div>
                <p class="{{ $label }}">{{ __('panel-company::resources.pages.command_dashboard.funnel.heading') }}</p>
                <p class="text-xs {{ $muted }}">{{ __('panel-company::resources.pages.command_dashboard.funnel.subtitle') }}</p>
            </div>
            <p class="{{ $num }} text-4xl font-bold leading-none text-primary-600 dark:text-primary-400">{{ $pct($data->adoptionRate) }}
                %</p>
        </div>

        <div class="mt-5 flex flex-col gap-3">
            @foreach ($data->steps as $i => $step)
                <div>
                    <div class="mb-1.5 flex justify-between text-xs">
                        <span class="text-gray-600 dark:text-gray-300">{{ $step->label }}</span>
                        <span
                            class="{{ $num }} {{ $muted }}">{{ $br($step->value) }} · {{ $pct($step->percent) }}%</span>
                    </div>
                    <div class="h-3 overflow-hidden rounded-md bg-gray-100 dark:bg-white/5">
                        <div class="h-full rounded-md {{ $funnelBar[$i] ?? 'bg-primary-500' }}"
                             style="width: {{ $step->percent }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-5 flex gap-6 border-t border-gray-100 pt-4 dark:border-white/5">
            <div>
                <p class="{{ $num }} text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $br($data->noAccess) }}</p>
                <p class="text-xs {{ $muted }}">{{ __('panel-company::resources.pages.command_dashboard.funnel.no_access') }}</p>
            </div>
            <div>
                <p class="{{ $num }} text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $br($data->accessNoPlan) }}</p>
                <p class="text-xs {{ $muted }}">{{ __('panel-company::resources.pages.command_dashboard.funnel.access_no_plan') }}</p>
            </div>
            <div>
                <p class="{{ $num }} text-lg font-semibold text-emerald-600 dark:text-emerald-400">
                    +{{ $br($data->newThisMonth) }}</p>
                <p class="text-xs {{ $muted }}">{{ __('panel-company::resources.pages.command_dashboard.funnel.new_this_month') }}</p>
            </div>
        </div>
    </section>
</x-filament-widgets::widget>

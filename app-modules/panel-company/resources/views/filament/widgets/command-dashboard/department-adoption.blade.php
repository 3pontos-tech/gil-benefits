@use('TresPontosTech\PanelCompany\Support\MetricsNumber')

@php
    $card = 'rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900';
    $label = 'text-sm font-semibold text-gray-500 dark:text-gray-400';
    $num = 'font-mono tabular-nums tracking-tight';
    $muted = 'text-gray-400 dark:text-gray-500';
    $pct = fn (float $n): string => MetricsNumber::percent($n);
@endphp

<x-filament-widgets::widget>
    <section class="{{ $card }} h-full">
        <p class="{{ $label }} mb-4">{{ __('panel-company::resources.pages.command_dashboard.departments.heading') }}</p>
        @if ($departments === [])
            <p class="py-8 text-center text-sm {{ $muted }}">{{ __('panel-company::resources.pages.command_dashboard.departments.empty') }}</p>
        @else
            <div class="flex flex-col gap-3.5">
                @foreach ($departments as $department)
                    @php $tone = $department->percent >= 70 ? 'bg-primary-500' : ($department->percent >= 50 ? 'bg-violet-500' : 'bg-pink-500'); @endphp
                    <div>
                        <div class="mb-1 flex justify-between text-xs">
                            <span class="text-gray-600 dark:text-gray-300">{{ $department->label }}</span>
                            <span class="{{ $num }} {{ $muted }}">{{ $pct($department->percent) }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-sm bg-gray-100 dark:bg-white/5">
                            <div class="h-full rounded-sm {{ $tone }}" style="width: {{ $department->percent }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</x-filament-widgets::widget>

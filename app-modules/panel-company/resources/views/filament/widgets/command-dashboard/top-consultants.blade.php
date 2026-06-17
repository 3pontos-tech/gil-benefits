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
@endphp

<x-filament-widgets::widget>
    <section class="{{ $card }}">
        <p class="{{ $label }} mb-3">{{ __('panel-company::resources.pages.command_dashboard.consultants.heading') }}</p>
        @if ($consultants === [])
            <p class="py-8 text-center text-sm {{ $muted }}">{{ __('panel-company::resources.pages.command_dashboard.consultants.empty') }}</p>
        @else
            <div class="flex flex-col gap-3">
                @foreach ($consultants as $consultant)
                    <div class="flex items-center gap-3">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600 dark:bg-white/5 dark:text-gray-300">{{ $consultant->initials }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-medium text-gray-800 dark:text-gray-100">{{ $consultant->name }}</p>
                            <div class="mt-1 h-1.5 overflow-hidden rounded-sm bg-gray-100 dark:bg-white/5">
                                <div class="h-full rounded-sm {{ $dot[$consultant->color] ?? 'bg-primary-500' }}" style="width: {{ $consultant->barWidthPercent }}%"></div>
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="{{ $num }} text-xs font-semibold text-gray-900 dark:text-white">{{ $br($consultant->sessions) }}</p>
                            @if ($consultant->rating !== null)
                                <p class="text-xs text-amber-500">★ {{ MetricsNumber::decimal($consultant->rating) }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</x-filament-widgets::widget>

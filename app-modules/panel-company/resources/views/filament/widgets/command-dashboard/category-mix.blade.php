@use('TresPontosTech\PanelCompany\Support\MetricsNumber')

@php
    $fill = [
        'primary' => 'fill-primary-500', 'emerald' => 'fill-emerald-500', 'blue' => 'fill-blue-500',
        'violet' => 'fill-violet-500', 'pink' => 'fill-pink-500', 'amber' => 'fill-amber-500',
        'teal' => 'fill-teal-500', 'red' => 'fill-red-500', 'orange' => 'fill-orange-500', 'neutral' => 'fill-gray-400',
    ];
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
    <section class="{{ $card }}">
        <p class="{{ $label }} mb-2">{{ __('panel-company::resources.pages.command_dashboard.categories.heading') }}</p>
        @if ($mix->items === [])
            <p class="py-8 text-center text-sm {{ $muted }}">{{ __('panel-company::resources.pages.command_dashboard.categories.empty') }}</p>
        @else
            <div class="flex flex-col items-center gap-2">
                @include('panel-company::filament.pages.partials.donut', [
                    'slices' => collect($mix->items)->map(fn ($c, $i) => ['path' => $paths[$i] ?? '', 'fill' => $fill[$c->color] ?? 'fill-gray-400'])->values()->all(),
                    'centerValue' => $br($mix->total),
                    'centerLabel' => __('panel-company::resources.pages.command_dashboard.categories.unit'),
                ])
                <div class="flex flex-1 flex-col gap-4">
                    @foreach ($mix->items as $category)
                        <div class="flex items-center gap-2 text-xs">
                            <span
                                class="size-2.5 shrink-0 rounded-sm {{ $dot[$category->color] ?? 'bg-gray-400' }}"></span>
                            <span
                                class="flex-1 leading-tight text-gray-600 dark:text-gray-300">{{ $category->label }}</span>
                            <span class="{{ $num }} {{ $muted }}">{{ $pct($category->percent) }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
</x-filament-widgets::widget>

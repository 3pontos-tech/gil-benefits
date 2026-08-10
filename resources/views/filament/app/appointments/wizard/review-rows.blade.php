@php
    /**
     * @var array<int, array{icon: string, label: string, value: string, highlight?: bool}> $rows
     * @var string|null $notice
     */
@endphp

<div class="flex flex-col gap-8">
    <dl class="flex flex-col divide-y divide-gray-200 border border-gray-200 dark:divide-white/10 dark:border-white/10">
        @foreach ($rows as $row)
            <div class="flex items-center justify-between gap-4 px-4 py-3.5">
                <dt class="flex items-center gap-2.5 text-[16px] text-gray-500 dark:text-gray-400">
                    <x-filament::icon :icon="$row['icon']" class="size-4 shrink-0"/>
                    {{ $row['label'] }}
                </dt>
                <dd @class([
                    'text-[16px]',
                    'font-medium text-success-600 dark:text-success-400' => $row['highlight'] ?? false,
                    'text-gray-950 dark:text-white' => ! ($row['highlight'] ?? false),
                ])>
                    {{ $row['value'] }}
                </dd>
            </div>
        @endforeach
    </dl>

    @if (filled($notice ?? null))
        <div class="flex items-start gap-3">
            <span class="flex size-6 shrink-0 items-center justify-center rounded-[4px] border border-danger-500/40 text-danger-500">
                <x-filament::icon icon="heroicon-o-exclamation-circle" class="size-4"/>
            </span>
            <p class="text-[16px] leading-snug text-gray-500 dark:text-gray-400">
                {{ $notice }}
            </p>
        </div>
    @endif
</div>

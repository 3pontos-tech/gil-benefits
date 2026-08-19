@php use TresPontosTech\PanelApp\DTOs\AccountSummaryRow; @endphp
@php /** @var list<AccountSummaryRow> $rows */ @endphp

<div class="border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
    <span class="flex size-[69px] shrink-0 items-center justify-center rounded-md bg-danger-500/10 text-danger-500">
        <x-filament::icon icon="heroicon-o-shield-check" class="size-10"/>
    </span>

    <div class="mt-4">
        <p class="text-[24px] font-bold leading-tight text-gray-950 dark:text-white">
            {{ __('panel-app::profile.summary.heading') }}
        </p>
        <p class="mt-2 text-[16px] text-gray-500 dark:text-gray-400">
            {{ __('panel-app::profile.summary.description') }}
        </p>
    </div>

    <ul class="mt-8 divide-y divide-gray-200 dark:divide-white/10">
        @foreach($rows as $row)
            <li class="flex items-center gap-3 py-4">
                <x-filament::icon :icon="$row->icon" class="size-5 shrink-0 text-gray-400 dark:text-gray-500"/>

                <span class="flex-1 text-[16px] font-bold text-gray-950 dark:text-white">
                    {{ $row->label }}
                </span>

                <span class="text-[16px] text-gray-500 dark:text-gray-400">
                    {{ $row->status }}
                </span>

                {{-- Linha informativa (isPositive nulo) não recebe ícone de estado. --}}
                @if($row->isPositive !== null)
                    <x-filament::icon
                        :icon="$row->isPositive ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle'"
                        @class([
                            'size-4 shrink-0',
                            'text-success-500' => $row->isPositive,
                            'text-warning-500' => ! $row->isPositive,
                        ])
                    />
                @endif
            </li>
        @endforeach
    </ul>
</div>

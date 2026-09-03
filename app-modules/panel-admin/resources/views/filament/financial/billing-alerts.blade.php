{{-- Alertas de cobrança (STORY-237). Dispensáveis por sessão. --}}
@php($alerts = $this->alerts())

<div class="flex flex-col gap-3">
    @foreach ($alerts as $alert)
        <div @class([
            'flex items-start justify-between gap-4 rounded-xl border p-4',
            'border-warning-300 bg-warning-50 dark:border-warning-500/30 dark:bg-warning-500/10' => $alert->severity === 'warning',
            'border-danger-300 bg-danger-50 dark:border-danger-500/30 dark:bg-danger-500/10' => $alert->severity === 'danger',
        ])>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-950 dark:text-white">
                    {{ trans_choice('panel-admin::widgets.financial.alerts.' . $alert->key, $alert->count(), [
                        'total' => number_format($alert->count(), 0, ',', '.'),
                        'value' => 'R$ ' . number_format($alert->totalCents / 100, 2, ',', '.'),
                    ]) }}
                </p>

                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                    {{ $alert->companies->take(3)->pluck('companyName')->join(', ') }}@if ($alert->count() > 3){{ __('panel-admin::widgets.financial.alerts.and_more', ['total' => $alert->count() - 3]) }}@endif
                </p>

                @if ($alert->isEstimatedDate)
                    <p class="mt-1 text-xs italic text-gray-500 dark:text-gray-400">
                        {{ __('panel-admin::widgets.financial.alerts.estimated_date') }}
                    </p>
                @endif
            </div>

            <x-filament::icon-button
                icon="heroicon-o-x-mark"
                color="gray"
                size="sm"
                class="shrink-0"
                :label="__('panel-admin::widgets.financial.alerts.dismiss')"
                wire:click="dismiss('{{ $alert->key }}')"
            />
        </div>
    @endforeach
</div>

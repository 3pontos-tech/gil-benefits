{{--
    Alertas de cobrança (STORY-237). Dispensáveis por sessão.

    Usa o callout do Filament em vez de utilitários Tailwind soltos: o tema do
    painel só compila as classes que encontra nas fontes declaradas, e as classes
    do callout já vêm no CSS base, com fundo e anel tingidos pela cor nos dois
    temas.
--}}
@php($alerts = $this->alerts())

<div class="flex flex-col gap-3">
    @foreach ($alerts as $alert)
        <x-filament::callout
            :color="$alert->severity"
            :icon="$alert->icon"
            :heading="trans_choice('panel-admin::widgets.financial.alerts.' . $alert->key, $alert->count(), [
                'total' => number_format($alert->count(), 0, ',', '.'),
                'value' => 'R$ ' . number_format($alert->totalCents / 100, 2, ',', '.'),
            ])"
            :description="$alert->companies->take(3)->pluck('companyName')->join(', ') . ($alert->count() > 3 ? __('panel-admin::widgets.financial.alerts.and_more', ['total' => $alert->count() - 3]) : '')"
        >
            @if ($alert->isEstimatedDate)
                <x-slot name="footer">
                    <x-filament::badge color="gray" size="sm" icon="heroicon-m-information-circle">
                        {{ __('panel-admin::widgets.financial.alerts.estimated_date') }}
                    </x-filament::badge>
                </x-slot>
            @endif

            <x-slot name="controls">
                <x-filament::icon-button
                    icon="heroicon-o-x-mark"
                    color="gray"
                    size="sm"
                    :label="__('panel-admin::widgets.financial.alerts.dismiss')"
                    wire:click="dismiss('{{ $alert->key }}')"
                />
            </x-slot>
        </x-filament::callout>
    @endforeach
</div>

@php
    use TresPontosTech\User\Enums\LifeMoment;

    $anamnese = $getRecord()->user?->anamnese;
    $lifeMoment = $anamnese?->life_moment instanceof LifeMoment
        ? $anamnese->life_moment
        : LifeMoment::tryFrom((string) $anamnese?->life_moment);

    $answers = [
        [
            'label' => __('panel-app::anamnese.fields.main_motivation'),
            'value' => $anamnese?->main_motivation,
        ],
        [
            'label' => __('panel-app::anamnese.fields.money_relationship'),
            'value' => $anamnese?->money_relationship,
        ],
        [
            'label' => __('panel-app::anamnese.fields.plans_monthly_expenses'),
            'value' => $anamnese?->plans_monthly_expenses,
        ],
        [
            'label' => __('panel-app::anamnese.fields.tried_financial_strategies'),
            'value' => $anamnese?->tried_financial_strategies,
        ],
    ];
@endphp

<div class="space-y-6 py-2">
    @if($lifeMoment)
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">
                {{ __('panel-app::anamnese.fields.life_moment') }}
            </p>
            <x-filament::badge color="primary" size="lg">
                {{ $lifeMoment->getLabel() }}
            </x-filament::badge>
            @if($lifeMoment->getDescription())
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $lifeMoment->getDescription() }}</p>
            @endif
        </div>

        <hr class="border-gray-200 dark:border-white/10">
    @endif

    <div class="space-y-6">
        @foreach($answers as $answer)
            <div class="space-y-1">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">
                    {{ $answer['label'] }}
                </p>
                <p class="text-sm text-gray-900 dark:text-gray-100 leading-relaxed whitespace-pre-line">
                    {{ $answer['value'] ?: '—' }}
                </p>
            </div>
        @endforeach
    </div>
</div>

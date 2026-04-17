@php
    use TresPontosTech\User\Enums\LifeMoment;

    $anamnese = $getRecord()->user?->anamnese;
    $lifeMoment = $anamnese?->life_moment instanceof LifeMoment
        ? $anamnese->life_moment
        : LifeMoment::tryFrom((string) $anamnese?->life_moment);

    $items = [
        [
            'label' => __('panel-app::anamnese.fields.life_moment'),
            'value' => $lifeMoment?->getLabel(),
            'icon'  => $lifeMoment?->getIcon(),
            'badge' => true,
        ],
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

<div class="divide-y divide-gray-100 dark:divide-gray-700 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
    @foreach ($items as $item)
        <div class="px-4 py-3">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $item['label'] }}</p>
            @if (!empty($item['badge']) && $item['value'])
                <span class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-700 dark:text-primary-300">
                    @if (!empty($item['icon']))
                        <x-filament::icon :icon="$item['icon']" class="w-4 h-4" />
                    @endif
                    {{ $item['value'] }}
                </span>
            @else
                <p class="text-sm text-gray-900 dark:text-gray-100 leading-relaxed">
                    {{ $item['value'] ?: '-' }}
                </p>
            @endif
        </div>
    @endforeach
</div>

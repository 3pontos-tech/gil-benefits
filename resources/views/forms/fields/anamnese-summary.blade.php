@php
    use TresPontosTech\User\Enums\LifeMoment;

    $lifeMoment = $get('life_moment') ? LifeMoment::tryFrom($get('life_moment')) : null;
    $mainMotivation = $get('main_motivation');
    $moneyRelationship = $get('money_relationship');
    $plansMonthlyExpenses = $get('plans_monthly_expenses');
    $triedStrategies = $get('tried_financial_strategies');

    $items = [
        [
            'icon' => 'heroicon-o-banknotes',
            'label' => __('panel-app::anamnese.fields.life_moment'),
            'value' => $lifeMoment?->getLabel() ?? '-',
        ],
        [
            'icon' => 'heroicon-o-chat-bubble-bottom-center-text',
            'label' => __('panel-app::anamnese.fields.main_motivation'),
            'value' => $mainMotivation ?: '-',
        ],
        [
            'icon' => 'heroicon-o-currency-dollar',
            'label' => __('panel-app::anamnese.fields.money_relationship'),
            'value' => $moneyRelationship ?: '-',
        ],
        [
            'icon' => 'heroicon-o-clipboard-document-list',
            'label' => __('panel-app::anamnese.fields.plans_monthly_expenses'),
            'value' => $plansMonthlyExpenses ?: '-',
        ],
        [
            'icon' => 'heroicon-o-light-bulb',
            'label' => __('panel-app::anamnese.fields.tried_financial_strategies'),
            'value' => $triedStrategies ?: '-',
        ],
    ];
@endphp

<div class="border-px rounded-lg shadow-sm">
    <div class="px-4 py-2">
        <h3 class="text-lg font-semibold">{{ __('panel-app::anamnese.summary.title') }}</h3>
        <p class="text-sm text-gray-500">{{ __('panel-app::anamnese.summary.description') }}</p>
    </div>
    <div class="divide-y divide-gray-100 dark:divide-gray-700">
        @foreach ($items as $item)
            <div class="flex items-start gap-3 p-4">
                <x-filament::icon :icon="$item['icon']" class="h-5 w-5 text-gray-400 mt-0.5 shrink-0" />
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-500">{{ $item['label'] }}</p>
                    <p class="font-medium text-sm mt-1">{{ $item['value'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>

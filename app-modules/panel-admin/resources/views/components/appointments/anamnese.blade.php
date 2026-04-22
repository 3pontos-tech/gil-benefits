@props([
    'anamnese',
])

<x-filament::section
    :heading="__('appointments::resources.appointments.infolist.anamnese')"
    icon="heroicon-o-clipboard-document-list"
    :collapsible="true"
>
    <div class="space-y-4">
        @if($anamnese->life_moment)
            <div>
                <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">
                    {{ __('panel-app::anamnese.fields.life_moment') }}
                </p>
                <x-filament::badge :icon="$anamnese->life_moment->getIcon()">
                    {{ $anamnese->life_moment->getLabel() }}
                </x-filament::badge>
            </div>
        @endif

        @foreach([
            'main_motivation',
            'money_relationship',
            'plans_monthly_expenses',
            'tried_financial_strategies',
        ] as $field)
            @if($anamnese->$field)
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">
                        {{ __('panel-app::anamnese.fields.' . $field) }}
                    </p>
                    <p class="mt-0.5 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
                        {{ $anamnese->$field }}
                    </p>
                </div>
            @endif
        @endforeach
    </div>
</x-filament::section>

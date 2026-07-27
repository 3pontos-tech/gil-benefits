@php use TresPontosTech\PanelApp\DTOs\UserJourney; @endphp
@php /** @var UserJourney $journey */ @endphp
@php
    // Cada card: ícone, tom da paleta do painel, valor, total opcional e delta do mês.
    $cards = [
        [
            'icon' => 'heroicon-o-calendar-days',
            'tone' => 'danger',
            'value' => $journey->completedConsultations,
            'total' => null,
            'delta' => $journey->completedThisMonth,
            'label' => __('panel-app::widgets.journey_hero.completed_consultations'),
        ],
        [
            'icon' => 'heroicon-o-book-open',
            'tone' => 'warning',
            'value' => $journey->topicsCoveredCount(),
            'total' => $journey->topicsTotal,
            'delta' => $journey->topicsCoveredThisMonth,
            'label' => __('panel-app::widgets.journey_hero.topics_covered'),
        ],
        [
            'icon' => 'heroicon-o-star',
            'tone' => 'info',
            'value' => $journey->ratingsGiven,
            'total' => null,
            'delta' => $journey->ratingsThisMonth,
            'label' => __('panel-app::widgets.journey_hero.ratings_given'),
        ],
        [
            'icon' => 'heroicon-o-arrow-trending-up',
            'tone' => 'success',
            'value' => $journey->healthScore,
            'total' => UserJourney::HEALTH_SCORE_MAX,
            'delta' => $journey->healthScoreDelta(),
            'label' => __('panel-app::widgets.journey_hero.financial_health'),
        ],
    ];
@endphp

<x-filament-widgets::widget>
    <header class="mb-6">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
            {{ __('panel-app::widgets.journey_hero.welcome') }}
        </p>
        <h1 class="mt-1 text-3xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-4xl">
            {{ $displayName }}
        </h1>
    </header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($cards as $card)
            @php
                $delta = $card['delta'];
                // Sem movimento no mês o indicador some, em vez de mostrar "+0".
                $hasDelta = $delta !== 0;
                $isPositive = $delta > 0;
            @endphp
            <div class="fi-dash-stat border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <div class="flex items-start justify-between gap-2">
                    <span @class([
                        'flex size-10 shrink-0 items-center justify-center border',
                        'border-danger-500/30 bg-danger-500/10 text-danger-500' => $card['tone'] === 'danger',
                        'border-warning-500/30 bg-warning-500/10 text-warning-500' => $card['tone'] === 'warning',
                        'border-info-500/30 bg-info-500/10 text-info-500' => $card['tone'] === 'info',
                        'border-success-500/30 bg-success-500/10 text-success-500' => $card['tone'] === 'success',
                    ])>
                        <x-filament::icon :icon="$card['icon']" class="size-5"/>
                    </span>

                    @if($hasDelta)
                        <span @class([
                            'inline-flex items-center gap-1 text-xs font-medium',
                            'text-success-600 dark:text-success-400' => $isPositive,
                            'text-danger-600 dark:text-danger-400' => ! $isPositive,
                        ])>
                            <x-filament::icon
                                :icon="$isPositive ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down'"
                                class="size-4 shrink-0"
                            />
                            {{ $isPositive ? '+' : '' }}{{ $delta }} {{ __('panel-app::widgets.journey_hero.this_month') }}
                        </span>
                    @endif
                </div>

                <p class="mt-6 flex items-baseline gap-1">
                    <span class="text-2xl font-bold leading-none text-gray-950 dark:text-white">{{ $card['value'] }}</span>
                    @if($card['total'] !== null)
                        <span class="text-sm font-medium text-gray-400 dark:text-gray-500">/{{ $card['total'] }}</span>
                    @endif
                </p>

                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
            </div>
        @endforeach
    </div>

    @unless($journey->isOnboarded())
        <div class="mt-4 flex flex-col items-start gap-3 border border-gray-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/10 dark:bg-gray-900">
            <div>
                <p class="text-sm font-semibold text-gray-950 dark:text-white">
                    {{ __('panel-app::widgets.journey_hero.onboarding_title') }}
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('panel-app::widgets.journey_hero.onboarding_description') }}
                </p>
            </div>
            <x-filament::button tag="a" :href="$anamneseUrl" class="w-full shrink-0 justify-center sm:w-auto">
                {{ __('panel-app::widgets.journey_hero.onboarding_cta') }}
            </x-filament::button>
        </div>
    @endunless
</x-filament-widgets::widget>

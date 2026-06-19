@php use TresPontosTech\App\DTOs\UserJourney; @endphp
@php use TresPontosTech\App\Filament\Pages\AnamneseWizardPage; @endphp
@php use TresPontosTech\App\Filament\Resources\Appointments\AppointmentResource; @endphp
@php /** @var UserJourney $journey */ @endphp
<x-filament-widgets::widget class="h-full">
    <div
        class="h-full rounded-2xl border border-primary-100 bg-primary-50 p-5 dark:border-primary-900/40 dark:bg-primary-950/20 sm:p-6">
        @if($journey->isOnboarded())
            <div class="flex flex-col gap-5 lg:flex-row lg:flex-wrap lg:items-center lg:justify-between lg:gap-6">
                <div class="flex-1 lg:min-w-60">
                    <p class="text-xs font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">
                        Sua jornada financeira
                    </p>
                    <h2 class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white sm:text-3xl">
                        Olá, {{ $firstName }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Acompanhe sua evolução financeira
                    </p>
                </div>

                @php
                    $tiles = [
                        [$journey->completedConsultations, 'consultorias'],
                        [$journey->topicsCoveredCount() . '/' . $journey->topicsTotal, 'temas abordados'],
                        [$journey->ratingsGiven, 'avaliações dadas'],
                        [$journey->lastConsultationAt?->diffForHumans(null, true) ?? '—', 'desde a última'],
                    ];
                @endphp
                <div class="grid w-full grid-cols-2 gap-2 sm:grid-cols-4 sm:gap-3 lg:flex lg:w-auto">
                    @foreach($tiles as [$value, $label])
                        <div class="rounded-xl bg-white/70 px-4 py-3 dark:bg-white/5">
                            <span
                                class="block text-2xl font-semibold leading-none text-gray-950 dark:text-white">{{ $value }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($journey->pendingRatings > 0)
                <div class="mt-6 flex flex-col items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-amber-500/30 dark:bg-amber-500/10">
                    <span class="flex items-start gap-2 text-sm font-semibold text-amber-800 dark:text-amber-300">
                        <x-filament::icon icon="heroicon-s-star" class="mt-0.5 size-5 shrink-0 text-amber-500" />
                        <span>
                            {{ $journey->pendingRatings }}
                            {{ $journey->pendingRatings === 1 ? 'consultoria aguardando sua avaliação' : 'consultorias aguardando sua avaliação' }}
                        </span>
                    </span>
                    <x-filament::button
                        tag="a"
                        :href="AppointmentResource::getUrl('index')"
                        color="warning"
                        icon="heroicon-m-arrow-right"
                        icon-position="after"
                        class="w-full justify-center sm:w-auto"
                    >
                        Avaliar agora
                    </x-filament::button>
                </div>
            @endif

            <div class="mt-6 border-t border-primary-100 pt-5 dark:border-primary-900/40">
                <p class="mb-4 text-xs font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">
                    Onde você está hoje
                </p>

                @php $current = $journey->stageIndex ?? 0; @endphp
                <ol class="flex items-start">
                    @foreach($journey->stages as $i => $stage)
                        @php
                            $isDone = $i < $current;
                            $isCurrent = $i === $current;
                        @endphp
                        <li class="flex flex-1 flex-col items-center">
                            <div class="flex w-full items-center">
                                <span @class([
                                    'h-1 flex-1 rounded-full',
                                    'invisible' => $loop->first,
                                    'bg-primary-500' => $i <= $current,
                                    'bg-gray-200 dark:bg-white/10' => $i > $current,
                                ])></span>

                                <span @class([
                                    'flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-bold sm:size-9',
                                    'bg-primary-600 text-white' => $isDone,
                                    'bg-primary-600 text-white ring-4 ring-primary-200 dark:ring-primary-500/25' => $isCurrent,
                                    'border-2 border-gray-200 bg-white text-gray-400 dark:border-white/15 dark:bg-gray-900 dark:text-gray-500' => $i > $current,
                                ])>
                                    @if($isDone)
                                        <x-filament::icon icon="heroicon-m-check" class="size-4 sm:size-5"/>
                                    @else
                                        {{ $i + 1 }}
                                    @endif
                                </span>

                                <span @class([
                                    'h-1 flex-1 rounded-full',
                                    'invisible' => $loop->last,
                                    'bg-primary-500' => $i < $current,
                                    'bg-gray-200 dark:bg-white/10' => $i >= $current,
                                ])></span>
                            </div>
                            <span @class([
                                'mt-2 hidden text-center text-[11px] leading-tight sm:block sm:text-xs',
                                'font-semibold text-primary-700 dark:text-primary-300' => $isCurrent,
                                'font-medium text-gray-600 dark:text-gray-300' => $isDone,
                                'text-gray-400 dark:text-gray-500' => $i > $current,
                            ])>{{ $stage->getLabel() }}</span>
                        </li>
                    @endforeach
                </ol>

                <p class="mt-3 text-center text-sm sm:hidden">
                    <span class="font-semibold text-primary-700 dark:text-primary-300">{{ $journey->stageLabel() }}</span>
                    <span class="text-gray-500 dark:text-gray-400">· etapa {{ $current + 1 }} de {{ count($journey->stages) }}</span>
                </p>
            </div>
        @else
            <p class="text-xs font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">
                Sua jornada financeira
            </p>
            <h2 class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                Comece sua jornada
            </h2>
            <p class="mb-4 mt-1 text-sm text-gray-500 dark:text-gray-400">
                Complete sua anamnese para descobrir seu momento financeiro.
            </p>
            <x-filament::button tag="a" :href="AnamneseWizardPage::getUrl()">
                Complete sua anamnese
            </x-filament::button>
        @endif
    </div>
</x-filament-widgets::widget>

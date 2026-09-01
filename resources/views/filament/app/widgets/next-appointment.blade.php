@php use Illuminate\Support\Str;use TresPontosTech\Consultants\Models\Consultant; @endphp
<x-filament-widgets::widget class="h-full">
    <div
        class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
        <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Próxima consultoria</p>
            <a href="{{ $listUrl }}"
               class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400">
                Ver todos
                <x-filament::icon icon="heroicon-m-arrow-right" class="size-3.5" />
            </a>
        </div>

        @if(! $appointment)
            <div class="flex flex-1 flex-col items-center justify-center py-8 text-center">
                <x-filament::icon icon="heroicon-o-calendar-days" class="mb-2 size-8 text-gray-300 dark:text-gray-600" />
                <p class="text-xs font-semibold text-gray-900 dark:text-white">Agende sua próxima consultoria</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Use o card Plano &amp; créditos para agendar.</p>
            </div>
        @else
            @php $consultant = $appointment->consultant; @endphp
            <div class="mt-4 flex items-center gap-3">
                <div
                    class="flex size-11 shrink-0 items-center justify-center rounded-full bg-primary-600 text-xs font-bold text-white">
                    {{ Str::of($consultant?->name ?? '?')->substr(0, 2)->upper() }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold text-gray-900 dark:text-white">
                        {{ $consultant instanceof Consultant ? $consultant->name : 'Aguardando atribuição' }}
                    </p>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $appointment->category_type->getLabel() }}</p>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3 rounded-xl bg-gray-50 px-4 py-3 dark:bg-white/5">
                <span class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <x-filament::icon icon="heroicon-o-calendar" class="size-4 shrink-0" />
                    Quando
                </span>
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $appointment->appointment_at->format('d/m · H\hi') }}</span>
                    <x-filament::badge color="warning">{{ $appointment->appointment_at->diffForHumans() }}</x-filament::badge>
                </div>
            </div>

            <div class="mt-auto flex items-center gap-3 pt-4">
                <div class="flex-1">
                    @if($appointment->meeting_url && $hasConfirmedStatus)
                        <x-filament::button tag="a" :href="$appointment->meeting_url" target="_blank"
                                            icon="heroicon-o-video-camera" class="w-full">
                            Entrar na reunião
                        </x-filament::button>
                    @else
                        <x-filament::button
                            disabled
                            class="w-full"
                            title="{{ __('panel-app::widgets.next_appointment.awaiting_tooltip') }}"
                        >
                            Aguardando confirmação
                        </x-filament::button>
                    @endif
                </div>

                @if($appointment->canBeRescheduled())
                    <div class="shrink-0">
                        {{ ($this->rescheduleAppointmentAction)(['appointment' => $appointment->getKey()]) }}
                    </div>
                @endif

                <div class="shrink-0">
                    {{ $this->cancelAppointmentAction }}
                </div>
            </div>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-widgets::widget>

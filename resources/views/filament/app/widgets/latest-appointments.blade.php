{{-- row-span-2 reserva duas linhas da grade para a lista, de modo que o card de
     plano e o de materiais se empilhem na coluna ao lado em vez de o segundo
     cair embaixo da lista. --}}
<x-filament-widgets::widget class="h-full md:row-span-2">
    <div class="flex h-full flex-col">
        {{-- 19 + 1 (borda do container) + 12 (p-3 do container) = os 32px pedidos
             entre o subtítulo e o primeiro card de agendamento. --}}
        <div class="mb-[19px] flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-[24px] font-bold leading-tight text-gray-950 dark:text-white">
                    {{ __('panel-app::widgets.latest_appointments.title') }}
                </h2>
                <p class="mt-1 text-[16px] text-gray-500 dark:text-gray-400">
                    {{ __('panel-app::widgets.latest_appointments.subtitle') }}
                </p>
            </div>

            {{-- Sem size: o padding padrão do fi-btn já é px-3 py-2, o pedido. --}}
            <x-filament::button tag="a" :href="$createUrl" class="shrink-0">
                {{ __('panel-app::widgets.latest_appointments.new_appointment') }}
            </x-filament::button>
        </div>

        <div class="flex-1 border border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-gray-900">
            @if($rows->isEmpty())
                <div class="flex h-full flex-col items-center justify-center gap-2 px-4 py-12 text-center">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="size-8 text-gray-300 dark:text-gray-600"/>
                    <p class="text-sm font-semibold text-gray-950 dark:text-white">
                        {{ __('panel-app::widgets.latest_appointments.empty_title') }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('panel-app::widgets.latest_appointments.empty_description') }}
                    </p>
                </div>
            @else
                <ul class="flex flex-col gap-3">
                    @foreach($rows as $row)
                        @php
                            // Tom do marcador de status, reutilizado pela bolinha e pelo halo.
                            $dot = match (true) {
                                $row['needsRescheduling'] => 'danger',
                                $row['isCompleted'] => 'success',
                                default => 'info',
                            };
                        @endphp
                        {{-- min-h é piso, não altura fixa: com as fontes do layout a linha
                             fecha em ~121px, abaixo dos 139px do Figma, e o piso garante a
                             medida sem impedir que ela cresça quando o texto precisa. --}}
                        <li class="fi-dash-appointment flex min-h-[139px] items-center gap-4 border border-gray-200 p-4 dark:border-white/10">
                            <div class="w-14 shrink-0 text-center">
                                <p class="text-[14px] font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                    {{ $row['month'] }}
                                </p>
                                <p class="text-[32px] font-bold leading-tight text-gray-950 dark:text-white">
                                    {{ $row['day'] }}
                                </p>
                            </div>

                            {{-- Régua vertical: dois traços de 2px com folga até a bolinha,
                                 que ganha um halo do mesmo tom. --}}
                            <div class="flex shrink-0 flex-col items-center gap-2 self-stretch">
                                <span class="w-0.5 flex-1 bg-gray-200 dark:bg-white/10"></span>
                                <span @class([
                                    'flex size-5 shrink-0 items-center justify-center rounded-full',
                                    'bg-danger-500/20' => $dot === 'danger',
                                    'bg-success-500/20' => $dot === 'success',
                                    'bg-info-500/20' => $dot === 'info',
                                ])>
                                    <span @class([
                                        'size-2 rounded-full',
                                        'bg-danger-500' => $dot === 'danger',
                                        'bg-success-500' => $dot === 'success',
                                        'bg-info-500' => $dot === 'info',
                                    ])></span>
                                </span>
                                <span class="w-0.5 flex-1 bg-gray-200 dark:bg-white/10"></span>
                            </div>

                            <div class="min-w-0 flex-1">
                                {{-- Teto de 2 linhas: sem ele, em telas estreitas o título de
                                     24px chega a 4 linhas e estica a linha para ~204px. --}}
                                <p class="line-clamp-2 text-[24px] font-bold leading-tight text-gray-950 dark:text-white">
                                    {{ $row['title'] }}
                                </p>
                                <p class="mt-1 flex items-center gap-1.5 text-[16px] text-gray-500 dark:text-gray-400">
                                    <x-filament::icon
                                        :icon="$row['needsRescheduling'] ? 'heroicon-o-exclamation-circle' : 'heroicon-o-clock'"
                                        @class([
                                            'size-4 shrink-0',
                                            'text-danger-500' => $row['needsRescheduling'],
                                        ])
                                    />
                                    {{ $row['schedule'] }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                {{ ($this->cancelAppointmentAction)(['appointment' => $row['id']]) }}

                                {{-- Os tamanhos do fi-btn param em text-sm (14px), então o
                                     16px pedido vem de uma classe explícita. --}}
                                @if($row['needsRescheduling'])
                                    <x-filament::button
                                        tag="a"
                                        :href="$createUrl"
                                        size="sm"
                                        color="danger"
                                        class="text-[16px]"
                                    >
                                        {{ __('panel-app::widgets.latest_appointments.reschedule') }}
                                    </x-filament::button>
                                @elseif($row['meetingUrl'])
                                    <x-filament::button
                                        tag="a"
                                        :href="$row['meetingUrl']"
                                        target="_blank"
                                        size="sm"
                                        color="info"
                                        icon="heroicon-o-video-camera"
                                        class="text-[16px]"
                                    >
                                        {{ __('panel-app::widgets.latest_appointments.join') }}
                                    </x-filament::button>
                                @else
                                    <span @class([
                                        'inline-flex items-center gap-1.5 whitespace-nowrap px-2.5 py-1.5 text-[16px] font-medium',
                                        'bg-info-500/10 text-info-600 dark:text-info-400' => $row['isPending'],
                                        'bg-success-500/10 text-success-600 dark:text-success-400' => ! $row['isPending'],
                                    ])>
                                        @if($row['isPending'])
                                            <x-filament::loading-indicator class="size-4 shrink-0"/>
                                        @endif
                                        {{ $row['status']->getLabel() }}
                                    </span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <x-filament-actions::modals/>
</x-filament-widgets::widget>

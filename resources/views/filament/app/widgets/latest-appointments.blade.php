<x-filament-widgets::widget class="h-full">
    <div class="flex h-full flex-col">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-gray-950 dark:text-white">
                    {{ __('panel-app::widgets.latest_appointments.title') }}
                </h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('panel-app::widgets.latest_appointments.subtitle') }}
                </p>
            </div>

            <x-filament::button tag="a" :href="$createUrl" size="sm" class="shrink-0">
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
                        {{-- 139px é a altura da linha no Figma. --}}
                        <li class="fi-dash-appointment flex min-h-[139px] items-center gap-4 border border-gray-200 px-4 py-4 dark:border-white/10">
                            <div class="w-10 shrink-0 text-center">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                    {{ $row['month'] }}
                                </p>
                                <p class="text-lg font-bold leading-tight text-gray-950 dark:text-white">
                                    {{ $row['day'] }}
                                </p>
                            </div>

                            {{-- Régua vertical com o marcador de status, como no layout. --}}
                            <div class="relative self-stretch">
                                <span class="block h-full w-px bg-gray-200 dark:bg-white/10"></span>
                                <span @class([
                                    'absolute left-1/2 top-1/2 size-2 -translate-x-1/2 -translate-y-1/2 rounded-full',
                                    'bg-danger-500' => $row['needsRescheduling'],
                                    'bg-success-500' => $row['isCompleted'],
                                    'bg-info-500' => ! $row['needsRescheduling'] && ! $row['isCompleted'],
                                ])></span>
                            </div>

                            <div class="min-w-0 flex-1">
                                {{-- Com a linha em 139px há espaço vertical, então o título
                                     quebra em duas linhas em vez de truncar. --}}
                                <p class="line-clamp-2 text-sm font-bold text-gray-950 dark:text-white">
                                    {{ $row['title'] }}
                                </p>
                                <p class="mt-1 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                    <x-filament::icon
                                        :icon="$row['needsRescheduling'] ? 'heroicon-o-exclamation-circle' : 'heroicon-o-clock'"
                                        @class([
                                            'size-3.5 shrink-0',
                                            'text-danger-500' => $row['needsRescheduling'],
                                        ])
                                    />
                                    {{ $row['schedule'] }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                {{ ($this->cancelAppointmentAction)(['appointment' => $row['id']]) }}

                                @if($row['needsRescheduling'])
                                    <x-filament::button tag="a" :href="$createUrl" size="xs" color="danger">
                                        {{ __('panel-app::widgets.latest_appointments.reschedule') }}
                                    </x-filament::button>
                                @elseif($row['meetingUrl'])
                                    <x-filament::button
                                        tag="a"
                                        :href="$row['meetingUrl']"
                                        target="_blank"
                                        size="xs"
                                        color="info"
                                        icon="heroicon-o-video-camera"
                                    >
                                        {{ __('panel-app::widgets.latest_appointments.join') }}
                                    </x-filament::button>
                                @else
                                    <span @class([
                                        'inline-flex items-center gap-1.5 whitespace-nowrap px-2 py-1 text-[11px] font-medium',
                                        'bg-info-500/10 text-info-600 dark:text-info-400' => $row['isPending'],
                                        'bg-success-500/10 text-success-600 dark:text-success-400' => ! $row['isPending'],
                                    ])>
                                        @if($row['isPending'])
                                            <x-filament::loading-indicator class="size-3 shrink-0"/>
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

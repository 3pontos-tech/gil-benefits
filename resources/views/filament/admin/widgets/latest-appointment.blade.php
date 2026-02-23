<x-filament-widgets::widget class="h-full">
    <x-filament::section icon="heroicon-o-calendar-days" class="h-full [&>.fi-section-content-ctn]:h-full [&>.fi-section-content-ctn>.fi-section-content]:h-full">
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <span>Próxima consultoria</span>
                @if($appointment)
                    <x-filament::badge
                        :color="$status->getColor()"
                        :icon="$status->getIcon()"
                    >
                        {{ $status->getLabel() }}
                    </x-filament::badge>
                @endif
            </div>
        </x-slot>

        @if(! $appointment)
            <div class="flex h-full items-center justify-center py-6">
                <div class="text-center">
                    <x-filament::icon icon="heroicon-o-calendar" class="mx-auto h-8 w-8 text-gray-400 dark:text-gray-500" />
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Nenhum agendamento encontrado.
                    </p>
                </div>
            </div>
        @else
            <div class="flex h-full flex-col justify-between">
                <div class="space-y-4">
                    {{-- Data e horário - sempre visível --}}
                    <div class="flex items-center gap-3">
                        <x-filament::icon icon="heroicon-o-clock" class="h-5 w-5 shrink-0 text-gray-400 dark:text-gray-500" />
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Data e horário</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $appointmentAt->format('d/m/Y \à\s H:i') }}
                            </p>
                        </div>
                    </div>

                    {{-- Consultor - visível quando atribuído --}}
                    <div class="flex items-center gap-3">
                        <x-filament::icon icon="heroicon-o-user" class="h-5 w-5 shrink-0 text-gray-400 dark:text-gray-500" />
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Consultor</p>
                            @if($consultantName)
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $consultantName }}
                                </p>
                            @else
                                <p class="text-sm italic text-gray-400 dark:text-gray-500">
                                    Aguardando atribuição
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- Reunião --}}
                    <div class="flex items-center gap-3">
                        <x-filament::icon icon="heroicon-o-video-camera" class="h-5 w-5 shrink-0 text-gray-400 dark:text-gray-500" />
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Reunião</p>
                            @if($meetingUrl && $hasConfirmedStatus)
                                <a
                                    href="{{ $meetingUrl }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                                >
                                    Acessar reunião
                                </a>
                            @else
                                <p class="text-sm italic text-gray-400 dark:text-gray-500">
                                    Aguardando confirmação
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>

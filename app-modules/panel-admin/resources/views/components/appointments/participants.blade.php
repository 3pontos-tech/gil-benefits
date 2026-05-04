@props([
    'appointment',
])

<x-filament::section>
    <div class="flex flex-col gap-6 items-center sm:flex-row sm:items-center sm:justify-between w-full">
        <div class="flex gap-4 sm:flex-row sm:gap-8">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-bold uppercase text-primary-700 dark:bg-primary-900/50 dark:text-primary-400">
                    {{ mb_substr($appointment->user->name, 0, 1) }}
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">
                        {{ __('appointments::resources.appointments.table.columns.user') }}
                    </p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $appointment->user->name }}
                    </p>
                </div>
            </div>

            @if($appointment->consultant)
                <div class="hidden sm:flex sm:items-center">
                    <x-filament::icon icon="heroicon-o-arrow-long-right"
                                      class="h-4 w-4 text-gray-300 dark:text-gray-600"/>
                </div>

                <div class="flex items-center gap-3">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-violet-100 text-sm font-bold uppercase text-violet-700 dark:bg-violet-900/50 dark:text-violet-400">
                        {{ mb_substr($appointment->consultant->name, 0, 1) }}
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">
                            {{ __('appointments::resources.appointments.table.columns.consultant') }}
                        </p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $appointment->consultant->name }}
                        </p>
                    </div>
                </div>
            @endif
        </div>

        <div class="flex flex-wrap items-start gap-2">
            @if($appointment->category_type)
                <x-filament::badge size="lg" :color="$appointment->category_type->getColor()"
                                   :icon="$appointment->category_type->getIcon()">
                    {{ $appointment->category_type->getLabel() }}
                </x-filament::badge>
            @endif
            <x-filament::badge size="lg" :color="$appointment->status->getColor()"
                               :icon="$appointment->status->getIcon()">
                {{ $appointment->status->getLabel() }}
            </x-filament::badge>
        </div>
    </div>
</x-filament::section>

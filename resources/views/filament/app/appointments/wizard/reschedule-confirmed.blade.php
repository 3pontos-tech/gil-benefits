@php
    /**
     * @var \Illuminate\Support\Carbon $previousAt
     * @var \Illuminate\Support\Carbon $newAt
     */
@endphp

<div class="flex flex-col gap-8">
    <div class="border border-gray-200 p-4 dark:border-white/10">
        <div class="flex items-center gap-3">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400">
                <x-filament::icon icon="heroicon-o-calendar" class="size-4"/>
            </span>
            <div class="min-w-0">
                <p class="text-[16px] font-bold text-gray-950 dark:text-white">
                    {{ __('panel-app::resources.appointments.reschedule.confirmed.before') }}
                </p>
                <p class="mt-0.5 text-[16px] text-gray-500 dark:text-gray-400">
                    {{ $previousAt->format('d/m/y - H:i') }}
                </p>
            </div>
        </div>

        <div class="ml-[17px] h-8 w-0.5 bg-gray-200 dark:bg-white/10"></div>

        <div class="flex items-center gap-3">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-success-500/15 text-success-600 dark:text-success-400">
                <x-filament::icon icon="heroicon-o-calendar-days" class="size-4"/>
            </span>
            <div class="min-w-0">
                <p class="text-[16px] font-bold text-gray-950 dark:text-white">
                    {{ __('panel-app::resources.appointments.reschedule.confirmed.now') }}
                </p>
                <p class="mt-0.5 text-[16px] text-gray-500 dark:text-gray-400">
                    {{ $newAt->format('d/m/y - H:i') }}
                </p>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2 text-success-600 dark:text-success-400">
        <x-filament::icon icon="heroicon-o-check-circle" class="size-5 shrink-0"/>
        <span class="text-[16px] font-medium">
            {{ __('panel-app::resources.appointments.reschedule.confirmed.awaiting_confirmation') }}
        </span>
    </div>
</div>

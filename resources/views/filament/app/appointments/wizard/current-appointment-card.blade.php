@php
    /** @var \TresPontosTech\Appointments\Models\Appointment $appointment */
@endphp

<div class="flex items-center gap-3 border border-gray-200 p-4 dark:border-white/10">
    <span class="flex size-9 shrink-0 items-center justify-center rounded-[4px] bg-danger-500/15 text-danger-500/80">
        <x-filament::icon icon="heroicon-o-calendar-days" class="size-4"/>
    </span>

    <div class="min-w-0">
        <p class="truncate text-[16px] font-bold text-gray-950 dark:text-white">
            {{ $appointment->category_type->getLabel() }}
        </p>
        <p class="mt-1 flex items-center gap-1.5 text-[16px] text-gray-500 dark:text-gray-400">
            <x-filament::icon icon="heroicon-o-calendar" class="size-4 shrink-0"/>
            <span>{{ $appointment->appointment_at->format('d/m/y - H:i') }}</span>
        </p>
    </div>
</div>

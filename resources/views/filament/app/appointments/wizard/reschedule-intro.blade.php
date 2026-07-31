@php
    /** @var \TresPontosTech\Appointments\Models\Appointment $appointment */
@endphp

<div class="flex flex-col gap-8">
    @include('filament.app.appointments.wizard.current-appointment-card', ['appointment' => $appointment])

    <div class="flex items-start gap-3">
        <x-filament::icon icon="heroicon-o-exclamation-circle" class="mt-0.5 size-5 shrink-0 text-warning-500"/>
        <p class="text-[16px] leading-snug text-warning-600 dark:text-warning-400">
            {{ __('panel-app::resources.appointments.reschedule.intro.keeps_current_slot') }}
        </p>
    </div>
</div>

<div class="space-y-4">
    @if($lastAppointmentAt !== null)
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('panel-consultant::resources.appointment_records.previous_summary.last_appointment_on', ['date' => $lastAppointmentAt->format('d/m/Y')]) }}
        </p>
    @endif

    @if($summary === null)
        <p class="italic text-gray-500 dark:text-gray-400">
            {{ __('panel-consultant::resources.appointment_records.previous_summary.empty') }}
        </p>
    @else
        <div class="prose prose-sm dark:prose-invert max-w-none">
            {!! \Illuminate\Support\Str::markdown($summary) !!}
        </div>
    @endif
</div>

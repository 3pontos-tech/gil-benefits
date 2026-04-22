@props([
    'appointment',
])

<x-filament::section icon="heroicon-o-calendar">
    <div class="space-y-4">
        <div class="text-center">
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ $appointment->appointment_at->format('d/m/Y') }}
            </p>
            <p class="text-lg text-primary-600 dark:text-primary-400">
                {{ $appointment->appointment_at->format('H:i') }}
            </p>
        </div>

        @if($appointment->meeting_url)
            <a
                href="{{ $appointment->meeting_url }}"
                target="_blank"
                rel="noopener"
                class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary-50 px-4 py-2.5 text-sm font-medium text-primary-700 ring-1 ring-primary-200 transition hover:bg-primary-100 dark:bg-primary-950/30 dark:text-primary-400 dark:ring-primary-800 dark:hover:bg-primary-950/50"
            >
                <x-filament::icon icon="heroicon-o-video-camera" class="h-4 w-4" />
                {{ __('appointments::resources.appointments.form.meeting_url') }}
            </a>
        @endif
    </div>
</x-filament::section>

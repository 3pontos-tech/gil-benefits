@props([
    'appointment',
])

<x-filament::section
    :heading="__('appointments::resources.appointments.infolist.metadata')"
    collapsible
    collapsed
>
    <dl class="space-y-3 text-sm">
        <div class="flex justify-between">
            <dt class="text-gray-500 dark:text-gray-400">
                {{ __('appointments::resources.appointments.table.columns.created_at') }}
            </dt>
            <dd class="text-gray-900 dark:text-white">
                {{ $appointment->created_at->format('d/m/Y H:i') }}
            </dd>
        </div>
        <div class="flex justify-between">
            <dt class="text-gray-500 dark:text-gray-400">
                {{ __('appointments::resources.appointments.table.columns.updated_at') }}
            </dt>
            <dd class="text-gray-900 dark:text-white">
                {{ $appointment->updated_at->diffForHumans() }}
            </dd>
        </div>
    </dl>
</x-filament::section>

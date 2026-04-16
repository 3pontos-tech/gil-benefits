@props([
    'notes' => null,
])

@if($notes)
    <x-filament::section
        :heading="__('appointments::resources.appointments.wizard.labels.notes')"
        icon="heroicon-o-document-text"
    >
        <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $notes }}</p>
    </x-filament::section>
@endif

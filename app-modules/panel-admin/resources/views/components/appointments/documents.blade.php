@props([
    'documents',
])

<x-filament::section
    :heading="__('appointments::resources.appointments.infolist.employee_documents')"
    icon="heroicon-o-document"
>
    @if($documents->isNotEmpty())
        <ul class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach($documents as $document)
                <li class="flex items-center justify-between gap-3 py-2.5">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                            {{ $document->title }}
                        </p>
                        <x-filament::badge size="sm" color="gray">
                            {{ $document->type->getLabel() }}
                        </x-filament::badge>
                    </div>
                    @if($document->getFirstMedia('documents'))
                        <button
                            type="button"
                            wire:click="downloadDocument('{{ $document->getKey() }}')"
                            class="shrink-0 text-gray-400 transition hover:text-primary-500 dark:hover:text-primary-400"
                            title="Download"
                        >
                            <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-5 w-5" />
                        </button>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-sm italic text-gray-400 dark:text-gray-500">
            {{ __('appointments::resources.appointments.infolist.documents.empty') }}
        </p>
    @endif
</x-filament::section>

@props([
    'page',
    'documents',
    'sharedDocuments' => null,
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
                    @if(($page->downloadDocumentAction)(['documentId' => $document->getKey()])->isVisible())
                        <div class="shrink-0">
                            {{ ($page->downloadDocumentAction)(['documentId' => $document->getKey()]) }}
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-sm italic text-gray-400 dark:text-gray-500">
            {{ __('appointments::resources.appointments.infolist.documents.empty') }}
        </p>
    @endif

    @if($sharedDocuments && $sharedDocuments->isNotEmpty())
        <hr class="my-4 border-gray-100 dark:border-gray-800" />
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            {{ __('appointments::resources.appointments.infolist.employee_shared_documents') }}
        </p>
        <ul class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach($sharedDocuments as $document)
                <li class="flex items-center justify-between gap-3 py-2.5">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                            {{ $document->title }}
                        </p>
                        <x-filament::badge size="sm" color="gray">
                            {{ $document->type->getLabel() }}
                        </x-filament::badge>
                    </div>
                    @if(($page->downloadSharedDocumentAction)(['documentId' => $document->getKey()])->isVisible())
                        <div class="shrink-0">
                            {{ ($page->downloadSharedDocumentAction)(['documentId' => $document->getKey()]) }}
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</x-filament::section>

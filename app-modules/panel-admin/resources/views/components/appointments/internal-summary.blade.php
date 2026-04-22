@use('Illuminate\Support\Str')

@props([
    'record' => null,
])

@if($record?->internal_summary)
    <x-filament::section
        :heading="__('appointments::resources.appointments.infolist.ai.internal_summary')"
        icon="heroicon-o-clipboard-document-list"
        collapsible
    >
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/30">
            <div class="prose prose-sm max-w-none dark:prose-invert
                prose-p:text-gray-700 prose-p:dark:text-gray-300
                prose-li:text-gray-700 prose-li:dark:text-gray-300
                prose-strong:text-gray-900 prose-strong:dark:text-white
                prose-headings:text-amber-900 prose-headings:dark:text-amber-200">
                {!! Str::markdown($record->internal_summary, [
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ]) !!}
            </div>
        </div>
    </x-filament::section>
@endif

@use('Illuminate\Support\Str')

@props([
    'record' => null,
])

@if($record?->content)
    <x-filament::section
        :heading="__('appointments::resources.appointments.infolist.ai.content')"
        icon="heroicon-o-sparkles"
    >
        <x-slot name="headerEnd">
            @if($record->isPublished())
                <x-filament::badge color="success">
                    {{ __('appointments::resources.appointments.infolist.ai.published_at') }}:
                    {{ $record->published_at->format('d/m/Y H:i') }}
                </x-filament::badge>
            @else
                <x-filament::badge color="warning">
                    {{ __('appointments::resources.appointments.infolist.ai.draft') }}
                </x-filament::badge>
            @endif
        </x-slot>

        <div class="prose prose-sm max-w-none dark:prose-invert
            prose-headings:font-semibold
            prose-h1:text-xl prose-h1:border-b prose-h1:border-gray-200 prose-h1:pb-2 prose-h1:dark:border-gray-700
            prose-h2:text-lg prose-h2:mt-6
            prose-p:text-gray-700 prose-p:dark:text-gray-300
            prose-li:text-gray-700 prose-li:dark:text-gray-300
            prose-strong:text-gray-900 prose-strong:dark:text-white">
            {!! Str::markdown($record->content, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]) !!}
        </div>
    </x-filament::section>
@elseif($record !== null)
    <x-filament::section icon="heroicon-o-sparkles">
        <div class="flex items-center justify-center gap-3 py-8 text-gray-400 dark:text-gray-500">
            <x-filament::loading-indicator class="h-5 w-5" />
            <span class="text-sm">{{ __('panel-admin::resources.appointments.view.processing') }}</span>
        </div>
    </x-filament::section>
@endif

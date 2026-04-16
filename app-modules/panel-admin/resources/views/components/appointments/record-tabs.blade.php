@use('Illuminate\Support\Str')

@props([
    'record' => null,
])

@if($record?->content)
    <div x-data="{ tab: 'record' }" class="space-y-0">
        <x-filament::tabs class="mb-6">
            <x-filament::tabs.item
                alpine-active="tab === 'record'"
                x-on:click="tab = 'record'"
                icon="heroicon-o-sparkles"
                class="flex-1"
            >
                {{ __('appointments::resources.appointments.infolist.ai.content') }}

                <x-slot name="badge">
                    @if($record->isPublished())
                        <x-filament::badge size="sm" color="success"
                                           :title="__('appointments::resources.appointments.infolist.ai.published_at')">
                            {{ $record->published_at->format('d/m/Y H:i') }}
                        </x-filament::badge>
                    @else
                        <x-filament::badge size="sm" color="warning">
                            {{ __('appointments::resources.appointments.infolist.ai.draft') }}
                        </x-filament::badge>
                    @endif
                </x-slot>
            </x-filament::tabs.item>

            @if($record->internal_summary)
                <x-filament::tabs.item
                    alpine-active="tab === 'summary'"
                    x-on:click="tab = 'summary'"
                    icon="heroicon-o-clipboard-document-list"
                    class="flex-1"
                >
                    {{ __('appointments::resources.appointments.infolist.ai.internal_summary') }}
                </x-filament::tabs.item>
            @endif
        </x-filament::tabs>

        {{-- Tab: Ata --}}
        <x-filament::section x-show="tab === 'record'" x-cloak>
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

        {{-- Tab: Resumo interno --}}
        @if($record->internal_summary)
            <x-filament::section x-show="tab === 'summary'" x-cloak>
                <div class="rounded-lg bg-amber-50 p-4 dark:bg-amber-950/30">
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
    </div>
@elseif($record !== null)
    <x-filament::section icon="heroicon-o-sparkles">
        <div class="flex items-center justify-center gap-3 py-8 text-gray-400 dark:text-gray-500">
            <x-filament::loading-indicator class="h-5 w-5"/>
            <span class="text-sm">{{ __('panel-admin::resources.appointments.view.processing') }}</span>
        </div>
    </x-filament::section>
@endif

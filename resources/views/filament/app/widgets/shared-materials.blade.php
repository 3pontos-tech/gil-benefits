<x-filament-widgets::widget class="h-full">
    <div class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
        <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Materiais compartilhados</p>
            @if($shares->isNotEmpty())
                <a href="{{ $listUrl }}"
                   class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400">
                    {{ __('panel-app::widgets.shared_materials.view_all') }}
                    <x-filament::icon icon="heroicon-m-arrow-right" class="size-3.5" />
                </a>
            @endif
        </div>

        @if($shares->isEmpty())
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Nenhum material compartilhado ainda</p>
        @else
            <ul class="mt-2 divide-y divide-gray-100 text-xs dark:divide-white/5">
                @foreach($shares as $share)
                    @php $document = $share->document; @endphp
                    @continue($document === null)
                    <li class="flex items-center justify-between gap-3 py-2.5">
                        <span class="flex min-w-0 items-center gap-2 text-gray-700 dark:text-gray-300">
                            <x-filament::icon :icon="$document->type?->getIcon() ?? 'heroicon-o-document'" class="size-4 shrink-0 text-gray-400" />
                            <span class="truncate">{{ $document->title }}</span>
                        </span>
                        @php $download = ($this->downloadDocumentAction)(['documentId' => $document->getKey()]); @endphp
                        @if($download->isVisible())
                            <span class="shrink-0">{{ $download }}</span>
                        @else
                            <span class="shrink-0 text-xs text-gray-400 dark:text-gray-500">
                                {{ __('panel-app::widgets.shared_materials.unavailable') }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>

            @if($hasMore)
                <a href="{{ $listUrl }}"
                   class="mt-auto block pt-3 text-center text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    {{ trans_choice('panel-app::widgets.shared_materials.more_count', $remainingCount, ['count' => $remainingCount]) }}
                </a>
            @endif
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-widgets::widget>

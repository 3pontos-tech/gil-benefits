@php use TresPontosTech\PanelApp\DTOs\UserJourney; @endphp
@php /** @var UserJourney $journey */ @endphp
<x-filament-widgets::widget class="h-full">
    <div class="h-full rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Temas financeiros</p>
        <p class="mb-3 mt-1.5 text-xs text-gray-500 dark:text-gray-400">Explore áreas que você ainda não abordou</p>
        <div class="flex flex-wrap gap-2">
            @foreach($categories as $category)
                @if($journey->hasCovered($category))
                    <span class="inline-flex items-center rounded-full bg-primary-600 px-3 py-1 text-xs font-medium text-white">
                        ✓ {{ $category->getLabel() }}
                    </span>
                @else
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-500 dark:bg-white/5 dark:text-gray-400">
                        + {{ $category->getLabel() }}
                    </span>
                @endif
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>

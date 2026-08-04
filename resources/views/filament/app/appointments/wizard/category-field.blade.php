@php
    use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;

    $statePath = $getStatePath();
    $current = $getState();
@endphp

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    @foreach (AppointmentCategoryEnum::cases() as $category)
        @php $isSelected = $current === $category->value; @endphp
        <button
            type="button"
            wire:click="$set('{{ $statePath }}', '{{ $category->value }}')"
            @class([
                'flex items-start gap-3 border p-4 text-left transition',
                'border-primary-500 bg-primary-500/5' => $isSelected,
                'border-gray-200 hover:border-gray-300 dark:border-white/10 dark:hover:border-white/20' => ! $isSelected,
            ])
        >
            <x-filament::icon
                :icon="$category->getIcon()"
                class="mt-0.5 size-5 shrink-0 text-gray-400 dark:text-gray-500"
            />
            <span class="min-w-0">
                <span class="block text-[16px] font-bold text-gray-950 dark:text-white">
                    {{ $category->getLabel() }}
                </span>
                <span class="mt-1 block text-[14px] leading-snug text-gray-500 dark:text-gray-400">
                    {{ $category->getDescription() }}
                </span>
            </span>
        </button>
    @endforeach
</div>

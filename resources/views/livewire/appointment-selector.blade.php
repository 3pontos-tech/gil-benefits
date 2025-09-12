@php
    use App\Enums\AppointmentCategoryEnum;
    use Filament\Support\Icons\Heroicon;
    $selectedValue = $getState();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <x-filament::button></x-filament::button>
    <div class="h-[500px] overflow-y-auto">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach (AppointmentCategoryEnum::cases() as $case)
                @php
                    $isSelected = $selectedValue == $case->value;
                @endphp
                <div
                    wire:key="appointment-{{ $case->value }}"
                    wire:click="$set('{{ $getStatePath() }}', '{{ $case->value }}')"
                    role="option"
                    aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                    class="relative cursor-pointer border rounded-lg p-4 transition-all duration-200 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-primary-500
                    {{ $isSelected
                        ? 'border-primary-500 bg-primary-50 dark:bg-primary-900 ring-2 ring-primary-500 text-gray-900 dark:text-gray-100'
                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}">
                    <div class="flex items-start gap-3">
                        <div class="shrink-0">
                            <x-filament::icon :icon="$case->getIcon()" class="w-6 h-6 {{ $isSelected ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400' }}" />
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-sm">
                                {{ $case->getLabel() }}
                            </div>
                            <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                {{ $case->getDescription() }}
                            </div>
                        </div>
                        @if ($isSelected)
                            <div class="absolute top-2 right-2">
                                <x-filament::icon :icon="Heroicon::CheckCircle" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <input
        type="hidden"
        name="{{ $getStatePath() }}"
        value="{{ $selectedValue }}"
        {{ $attributes->merge($getExtraAttributes())->class(['sr-only']) }} />
</x-dynamic-component>

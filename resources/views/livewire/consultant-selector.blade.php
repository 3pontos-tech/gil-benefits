@php
    use Filament\Support\Icons\Heroicon;$consultants = $getConsultants();
    $selectedValue = $getState();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="h-[500px] overflow-y-auto flex flex-col gap-4 scrollbar-hidden">
        @foreach ($consultants as $consultant)
            @php
                $isSelected = $selectedValue == $consultant['id'];
            @endphp

            <div
                wire:key="consultant-{{ $consultant['id'] }}"
                wire:click="$set('{{ $getStatePath() }}', {{ $consultant['id'] }})"
                class="relative cursor-pointer border rounded-lg p-4 transition-all duration-200 hover:shadow-lg
        {{ $isSelected
            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900 ring-2 ring-primary-500 text-gray-900 dark:text-gray-100'
            : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}">


                <div class="flex relative gap-2">
                    <x-filament::avatar
                        src="https://placehold.co/50"
                        alt="{{ $consultant['name'] }}"
                        class="w-12 h-12 rounded-full object-cover"/>

                    @if($isSelected)
                        <div
                            class="bg-red-500 absolute top-0 right-0 w-5 h-5 rounded-full flex items-center justify-center">
                            <x-filament::icon
                                :icon="Heroicon::CheckBadge"
                                class="w-3 h-3"/>
                        </div>
                    @endif
                    <div>
                        <div>
                            <h3 class="font-semibold truncate">{{ $consultant['name'] }}</h3>
                            <p class="text-sm text-gray-400 line-clamp-2 mt-1">{{ $consultant['description'] ?? '' }}</p>
                        </div>

                        <div class="space-y-2 mt-2">
                            @if(!empty($consultant['phone']))
                                <div class="flex items-center space-x-2">
                                    <x-filament::icon :icon="Heroicon::Phone" class="w-4 h-4"/>
                                    <span class="truncate text-gray-400">{{ $consultant['phone'] }}</span>
                                </div>
                            @endif

                            @if(!empty($consultant['email']))
                                <div class="flex items-center space-x-2">
                                    <x-filament::icon :icon="Heroicon::Envelope" class="w-4 h-4"/>
                                    <span class="truncate text-gray-400">{{ $consultant['email'] }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Campo Hidden para o formulário -->
    <input
        type="hidden"
        name="{{ $getStatePath() }}"
        value="{{ $selectedValue }}"
        {{ $attributes->merge($getExtraAttributes())->class(['sr-only']) }} />
</x-dynamic-component>

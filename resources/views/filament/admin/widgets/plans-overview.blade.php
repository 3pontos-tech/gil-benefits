@props([
    'planName',
    'description',
    'status',
    'features'
])

@php
    use Filament\Support\Icons\Heroicon;
@endphp
<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-cube">
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white leading-tight">
                        Plano {{ $planName }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{ $description }}
                    </p>
                </div>
                <div>
                    <x-filament::badge icon="heroicon-o-check-circle">
                        1 sessão restante esse mês
                    </x-filament::badge>
                </div>
            </div>
        </x-slot>


        <div class="grid grid-cols-2 gap-3 mb-6">
            @foreach($features as $key => $feature)
                <div class="flex items-center gap-2 text-sm">
                    <div class="h-1.5 w-1.5 rounded-full bg-primary-500"></div>
                    <span>{{ __('all.' . $key) }}</span>
                </div>
            @endforeach
        </div>


        <x-slot name="footer">
            <x-filament::button icon="heroicon-o-calendar">Agendar nova sessão</x-filament::button>
        </x-slot>


    </x-filament::section>
</x-filament-widgets::widget>

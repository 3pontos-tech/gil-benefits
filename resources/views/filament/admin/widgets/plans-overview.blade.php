@php
    use Filament\Support\Icons\Heroicon;@endphp
<x-filament-widgets::widget>
    <x-filament::section
        icon="heroicon-o-cube"
        class="overflow-hidden border border-gray-200 dark:border-white/10 shadow-sm hover:shadow-md transition-shadow">

        <x-slot name="heading">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white leading-tight">
                Plano {{ $planName }}
            </h2>
        </x-slot>

        <x-slot name="description">
            <div>
                <x-filament::badge icon="heroicon-o-check-circle">
                    @if($status === 'active')
                        Ativo
                    @elseif($status === 'inactive')
                        Inativo
                    @else
                        Expirado
                    @endif
                </x-filament::badge>

                @if($isRecurring)
                    <x-filament::badge icon="heroicon-o-arrow-path">
                        Recorrente
                    </x-filament::badge>
                @endif
            </div>
        </x-slot>

        <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
            {{ $description }}
        </p>

        <x-filament::button icon="heroicon-o-calendar">Agendar nova sessão</x-filament::button>

    </x-filament::section>
</x-filament-widgets::widget>

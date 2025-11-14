@php use Filament\Support\Icons\Heroicon; @endphp
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <h2 class="text-xl font-semibold"> Histórico de Consultas</h2>
        </x-slot>

        <x-slot name="description">
            Aqui você encontra todas as suas consultas anteriores, organizadas pela data mais recente.
        </x-slot>

        <div class="space-y-4">
            @foreach ($appointments as $appointment)
                <x-filament::card class="p-2 rounded-xl space-y-2">
                    <div class="flex justify-between">
                        <div class="flex gap-2">
                            <h3 class="text-lg font-semibold">
                                {{ $appointment->consultant->name }}
                            </h3>
                            @if ($appointment->status)
                                <x-filament::badge :color="$appointment->status->getColor()">
                                    {{ $appointment->status->getLabel() }}
                                </x-filament::badge>
                            @endif
                        </div>

                        <span class="text-sm text-gray-500">
                            {{ $appointment->appointment_at->format('d/m/Y H:i') }}
                        </span>
                    </div>

                    <div class="flex items-center space-x-2">
                        <x-filament::icon
                            :icon="$appointment->category_type->getIcon()"
                            class="w-4 h-4"
                        />

                        <span class="text-sm font-medium">
                            {{ $appointment->category_type->getLabel() }}
                        </span>
                    </div>

                </x-filament::card>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

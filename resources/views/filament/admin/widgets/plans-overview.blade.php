@php
    use Filament\Support\Icons\Heroicon;@endphp
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <h2 class="text-xl font-semibold">Planos Disponíveis</h2>
        </x-slot>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($plans as $plan)
                <x-filament::card class="p-4 rounded-xl">
                    <h3 class="text-lg font-semibold">
                        {{ ucfirst($plan->name) }}
                    </h3>

                    @if ($plan->description)
                        <p class="text-sm ">
                            {{ $plan->description }}
                        </p>
                    @endif

                    <div class="mt-4 flex place-content-between w-full">
                        <x-filament::button
                            tag="a"
                            class="w-full cursor-pointer"
                            icon="heroicon-o-calendar"
                        >
                            Agendar Nova Sessão
                        </x-filament::button>
                    </div>
                </x-filament::card>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

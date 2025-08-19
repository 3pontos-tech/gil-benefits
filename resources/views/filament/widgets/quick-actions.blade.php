@php use Filament\Support\Icons\Heroicon; @endphp
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <span class="flex gap-2">
                <x-filament::icon :icon="Heroicon::Cog6Tooth" class="w-5 h-5"/>
                Atalhos Administrativos
            </span>
        </x-slot>
        <x-slot name="description">
            Acesso rápido às principais funcionalidades administrativas
        </x-slot>

        <div class="flex place-content-between gap-2">
            @foreach ($shortcuts as $shortcut)
                <x-filament::card
                    class="h-auto flex flex-col items-start gap-2 text-left hover:bg-gray-800 cursor-pointer"
                >
                    <a href="{{$shortcut['href']}}">
                        <div class="flex items-center gap-2 w-full">
                            <x-filament::icon :icon="$shortcut['icon']" />
                            <span class="font-bold">{{ $shortcut['title'] }}</span>
                        </div>
                        <p class="text-xs text-gray-500 leading-relaxed mt-1">
                            {{ $shortcut['description'] }}
                        </p>
                    </a>
                </x-filament::card>
            @endforeach
        </div>

    </x-filament::section>
</x-filament-widgets::widget>

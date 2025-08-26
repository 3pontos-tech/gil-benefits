<x-filament-panels::page>
    <p>Browse and connect with our financial consulting experts.</p>

    {{$this->consultantSchema}}

    <div class="max-w-7xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-white">Consultants</h1>
            <p class="text-gray-500">Browse and connect with our financial consulting experts.</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3" wire:poll>
            @forelse($this->consultants as $consultant)
                <x-filament::section :secondary="true">
                    <x-slot:heading>
                        <div class="flex items-start space-x-4">
                            <div class="relative h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center text-lg font-medium text-gray-600">
                                @if($mediaUrl = $consultant->getFirstMediaUrl('avatars'))
                                    <img class="h-16 w-16 rounded-full object-cover"
                                         src="{{ $mediaUrl }}"
                                         alt="{{$consultant->name}}"/>
                                @else
                                    {{strtoupper(substr($consultant->name, 0, 1))}}
                                @endif
                            </div>
                            <div class="flex-1 space-y-3">
                                <h3 class="text-lg font-semibold leading-none dark:text-white text-black">{{$consultant->name}}</h3>
                                <p class="text-xs  dark:text-gray-300 line-clamp-3">{{$consultant->description}}</p>
                            </div>
                        </div>
                    </x-slot>
                    <div class="space-y-4 ">
                        <div class="flex flex-wrap gap-1">
                            @forelse($consultant->tags as $tag)
                                <x-filament::badge color="gray">
                                    {{$tag->name}}
                                </x-filament::badge>
                            @empty
                                <x-filament::badge color="gray">
                                    Consultor
                                </x-filament::badge>
                            @endforelse
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center space-x-2 text-sm text-gray-300">
                                <x-heroicon-c-phone class="h-4 w-4"/>
                                <span>{{$consultant->phone}}</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-300">
                                <x-heroicon-c-envelope class="h-4 w-4"/>
                                <span>{{$consultant->email}}</span>
                            </div>
                        </div>
                    </div>

                    <x-slot:footer>
                        <x-filament::button color="gray" wire:click="save">
                            Agende uma consultoria
                        </x-filament::button>
                    </x-slot:footer>

                </x-filament::section>
            @empty
                <p>There is no one</p>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>

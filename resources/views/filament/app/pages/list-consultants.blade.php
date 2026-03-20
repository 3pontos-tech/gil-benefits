@php use App\Filament\App\Resources\Consultants\Pages\ViewConsultant;use Filament\Support\Icons\Heroicon; @endphp
<x-filament-panels::page>

    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3" wire:poll>
        @forelse($this->getTableQuery()->get() as $consultant)
            <x-filament::section :secondary="true">
                <x-slot:heading>
                    <div class="flex items-start  space-x-4">
                        <div class="relativemin-h-16 min-w-16 rounded-full bg-gray-100 flex items-center justify-center text-lg font-medium text-gray-600">
                            @if($mediaUrl = $consultant->getFirstMediaUrl('avatars'))
                                <img class="h-16 w-16 rounded-full object-cover"
                                     src="{{ $mediaUrl }}"
                                     alt="{{$consultant->name}}"/>
                            @else
                                {{strtoupper(substr($consultant->name, 0, 1))}}
                            @endif
                        </div>
                        <div class="flex flex-col  h-full  my-auto space-y-3">
                            <h3 class="text-lg font-semibold leading-none dark:text-white text-black">{{$consultant->name}}</h3>
                            <p class="text-xs  dark:text-gray-300 ">{{$consultant->short_description}}</p>
                        </div>
                    </div>
                </x-slot>
                <div class="space-y-4 ">
                    <div class="flex flex-wrap gap-1">
                        @forelse($consultant->tagsWithType('specialization') as $tag)
                            <x-filament::badge size="sm" color="gray">
                                {{$tag->name}}
                            </x-filament::badge>
                        @empty
                            <x-filament::badge color="gray">
                                Marketing
                            </x-filament::badge>
                            <x-filament::badge color="gray">
                                Vendas
                            </x-filament::badge>
                            <x-filament::badge color="gray">
                                Mercado Financeiro
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
                    <div class="flex justify-between">
                        <x-filament::button class="w-9/12" color="primary" wire:click="save">
                            Agende uma consultoria
                        </x-filament::button>
                        <x-filament::button
                                tag="a"
                                href="{{ ViewConsultant::getUrl(['record' => $consultant->slug]) }}"
                                class="w-2/12"
                                tooltip="Conheça mais sobre {{$consultant->name}}"
                                :icon="Heroicon::Link"
                                color="gray"
                                wire:ignore.self
                        >
                        </x-filament::button>
                    </div>
                </x-slot:footer>

            </x-filament::section>
        @empty
            <p>There is no one</p>
        @endforelse
    </div>
    </div>
</x-filament-panels::page>

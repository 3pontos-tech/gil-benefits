@php
    use App\Enums\AvailableTagsEnum;use App\Models\Consultant;use Filament\Support\Icons\Heroicon;

    /** @var Consultant $consultant */
    $consultant = $this->getRecord();
@endphp
<div class="container">
    <x-filament::section>
        <x-slot:heading>
            <div class="flex flex-col md:flex-row md:items-start gap-4  md:space-x-6">
                <span class="relative flex size-8 shrink-1 overflow-hidden rounded-full h-24 w-24 mx-auto md:mx-0">
                    <img class="aspect-square size-full" alt="{{ $consultant->name }}"
                         src="{{ $consultant->getFirstMediaUrl('avatars') }}">
                </span>
                <div class="text-center md:text-left">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between mb-4">
                        <div class="flex flex-col gap-2">
                            <div>
                                <h1 class="text-3xl font-bold mb-1">{{ $consultant->name }}</h1>
                                <p class="text-md text-muted-foreground mb-2">
                                    {{ $consultant->short_description }}
                                </p>
                            </div>
                            <div class="flex items-center justify-center md:justify-start space-x-4 text-xs text-muted-foreground">
                                <div class="flex items-center  space-x-1">
                                    <x-heroicon-c-map-pin class="h-4 w-4"></x-heroicon-c-map-pin>
                                    <span>{{ 'Cachoeira Paulista - SP' }}</span>
                                </div>
                                <div class="flex items-center space-x-1">
                                    <x-heroicon-c-user class="h-4 w-4"></x-heroicon-c-user>
                                    <span>12+ anos de exp.</span>
                                </div>
                                <div class="flex items-center space-x-1">
                                    <x-heroicon-c-calendar-date-range
                                            class="h-4 w-4"></x-heroicon-c-calendar-date-range>
                                    <span>847 atendimentos</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap justify-center md:justify-start gap-4 text-xs">
                        <div class="flex items-center space-x-2">
                            <x-heroicon-c-device-phone-mobile class="h-4 w-4"></x-heroicon-c-device-phone-mobile>
                            <span>{{ $consultant->phone }}</span>
                        </div>
                        <a href="{{ 'mailto:' . $consultant->email }}" class="flex items-center space-x-2">
                            <x-heroicon-c-envelope class="h-4 w-4"></x-heroicon-c-envelope>
                            <span>{{ $consultant->email }}</span>
                        </a>
                        @foreach($consultant->socials_urls as $social => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                               class="flex items-center space-x-2  hover:underline">
                                <x-dynamic-component class="h-4 w-4 hover:text-primary-500"
                                                     component="fab-{{$social}}" />
                            </a>
                        @endforeach

                    </div>
                </div>
            </div>
        </x-slot:heading>

        <div class="grid grid-cols-12 gap-6">
            <div id="about-content" class="md:col-span-8 col-span-12 flex flex-col gap-6">
                <div id="about">
                    <h3 class="font-bold mb-4 text-lg">Sobre {{ str($consultant->name)->explode(' ')->first() }}</h3>
                    <div id="about-content" class="prose prose-sm prose-neutral dark:prose-invert">
                        {!! str($consultant->biography)->markdown() !!}
                    </div>
                </div>
                <div id="about">
                    <h3 class="font-bold mb-4 text-lg">Como podemos facilitar o trabalho?</h3>
                    <div id="about-content" class="prose prose-sm dark:prose-invert">
                        {!! str($consultant->readme)->markdown() !!}
                    </div>
                </div>

            </div>

            <div id="sidebar" class="md:col-span-4 grid grid-cols-2  col-span-12 wrap-normal md:flex md:flex-col gap-6">
                <div class=" md:flex gap-4 md:flex-col">
                    @if($specializations = $consultant->tagsWithType(AvailableTagsEnum::Specialization->value))
                        <x-filament::section class="col-span-1">
                            <div class="flex flex-row items-center gap-2">
                                <x-filament::icon :icon="AvailableTagsEnum::Specialization->getIcon()"
                                                  class="h-4 w-4 text-muted-foreground"></x-filament::icon>
                                <h3 class="font-bold">{{ AvailableTagsEnum::Specialization->getLabel() }}</h3>
                            </div>

                            <div class="px-4 mt-4 flex flex-wrap gap-2">
                                @foreach($specializations as $tag)
                                    <x-filament::badge color="gray"> {{ $tag->name }}</x-filament::badge>
                                @endforeach
                            </div>
                        </x-filament::section>
                    @endif
                </div>

                <div class="flex gap-4 flex-col">
                    @if($tags = $consultant->tagsWithType(AvailableTagsEnum::Language->value))
                        <x-filament::section class="col-span-1">
                            <div class="flex flex-row items-center gap-2">
                                <x-filament::icon :icon="AvailableTagsEnum::Language->getIcon()"
                                                  class="h-4 w-4 text-muted-foreground"></x-filament::icon>
                                <h3 class="font-bold">{{ AvailableTagsEnum::Language->getLabel() }}</h3>
                            </div>

                            <div class="px-4 mt-4 flex flex-wrap gap-2">
                                @foreach($tags as $tag)
                                    <x-filament::badge color="slate"> {{ $tag->name }}</x-filament::badge>
                                @endforeach
                            </div>
                        </x-filament::section>
                    @endif
                </div>
                <div class="flex gap-4 flex-col">
                    @if($tags = $consultant->tagsWithType(AvailableTagsEnum::Expertise->value))
                        <x-filament::section>
                            <div class="flex flex-row items-center gap-2">
                                <x-filament::icon :icon="AvailableTagsEnum::Expertise->getIcon()"
                                                  class="h-4 w-4 text-muted-foreground"></x-filament::icon>
                                <h3 class="font-bold">{{ AvailableTagsEnum::Expertise->getLabel() }}</h3>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-4">
                                <ul class="mt-2 flex flex-col gap-2">
                                    @foreach($tags as $tag)
                                        <li class="flex gap-2">
                                            <x-filament::icon :icon="Heroicon::Check"></x-filament::icon>
                                            <span class="text-muted-foreground text-xs">{{ $tag->name }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </x-filament::section>
                    @endif
                </div>
                <div class="flex gap-4 flex-col">
                    @if($tags = $consultant->tagsWithType(AvailableTagsEnum::Education->value))
                        <x-filament::section>
                            <div class="flex flex-row items-center gap-2">
                                <x-filament::icon :icon="AvailableTagsEnum::Education->getIcon()"
                                                  class="h-4 w-4 text-muted-foreground"></x-filament::icon>
                                <h3 class="font-bold">{{ AvailableTagsEnum::Education->getLabel() }}</h3>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-4">
                                <ul class="mt-2 flex flex-col gap-2">
                                    @foreach($tags as $tag)
                                        <li class="flex gap-4 items-center">
                                            <div class="h-2.5 w-2.5 bg-zinc-600 rounded-full animate-pulse"></div>
                                            <span class="text-muted-foreground text-xs">{{ $tag->name }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </x-filament::section>
                    @endif
                </div>
            </div>
        </div>

    </x-filament::section>
</div>
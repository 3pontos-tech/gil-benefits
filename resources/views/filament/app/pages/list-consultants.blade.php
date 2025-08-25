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
                <div class="bg-primary rounded-lg border border-gray-100 shadow-sm h-full">
                    <div class="p-6 pb-4">
                        <div class="flex items-start space-x-4">
                            <div class="relative h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center text-lg font-medium text-gray-600">
                              <x-heroicon-c-user-circle/>
                            </div>
                            <div class="flex-1 space-y-2">
                                <h3 class="text-lg font-semibold leading-none text-white">{{$consultant->name}}</h3>
                                <p class="text-sm text-gray-300 line-clamp-3">{{$consultant->description}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 pb-6 space-y-4">
                        <div class="flex flex-wrap gap-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-700 text-gray-100">Investment Planning</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-700 text-gray-100">Wealth Management</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-700 text-gray-100">Retirement</span>
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

                        <button wire:click='save' class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-black bg-white hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-white">
                            <x-heroicon-c-calendar class="mr-2 h-4 w-4"/>
                            Book Appointment
                        </button>
                    </div>
                </div>
            @empty
                <p>There is no one</p>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>

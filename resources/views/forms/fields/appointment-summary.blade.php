@php
    use Carbon\Carbon;use TresPontosTech\Consultants\Models\Consultant;

    $consultant = Consultant::query()->find($get('consultant_id'));
    $date = $get('date') ? Carbon::parse($get('date'))->translatedFormat('l, F d, Y') : null;
    $time = $get('time');
    $duration = '60 minutes';
@endphp

<div class="space-y-6">
    <div class="space-y-4">
        <div class="border-px rounded-lg shadow-sm">
            <div class="px-4 py-2">
                <h3 class="text-lg font-semibold">Appointment Summary</h3>
            </div>
            <div class="p-4 space-y-4">
                {{-- Consultant --}}
                <div class="flex items-center space-x-3">
                    <x-heroicon-o-user class="h-5 w-5 text-gray-400"/>
                    <div>
                        <p class="font-medium">{{ $consultant?->name ?? '-' }}</p>
                        <p class="text-sm text-gray-500">{{ $consultant?->email ?? '-' }}</p>
                    </div>
                </div>

                {{-- Date & Time --}}
                <div class="flex items-center space-x-3">
                    <x-heroicon-o-calendar class="h-5 w-5 text-gray-400"/>
                    <div>
                        <p class="font-medium">{{ $date ?? '-' }}</p>
                        <p class="text-sm text-gray-500">{{ $time ?? '-' }}</p>
                    </div>
                </div>

                {{-- Duration --}}
                <div class="flex items-center space-x-3">
                    <x-heroicon-o-clock class="h-5 w-5 text-gray-400"/>
                    <div>
                        <p class="font-medium">{{ $duration }}</p>
                        <p class="text-sm text-gray-500">Meeting duration</p>
                    </div>
                </div>


            </div>
        </div>
    </div>
</div>

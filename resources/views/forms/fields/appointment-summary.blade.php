@php
    use Carbon\Carbon;
    use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;

    $categoryType = $get('category_type') ? AppointmentCategoryEnum::tryFrom($get('category_type')) : null;
    $date = $get('date') ? Carbon::parse($get('date'))->translatedFormat('l, F d, Y') : null;
    $time = $get('appointment_at') ? Carbon::parse($get('appointment_at'))->format('H:i') : null;

    $duration = __('views.appointment_summary.duration_minutes');
@endphp

<div class="space-y-6">
    <div class="space-y-4">
        <div class="border-px rounded-lg shadow-sm">
            <div class="px-4 py-2">
                <h3 class="text-lg font-semibold">{{ __('views.appointment_summary.title') }}</h3>
            </div>
            <div class="p-4 space-y-4">
                {{-- Category --}}
                <div class="flex items-center space-x-3">
                    <x-heroicon-o-tag class="h-5 w-5 text-gray-400"/>
                    <div>
                        <p class="font-medium">{{ $categoryType?->getLabel() ?? '-' }}</p>
                        <p class="text-sm text-gray-500">{{ __('appointments::resources.appointments.wizard.steps.category_type') }}</p>
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
                        <p class="text-sm text-gray-500">{{ __('views.appointment_summary.meeting_duration') }}</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

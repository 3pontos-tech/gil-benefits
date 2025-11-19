@props([
    'planName',
    'description',
    'status',
    'features',
    'availableAppointments'
])

@php
    use Filament\Support\Icons\Heroicon;use TresPontosTech\Appointments\Filament\App\Resources\Appointments\AppointmentResource;
@endphp
<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-cube">
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white leading-tight">
                        Plano {{ $planName }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{ $description }}
                    </p>
                </div>
                <div>
                    <x-filament::badge icon="heroicon-o-check-circle">
                        {{ trans_choice('all.appointments_left', $availableAppointments, ['count' => $availableAppointments]) }}
                    </x-filament::badge>
                </div>
            </div>
        </x-slot>


        <div class="grid grid-cols-2 gap-3 mb-6">
            @foreach($features as $key => $feature)
                <div class="flex items-center gap-2 text-sm">
                    <div class="h-1.5 w-1.5 rounded-full bg-primary-500"></div>
                    <span>{{ __('all.' . $key) }}</span>
                </div>
            @endforeach
        </div>


        <x-slot name="footer">
            @php
                $blockReasons = [];
                if (($availableAppointments ?? 0) <= 0) {
                    $blockReasons[] = __('Você não possui agendamentos disponíveis neste mês.');
                }
                if (($hasOngoingAppointment ?? false) === true) {
                    $blockReasons[] = __('Você possui uma consultoria em andamento. Finalize a anterior para agendar outra.');
                }
            @endphp

            <x-filament::button
                wire:click="redirectToAppointmentCreation"
                icon="heroicon-o-calendar"
                :disabled="isset($canCreateAppointment) && ! $canCreateAppointment"
            >
                Agendar Consultoria
            </x-filament::button>

            @if(isset($canCreateAppointment) && ! $canCreateAppointment && count($blockReasons) > 0)
                <div class="mt-2 text-sm text-danger-600 dark:text-danger-400">
                    @foreach($blockReasons as $reason)
                        <div class="flex items-start gap-2">
                            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-4 w-4 shrink-0" />
                            <span>{{ $reason }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-slot>


    </x-filament::section>
</x-filament-widgets::widget>

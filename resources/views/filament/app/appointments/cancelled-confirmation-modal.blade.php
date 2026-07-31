@php
    /** @var \TresPontosTech\Appointments\Models\Appointment $appointment */
@endphp

<div class="flex flex-col gap-8">
    {{-- Card do agendamento que acabou de ser cancelado, no mesmo desenho
         contornado da confirmação. --}}
    <div class="border border-gray-200 p-4 dark:border-white/10">
        <p class="flex flex-wrap items-center gap-x-1.5 text-[16px] text-gray-950 dark:text-white">
            <span class="font-bold">
                {{ __('panel-app::resources.appointments.cancel.confirmed.appointment_cancelled') }}
            </span>
            <span class="text-gray-300 dark:text-gray-600" aria-hidden="true">•</span>
            <span class="text-gray-500 dark:text-gray-400">
                {{ $appointment->category_type->getLabel() }}
            </span>
        </p>
        <p class="mt-1 text-[16px] text-gray-500 dark:text-gray-400">
            {{ $appointment->appointment_at->format('d/m/y - H:i') }}.
        </p>
    </div>

    {{-- Estado do crédito. Só aparece quando o cancelamento foi dentro do prazo
         e o crédito volta — o "caso aplicável" do texto de cabeçalho. --}}
    @if ($keepsCredit)
        <div class="flex items-center gap-2 text-success-600 dark:text-success-400">
            <x-filament::icon icon="heroicon-o-check-circle" class="size-5 shrink-0"/>
            <span class="text-[16px] font-medium">
                {{ __('panel-app::resources.appointments.cancel.confirmed.credit_processing') }}
            </span>
        </div>
    @endif
</div>

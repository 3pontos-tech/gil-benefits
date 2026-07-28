@php
    /** @var \TresPontosTech\Appointments\Models\Appointment $appointment */
    $consultant = $appointment->consultant;
@endphp

<div class="flex flex-col gap-4">
    <div class="flex items-center gap-3 border border-gray-200 p-4 dark:border-white/10">
        <span class="flex size-10 shrink-0 items-center justify-center border border-danger-500/30 bg-danger-500/10 text-danger-500">
            <x-filament::icon icon="heroicon-o-calendar-days" class="size-5"/>
        </span>

        <div class="min-w-0">
            <p class="truncate text-[16px] font-bold text-gray-950 dark:text-white">
                {{ $appointment->category_type->getLabel() }}
            </p>
            <p class="mt-1 flex flex-wrap items-center gap-x-1.5 text-[16px] text-gray-500 dark:text-gray-400">
                <x-filament::icon icon="heroicon-o-calendar" class="size-4 shrink-0"/>
                <span>{{ $appointment->appointment_at->format('d/m/y - H:i') }}</span>
                @if($consultant)
                    <span aria-hidden="true">-</span>
                    <span class="text-primary-600 dark:text-primary-400">{{ $consultant->name }}</span>
                @endif
            </p>
        </div>
    </div>

    {{-- O aviso muda de tom: antes do prazo o crédito volta, depois dele é consumido. --}}
    <div @class([
        'flex items-start gap-3 border p-4',
        'border-warning-500/30 bg-warning-500/10' => $keepsCredit,
        'border-danger-500/30 bg-danger-500/10' => ! $keepsCredit,
    ])>
        <x-filament::icon
            icon="heroicon-o-exclamation-circle"
            @class([
                'mt-0.5 size-5 shrink-0',
                'text-warning-500' => $keepsCredit,
                'text-danger-500' => ! $keepsCredit,
            ])
        />
        <p @class([
            'text-[16px] leading-snug',
            'text-warning-700 dark:text-warning-300' => $keepsCredit,
            'text-danger-700 dark:text-danger-300' => ! $keepsCredit,
        ])>
            {{ $keepsCredit
                ? __('panel-app::resources.appointments.cancel.notice_keeps_credit', ['hours' => $noticeHours])
                : __('panel-app::resources.appointments.cancel.notice_loses_credit', ['hours' => $noticeHours]) }}
        </p>
    </div>
</div>

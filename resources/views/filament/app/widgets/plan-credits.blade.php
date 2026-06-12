@php
    $pct = $monthlyLimit > 0 ? min(100, (int) round((($monthlyLimit - $monthlyLeft) / $monthlyLimit) * 100)) : 0;
    $statusColors = [
        'active' => 'text-green-700 bg-green-50 dark:text-green-300 dark:bg-green-500/10',
        'inactive' => 'text-gray-600 bg-gray-100 dark:text-gray-300 dark:bg-white/10',
        'expired' => 'text-danger-700 bg-danger-50 dark:text-danger-300 dark:bg-danger-500/10',
    ];
@endphp
<x-filament-widgets::widget class="h-full">
    <div class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
        <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Plano &amp; créditos</p>
            @if($plan)
                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusColors[$plan->status] ?? $statusColors['inactive'] }}">
                    {{ __('panel-app::widgets.plan_status.' . $plan->status) }}
                </span>
            @endif
        </div>

        @if($plan)
            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">{{ $plan->name }}</p>
        @endif

        <div class="mt-4 flex items-center gap-4">
            <div class="flex size-16 shrink-0 items-center justify-center rounded-full"
                 style="background: conic-gradient(var(--primary-600) {{ $pct }}%, var(--gray-200) {{ $pct }}%);">
                <span class="flex size-12 items-center justify-center rounded-full bg-white text-sm font-bold text-gray-900 dark:bg-gray-900 dark:text-white">
                    {{ $monthlyLeft }}/{{ $monthlyLimit }}
                </span>
            </div>
            <div class="flex-1 text-sm leading-snug text-gray-500 dark:text-gray-400">
                agendamentos restantes este mês
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4 dark:border-white/5">
            <span class="text-sm text-gray-500 dark:text-gray-400">Créditos avulsos</span>
            <span class="text-lg font-semibold text-gray-900 dark:text-white">+{{ $availableCredits }}</span>
        </div>

        <div class="mt-auto pt-4">
            @if($canCreateAppointment)
                <x-filament::button wire:click="redirectToAppointmentCreation" class="w-full">
                    Agendar consultoria
                </x-filament::button>
            @else
                <x-filament::button disabled class="w-full">
                    Agendar consultoria
                </x-filament::button>
                @foreach($blockReasons as $reason)
                    <p class="mt-2 flex items-start gap-1.5 text-xs text-danger-600 dark:text-danger-400">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 size-4 shrink-0" />
                        <span>{{ $reason }}</span>
                    </p>
                @endforeach
            @endif

            @if($plan)
                <div class="mt-3 text-center">
                    {{ $this->viewPlanAction }}
                </div>
            @endif
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-widgets::widget>

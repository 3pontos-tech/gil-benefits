<x-filament-widgets::widget class="h-full">
    <div class="flex h-full flex-col gap-4 border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
        <div class="flex items-center justify-between gap-2">
            <h2 class="whitespace-nowrap text-sm font-bold text-gray-950 dark:text-white">
                {{ __('panel-app::widgets.plan_credits.title') }}
            </h2>
            @if($plan)
                {{ $this->viewPlanAction }}
            @else
                <a
                    href="{{ $creditsUrl }}"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 transition hover:text-gray-950 dark:text-gray-400 dark:hover:text-white"
                >
                    {{ __('panel-app::widgets.plan_credits.access_plan') }}
                    <x-filament::icon icon="heroicon-m-arrow-right" class="size-3.5"/>
                </a>
            @endif
        </div>

        {{-- Cartão de créditos: único ponto com gradiente, nos tons da marca. --}}
        <div
            class="p-5 text-white"
            style="background-image: linear-gradient(135deg, var(--danger-600) 0%, var(--danger-500) 40%, var(--primary-500) 100%);"
        >
            <p class="text-lg font-bold">{{ __('panel-app::widgets.plan_credits.credits_card_title') }}</p>

            <p class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-bold leading-none">{{ $creditsTotal }}</span>
                <span class="text-xs text-white/80">{{ __('panel-app::widgets.plan_credits.credits_available') }}</span>
            </p>

            @if($ownCredits > 0 || $companyCredits > 0)
                <p class="mt-1 flex flex-wrap items-center gap-x-3 text-[11px] text-white/70">
                    @if($ownCredits > 0)
                        <span>{{ trans_choice('panel-app::widgets.plan_credits.credits_own', $ownCredits, ['count' => $ownCredits]) }}</span>
                    @endif
                    @if($companyCredits > 0)
                        <span>{{ trans_choice('panel-app::widgets.plan_credits.credits_company', $companyCredits, ['count' => $companyCredits]) }}</span>
                    @endif
                </p>
            @endif

            <p class="mt-4 text-[10px] uppercase tracking-wide text-white/70">
                {{ __('panel-app::widgets.plan_credits.holder') }}
            </p>
            <p class="text-sm font-bold">{{ $holderName }}</p>
        </div>

        <div class="border border-gray-200 px-4 py-3 dark:border-white/10">
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                {{ __('panel-app::widgets.plan_credits.monthly_appointments') }}
            </p>
            <p class="mt-1 flex items-baseline gap-1">
                <span class="text-xl font-bold leading-none text-gray-950 dark:text-white">{{ $monthlyLeft }}</span>
                <span class="text-xs font-medium text-gray-400 dark:text-gray-500">/{{ $monthlyLimit }}</span>
            </p>
        </div>

        <div class="border border-gray-200 px-4 py-3 dark:border-white/10">
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                {{ __('panel-app::widgets.plan_credits.consultant') }}
            </p>
            <p class="mt-1 flex items-center gap-2">
                @if($consultantName)
                    <span class="size-2 shrink-0 rounded-full bg-success-500"></span>
                    <span class="truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $consultantName }}</span>
                @else
                    <span class="size-2 shrink-0 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                    <span class="truncate text-sm text-gray-500 dark:text-gray-400">
                        {{ __('panel-app::widgets.plan_credits.no_consultant') }}
                    </span>
                @endif
            </p>
        </div>

        <div class="mt-auto">
            {{-- Quando o agendamento está bloqueado o botão sai sem wire:click:
                 desabilitar no HTML não impediria a chamada Livewire. --}}
            @if($canCreateAppointment)
                <x-filament::button
                    wire:click="redirectToAppointmentCreation"
                    icon="heroicon-o-calendar-days"
                    class="w-full justify-center"
                >
                    {{ __('panel-app::widgets.plan_credits.book_appointment') }}
                </x-filament::button>
            @else
                @foreach($blockReasons as $reason)
                    <p class="mb-2 flex items-start gap-1.5 text-xs text-danger-600 dark:text-danger-400">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 size-4 shrink-0"/>
                        <span>{{ $reason }}</span>
                    </p>
                @endforeach

                <x-filament::button disabled icon="heroicon-o-calendar-days" class="w-full justify-center">
                    {{ __('panel-app::widgets.plan_credits.book_appointment') }}
                </x-filament::button>
            @endif
        </div>
    </div>

    <x-filament-actions::modals/>
</x-filament-widgets::widget>

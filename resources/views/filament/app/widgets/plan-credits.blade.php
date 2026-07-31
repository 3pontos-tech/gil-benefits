{{-- O card preenche a própria linha da grade em vez de abraçar o conteúdo: com
     self-start sobrava ~99px de linha entre ele e o card de materiais abaixo.
     Preenchendo, resta só o row-gap da grade, e a sobra vira respiro acima do
     botão, que fica ancorado no rodapé. --}}
<x-filament-widgets::widget class="h-full">
    <div class="flex h-full flex-col gap-8 border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
        <div class="flex items-center justify-between gap-2">
            <h2 class="whitespace-nowrap text-[24px] font-bold leading-tight text-gray-950 dark:text-white">
                {{ __('panel-app::widgets.plan_credits.title') }}
            </h2>
            @if($plan)
                {{ $this->viewPlanAction }}
            @else
                <a
                    href="{{ $creditsUrl }}"
                    class="inline-flex items-center gap-1.5 whitespace-nowrap text-xs font-medium text-primary-600 transition hover:text-primary-500 dark:text-primary-400"
                >
                    {{ __('panel-app::widgets.plan_credits.access_plan') }}
                    <x-filament::icon icon="heroicon-m-arrow-right" class="size-3.5"/>
                </a>
            @endif
        </div>

        {{-- Cartão de créditos: gradiente e raio vêm literais do design. São os
             únicos valores de cor fora da paleta do painel, então não acompanham
             uma eventual mudança de tema. --}}
        <div
            class="p-8 text-white"
            style="border-radius: 4px; background: linear-gradient(114deg, #FD0342 15.42%, #FF803C 84.58%);"
        >
            <p class="text-[24px] font-bold leading-tight">
                {{ __('panel-app::widgets.plan_credits.credits_card_title') }}
            </p>

            <p class="mt-8 flex items-baseline gap-2">
                <span class="text-[32px] font-bold leading-none">{{ $creditsTotal }}</span>
                <span class="text-[16px] text-white/80">{{ __('panel-app::widgets.plan_credits.credits_available') }}</span>
            </p>

            {{-- Rótulo e nome ficam próximos entre si e afastados do bloco acima,
                 para lerem como um par. --}}
            <p class="mt-10 text-[16px] uppercase tracking-wide text-white/70">
                {{ __('panel-app::widgets.plan_credits.holder') }}
            </p>
            <p class="mt-2 text-[20px] font-bold leading-tight">{{ $holderName }}</p>
        </div>

        <div class="border border-gray-200 px-4 py-3 dark:border-white/10">
            <p class="text-[16px] text-gray-500 dark:text-gray-400">
                {{ __('panel-app::widgets.plan_credits.monthly_appointments') }}
            </p>
            <p class="mt-1 flex items-baseline gap-1">
                <span class="text-[24px] font-bold leading-none text-gray-950 dark:text-white">{{ $monthlyLeft }}</span>
                {{-- O total fica subordinado, como o "/100" dos cards de indicador. --}}
                <span class="text-[16px] font-medium text-gray-400 dark:text-gray-500">/{{ $monthlyLimit }}</span>
            </p>
        </div>

        <div class="border border-gray-200 px-4 py-3 dark:border-white/10">
            <p class="text-[16px] text-gray-500 dark:text-gray-400">
                {{ __('panel-app::widgets.plan_credits.consultant') }}
            </p>
            <p class="mt-1 flex items-center gap-2">
                @if($consultantName)
                    <span class="size-2 shrink-0 rounded-full bg-success-500"></span>
                    <span class="truncate text-[24px] font-semibold leading-tight text-gray-950 dark:text-white">{{ $consultantName }}</span>
                @else
                    <span class="size-2 shrink-0 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                    <span class="truncate text-[24px] text-gray-500 dark:text-gray-400">
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
                    wire:click="mountAction('scheduleAppointment')"
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

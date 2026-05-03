@php
    use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
    use TresPontosTech\Company\Models\Company;

    /** @var Company $company */
    /** @var Subscription|null $subscription */

    $quantity  = $subscription?->quantity ?? 0;
    $plan      = $subscription?->plan;

    $tierPrice = match (true) {
        $quantity <= 15 => 44.90,
        $quantity <= 30 => 34.90,
        $quantity <= 70 => 24.90,
        default         => 11.90,
    };

    $totalPerMonth = $quantity * $tierPrice;

    $billingDay  = $subscription?->created_at->day ?? now()->day;
    $nextBilling = now()->day($billingDay)->startOfDay();
    if ($nextBilling->isPast()) {
        $nextBilling->addMonthNoOverflow();
    }
@endphp

<div class="py-8 px-4 sm:px-6 lg:px-8">

    @if ($subscription && $plan)
        <x-filament::section heading="Assinatura Atual" class="mb-6">
            <div class="space-y-5">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium mb-1">Plano</p>
                    <p class="text-2xl font-bold">{{ $plan->name }}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium mb-1">Valor mensal</p>
                    <p class="text-3xl font-bold">
                        R$ {{ number_format($totalPerMonth, 2, ',', '.') }}
                        <span class="text-base font-normal text-gray-500">/mês</span>
                    </p>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $quantity }} colaboradores × R$ {{ number_format($tierPrice, 2, ',', '.') }}/assento
                    </p>
                </div>

                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium mb-1">Próxima cobrança</p>
                    <p class="text-base">{{ $nextBilling->translatedFormat('d \d\e F \d\e Y') }}</p>
                </div>
            </div>

            <x-slot name="footer">
                {{$this->cancelSubscription}}
                <x-filament-actions::modals />
            </x-slot>
        </x-filament::section>

        <x-filament::section heading="Empresa">
            <dl class="space-y-3">
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500 dark:text-gray-400">Nome</dt>
                    <dd class="font-medium">{{ $company->name }}</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500 dark:text-gray-400">CNPJ</dt>
                    <dd class="font-medium">{{ $company->tax_id }}</dd>
                </div>
            </dl>
        </x-filament::section>

    @else
        <x-filament::section heading="Assinatura">
            <p class="text-gray-500">Nenhuma assinatura ativa encontrada para esta empresa.</p>
        </x-filament::section>
    @endif

    <div class="mt-6">
        <a href="{{ $returnUrl }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            ← Voltar ao painel
        </a>
    </div>

</div>

<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Support;

use Carbon\CarbonImmutable;
use TresPontosTech\Billing\Core\DTOs\CompanyBillingStatus;
use TresPontosTech\Billing\Core\Enums\CompanyFinancialStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Models\Company;

/**
 * Status financeiro unificado e próxima cobrança estimada (FLM-41, D-05).
 *
 * Alimenta três stories de uma vez: os cards por status da 233, a coluna de
 * próxima cobrança da 234 e o alerta de vencimento da 237.
 */
final class CompanyStatusResolver
{
    /**
     * `stripe_status` → status financeiro.
     *
     * A coluna guarda tanto os status do Cashier/Stripe quanto os que os handlers
     * de Virtu e Barte escrevem (`pending`, `defaulter`, `inactive`), então o mapa
     * cobre os dois vocabulários. Status desconhecido cai em `Cancelled` pelo
     * caminho conservador: melhor não contar como receita viva do que inflar a
     * base de clientes com um estado que ninguém mapeou.
     */
    private const array STATUS_MAP = [
        'active' => CompanyFinancialStatusEnum::Active,
        'trialing' => CompanyFinancialStatusEnum::Trial,
        'pending' => CompanyFinancialStatusEnum::Trial,
        'past_due' => CompanyFinancialStatusEnum::Delinquent,
        'defaulter' => CompanyFinancialStatusEnum::Delinquent,
        'unpaid' => CompanyFinancialStatusEnum::Delinquent,
        'inactive' => CompanyFinancialStatusEnum::Cancelled,
        'canceled' => CompanyFinancialStatusEnum::Cancelled,
        'cancelled' => CompanyFinancialStatusEnum::Cancelled,
        'incomplete_expired' => CompanyFinancialStatusEnum::Cancelled,
    ];

    public function resolve(Company $company, ?CarbonImmutable $now = null): CompanyBillingStatus
    {
        $now ??= CarbonImmutable::now();

        $subscription = $this->latestSubscription($company);

        if ($subscription instanceof Subscription) {
            $status = $this->statusFromSubscription($subscription, $now);

            return new CompanyBillingStatus(
                status: $status,
                nextChargeAt: $this->nextChargeFor($status, $subscription->created_at?->toImmutable(), $now),
            );
        }

        $contract = $company->activeContractualPlan();

        if (! $contract instanceof CompanyPlan) {
            return CompanyBillingStatus::none();
        }

        return new CompanyBillingStatus(
            status: CompanyFinancialStatusEnum::Active,
            nextChargeAt: $this->nextChargeFor(
                CompanyFinancialStatusEnum::Active,
                ($contract->starts_at ?? $contract->created_at)?->toImmutable(),
                $now,
            ),
        );
    }

    /**
     * Respeita a relação já carregada quando existe.
     *
     * Sem isso, uma listagem de todas as empresas dispararia uma consulta por
     * linha só para descobrir o status — exatamente o tipo de N+1 que estoura o
     * guarda-rail de 2s do épico. Quem chama em lote faz `with('subscriptions')`
     * e este método usa o que já está na memória.
     */
    private function latestSubscription(Company $company): ?Subscription
    {
        if ($company->relationLoaded('subscriptions')) {
            /** @var Subscription|null $subscription */
            $subscription = $company->subscriptions->sortByDesc('created_at')->first();

            return $subscription;
        }

        return $company->subscriptions()->latest('created_at')->first();
    }

    /**
     * Trial vence o status persistido enquanto `trial_ends_at` está no futuro:
     * o Cashier deixa `stripe_status` em `active` durante o teste, e o card
     * "Em Trial" da STORY-233 ficaria sempre zerado se olhássemos só a coluna.
     */
    private function statusFromSubscription(Subscription $subscription, CarbonImmutable $now): CompanyFinancialStatusEnum
    {
        $mapped = self::STATUS_MAP[$subscription->stripe_status] ?? CompanyFinancialStatusEnum::Cancelled;

        if ($mapped === CompanyFinancialStatusEnum::Cancelled) {
            return $mapped;
        }

        $trialEndsAt = $subscription->trial_ends_at?->toImmutable();

        if ($trialEndsAt instanceof CarbonImmutable && $trialEndsAt->greaterThan($now)) {
            return CompanyFinancialStatusEnum::Trial;
        }

        return $mapped;
    }

    /**
     * Próxima cobrança estimada: mesmo dia do mês da âncora, rolado para a
     * próxima ocorrência futura.
     *
     * O ciclo é sempre mensal porque é o único que o produto vende — o
     * `VirtuAdapter` fixa `VirtuIntervalEnum::Monthly` ao criar o link, o
     * `SyncBartePlans` só importa planos `MONTHLY`, e `billing_plans` sequer tem
     * coluna de intervalo. Se um ciclo diferente passar a existir, este método é
     * o ponto que precisa mudar — hoje ele não teria como descobrir sozinho.
     *
     * `addMonthsNoOverflow` cuida da virada de mês curto: âncora no dia 31
     * cai no dia 30 em novembro, e não no dia 1º de dezembro.
     */
    private function nextChargeFor(
        CompanyFinancialStatusEnum $status,
        ?CarbonImmutable $anchor,
        CarbonImmutable $now,
    ): ?CarbonImmutable {
        if (! $anchor instanceof CarbonImmutable) {
            return null;
        }

        if (! in_array($status, CompanyFinancialStatusEnum::livingCases(), strict: true)) {
            return null;
        }

        $elapsed = max(0, (int) $anchor->diffInMonths($now));
        $next = $anchor->addMonthsNoOverflow($elapsed);

        while ($next->lessThanOrEqualTo($now)) {
            $next = $next->addMonthNoOverflow();
        }

        return $next;
    }
}

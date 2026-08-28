<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Support;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use TresPontosTech\Billing\Core\DTOs\MonthlyValue;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\MonthlyValueSourceEnum;
use TresPontosTech\Billing\Core\Enums\SeatPricingTierEnum;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Models\Company;

/**
 * Ponto único de valoração do cockpit financeiro (FLM-41, decisão D-01).
 *
 * A regra não é inventada aqui: é a mesma que `VirtuAdapter::resolveAmountInCents`
 * e `BarteAdapter` aplicam ao criar o checkout — assinatura de empresa é metered
 * e vale a faixa de assento vezes a quantidade; assinatura de pessoa vale o preço
 * cadastrado. Espelhar em vez de recalcular é o que garante que o painel mostre
 * o mesmo número que o cliente pagou.
 *
 * Nenhuma coluna de dinheiro foi criada para isso: o épico não duplica a verdade
 * do financeiro, deriva dela.
 */
final class RevenueResolver
{
    /**
     * Status que representam receita contratada.
     *
     * Inadimplente entra: a assinatura segue valendo enquanto não é cancelada, e
     * tirá-la do MRR a cada atraso serrilharia o gráfico de evolução a cada
     * recuperação. Quem quer a visão de caixa olha o módulo de cobranças.
     */
    private const array REVENUE_STATUSES = ['active', 'trialing', 'past_due', 'defaulter'];

    /**
     * Valor mensal de uma assinatura, exatamente como o checkout o calculou.
     *
     * Devolve `null` quando a assinatura avulsa aponta para um preço que não
     * existe mais — situação real, já que `billing_plan_prices` usa soft delete.
     */
    public function forSubscription(Subscription $subscription): ?int
    {
        if ($this->isMetered($subscription)) {
            $quantity = max(1, $subscription->quantity ?? 1);

            return MoneyCents::fromReais(
                SeatPricingTierEnum::fromQuantity($quantity)->pricePerSeat() * $quantity
            )->cents;
        }

        $unitAmount = $subscription->price?->unit_amount_decimal;

        return $unitAmount === null ? null : (int) $unitAmount;
    }

    /**
     * Quanto uma empresa paga por mês.
     *
     * Só conta o que foi efetivamente cobrado por uma assinatura. Contrato B2B
     * não guarda preço, e estimá-lo pela tabela de assento foi descartado: o
     * número apareceria na tela idêntico a uma cobrança real sendo um palpite
     * sobre um valor que, por definição, é negociado fora da tabela.
     *
     * A empresa nesse caso vale `unknown`, nunca zero — ela é pagante e a Flamma
     * não sabe quanto. Cabe à tela mostrar o badge, não ao resolver inventar.
     */
    public function monthlyValueForCompany(Company $company): MonthlyValue
    {
        $subscription = $this->revenueSubscriptionOf($company);

        if (! $subscription instanceof Subscription) {
            return MonthlyValue::unknown();
        }

        $cents = $this->forSubscription($subscription);

        return $cents === null
            ? MonthlyValue::unknown()
            : MonthlyValue::charged($cents, MonthlyValueSourceEnum::SubscriptionSeatTier);
    }

    /**
     * Quanto um assinante avulso paga por mês.
     */
    public function monthlyValueForUser(User $user): MonthlyValue
    {
        $subscription = $this->revenueSubscriptionOf($user);

        if (! $subscription instanceof Subscription) {
            return MonthlyValue::unknown();
        }

        $cents = $this->forSubscription($subscription);

        return $cents === null
            ? MonthlyValue::unknown()
            : MonthlyValue::charged($cents, MonthlyValueSourceEnum::SubscriptionPrice);
    }

    /**
     * A assinatura mais recente que representa receita, respeitando a relação
     * já carregada quando existe.
     *
     * Sem esse desvio, a listagem de todas as empresas dispararia uma consulta
     * por linha só para descobrir o valor — o mesmo N+1 que o
     * `CompanyStatusResolver` evita. Quem chama em lote faz
     * `with('subscriptions')` e o filtro corre em memória.
     */
    private function revenueSubscriptionOf(Company|User $billable): ?Subscription
    {
        if ($billable->relationLoaded('subscriptions')) {
            /** @var Subscription|null $subscription */
            $subscription = $billable->subscriptions
                ->filter(fn (Subscription $subscription): bool => in_array(
                    $subscription->stripe_status,
                    self::REVENUE_STATUSES,
                    strict: true,
                ))
                ->sortByDesc('created_at')
                ->first();

            return $subscription;
        }

        return $billable->subscriptions()
            ->whereIn('stripe_status', self::REVENUE_STATUSES)
            ->latest('created_at')
            ->first();
    }

    /**
     * Metered é definido pelo tipo do billable, como no checkout: só empresa
     * é cobrada por assento (`BillableTypeEnum::isMetered`).
     *
     * O tipo é resolvido pelo morph map antes da comparação: `Company` está
     * registrada como `company` no CompanyServiceProvider, então comparar a
     * coluna crua com o FQCN do enum falharia em silêncio e jogaria toda
     * empresa para o caminho de preço avulso.
     */
    private function isMetered(Subscription $subscription): bool
    {
        $type = $subscription->subscriptionable_type;
        $class = Relation::getMorphedModel($type) ?? $type;

        return BillableTypeEnum::tryFrom($class)?->isMetered() ?? false;
    }
}

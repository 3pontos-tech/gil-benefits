<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Billing\Core\Support\RevenueResolver;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialPeriod;
use TresPontosTech\PanelAdmin\DTOs\Financial\MonthlyRevenue;

/**
 * Receita de um mês, reconstruída por vigência (FLM-41, D-02).
 *
 * Não existe snapshot histórico: o que se sabe de cada assinatura é quando ela
 * nasceu e, se acabou, quando acabou. Com isso dá para dizer quem estava vigente
 * em cada mês — mas o **preço** aplicado é sempre o de hoje, porque a régua de
 * assento vive em código e a quantidade contratada não guarda histórico.
 *
 * A consequência precisa ser dita na tela: mudanças de plano ou de quantidade no
 * passado são invisíveis aqui. Todo mês anterior ao go-live é estimativa, não
 * extrato.
 *
 * Contrato B2B entra pelo valor que o financeiro preencheu, e só por ele: sem
 * valor, a empresa continua fora do MRR, porque entrar com um palpite seria pior
 * do que não entrar (D-01). A vigência do contrato tem as mesmas datas da
 * assinatura, então o mês reconstruído usa a mesma régua para os dois.
 */
final class RevenueReconstructor
{
    public function __construct(private readonly RevenueResolver $resolver) {}

    /**
     * @param  array<int, string>  $companyIds  Vazio significa todas as empresas.
     */
    public function forPeriod(FinancialPeriod $period, array $companyIds = []): MonthlyRevenue
    {
        $subscriptions = $this->vigentesEm($period, $companyIds);

        $b2bCents = 0;
        $standaloneCents = 0;
        $payingCompanies = [];
        $payingUsers = 0;
        $companiesWithKnownValue = 0;

        foreach ($subscriptions as $subscription) {
            $cents = $this->resolver->forSubscription($subscription);

            if ($this->isCompanySubscription($subscription)) {
                $payingCompanies[$subscription->subscriptionable_id] = true;

                if ($cents !== null) {
                    $b2bCents += $cents;
                    ++$companiesWithKnownValue;
                }

                continue;
            }

            ++$payingUsers;
            $standaloneCents += $cents ?? 0;
        }

        foreach ($this->contratosVigentesEm($period, $companyIds) as $contract) {
            $companyId = (string) $contract->company_id;

            // Assinatura tem precedência: se a empresa já foi contada por uma,
            // o contrato não soma de novo — é a mesma empresa pagando uma vez.
            if (isset($payingCompanies[$companyId])) {
                continue;
            }

            $payingCompanies[$companyId] = true;

            if ($contract->monthly_value_cents !== null) {
                $b2bCents += $contract->monthly_value_cents;
                ++$companiesWithKnownValue;
            }
        }

        return new MonthlyRevenue(
            period: $period,
            b2bCents: $b2bCents,
            standaloneCents: $standaloneCents,
            payingCompanies: count($payingCompanies),
            payingUsers: $payingUsers,
            companiesWithKnownValue: $companiesWithKnownValue,
        );
    }

    /**
     * Contratos vigentes no mês, pela mesma régua das assinaturas: começaram até
     * o fim dele e ou seguem valendo ou terminaram depois do início dele.
     *
     * O status não filtra aqui, como não filtra lá: um contrato encerrado hoje
     * esteve valendo nos meses anteriores, e é isso que o gráfico mostra.
     *
     * @param  array<int, string>  $companyIds
     * @return Collection<int, CompanyPlan>
     */
    private function contratosVigentesEm(FinancialPeriod $period, array $companyIds): Collection
    {
        return CompanyPlan::query()
            ->where(fn (Builder $query) => $query
                ->whereNull('starts_at')
                ->orWhere('starts_at', '<=', $period->end))
            ->where(fn (Builder $query) => $query
                ->whereNull('ends_at')
                ->orWhere('ends_at', '>=', $period->start))
            ->whereNotIn('company_id', $this->bucketCompanyIds())
            ->when(
                $companyIds !== [],
                fn (Builder $query) => $query->whereIn('company_id', $companyIds),
            )
            ->get();
    }

    /**
     * Assinaturas vigentes no mês: nascidas até o fim dele e que ou seguem vivas
     * ou terminaram depois do início dele.
     *
     * O status atual não filtra nada aqui de propósito — uma assinatura cancelada
     * hoje esteve ativa nos meses anteriores ao cancelamento, e é justamente isso
     * que o gráfico de evolução precisa mostrar. Quem delimita é `ends_at`.
     *
     * @param  array<int, string>  $companyIds
     * @return Collection<int, Subscription>
     */
    private function vigentesEm(FinancialPeriod $period, array $companyIds): Collection
    {
        return Subscription::query()
            ->where('created_at', '<=', $period->end)
            ->where(fn (Builder $query) => $query
                ->whereNull('ends_at')
                ->orWhere('ends_at', '>=', $period->start))
            ->whereNot(fn (Builder $query) => $query
                ->where('subscriptionable_type', $this->companyMorphKey())
                ->whereIn('subscriptionable_id', $this->bucketCompanyIds()))
            ->when(
                $companyIds !== [],
                fn (Builder $query) => $query
                    ->where('subscriptionable_type', $this->companyMorphKey())
                    ->whereIn('subscriptionable_id', $companyIds),
            )
            ->with('price')
            ->get();
    }

    private function isCompanySubscription(Subscription $subscription): bool
    {
        return $subscription->subscriptionable_type === $this->companyMorphKey();
    }

    /**
     * Chave de morph da empresa, resolvida pelo mapa em vez de fixada: `Company`
     * está registrada como `company` no CompanyServiceProvider, e comparar com o
     * FQCN classificaria toda empresa como avulso em silêncio.
     */
    private function companyMorphKey(): string
    {
        $alias = array_search(Company::class, Relation::morphMap(), strict: true);

        return $alias === false ? Company::class : $alias;
    }

    /**
     * A empresa-balde não é cliente: as assinaturas dela pertencem a pessoas sem
     * empregador, que já são contadas como avulsos pelo morph de `User`.
     *
     * @return array<int, string>
     */
    private function bucketCompanyIds(): array
    {
        /** @var array<int, string> $ids */
        $ids = Company::query()
            ->where('slug', Company::DEFAULT_SLUG)
            ->pluck('id')
            ->all();

        return $ids === [] ? [''] : $ids;
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Actions;

use App\Models\Users\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use TresPontosTech\Billing\Core\DTOs\QuotaAllowance;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Models\Company;

/**
 * Descobre de qual contrato a cota desta pessoa vem, devolvendo limite, âncora e empresa.
 *
 * A cota tem exatamente duas origens: o plano contratual da empresa e a assinatura
 * do próprio usuário, nesta ordem de precedência. A assinatura de uma empresa nunca
 * gera cota para ninguém — ela define assentos e preço subsidiado.
 */
final readonly class ResolveQuotaAllowance
{
    /**
     * A empresa considerada é a do tenant selecionado na tela, com fallback para
     * `employerCompanyId()` fora de um painel (console, jobs, e-mail). Sem isso a
     * cota poderia vir de uma empresa diferente da que a tela está exibindo.
     *
     * `$companyId` fixa a empresa em vez de deduzi-la do contexto. Quem já tem em mãos
     * a linha que gastou a cota — um agendamento, por exemplo — passa a empresa dela e
     * não fica à mercê do tenant ativo no momento da leitura.
     */
    public function for(User $user, ?string $companyId = null): QuotaAllowance
    {
        $companyId ??= $this->companyIdFor($user);

        $contractualPlan = $this->contractualPlanFor($user, $companyId);

        if ($contractualPlan instanceof CompanyPlan) {
            return new QuotaAllowance(
                $contractualPlan->monthly_appointments_per_employee,
                CarbonImmutable::instance($contractualPlan->starts_at ?? $contractualPlan->created_at),
                $companyId,
            );
        }

        /** @var Subscription|null $subscription */
        $subscription = $user->activeSubscription()->with('price')->first();

        if ($subscription === null) {
            return QuotaAllowance::none();
        }

        $anchor = $subscription->quota_anchor_at ?? $subscription->created_at;

        if ($anchor === null) {
            return QuotaAllowance::none();
        }

        return new QuotaAllowance(
            (int) ($subscription->price->monthly_appointments ?? 0),
            CarbonImmutable::instance($anchor),
            $companyId,
        );
    }

    /**
     * O plano contratual em vigor para esta pessoa, pela mesma regra de empresa que
     * define a cota.
     *
     * Público porque a tela precisa descrever o mesmo plano de onde o número saiu:
     * resolver o plano por outro caminho faz o card exibir limite de uma empresa e
     * saldo de outra, e para quem tem contrato em duas empresas isso vira "2 de 1".
     */
    public function contractualPlanFor(User $user, ?string $companyId = null): ?CompanyPlan
    {
        $companyId ??= $this->companyIdFor($user);

        if ($companyId === null) {
            return null;
        }

        return CompanyPlan::query()->where('company_id', $companyId)->activeOn()->first();
    }

    /**
     * A empresa sob a qual esta pessoa está sendo lida agora.
     *
     * É a mesma expressão que `BookAppointmentAction` grava em `appointments.company_id`,
     * de propósito: o que conta o consumo e o que registra o consumo precisam concordar.
     *
     * Pública porque crédito responde à mesma pergunta e não pode usar o `companyId` da
     * allowance: quem não tem plano recebe `QuotaAllowance::none()`, cuja empresa é nula,
     * e é justamente quem depende de crédito para agendar.
     */
    public function companyIdFor(User $user): ?string
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Company ? $tenant->getKey() : $user->employerCompanyId();
    }
}

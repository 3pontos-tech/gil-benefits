<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Actions;

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Entities\PlanEntity;
use TresPontosTech\Billing\Core\Enums\BenefitAccessEnum;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;
use TresPontosTech\Company\Models\Company;

/**
 * Responde se a pessoa tem o benefício pago nesta empresa. É a régua de quem usa o produto:
 * o painel (RedirectUserIfNotSubscribed) e a API do app perguntam aqui.
 *
 * Só lê o banco, sem efeito colateral: criar o cliente no provedor de pagamento e
 * redirecionar ficam com quem chama.
 */
final readonly class ResolveBenefitAccess
{
    public function __construct(
        private PlanRepository $planRepository,
    ) {}

    /**
     * Nesta ordem: plano contratual da empresa; empresa sem plano e sem assinatura ativa
     * (a padrão não paga, então nunca cai aqui); e então o plano da própria pessoa. Na empresa
     * padrão só vale a assinatura a preço cheio ou o voucher de campanha; nas demais, qualquer
     * assinatura num plano de colaborador.
     */
    public function for(User $user, Company $company): BenefitAccessEnum
    {
        if ($company->hasActivePlan()) {
            return BenefitAccessEnum::ContractualPlan;
        }

        if ($company->subsidizesEmployees() && ! $this->companyHasActiveSubscription($company)) {
            return BenefitAccessEnum::CompanyPlanInactive;
        }

        $hasPersonalPlan = $company->subsidizesEmployees()
            ? $this->isSubscribedToAnEmployeePlan($user)
            : $this->hasStandalonePriceSubscription($user) || $user->hasVoucherAccess();

        return $hasPersonalPlan ? BenefitAccessEnum::PersonalPlan : BenefitAccessEnum::PersonalPlanRequired;
    }

    private function companyHasActiveSubscription(Company $company): bool
    {
        return $company->subscriptions->grantsAnyAccess();
    }

    private function isSubscribedToAnEmployeePlan(User $user): bool
    {
        $employeePlans = $this->planRepository->getPlansFor('user');

        return $employeePlans->contains(
            fn (PlanEntity $plan): bool => $user->subscriptions->grantsAccessTo($plan->slug),
        );
    }

    /**
     * Sem empregador, a assinatura só vale se foi comprada pelo valor cheio: quem entrou por um
     * preço subsidiado não pode ficar na empresa padrão.
     */
    private function hasStandalonePriceSubscription(User $user): bool
    {
        $standalonePriceIds = Price::query()
            ->where('audience', PriceAudienceEnum::Standalone)
            ->pluck('provider_price_id');

        if ($standalonePriceIds->isEmpty()) {
            return false;
        }

        return $user->subscriptions
            ->whereIn('stripe_price', $standalonePriceIds)
            ->grantsAnyAccess();
    }
}

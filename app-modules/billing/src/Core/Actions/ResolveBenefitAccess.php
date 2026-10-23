<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Actions;

use App\Models\Users\User;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Enums\BenefitAccessEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
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
        private BillingManager $billingManager,
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
        return collect(BillingProviderEnum::activeCases())
            ->contains(fn (BillingProviderEnum $provider): bool => $this->billingManager
                ->getDriver($provider)
                ->hasActiveSubscription($company));
    }

    private function isSubscribedToAnEmployeePlan(User $user): bool
    {
        $employeePlans = $this->planRepository->getPlansFor('user');

        return collect(BillingProviderEnum::activeCases())
            ->contains(function (BillingProviderEnum $provider) use ($user, $employeePlans): bool {
                $driver = $this->billingManager->getDriver($provider);

                foreach ($employeePlans as $plan) {
                    if ($driver->isSubscribed($user, $plan->slug)) {
                        return true;
                    }
                }

                return false;
            });
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

        return Subscription::query()
            ->where('subscriptionable_type', $user->getMorphClass())
            ->where('subscriptionable_id', $user->getKey())
            ->where('stripe_status', 'active')
            ->whereIn('stripe_price', $standalonePriceIds)
            ->exists();
    }
}

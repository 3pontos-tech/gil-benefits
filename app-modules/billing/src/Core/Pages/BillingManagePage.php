<?php

namespace TresPontosTech\Billing\Core\Pages;

use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Locked;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Models\Company;

class BillingManagePage extends Page
{
    protected static ?string $slug = 'billing-manage';

    protected static string $layout = 'filament-panels::components.layout.simple';

    protected Width|string|null $maxContentWidth = Width::ExtraLarge;

    protected string $view = 'billing-manage';

    protected static bool $shouldRegisterNavigation = false;

    #[Locked]
    public ?Subscription $subscription = null;

    protected function getViewData(): array
    {
        /** @var Company $company */
        $company = Filament::getTenant();

        $this->subscription = Subscription::query()
            ->where('subscriptionable_type', $company->getMorphClass())
            ->where('subscriptionable_id', $company->getKey())
            ->where('stripe_status', 'active')
            ->with('plan')
            ->latest()
            ->first();

        // Fallback: quando stripe_price não bate com nenhum provider_price_id (dados corrompidos
        // ou legados), o hasOneThrough retorna null. Buscamos o plano ativo pelo provider da company.
        if ($this->subscription && ! $this->subscription->plan) {
            $provider = BillingCustomer::getActiveProvider($company);

            $fallbackPlan = $provider instanceof BillingProviderEnum
                ? Plan::query()
                    ->where('provider', $provider)
                    ->where('type', BillableTypeEnum::Company)
                    ->where('active', true)
                    ->first()
                : null;

            $this->subscription->setRelation('plan', $fallbackPlan);
        }

        return [
            'company' => $company,
            'subscription' => $this->subscription,
            'returnUrl' => Dashboard::getUrl(),
        ];
    }

    public function cancelSubscription(): void
    {
        /** @var Company $company */
        $company = Filament::getTenant();

        if (! $this->subscription instanceof Subscription) {
            Notification::make()->title('Nenhuma assinatura ativa encontrada.')->warning()->send();

            return;
        }

        // plan pode ser null quando stripe_price não bate com nenhum provider_price_id
        $provider = $this->subscription->plan?->provider
            ?? BillingCustomer::getActiveProvider($company)
            ?? BillingProviderEnum::Stripe;

        resolve(BillingManager::class)
            ->getDriver($provider)
            ->cancelSubscription($company);

        Notification::make()->title('Assinatura cancelada com sucesso.')->success()->send();

        $this->redirect(TenantSubscriptionPage::getUrl(tenant: $company));
    }
}

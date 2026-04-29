<?php

namespace TresPontosTech\App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Locked;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;

class UserBillingManagePage extends Page
{
    protected static ?string $slug = 'billing-manage';

    protected static string $layout = 'filament-panels::components.layout.simple';

    protected Width|string|null $maxContentWidth = Width::Container;

    protected string $view = 'user-billing-manage';

    protected static bool $shouldRegisterNavigation = false;

    #[Locked]
    public ?Subscription $subscription = null;

    protected function getViewData(): array
    {
        $user = auth()->user();

        $this->subscription = Subscription::query()
            ->where('subscriptionable_type', $user->getMorphClass())
            ->where('subscriptionable_id', $user->getKey())
            ->where('stripe_status', 'active')
            ->with('price.plan')
            ->latest()
            ->first();

        // Fallback: quando stripe_price não bate com nenhum provider_price_id (dados corrompidos
        // ou legados), o hasOneThrough retorna null. Buscamos o plano ativo pelo provider do usuário.
        if ($this->subscription && ! $this->subscription->price?->plan) {
            $provider = BillingCustomer::getActiveProvider($user);

            $fallbackPlan = $provider instanceof BillingProviderEnum
                ? Plan::query()
                    ->where('provider', $provider)
                    ->where('type', BillableTypeEnum::User)
                    ->where('active', true)
                    ->first()
                : null;

            $this->subscription->price?->setRelation('plan', $fallbackPlan);
        }

        return [
            'user' => $user,
            'subscription' => $this->subscription,
            'returnUrl' => UserDashboard::getUrl(),
        ];
    }

    public function cancelSubscription(): void
    {
        $user = auth()->user();

        if (! $this->subscription instanceof Subscription) {
            Notification::make()->title('Nenhuma assinatura ativa encontrada.')->warning()->send();

            return;
        }

        $provider = $this->subscription->price?->plan?->provider
            ?? BillingCustomer::getActiveProvider($user)
            ?? BillingProviderEnum::Stripe;

        resolve(BillingManager::class)
            ->getDriver($provider)
            ->cancelSubscription($user);

        Notification::make()->title('Assinatura cancelada com sucesso.')->success()->send();

        $this->redirect(UserSubscriptionPage::getUrl());
    }
}

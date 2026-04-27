<?php

namespace TresPontosTech\App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;

class UserBillingManagePage extends Page
{
    protected static ?string $slug = 'billing-manage';

    protected static string $layout = 'filament-panels::components.layout.simple';

    protected Width|string|null $maxContentWidth = Width::Container;

    protected string $view = 'user-billing-manage';

    protected static bool $shouldRegisterNavigation = false;

    protected function getViewData(): array
    {
        $user = auth()->user();

        $subscription = Subscription::query()
            ->where('subscriptionable_type', $user->getMorphClass())
            ->where('subscriptionable_id', $user->getKey())
            ->where('stripe_status', 'active')
            ->with('price.plan')
            ->latest()
            ->first();

        return [
            'user' => $user,
            'subscription' => $subscription,
            'returnUrl' => UserDashboard::getUrl(),
        ];
    }

    public function cancelSubscription(): void
    {
        $user = auth()->user();
        $provider = BillingCustomer::getActiveProvider($user);

        resolve(BillingManager::class)
            ->getDriver($provider)
            ->cancelSubscription($user);

        Notification::make()->title('Assinatura cancelada com sucesso.')->success()->send();

        $this->redirect(UserSubscriptionPage::getUrl());
    }
}

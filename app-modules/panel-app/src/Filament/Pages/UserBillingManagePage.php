<?php

namespace TresPontosTech\App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Locked;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\PanelCompany\Filament\Actions\CancelSubscriptionAction;

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

        return [
            'user' => $user,
            'subscription' => $this->subscription,
            'returnUrl' => UserDashboard::getUrl(),
        ];
    }

    public function cancelSubscription(): Action
    {
        return CancelSubscriptionAction::make()
            ->forBillable(auth()->user(), UserSubscriptionPage::getUrl());
    }
}

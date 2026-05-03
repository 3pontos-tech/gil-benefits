<?php

namespace TresPontosTech\Billing\Core\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Locked;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Filament\Actions\CancelSubscriptionAction;

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

        return [
            'company' => $company,
            'subscription' => $this->subscription,
            'returnUrl' => Dashboard::getUrl(),
        ];
    }

    public function cancelSubscription(): Action
    {
        $company = Filament::getTenant();

        return CancelSubscriptionAction::make()
            ->forBillable($company, TenantSubscriptionPage::getUrl(tenant: $company));
    }
}

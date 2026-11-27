<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\PanelCompany\Filament\Pages\BillingManagePage;

use function Pest\Livewire\livewire;

it('shows the subscription of a company in default, which still has access and may cancel', function (): void {
    actingAsCompanyOwner();
    $subscription = filament()->getTenant()->subscriptions()->sole();
    $subscription->update(['stripe_status' => Subscription::STATUS_DEFAULTER]);

    livewire(BillingManagePage::class)
        ->assertOk()
        ->assertSet('subscription.id', $subscription->id);
});

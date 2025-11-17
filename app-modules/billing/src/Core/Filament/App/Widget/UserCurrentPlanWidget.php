<?php

namespace TresPontosTech\Billing\Core\Filament\App\Widget;

use Filament\Widgets\Widget;

class UserCurrentPlanWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.plans-overview';

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    protected function getViewData(): array
    {
        $subscription = auth()
            ->user()
            ->activeSubscription()
            ->with('price.billingPlan')
            ->first();

        $plan = $subscription?->price?->billingPlan;

        return [
            'planName' => $plan->name,
            'description' => $plan->description,
            'status' => $subscription->ends_at ? 'expired' : ($subscription->stripe_status === 'active' ? 'active' : 'inactive'),
            'isRecurring' => $subscription->price->type === 'recurring',
        ];
    }
}

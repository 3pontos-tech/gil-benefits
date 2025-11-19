<?php

namespace TresPontosTech\User\Filament\App\Widgets;

use Filament\Widgets\Widget;
use TresPontosTech\Appointments\Filament\App\Resources\Appointments\AppointmentResource;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;

class UserCurrentPlanWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.plans-overview';

    protected int|string|array $columnSpan = 4;

    protected function getViewData(): array
    {
        $user = auth()->user();

        /** @var Subscription $subscription */
        $subscription = $user
            ->activeSubscription()
            ->with('price.plan')
            ->first();

        $plan = $subscription?->price?->plan;

        return [
            'planName' => $plan->name,
            'description' => $plan->description,
            'status' => $subscription->ends_at ? 'expired' : ($subscription->stripe_status === 'active' ? 'active' : 'inactive'),
            'features' => json_decode($subscription->price?->metadata, true)['features'] ?? [],
        ];
    }

    public function redirectToAppointmentCreation() {

        return redirect()->intended(AppointmentResource::getUrl('create'));
    }
}

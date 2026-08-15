<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\Contracts\SupportsSubscriptionCancellation;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;

class CancelSubscriptionAction extends Action
{
    public static function make(?string $name = 'cancelSubscription'): static
    {
        return parent::make($name)
            ->label('Cancelar assinatura')
            ->requiresConfirmation();
    }

    public function forBillable(mixed $billable, string $redirectUrl): static
    {
        return $this
            // Hidden, not disabled: on a gateway without a cancellation endpoint
            // there is nothing the user could do to make the button work, so
            // showing it greyed out would only invite a support ticket.
            ->visible(fn ($livewire): bool => $this->driverFor($livewire->subscription ?? null, $billable) instanceof SupportsSubscriptionCancellation)
            ->action(function ($livewire) use ($billable, $redirectUrl): void {
                $driver = $this->driverFor($livewire->subscription ?? null, $billable);

                if (! $driver instanceof SupportsSubscriptionCancellation) {
                    Notification::make()->title('Nenhuma assinatura ativa encontrada.')->warning()->send();

                    return;
                }

                $driver->cancelSubscription($billable);

                Notification::make()->title('Assinatura cancelada com sucesso.')->success()->send();

                $livewire->redirect($redirectUrl);
            });
    }

    /**
     * Resolves the driver that owns this subscription, or null when there is no
     * subscription to cancel. Shared by the visibility check and the action so
     * the two can never disagree about which gateway is in play.
     */
    private function driverFor(mixed $subscription, mixed $billable): ?BillingContract
    {
        if (! $subscription instanceof Subscription) {
            return null;
        }

        $provider = $subscription->price?->plan->provider
            ?? $subscription->plan->provider
            ?? BillingCustomer::getActiveProvider($billable)
            ?? BillingProviderEnum::Barte;

        return resolve(BillingManager::class)->getDriver($provider);
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use TresPontosTech\Billing\Core\BillingManager;
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
        return $this->action(function ($livewire) use ($billable, $redirectUrl): void {
            $subscription = $livewire->subscription;

            if (! $subscription instanceof Subscription) {
                Notification::make()->title('Nenhuma assinatura ativa encontrada.')->warning()->send();

                return;
            }

            $provider = $subscription->price?->plan?->provider
                ?? $subscription->plan?->provider
                ?? BillingCustomer::getActiveProvider($billable)
                ?? BillingProviderEnum::Barte;

            resolve(BillingManager::class)
                ->getDriver($provider)
                ->cancelSubscription($billable);

            Notification::make()->title('Assinatura cancelada com sucesso.')->success()->send();

            $livewire->redirect($redirectUrl);
        });
    }
}

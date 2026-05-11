<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Actions;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Filament\Pages\Tenancy\EditTenantProfile;

class PurchaseCreditsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'purchase_credits';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('panel-company::resources.actions.purchase_credits.label'))
            ->icon(Heroicon::OutlinedCreditCard)
            ->form([
                TextInput::make('quantity')
                    ->label(__('panel-company::resources.actions.purchase_credits.quantity'))
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->required(),
            ])
            ->action(function (array $data, $livewire): void {
                /** @var Company $company */
                $company = filament()->getTenant();

                $url = resolve(BillingManager::class)
                    ->driver()
                    ->purchaseCredits(
                        billable: $company,
                        company: $company,
                        quantity: (int) $data['quantity'],
                        successUrl: EditTenantProfile::getUrl(),
                        cancelUrl: EditTenantProfile::getUrl(),
                    );

                $livewire->redirect($url);
            });
    }

    public function forBillable(Company|User $billable, string $successUrl, string $cancelUrl): static
    {
        return $this->action(function (array $data, $livewire) use ($billable, $successUrl, $cancelUrl): void {
            /** @var Company $company */
            $company = filament()->getTenant();

            $url = resolve(BillingManager::class)
                ->driver()
                ->purchaseCredits(
                    billable: $billable,
                    company: $company,
                    quantity: (int) $data['quantity'],
                    successUrl: $successUrl,
                    cancelUrl: $cancelUrl,
                );

            $livewire->redirect($url);
        });
    }
}

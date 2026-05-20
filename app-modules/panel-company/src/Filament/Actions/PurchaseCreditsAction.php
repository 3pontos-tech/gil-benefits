<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Actions;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Filament\Pages\CompanyCreditPage;

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
            ]);

        $this->forBillable();
    }

    public function forBillable(Company|User|null $billable = null, ?string $successUrl = null, ?string $cancelUrl = null): static
    {
        return $this->action(function (array $data, $livewire) use ($billable, $successUrl, $cancelUrl): void {
            /** @var Company $company */
            $company = filament()->getTenant();

            $resolvedBillable = $billable ?? $company;
            $resolvedSuccessUrl = $successUrl ?? CompanyCreditPage::getUrl();
            $resolvedCancelUrl = $cancelUrl ?? CompanyCreditPage::getUrl();

            $driver = resolve(BillingManager::class)->driver();

            $url = $driver->purchaseCredits(
                billable: $resolvedBillable,
                company: $company,
                quantity: (int) $data['quantity'],
                successUrl: $resolvedSuccessUrl,
                cancelUrl: $resolvedCancelUrl,
            );

            if ($driver->checkoutOpensInNewTab()) {
                $livewire->js("window.open('" . addslashes($url) . "', '_blank')");
            } else {
                $livewire->redirect($url);
            }
        });
    }
}

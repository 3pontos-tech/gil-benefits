<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Actions;

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Enums\CreditOrderStatusEnum;
use TresPontosTech\Credits\Models\CreditOrder;
use TresPontosTech\Credits\Models\UserCredit;

final readonly class StartCreditOrder
{
    public function handle(
        BillingProviderEnum $provider,
        Company|User $billable,
        Company $company,
        int $quantity,
        ?string $checkoutId = null,
    ): CreditOrder {
        return CreditOrder::query()->create([
            'provider' => $provider,
            'checkout_id' => $checkoutId,
            'billable_type' => $billable->getMorphClass(),
            'billable_id' => $billable->getKey(),
            'company_id' => $company->getKey(),
            'quantity' => $quantity,
            'amount_cents' => UserCredit::priceFor($quantity),
            'status' => CreditOrderStatusEnum::Pending,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Actions\Credit;

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\CreditOrderStatusEnum;
use TresPontosTech\Billing\Core\Models\CreditOrder;
use TresPontosTech\Company\Models\Company;

final readonly class StartCreditOrder
{
    public function handle(
        BillingProviderEnum $provider,
        Company|User $billable,
        Company $company,
        int $quantity,
        int $amountCents,
    ): CreditOrder {
        return CreditOrder::query()->create([
            'provider' => $provider,
            'billable_type' => $billable->getMorphClass(),
            'billable_id' => $billable->getKey(),
            'company_id' => $company->getKey(),
            'quantity' => $quantity,
            'amount_cents' => $amountCents,
            'status' => CreditOrderStatusEnum::Pending,
        ]);
    }
}

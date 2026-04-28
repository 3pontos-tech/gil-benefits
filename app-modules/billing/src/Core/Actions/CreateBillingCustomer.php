<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Actions;

use TresPontosTech\Billing\Core\DTOs\CreateBillingCustomerDto;
use TresPontosTech\Billing\Core\Models\BillingCustomer;

final class CreateBillingCustomer
{
    public function handle(CreateBillingCustomerDto $dto): BillingCustomer
    {
        return BillingCustomer::query()->create([
            'billable_type' => $dto->billableType,
            'billable_id' => $dto->billableId,
            'provider' => $dto->provider,
            'provider_customer_id' => $dto->providerCustomerId,
        ]);
    }
}

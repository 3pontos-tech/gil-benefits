<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\DTOs;

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Company\Models\Company;

final readonly class CreateBillingCustomerDto
{
    public function __construct(
        public string $billableType,
        public int|string $billableId,
        public BillingProviderEnum $provider,
        public string $providerCustomerId,
    ) {}

    public static function make(Company|User $billable, BillingProviderEnum $provider, string $providerCustomerId): self
    {
        return new self(
            billableType: $billable->getMorphClass(),
            billableId: $billable->getKey(),
            provider: $provider,
            providerCustomerId: $providerCustomerId,
        );
    }
}

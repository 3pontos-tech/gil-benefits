<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\DTOs;

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Models\UserCredit;

final readonly class CreditOrderDTO
{
    public function __construct(
        public BillingProviderEnum $provider,
        public Company|User $billable,
        public Company $company,
        public int $quantity,
        public ?string $checkoutId = null,
    ) {}

    public function amountCents(): int
    {
        return UserCredit::priceFor($this->quantity);
    }

    public function withCheckout(?string $checkoutId): self
    {
        return new self(
            provider: $this->provider,
            billable: $this->billable,
            company: $this->company,
            quantity: $this->quantity,
            checkoutId: $checkoutId,
        );
    }
}

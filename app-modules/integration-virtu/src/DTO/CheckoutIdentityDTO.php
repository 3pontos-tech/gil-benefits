<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationVirtu\DTO;

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;

/**
 * Buyer identity pushed into the hosted checkout as query params.
 *
 * Virtu has no customer endpoint: the buyer record is created from whatever the
 * checkout form collects. Pre-filling it with data we already hold is what makes
 * the webhook's `customer.cpf` trustworthy as a secondary way to recognise who
 * paid, when the link id alone is not enough.
 *
 * Invalid values are dropped by the gateway without an error, so a missing CPF
 * degrades to an empty field rather than a failed checkout.
 */
final readonly class CheckoutIdentityDTO
{
    public function __construct(
        public string $name,
        public ?string $email,
        public ?string $taxId,
    ) {}

    public static function fromBillable(Company|User $billable): self
    {
        if ($billable instanceof Company) {
            return new self(
                name: $billable->name,
                email: $billable->owner?->email,
                taxId: $billable->tax_id,
            );
        }

        return new self(
            name: $billable->name,
            email: $billable->email,
            // `detail` is a separate record and may not exist yet — the Barte
            // adapter guards on the same relation before creating a buyer.
            taxId: $billable->detail?->tax_id,
        );
    }

    /**
     * @return array<string, string>
     */
    public function toQueryParams(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'cpf' => $this->taxId,
        ], fn (?string $value): bool => filled($value));
    }
}

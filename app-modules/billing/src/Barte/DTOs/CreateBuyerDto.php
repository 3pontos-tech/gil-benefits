<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Barte\DTOs;

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;

readonly class CreateBuyerDto
{
    public function __construct(
        public string $documentNumber,
        public string $documentType,
        public string $name,
        public string $email,
        public string $documentNation = 'BR',
    ) {}

    public static function fromBillable(Company|User $billable): self
    {
        return new self(
            documentNumber: $billable instanceof Company ? $billable->tax_id : $billable->detail->tax_id,
            documentType: $billable instanceof Company ? 'cnpj' : 'cpf',
            name: $billable->name,
            email: $billable->email ?? $billable->owner->email,
        );
    }

    public function toArray(): array
    {
        return [
            'document' => [
                'documentNumber' => $this->documentNumber,
                'documentType' => $this->documentType,
                'documentNation' => $this->documentNation,
            ],
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}

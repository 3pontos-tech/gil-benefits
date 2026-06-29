<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Actions\Credit;

use TresPontosTech\Billing\Core\DTOs\CreditDTO;

class PurchaseCredits
{
    public function __construct(private readonly IssueCredits $issueCredits) {}

    public function handle(CreditDTO $dto): void
    {
        $this->issueCredits->handle($dto);
    }
}

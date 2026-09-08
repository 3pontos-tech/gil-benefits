<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\DTOs;

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;

final readonly class GrantCreditDTO
{
    public function __construct(
        public string $adminUserId,
        public Company $company,
        public int $quantity,
        public string $justification,
        public ?User $targetUser = null,
    ) {}
}

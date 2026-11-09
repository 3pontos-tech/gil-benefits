<?php

declare(strict_types=1);

namespace TresPontosTech\Company\Events;

use App\Models\Users\User;
use Illuminate\Foundation\Events\Dispatchable;
use TresPontosTech\Company\Models\Company;

/**
 * The company's responsible person changed: companies.user_id now points at
 * $newOwner. Consumers that key anything off the responsible (credit pools,
 * billing contacts) react here, never to a raw column change.
 */
final class CompanyOwnershipTransferred
{
    use Dispatchable;

    public function __construct(
        public readonly Company $company,
        public readonly User $previousOwner,
        public readonly User $newOwner,
    ) {}
}

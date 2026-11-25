<?php

declare(strict_types=1);

namespace TresPontosTech\User\Events;

use App\Models\Users\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A person who already had an account and joined a company through the import, linked or
 * reactivated. Separate from UserImported because no temporary password was created for them.
 */
final readonly class ExistingUserImported
{
    use Dispatchable;

    public function __construct(
        public User $user,
    ) {}
}

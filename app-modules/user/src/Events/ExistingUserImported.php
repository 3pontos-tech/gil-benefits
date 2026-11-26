<?php

declare(strict_types=1);

namespace TresPontosTech\User\Events;

use App\Models\Users\User;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class ExistingUserImported
{
    use Dispatchable;

    public function __construct(
        public User $user,
    ) {}
}

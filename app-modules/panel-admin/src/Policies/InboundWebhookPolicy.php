<?php

namespace TresPontosTech\Admin\Policies;

use App\Models\Users\User;

class InboundWebhookPolicy
{
    /**
     * Create a new policy instance.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}

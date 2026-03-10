<?php

namespace TresPontosTech\Admin\Policies;

use App\Models\Users\User;
use Basement\Webhooks\Models\InboundWebhook;

class InboundWebhookPolicy
{
    /**
     * Create a new policy instance.
     */
    public function viewAny(User $user)
    {
        return $user->isSuperAdmin();
    }
}

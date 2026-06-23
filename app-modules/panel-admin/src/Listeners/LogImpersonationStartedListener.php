<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Listeners;

use STS\FilamentImpersonate\Events\EnterImpersonation;
use TresPontosTech\PanelAdmin\Models\ImpersonationLog;

class LogImpersonationStartedListener
{
    public function handle(EnterImpersonation $event): void
    {
        ImpersonationLog::query()->create([
            'admin_id' => $event->impersonator->getAuthIdentifier(),
            'impersonated_user_id' => $event->impersonated->getAuthIdentifier(),
            'ip_address' => request()->ip(),
            'started_at' => now(),
        ]);
    }
}

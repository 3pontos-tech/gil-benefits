<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Listeners;

use STS\FilamentImpersonate\Events\LeaveImpersonation;
use TresPontosTech\PanelAdmin\Models\ImpersonationLog;

class LogImpersonationEndedListener
{
    public function handle(LeaveImpersonation $event): void
    {
        $log = ImpersonationLog::query()
            ->where('admin_id', $event->impersonator->getAuthIdentifier())
            ->where('impersonated_user_id', $event->impersonated->getAuthIdentifier())
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        if ($log) {
            $log->ended_at = now();
            $log->save();
        }
    }
}

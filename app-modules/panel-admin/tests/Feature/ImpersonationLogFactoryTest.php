<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\PanelAdmin\Models\ImpersonationLog;

it('creates an impersonation log with admin and impersonated user through its factory', function (): void {
    $log = ImpersonationLog::factory()->create();

    $this->assertModelExists($log);

    expect($log->admin)->toBeInstanceOf(User::class)
        ->and($log->impersonatedUser)->toBeInstanceOf(User::class)
        ->and($log->started_at)->not->toBeNull();
});

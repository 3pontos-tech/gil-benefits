<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Support\Actions\CreateSupportTicketAction;
use TresPontosTech\Support\DTOs\CreateSupportTicketDTO;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Models\SupportTicket;

beforeEach(function (): void {
    Date::setTestNow('2026-06-11 12:00:00');
    Mail::fake();
});

afterEach(function (): void {
    Date::setTestNow();
    SupportTicket::clearBootedModels();
});

function dto(string $subject = 'subject'): CreateSupportTicketDTO
{
    return new CreateSupportTicketDTO(
        category: SupportTicketCategoryEnum::Bug,
        subject: $subject,
        description: 'description',
        visitorName: 'Visitor',
        visitorEmail: 'visitor@example.com',
        environment: 'testing',
    );
}

it('creates tickets with sequential global protocols', function (): void {
    $action = resolve(CreateSupportTicketAction::class);

    $first = $action->execute(dto('first'));
    $second = $action->execute(dto('second'));

    expect($first->protocol)->toBe('SUP-2026-0001')
        ->and($second->protocol)->toBe('SUP-2026-0002');
});

it('does not collide with an existing protocol hidden by the tenant scope (regression)', function (): void {
    $action = resolve(CreateSupportTicketAction::class);

    // First ticket exists globally (e.g. opened by a guest).
    $action->execute(dto('guest'));

    // Tenant scope now hides every row from this context.
    SupportTicket::addGlobalScope('company_tenancy', fn ($query) => $query->whereRaw('1 = 0'));

    // Must still take the next global number instead of colliding on SUP-2026-0001.
    $next = $action->execute(dto('company'));

    expect($next->protocol)->toBe('SUP-2026-0002');
});

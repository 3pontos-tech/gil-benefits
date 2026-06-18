<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use TresPontosTech\Support\Actions\TransitionSupportTicketStatusAction;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Exceptions\InvalidTransitionException;
use TresPontosTech\Support\Mail\SupportTicketStatusUpdatedMail;
use TresPontosTech\Support\Models\SupportTicket;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    Mail::fake();
});

function ticketWithStatus(SupportTicketStatusEnum $status, ?string $email = 'requester@example.com'): SupportTicket
{
    return SupportTicket::query()->create([
        'protocol' => 'SUP-2026-0001',
        'visitor_name' => 'Requester',
        'visitor_email' => $email,
        'category' => SupportTicketCategoryEnum::Bug,
        'subject' => 'subject',
        'description' => 'description',
        'status' => $status,
        'environment' => 'testing',
    ]);
}

// --- graph guard on the enum ---

it('exposes the allowed transition graph', function (): void {
    expect(SupportTicketStatusEnum::Pending->allowedTransitions())
        ->toBe([SupportTicketStatusEnum::InProgress, SupportTicketStatusEnum::Closed])
        ->and(SupportTicketStatusEnum::InProgress->allowedTransitions())
        ->toBe([SupportTicketStatusEnum::Resolved, SupportTicketStatusEnum::Closed])
        ->and(SupportTicketStatusEnum::Resolved->allowedTransitions())
        ->toBe([SupportTicketStatusEnum::InProgress, SupportTicketStatusEnum::Closed])
        ->and(SupportTicketStatusEnum::Closed->allowedTransitions())
        ->toBe([]);
});

it('answers canTransitionTo for valid and invalid edges', function (): void {
    expect(SupportTicketStatusEnum::Pending->canTransitionTo(SupportTicketStatusEnum::InProgress))->toBeTrue()
        ->and(SupportTicketStatusEnum::InProgress->canTransitionTo(SupportTicketStatusEnum::Resolved))->toBeTrue()
        ->and(SupportTicketStatusEnum::Pending->canTransitionTo(SupportTicketStatusEnum::Resolved))->toBeFalse()
        ->and(SupportTicketStatusEnum::Resolved->canTransitionTo(SupportTicketStatusEnum::InProgress))->toBeTrue()
        ->and(SupportTicketStatusEnum::Closed->canTransitionTo(SupportTicketStatusEnum::InProgress))->toBeFalse();
});

// --- the action ---

it('transitions a valid edge and persists the new status', function (): void {
    $ticket = ticketWithStatus(SupportTicketStatusEnum::Pending);

    resolve(TransitionSupportTicketStatusAction::class)->execute($ticket, SupportTicketStatusEnum::InProgress);

    assertDatabaseHas(SupportTicket::class, [
        'id' => $ticket->getKey(),
        'status' => SupportTicketStatusEnum::InProgress->value,
    ]);
});

it('rejects an invalid transition', function (): void {
    $ticket = ticketWithStatus(SupportTicketStatusEnum::Pending);

    expect(fn () => resolve(TransitionSupportTicketStatusAction::class)
        ->execute($ticket, SupportTicketStatusEnum::Resolved))
        ->toThrow(InvalidTransitionException::class);

    expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::Pending);
    Mail::assertNothingQueued();
});

it('notifies the requester when resolved', function (): void {
    $ticket = ticketWithStatus(SupportTicketStatusEnum::InProgress);

    resolve(TransitionSupportTicketStatusAction::class)->execute($ticket, SupportTicketStatusEnum::Resolved);

    Mail::assertQueued(
        SupportTicketStatusUpdatedMail::class,
        fn (SupportTicketStatusUpdatedMail $mail): bool => $mail->hasTo('requester@example.com'),
    );
});

it('notifies the requester when moved to in progress', function (): void {
    $ticket = ticketWithStatus(SupportTicketStatusEnum::Pending);

    resolve(TransitionSupportTicketStatusAction::class)->execute($ticket, SupportTicketStatusEnum::InProgress);

    Mail::assertQueued(
        SupportTicketStatusUpdatedMail::class,
        fn (SupportTicketStatusUpdatedMail $mail): bool => $mail->hasTo('requester@example.com'),
    );
});

it('notifies the requester when closed', function (): void {
    $ticket = ticketWithStatus(SupportTicketStatusEnum::Resolved);

    resolve(TransitionSupportTicketStatusAction::class)->execute($ticket, SupportTicketStatusEnum::Closed);

    Mail::assertQueued(SupportTicketStatusUpdatedMail::class);
});

it('reopens a resolved ticket back into progress', function (): void {
    $ticket = ticketWithStatus(SupportTicketStatusEnum::Resolved);

    resolve(TransitionSupportTicketStatusAction::class)->execute($ticket, SupportTicketStatusEnum::InProgress);

    expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::InProgress);
});

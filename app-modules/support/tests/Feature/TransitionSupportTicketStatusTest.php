<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Support\Actions\TransitionSupportTicketStatusAction;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketChannelEnum;
use TresPontosTech\Support\Enums\TicketStatusChangeSourceEnum;
use TresPontosTech\Support\Events\SupportTicketStatusChanged;
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
        'channel' => TicketChannelEnum::PanelApp,
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

    resolve(TransitionSupportTicketStatusAction::class)->execute($ticket, SupportTicketStatusEnum::InProgress, TicketStatusChangeSourceEnum::Admin);

    assertDatabaseHas(SupportTicket::class, [
        'id' => $ticket->getKey(),
        'status' => SupportTicketStatusEnum::InProgress->value,
    ]);
});

it('rejects an invalid transition', function (): void {
    $ticket = ticketWithStatus(SupportTicketStatusEnum::Pending);

    expect(fn () => resolve(TransitionSupportTicketStatusAction::class)
        ->execute($ticket, SupportTicketStatusEnum::Resolved, TicketStatusChangeSourceEnum::Admin))
        ->toThrow(InvalidTransitionException::class);

    expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::Pending);
    Mail::assertNothingQueued();
});

it('notifies the requester when resolved', function (): void {
    $ticket = ticketWithStatus(SupportTicketStatusEnum::InProgress);

    resolve(TransitionSupportTicketStatusAction::class)->execute($ticket, SupportTicketStatusEnum::Resolved, TicketStatusChangeSourceEnum::Admin);

    Mail::assertQueued(
        SupportTicketStatusUpdatedMail::class,
        fn (SupportTicketStatusUpdatedMail $mail): bool => $mail->hasTo('requester@example.com'),
    );
});

it('notifies the requester when moved to in progress', function (): void {
    $ticket = ticketWithStatus(SupportTicketStatusEnum::Pending);

    resolve(TransitionSupportTicketStatusAction::class)->execute($ticket, SupportTicketStatusEnum::InProgress, TicketStatusChangeSourceEnum::Admin);

    Mail::assertQueued(
        SupportTicketStatusUpdatedMail::class,
        fn (SupportTicketStatusUpdatedMail $mail): bool => $mail->hasTo('requester@example.com'),
    );
});

it('notifies the requester when closed', function (): void {
    $ticket = ticketWithStatus(SupportTicketStatusEnum::Resolved);

    resolve(TransitionSupportTicketStatusAction::class)->execute($ticket, SupportTicketStatusEnum::Closed, TicketStatusChangeSourceEnum::Admin);

    Mail::assertQueued(SupportTicketStatusUpdatedMail::class);
});

it('reopens a resolved ticket back into progress', function (): void {
    $ticket = ticketWithStatus(SupportTicketStatusEnum::Resolved);

    resolve(TransitionSupportTicketStatusAction::class)->execute($ticket, SupportTicketStatusEnum::InProgress, TicketStatusChangeSourceEnum::Admin);

    expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::InProgress);
});

it('fires SupportTicketStatusChanged with the old and new status', function (): void {
    Event::fake([SupportTicketStatusChanged::class]);
    $ticket = ticketWithStatus(SupportTicketStatusEnum::Pending);

    resolve(TransitionSupportTicketStatusAction::class)->execute($ticket, SupportTicketStatusEnum::InProgress, TicketStatusChangeSourceEnum::Admin);

    Event::assertDispatched(
        SupportTicketStatusChanged::class,
        fn (SupportTicketStatusChanged $event): bool => $event->ticket->is($ticket)
            && $event->from === SupportTicketStatusEnum::Pending
            && $event->to === SupportTicketStatusEnum::InProgress,
    );
});

it('records the history row even when no listener of the event runs', function (): void {
    Event::fake([SupportTicketStatusChanged::class]);
    $ticket = ticketWithStatus(SupportTicketStatusEnum::Pending);

    resolve(TransitionSupportTicketStatusAction::class)->execute($ticket, SupportTicketStatusEnum::InProgress, TicketStatusChangeSourceEnum::Admin);

    expect($ticket->statusChanges()->sole())
        ->from_status->toBe(SupportTicketStatusEnum::Pending)
        ->to_status->toBe(SupportTicketStatusEnum::InProgress);
});

it('records the transition in the status history with who made it', function (): void {
    $admin = User::factory()->create();
    $ticket = ticketWithStatus(SupportTicketStatusEnum::Resolved);

    resolve(TransitionSupportTicketStatusAction::class)->execute(
        $ticket,
        SupportTicketStatusEnum::InProgress,
        TicketStatusChangeSourceEnum::Admin,
        $admin,
        'The requester says the problem came back.',
    );

    expect($ticket->statusChanges()->sole())
        ->from_status->toBe(SupportTicketStatusEnum::Resolved)
        ->to_status->toBe(SupportTicketStatusEnum::InProgress)
        ->source->toBe(TicketStatusChangeSourceEnum::Admin)
        ->actor_user_id->toBe($admin->id)
        ->reason->toBe('The requester says the problem came back.');
});

it('records nothing when the transition is rejected', function (): void {
    $ticket = ticketWithStatus(SupportTicketStatusEnum::Closed);

    expect(fn () => resolve(TransitionSupportTicketStatusAction::class)
        ->execute($ticket, SupportTicketStatusEnum::InProgress, TicketStatusChangeSourceEnum::Admin))
        ->toThrow(InvalidTransitionException::class);

    expect($ticket->statusChanges()->exists())->toBeFalse();
});

function staleCopy(SupportTicket $ticket, SupportTicketStatusEnum $movedTo): SupportTicket
{
    $stale = SupportTicket::query()->findOrFail($ticket->id);
    $ticket->update(['status' => $movedTo]);

    return $stale;
}

describe('concurrent changes', function (): void {
    it('guards on the stored status, not on the stale instance the caller holds', function (): void {
        $ticket = ticketWithStatus(SupportTicketStatusEnum::Resolved);
        $stale = staleCopy($ticket, SupportTicketStatusEnum::Closed);

        expect(fn () => resolve(TransitionSupportTicketStatusAction::class)
            ->execute($stale, SupportTicketStatusEnum::InProgress, TicketStatusChangeSourceEnum::Chatx))
            ->toThrow(InvalidTransitionException::class);

        expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::Closed)
            ->and($ticket->statusChanges()->exists())->toBeFalse();
    });

    it('records the stored status as where the ticket came from', function (): void {
        $ticket = ticketWithStatus(SupportTicketStatusEnum::Resolved);
        $stale = staleCopy($ticket, SupportTicketStatusEnum::InProgress);

        resolve(TransitionSupportTicketStatusAction::class)
            ->execute($stale, SupportTicketStatusEnum::Closed, TicketStatusChangeSourceEnum::Admin);

        expect($ticket->statusChanges()->sole()->from_status)->toBe(SupportTicketStatusEnum::InProgress)
            ->and($stale->status)->toBe(SupportTicketStatusEnum::Closed);
    });

    it('refuses a move the graph allows when the ticket left the expected status', function (): void {
        $ticket = ticketWithStatus(SupportTicketStatusEnum::Resolved);
        $stale = staleCopy($ticket, SupportTicketStatusEnum::InProgress);

        expect(fn () => resolve(TransitionSupportTicketStatusAction::class)->execute(
            $stale,
            SupportTicketStatusEnum::Closed,
            TicketStatusChangeSourceEnum::Chatx,
            expectedFrom: SupportTicketStatusEnum::Resolved,
        ))->toThrow(InvalidTransitionException::class);

        expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::InProgress)
            ->and($ticket->statusChanges()->exists())->toBeFalse();
    });
});

it('queues the requester e-mail even when a listener of the change fails', function (): void {
    $ticket = ticketWithStatus(SupportTicketStatusEnum::InProgress);

    Event::listen(SupportTicketStatusChanged::class, function (): never {
        throw new RuntimeException('listener down');
    });

    expect(fn () => resolve(TransitionSupportTicketStatusAction::class)
        ->execute($ticket, SupportTicketStatusEnum::Resolved, TicketStatusChangeSourceEnum::Admin))
        ->toThrow(RuntimeException::class, 'listener down');

    Mail::assertQueued(SupportTicketStatusUpdatedMail::class, 1);
});

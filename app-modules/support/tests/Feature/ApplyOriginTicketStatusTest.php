<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketOriginSourceEnum;
use TresPontosTech\Support\Enums\TicketStatusChangeSourceEnum;
use TresPontosTech\Support\Events\OriginTicketStatusRequested;
use TresPontosTech\Support\Listeners\ApplyOriginTicketStatus;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\TicketOrigin;

beforeEach(function (): void {
    Mail::fake();
    Bus::fake();
});

function ticketFromOrigin(SupportTicketStatusEnum $status, string $reference = 'conv-1'): SupportTicket
{
    $ticket = SupportTicket::factory()->create([
        'user_id' => User::factory(),
        'status' => $status,
    ]);

    TicketOrigin::factory()->create([
        'support_ticket_id' => $ticket->id,
        'external_reference' => $reference,
    ]);

    return $ticket;
}

function originAnswer(SupportTicketStatusEnum $status, ?string $message = null, string $reference = 'conv-1'): OriginTicketStatusRequested
{
    return new OriginTicketStatusRequested(TicketOriginSourceEnum::Chatx, $reference, $status, $message);
}

it('reopens a resolved ticket on behalf of its requester, keeping the message as the reason', function (): void {
    $ticket = ticketFromOrigin(SupportTicketStatusEnum::Resolved);

    resolve(ApplyOriginTicketStatus::class)->handle(originAnswer(SupportTicketStatusEnum::InProgress, 'Still failing.'));

    expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::InProgress)
        ->and($ticket->statusChanges()->sole())
        ->from_status->toBe(SupportTicketStatusEnum::Resolved)
        ->to_status->toBe(SupportTicketStatusEnum::InProgress)
        ->source->toBe(TicketStatusChangeSourceEnum::Chatx)
        ->actor_user_id->toBe($ticket->user_id)
        ->reason->toBe('Still failing.');
});

it('closes a resolved ticket without a reason', function (): void {
    $ticket = ticketFromOrigin(SupportTicketStatusEnum::Resolved);

    resolve(ApplyOriginTicketStatus::class)->handle(originAnswer(SupportTicketStatusEnum::Closed, 'ignored'));

    expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::Closed)
        ->and($ticket->statusChanges()->sole())
        ->source->toBe(TicketStatusChangeSourceEnum::Chatx)
        ->reason->toBeNull();
});

it('keeps the requester as the actor after their account was deleted', function (): void {
    $ticket = ticketFromOrigin(SupportTicketStatusEnum::Resolved);
    $ticket->user->delete();

    resolve(ApplyOriginTicketStatus::class)->handle(originAnswer(SupportTicketStatusEnum::Closed));

    expect($ticket->statusChanges()->sole()->actor_user_id)->toBe($ticket->user_id);
});

it('leaves a ticket that is no longer resolved alone', function (SupportTicketStatusEnum $status, SupportTicketStatusEnum $requested): void {
    $ticket = ticketFromOrigin($status);

    resolve(ApplyOriginTicketStatus::class)->handle(originAnswer($requested));

    expect($ticket->refresh()->status)->toBe($status)
        ->and($ticket->statusChanges()->count())->toBe(0);
})->with([
    'reopen on pending' => [SupportTicketStatusEnum::Pending, SupportTicketStatusEnum::InProgress],
    'reopen on closed' => [SupportTicketStatusEnum::Closed, SupportTicketStatusEnum::InProgress],
    'close on in progress' => [SupportTicketStatusEnum::InProgress, SupportTicketStatusEnum::Closed],
]);

it('ignores an unknown origin reference', function (): void {
    $ticket = ticketFromOrigin(SupportTicketStatusEnum::Resolved);

    resolve(ApplyOriginTicketStatus::class)->handle(originAnswer(SupportTicketStatusEnum::Closed, reference: 'unknown'));

    expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::Resolved);
});

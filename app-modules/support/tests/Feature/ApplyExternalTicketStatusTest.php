<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationChannelEnum;
use TresPontosTech\Support\Enums\TicketDestinationStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;
use TresPontosTech\Support\Events\ExternalTicketStatusChanged;
use TresPontosTech\Support\Listeners\ApplyExternalTicketStatus;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\TicketDestination;

beforeEach(function (): void {
    Mail::fake();
    // The applied transition fires SupportTicketStatusChanged, whose Monday
    // listener queues an outbound push — irrelevant here and not to be executed.
    Bus::fake();
});

function ticketWithDestination(SupportTicketStatusEnum $status, string $reference = 'monday-1'): SupportTicket
{
    $ticket = SupportTicket::query()->create([
        'protocol' => 'SUP-2026-0001',
        'category' => SupportTicketCategoryEnum::Bug,
        'subject' => 'subject',
        'description' => 'description',
        'status' => $status,
        'environment' => 'testing',
    ]);

    TicketDestination::query()->create([
        'support_ticket_id' => $ticket->id,
        'type' => TicketDestinationTypeEnum::Monday,
        'channel' => TicketDestinationChannelEnum::SupportTi,
        'reference_id' => $reference,
        'status' => TicketDestinationStatusEnum::Sent,
    ]);

    return $ticket;
}

function reported(SupportTicketStatusEnum $status, string $reference = 'monday-1'): ExternalTicketStatusChanged
{
    return new ExternalTicketStatusChanged(TicketDestinationTypeEnum::Monday, $reference, $status);
}

it('applies a valid externally-reported transition', function (): void {
    $ticket = ticketWithDestination(SupportTicketStatusEnum::Pending);

    resolve(ApplyExternalTicketStatus::class)->handle(reported(SupportTicketStatusEnum::InProgress));

    expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::InProgress);
});

it('ignores a transition the state machine forbids', function (): void {
    $ticket = ticketWithDestination(SupportTicketStatusEnum::Closed);

    resolve(ApplyExternalTicketStatus::class)->handle(reported(SupportTicketStatusEnum::InProgress));

    expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::Closed);
});

it('is a no-op when already in the reported status (echo)', function (): void {
    $ticket = ticketWithDestination(SupportTicketStatusEnum::InProgress);

    resolve(ApplyExternalTicketStatus::class)->handle(reported(SupportTicketStatusEnum::InProgress));

    expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::InProgress);
});

it('ignores an unknown destination reference', function (): void {
    $ticket = ticketWithDestination(SupportTicketStatusEnum::Pending);

    resolve(ApplyExternalTicketStatus::class)->handle(reported(SupportTicketStatusEnum::InProgress, 'does-not-exist'));

    expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::Pending);
});

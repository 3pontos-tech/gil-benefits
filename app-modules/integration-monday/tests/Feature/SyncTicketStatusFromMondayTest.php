<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\IntegrationMonday\Events\MondayItemColumnChanged;
use TresPontosTech\IntegrationMonday\Jobs\SyncMondayCardStatusJob;
use TresPontosTech\IntegrationMonday\Listeners\SyncTicketStatusFromMonday;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationChannelEnum;
use TresPontosTech\Support\Enums\TicketDestinationStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\TicketDestination;

beforeEach(function (): void {
    config([
        'monday.columns' => ['status' => 'status'],
        'monday.status_indexes' => ['pending' => 17, 'in_progress' => 0, 'resolved' => 2, 'closed' => 3],
    ]);
    Mail::fake();
});

function ticketWithCard(SupportTicketStatusEnum $status, string $itemId = '987654'): SupportTicket
{
    $ticket = SupportTicket::query()->create([
        'protocol' => 'SUP-2026-0001',
        'category' => SupportTicketCategoryEnum::Bug,
        'subject' => 'Subject',
        'description' => 'description',
        'status' => $status,
        'visitor_email' => 'jane@example.com',
        'environment' => 'testing',
    ]);

    TicketDestination::query()->create([
        'support_ticket_id' => $ticket->id,
        'type' => TicketDestinationTypeEnum::Monday,
        'channel' => TicketDestinationChannelEnum::SupportTi,
        'reference_id' => $itemId,
        'status' => TicketDestinationStatusEnum::Sent,
    ]);

    return $ticket;
}

function handleMondayEvent(MondayItemColumnChanged $event): void
{
    resolve(SyncTicketStatusFromMonday::class)->handle($event);
}

it('applies a valid status transition coming from Monday', function (): void {
    $ticket = ticketWithCard(SupportTicketStatusEnum::Pending);

    handleMondayEvent(new MondayItemColumnChanged('111', '987654', 'status', 0));

    expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::InProgress);
});

it('does not push the change back to Monday (loop guard)', function (): void {
    Bus::fake();
    $ticket = ticketWithCard(SupportTicketStatusEnum::Pending);

    handleMondayEvent(new MondayItemColumnChanged('111', '987654', 'status', 0));

    expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::InProgress);
    Bus::assertNotDispatched(SyncMondayCardStatusJob::class);
});

it('ignores a transition the state machine forbids', function (): void {
    $ticket = ticketWithCard(SupportTicketStatusEnum::Closed);

    handleMondayEvent(new MondayItemColumnChanged('111', '987654', 'status', 0));

    expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::Closed);
});

it('ignores events for an unknown item', function (): void {
    $ticket = ticketWithCard(SupportTicketStatusEnum::Pending);

    handleMondayEvent(new MondayItemColumnChanged('111', 'does-not-exist', 'status', 0));

    expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::Pending);
});

it('ignores changes on columns other than status', function (): void {
    $ticket = ticketWithCard(SupportTicketStatusEnum::Pending);

    handleMondayEvent(new MondayItemColumnChanged('111', '987654', 'text_protocol', 0));

    expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::Pending);
});

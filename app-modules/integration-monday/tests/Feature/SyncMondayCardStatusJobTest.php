<?php

declare(strict_types=1);

use TresPontosTech\IntegrationMonday\Jobs\SyncMondayCardStatusJob;
use TresPontosTech\IntegrationMonday\MondayClient;
use TresPontosTech\IntegrationMonday\Testing\FakeMondayClient;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationChannelEnum;
use TresPontosTech\Support\Enums\TicketDestinationStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\TicketDestination;

beforeEach(function (): void {
    config([
        'monday.board_id' => '111',
        'monday.columns' => ['status' => 'status', 'updated_at' => 'date_updated'],
        'monday.status_indexes' => ['pending' => 17, 'in_progress' => 0, 'resolved' => 2, 'closed' => 3],
    ]);
    $this->app->instance(MondayClient::class, $this->fake = new FakeMondayClient);
});

function ticketWithMondayCard(string $itemId = '987654'): SupportTicket
{
    $ticket = SupportTicket::query()->create([
        'protocol' => 'SUP-2026-0001',
        'category' => SupportTicketCategoryEnum::Bug,
        'subject' => 'Subject',
        'description' => 'description',
        'status' => SupportTicketStatusEnum::InProgress,
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

it('pushes the status to the ticket Monday card', function (): void {
    $ticket = ticketWithMondayCard('987654');

    (new SyncMondayCardStatusJob($ticket->id, SupportTicketStatusEnum::Resolved))->handle();

    expect($this->fake->columnValueChanges)->toHaveCount(1);

    $change = $this->fake->columnValueChanges[0];

    expect($change['itemId'])->toBe('987654')
        ->and($change['columnValues']['status'])->toBe(['index' => 2])
        ->and($change['columnValues']['date_updated'])->toHaveKey('date');
});

it('is a no-op when the ticket has no Monday card', function (): void {
    $ticket = SupportTicket::query()->create([
        'protocol' => 'SUP-2026-0002',
        'category' => SupportTicketCategoryEnum::Bug,
        'subject' => 'Subject',
        'description' => 'description',
        'status' => SupportTicketStatusEnum::InProgress,
        'environment' => 'testing',
    ]);

    (new SyncMondayCardStatusJob($ticket->id, SupportTicketStatusEnum::Resolved))->handle();

    expect($this->fake->columnValueChanges)->toHaveCount(0);
});

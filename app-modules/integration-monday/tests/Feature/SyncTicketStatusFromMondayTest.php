<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use TresPontosTech\IntegrationMonday\Events\MondayItemColumnChanged;
use TresPontosTech\IntegrationMonday\Listeners\SyncTicketStatusFromMonday;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;
use TresPontosTech\Support\Events\ExternalTicketStatusChanged;

beforeEach(function (): void {
    config([
        'monday.columns' => ['status' => 'status'],
        'monday.status_indexes' => ['pending' => 17, 'in_progress' => 0, 'resolved' => 2, 'closed' => 3],
    ]);
    Event::fake([ExternalTicketStatusChanged::class]);
});

function handleMondayEvent(MondayItemColumnChanged $event): void
{
    resolve(SyncTicketStatusFromMonday::class)->handle($event);
}

it('reports the mapped status by destination reference', function (): void {
    handleMondayEvent(new MondayItemColumnChanged('111', '987654', 'status', 0));

    Event::assertDispatched(
        ExternalTicketStatusChanged::class,
        fn (ExternalTicketStatusChanged $event): bool => $event->type === TicketDestinationTypeEnum::Monday
            && $event->reference === '987654'
            && $event->status === SupportTicketStatusEnum::InProgress,
    );
});

it('ignores changes on columns other than status', function (): void {
    handleMondayEvent(new MondayItemColumnChanged('111', '987654', 'text_protocol', 0));

    Event::assertNotDispatched(ExternalTicketStatusChanged::class);
});

it('ignores an unmapped status index', function (): void {
    handleMondayEvent(new MondayItemColumnChanged('111', '987654', 'status', 99));

    Event::assertNotDispatched(ExternalTicketStatusChanged::class);
});

it('ignores an event without an index', function (): void {
    handleMondayEvent(new MondayItemColumnChanged('111', '987654', 'status', null));

    Event::assertNotDispatched(ExternalTicketStatusChanged::class);
});

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use TresPontosTech\IntegrationMonday\Jobs\SyncMondayCardStatusJob;
use TresPontosTech\IntegrationMonday\Listeners\PushTicketStatusToMonday;
use TresPontosTech\IntegrationMonday\Support\MondaySyncContext;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Events\SupportTicketStatusChanged;
use TresPontosTech\Support\Models\SupportTicket;

beforeEach(function (): void {
    Bus::fake();
});

function statusChangedEvent(): SupportTicketStatusChanged
{
    $ticket = SupportTicket::query()->create([
        'protocol' => 'SUP-2026-0001',
        'category' => SupportTicketCategoryEnum::Bug,
        'subject' => 'Subject',
        'description' => 'description',
        'status' => SupportTicketStatusEnum::InProgress,
        'environment' => 'testing',
    ]);

    return new SupportTicketStatusChanged($ticket, SupportTicketStatusEnum::Pending, SupportTicketStatusEnum::InProgress);
}

it('queues a card status push for an app-side change', function (): void {
    $event = statusChangedEvent();

    resolve(PushTicketStatusToMonday::class)->handle($event);

    Bus::assertDispatched(
        SyncMondayCardStatusJob::class,
        fn (SyncMondayCardStatusJob $job): bool => $job->ticketId === $event->ticket->id
            && $job->status === SupportTicketStatusEnum::InProgress,
    );
});

it('does not push when muted (change originated from Monday)', function (): void {
    $event = statusChangedEvent();

    MondaySyncContext::mute(fn () => resolve(PushTicketStatusToMonday::class)->handle($event));

    Bus::assertNotDispatched(SyncMondayCardStatusJob::class);
});

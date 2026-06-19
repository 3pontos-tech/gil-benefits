<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use TresPontosTech\IntegrationMonday\Jobs\SyncMondayCardStatusJob;
use TresPontosTech\IntegrationMonday\Support\MondaySyncContext;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;

beforeEach(function (): void {
    Bus::fake();
});

function freshTicket(): SupportTicket
{
    return SupportTicket::query()->create([
        'protocol' => 'SUP-2026-0001',
        'category' => SupportTicketCategoryEnum::Bug,
        'subject' => 'Subject',
        'description' => 'description',
        'status' => SupportTicketStatusEnum::Pending,
        'environment' => 'testing',
    ]);
}

it('queues a card status push when the ticket status changes', function (): void {
    $ticket = freshTicket();

    $ticket->update(['status' => SupportTicketStatusEnum::InProgress]);

    Bus::assertDispatched(
        SyncMondayCardStatusJob::class,
        fn (SyncMondayCardStatusJob $job): bool => $job->ticketId === $ticket->id
            && $job->status === SupportTicketStatusEnum::InProgress,
    );
});

it('does not push when muted', function (): void {
    $ticket = freshTicket();

    MondaySyncContext::mute(fn () => $ticket->update(['status' => SupportTicketStatusEnum::InProgress]));

    Bus::assertNotDispatched(SyncMondayCardStatusJob::class);
});

it('does not push when a non-status attribute changes', function (): void {
    $ticket = freshTicket();

    $ticket->update(['subject' => 'Changed subject']);

    Bus::assertNotDispatched(SyncMondayCardStatusJob::class);
});

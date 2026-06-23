<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use TresPontosTech\IntegrationMonday\Jobs\SyncMondayCardStatusJob;
use TresPontosTech\IntegrationMonday\Listeners\PushTicketStatusToMonday;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Events\SupportTicketStatusChanged;
use TresPontosTech\Support\Models\SupportTicket;

beforeEach(function (): void {
    Bus::fake();
});

it('queues a card status push for a ticket status change', function (): void {
    $ticket = SupportTicket::query()->create([
        'protocol' => 'SUP-2026-0001',
        'category' => SupportTicketCategoryEnum::Bug,
        'subject' => 'Subject',
        'description' => 'description',
        'status' => SupportTicketStatusEnum::InProgress,
        'environment' => 'testing',
    ]);

    $event = new SupportTicketStatusChanged($ticket, SupportTicketStatusEnum::Pending, SupportTicketStatusEnum::InProgress);

    resolve(PushTicketStatusToMonday::class)->handle($event);

    Bus::assertDispatched(
        SyncMondayCardStatusJob::class,
        fn (SyncMondayCardStatusJob $job): bool => $job->ticketId === $ticket->id
            && $job->status === SupportTicketStatusEnum::InProgress,
    );
});

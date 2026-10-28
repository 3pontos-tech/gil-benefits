<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use TresPontosTech\IntegrationMonday\Events\MondayItemColumnChanged;
use TresPontosTech\IntegrationMonday\Listeners\PushTicketStatusToMonday;
use TresPontosTech\IntegrationMonday\Listeners\SyncTicketStatusFromMonday;
use TresPontosTech\Support\Events\ExternalTicketStatusChanged;
use TresPontosTech\Support\Events\SupportTicketStatusChanged;
use TresPontosTech\Support\Listeners\ApplyExternalTicketStatus;

it('registers each ticket listener exactly once', function (string $event, string $listener): void {
    $registered = array_filter(
        Event::getRawListeners()[$event] ?? [],
        fn (mixed $raw): bool => $raw === $listener . '@handle' || $raw === $listener,
    );

    expect($registered)->toHaveCount(1);
})->with([
    'status pushed to Monday' => [SupportTicketStatusChanged::class, PushTicketStatusToMonday::class],
    'external status applied' => [ExternalTicketStatusChanged::class, ApplyExternalTicketStatus::class],
    'Monday column synced' => [MondayItemColumnChanged::class, SyncTicketStatusFromMonday::class],
]);

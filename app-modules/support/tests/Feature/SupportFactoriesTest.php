<?php

declare(strict_types=1);

use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\TicketDestination;

it('creates a support ticket through its factory', function (): void {
    $ticket = SupportTicket::factory()->create();

    $this->assertModelExists($ticket);

    expect($ticket->status)->toBeInstanceOf(SupportTicketStatusEnum::class)
        ->and($ticket->protocol)->toStartWith('SUP-');
});

it('creates a ticket destination linked to a ticket through its factory', function (): void {
    $destination = TicketDestination::factory()->create();

    $this->assertModelExists($destination);

    expect($destination->status)->toBeInstanceOf(TicketDestinationStatusEnum::class)
        ->and($destination->ticket)->toBeInstanceOf(SupportTicket::class);
});

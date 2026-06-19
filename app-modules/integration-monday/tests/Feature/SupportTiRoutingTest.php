<?php

declare(strict_types=1);

use TresPontosTech\IntegrationMonday\MondayClient;
use TresPontosTech\IntegrationMonday\Testing\FakeMondayClient;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Services\TicketRouterService;

beforeEach(function (): void {
    config([
        'monday.board_id' => '111',
        'monday.group_id' => 'topics',
        'monday.columns' => ['status' => 'status', 'protocol' => 'text_protocol', 'category' => 'text_category', 'requester' => 'text_requester', 'description' => 'long_text', 'attachments' => 'file'],
        'monday.status_indexes' => ['pending' => 17, 'in_progress' => 0, 'resolved' => 2, 'closed' => 3],
    ]);
});

it('routes a TI ticket to a Monday card when the board is configured', function (): void {
    $this->app->instance(MondayClient::class, $fake = new FakeMondayClient('987654'));

    $ticket = SupportTicket::query()->create([
        'protocol' => 'SUP-2026-0001',
        'category' => SupportTicketCategoryEnum::Bug,
        'subject' => 'Subject',
        'description' => 'description',
        'status' => SupportTicketStatusEnum::Pending,
        'environment' => 'testing',
    ]);

    resolve(TicketRouterService::class)->dispatch($ticket);

    $destination = $ticket->destinations()->sole();

    expect($destination->type)->toBe(TicketDestinationTypeEnum::Monday)
        ->and($destination->status)->toBe(TicketDestinationStatusEnum::Sent)
        ->and($destination->reference_id)->toBe('987654')
        ->and($fake->createdItems)->toHaveCount(1);
});

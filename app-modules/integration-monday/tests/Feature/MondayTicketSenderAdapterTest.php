<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use TresPontosTech\IntegrationMonday\Senders\MondayTicketSenderAdapter;
use TresPontosTech\IntegrationMonday\Testing\FakeMondayClient;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationChannelEnum;
use TresPontosTech\Support\Enums\TicketDestinationStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;

beforeEach(function (): void {
    config([
        'monday.board_id' => '111',
        'monday.group_id' => 'topics',
        'monday.columns' => ['status' => 'status', 'protocol' => 'text_protocol', 'category' => 'text_category', 'requester' => 'text_requester', 'description' => 'long_text', 'attachments' => 'file'],
        'monday.status_indexes' => ['pending' => 17, 'in_progress' => 0, 'resolved' => 2, 'closed' => 3],
    ]);
});

function ticketForMonday(): SupportTicket
{
    return SupportTicket::query()->create([
        'protocol' => 'SUP-2026-0001',
        'category' => SupportTicketCategoryEnum::Bug,
        'subject' => 'Login broken',
        'description' => 'Steps to reproduce...',
        'status' => SupportTicketStatusEnum::Pending,
        'visitor_name' => 'Jane',
        'visitor_email' => 'jane@example.com',
        'environment' => 'testing',
    ]);
}

it('creates a card with the ticket fields and returns a sent result', function (): void {
    $fake = new FakeMondayClient('987654');

    $result = (new MondayTicketSenderAdapter($fake))
        ->send(ticketForMonday(), TicketDestinationChannelEnum::SupportTi);

    expect($result->status)->toBe(TicketDestinationStatusEnum::Sent)
        ->and($result->referenceId)->toBe('987654')
        ->and($fake->createdItems)->toHaveCount(1);

    $created = $fake->createdItems[0];
    expect($created['itemName'])->toContain('SUP-2026-0001')
        ->and($created['columnValues']['long_text'])->toBe(['text' => 'Steps to reproduce...'])
        ->and($created['columnValues']['text_protocol'])->toBe('SUP-2026-0001')
        ->and($created['columnValues']['text_requester'])->toBe('jane@example.com');
});

it('returns a failed result when the Monday request fails', function (): void {
    $fake = new FakeMondayClient;
    $fake->shouldFail = true;

    $result = (new MondayTicketSenderAdapter($fake))
        ->send(ticketForMonday(), TicketDestinationChannelEnum::SupportTi);

    expect($result->status)->toBe(TicketDestinationStatusEnum::Failed);
});

it('uploads the ticket attachments to the file column', function (): void {
    Storage::fake('r2');
    $fake = new FakeMondayClient('987654');

    $ticket = ticketForMonday();
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    $ticket->addMediaFromString($png)->usingFileName('evidencia.png')->toMediaCollection('attachments');

    (new MondayTicketSenderAdapter($fake))->send($ticket, TicketDestinationChannelEnum::SupportTi);

    expect($fake->uploadedFiles)->toHaveCount(1)
        ->and($fake->uploadedFiles[0]['filename'])->toBe('evidencia.png')
        ->and($fake->uploadedFiles[0]['itemId'])->toBe('987654');
});

it('still succeeds when an attachment upload fails', function (): void {
    Storage::fake('r2');
    $fake = new FakeMondayClient('987654');
    $fake->shouldFailUpload = true;

    $ticket = ticketForMonday();
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    $ticket->addMediaFromString($png)->usingFileName('evidencia.png')->toMediaCollection('attachments');

    $result = (new MondayTicketSenderAdapter($fake))->send($ticket, TicketDestinationChannelEnum::SupportTi);

    expect($result->status)->toBe(TicketDestinationStatusEnum::Sent)
        ->and($result->referenceId)->toBe('987654');
});

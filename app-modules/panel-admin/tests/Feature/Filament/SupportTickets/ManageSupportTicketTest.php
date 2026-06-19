<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Admin\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use TresPontosTech\Admin\Filament\Resources\SupportTickets\Pages\ViewSupportTicket;
use TresPontosTech\Admin\Filament\Resources\SupportTickets\RelationManagers\DestinationsRelationManager;
use TresPontosTech\Admin\Filament\Resources\SupportTickets\SupportTicketResource;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationChannelEnum;
use TresPontosTech\Support\Enums\TicketDestinationStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;
use TresPontosTech\Support\Mail\SupportTicketStatusUpdatedMail;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\TicketDestination;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

function makeTicketFor(?User $user = null, SupportTicketStatusEnum $status = SupportTicketStatusEnum::Pending): SupportTicket
{
    static $seq = 0;
    ++$seq;

    return SupportTicket::query()->create([
        'protocol' => sprintf('SUP-2026-%04d', $seq),
        'user_id' => $user?->getKey(),
        'category' => SupportTicketCategoryEnum::Bug,
        'subject' => 'subject',
        'description' => 'description',
        'status' => $status,
        'environment' => 'testing',
    ]);
}

it('cannot create, edit or delete tickets from the admin panel', function (): void {
    expect(SupportTicketResource::canCreate())->toBeFalse()
        ->and(SupportTicketResource::canEdit(new SupportTicket))->toBeFalse()
        ->and(SupportTicketResource::canDelete(new SupportTicket))->toBeFalse()
        ->and(SupportTicketResource::canDeleteAny())->toBeFalse();
});

it('lists tickets from every user (not scoped)', function (): void {
    makeTicketFor(User::factory()->create());
    makeTicketFor(User::factory()->create());
    makeTicketFor(); // guest ticket

    expect(SupportTicketResource::getEloquentQuery()->count())->toBe(3);
});

it('can change a ticket status from the table and notifies the requester', function (): void {
    Mail::fake();

    $ticket = makeTicketFor(User::factory()->create(), SupportTicketStatusEnum::InProgress);

    livewire(ListSupportTickets::class)
        ->callTableAction('update_status', $ticket, data: [
            'status' => SupportTicketStatusEnum::Resolved->value,
        ])
        ->assertHasNoTableActionErrors();

    expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::Resolved);
    Mail::assertQueued(SupportTicketStatusUpdatedMail::class);
});

it('hides the status action for a closed (terminal) ticket', function (): void {
    $open = makeTicketFor(User::factory()->create(), SupportTicketStatusEnum::InProgress);
    $closed = makeTicketFor(User::factory()->create(), SupportTicketStatusEnum::Closed);

    livewire(ListSupportTickets::class)
        ->assertTableActionVisible('update_status', $open)
        ->assertTableActionHidden('update_status', $closed);
});

it('shows the ticket destinations in the relation manager', function (): void {
    $ticket = makeTicketFor(User::factory()->create());

    $destination = TicketDestination::query()->create([
        'support_ticket_id' => $ticket->getKey(),
        'type' => TicketDestinationTypeEnum::Email,
        'channel' => TicketDestinationChannelEnum::Financial,
        'status' => TicketDestinationStatusEnum::Sent,
        'reference_id' => 'msg-123',
    ]);

    livewire(DestinationsRelationManager::class, [
        'ownerRecord' => $ticket,
        'pageClass' => ViewSupportTicket::class,
    ])
        ->assertCanSeeTableRecords([$destination])
        ->assertTableColumnStateSet('reference_id', 'msg-123', $destination);
});

it('can change a ticket status from the view page', function (): void {
    $ticket = makeTicketFor(User::factory()->create(), SupportTicketStatusEnum::InProgress);

    livewire(ViewSupportTicket::class, ['record' => $ticket->getKey()])
        ->callAction('update_status', data: [
            'status' => SupportTicketStatusEnum::Resolved->value,
        ])
        ->assertHasNoActionErrors();

    expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::Resolved);
});

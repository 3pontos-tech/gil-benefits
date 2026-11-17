<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use TresPontosTech\PanelAdmin\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use TresPontosTech\PanelAdmin\Filament\Resources\SupportTickets\Pages\ViewSupportTicket;
use TresPontosTech\PanelAdmin\Filament\Resources\SupportTickets\RelationManagers\DestinationsRelationManager;
use TresPontosTech\PanelAdmin\Filament\Resources\SupportTickets\RelationManagers\NotificationsRelationManager;
use TresPontosTech\PanelAdmin\Filament\Resources\SupportTickets\RelationManagers\StatusChangesRelationManager;
use TresPontosTech\PanelAdmin\Filament\Resources\SupportTickets\SupportTicketResource;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketChannelEnum;
use TresPontosTech\Support\Enums\TicketDestinationChannelEnum;
use TresPontosTech\Support\Enums\TicketDestinationStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;
use TresPontosTech\Support\Enums\TicketNotificationStatusEnum;
use TresPontosTech\Support\Enums\TicketStatusChangeSourceEnum;
use TresPontosTech\Support\Jobs\SendTicketNotificationJob;
use TresPontosTech\Support\Mail\SupportTicketStatusUpdatedMail;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\SupportTicketNotification;
use TresPontosTech\Support\Models\SupportTicketStatusChange;
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
        'channel' => TicketChannelEnum::PanelApp,
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

describe('notifications tab', function (): void {
    it('lists the notifications sent for the ticket', function (): void {
        $ticket = makeTicketFor();
        $sent = SupportTicketNotification::factory()->for($ticket, 'ticket')->sent()->create();
        $failed = SupportTicketNotification::factory()->for($ticket, 'ticket')->failed()->create();

        livewire(NotificationsRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => ViewSupportTicket::class,
        ])
            ->assertCanSeeTableRecords([$sent, $failed])
            ->assertTableActionHidden('resend', $sent)
            ->assertTableActionVisible('resend', $failed);
    });

    it('hides the tab when the ticket has no notifications', function (): void {
        expect(NotificationsRelationManager::canViewForRecord(makeTicketFor(), ViewSupportTicket::class))->toBeFalse();
    });

    it('requeues a failed notification from the resend action', function (): void {
        Queue::fake([SendTicketNotificationJob::class]);

        $ticket = makeTicketFor();
        $failed = SupportTicketNotification::factory()->for($ticket, 'ticket')->failed()->create();

        livewire(NotificationsRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => ViewSupportTicket::class,
        ])
            ->callTableAction('resend', $failed)
            ->assertNotified();

        expect($failed->refresh()->status)->toBe(TicketNotificationStatusEnum::Pending);
        Queue::assertPushed(SendTicketNotificationJob::class);
    });
});

describe('reopening', function (): void {
    it('asks for a reason only when reopening a resolved ticket', function (): void {
        $resolved = makeTicketFor(User::factory()->create(), SupportTicketStatusEnum::Resolved);

        livewire(ListSupportTickets::class)
            ->mountTableAction('update_status', $resolved)
            ->fillForm(['status' => SupportTicketStatusEnum::Closed->value])
            ->assertFormFieldHidden('reason')
            ->fillForm(['status' => SupportTicketStatusEnum::InProgress->value])
            ->assertFormFieldVisible('reason');
    });

    it('refuses to reopen without a reason', function (): void {
        $ticket = makeTicketFor(User::factory()->create(), SupportTicketStatusEnum::Resolved);

        livewire(ListSupportTickets::class)
            ->callTableAction('update_status', $ticket, data: [
                'status' => SupportTicketStatusEnum::InProgress->value,
            ])
            ->assertHasTableActionErrors(['reason' => 'required']);

        expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::Resolved)
            ->and($ticket->statusChanges()->exists())->toBeFalse();
    });

    it('keeps the reason and the admin in the history when reopening', function (): void {
        $admin = auth()->user();
        $ticket = makeTicketFor(User::factory()->create(), SupportTicketStatusEnum::Resolved);

        livewire(ViewSupportTicket::class, ['record' => $ticket->getKey()])
            ->callAction('update_status', data: [
                'status' => SupportTicketStatusEnum::InProgress->value,
                'reason' => 'The requester says the problem came back.',
            ])
            ->assertHasNoActionErrors();

        expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::InProgress)
            ->and($ticket->statusChanges()->sole())
            ->from_status->toBe(SupportTicketStatusEnum::Resolved)
            ->to_status->toBe(SupportTicketStatusEnum::InProgress)
            ->source->toBe(TicketStatusChangeSourceEnum::Admin)
            ->actor_user_id->toBe($admin?->getAuthIdentifier())
            ->reason->toBe('The requester says the problem came back.');
    });

    it('records other admin transitions without a reason', function (): void {
        $ticket = makeTicketFor(User::factory()->create(), SupportTicketStatusEnum::InProgress);

        livewire(ListSupportTickets::class)
            ->callTableAction('update_status', $ticket, data: [
                'status' => SupportTicketStatusEnum::Resolved->value,
            ])
            ->assertHasNoTableActionErrors();

        expect($ticket->statusChanges()->sole())
            ->source->toBe(TicketStatusChangeSourceEnum::Admin)
            ->reason->toBeNull();
    });
});

it('filters the tickets that were reopened at least once', function (): void {
    $reopened = makeTicketFor(status: SupportTicketStatusEnum::InProgress);
    SupportTicketStatusChange::factory()->for($reopened, 'ticket')->reopening('The problem came back.')->create();

    $startedOnly = makeTicketFor(status: SupportTicketStatusEnum::InProgress);
    SupportTicketStatusChange::factory()->for($startedOnly, 'ticket')->create([
        'from_status' => SupportTicketStatusEnum::Pending,
        'to_status' => SupportTicketStatusEnum::InProgress,
    ]);

    $closedAfterResolved = makeTicketFor(status: SupportTicketStatusEnum::Closed);
    SupportTicketStatusChange::factory()->for($closedAfterResolved, 'ticket')->create([
        'from_status' => SupportTicketStatusEnum::Resolved,
        'to_status' => SupportTicketStatusEnum::Closed,
    ]);

    $neverChanged = makeTicketFor();

    livewire(ListSupportTickets::class)
        ->filterTable('reopened')
        ->assertCanSeeTableRecords([$reopened])
        ->assertCanNotSeeTableRecords([$startedOnly, $closedAfterResolved, $neverChanged]);
});

describe('history tab', function (): void {
    it('lists the status changes of the ticket', function (): void {
        $ticket = makeTicketFor();
        $opening = SupportTicketStatusChange::factory()->for($ticket, 'ticket')->opening()->create();
        $reopening = SupportTicketStatusChange::factory()->for($ticket, 'ticket')
            ->reopening('The requester says the problem came back.')
            ->create();
        $otherTicketChange = SupportTicketStatusChange::factory()->create();

        livewire(StatusChangesRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => ViewSupportTicket::class,
        ])
            ->assertCanSeeTableRecords([$opening, $reopening])
            ->assertCanNotSeeTableRecords([$otherTicketChange])
            ->assertTableColumnStateSet('reason', 'The requester says the problem came back.', $reopening);
    });

    it('keeps naming an author whose account was deleted', function (): void {
        $ticket = makeTicketFor();
        $author = User::factory()->create(['name' => 'Former Admin']);
        $change = SupportTicketStatusChange::factory()->for($ticket, 'ticket')->create(['actor_user_id' => $author->getKey()]);
        $author->delete();

        livewire(StatusChangesRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => ViewSupportTicket::class,
        ])
            ->assertTableColumnStateSet('actor.name', 'Former Admin', $change);
    });
});

it('shows the channel the ticket was opened from', function (): void {
    $ticket = makeTicketFor();

    livewire(ViewSupportTicket::class, ['record' => $ticket->getKey()])
        ->assertSee(TicketChannelEnum::PanelApp->getLabel());
});

<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Admin\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use TresPontosTech\Admin\Filament\Resources\SupportTickets\SupportTicketResource;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;

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

it('can change a ticket status', function (): void {
    $ticket = makeTicketFor(User::factory()->create());

    livewire(ListSupportTickets::class)
        ->callTableAction('update_status', $ticket, data: [
            'status' => SupportTicketStatusEnum::Resolved->value,
        ])
        ->assertHasNoTableActionErrors();

    expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::Resolved);
});

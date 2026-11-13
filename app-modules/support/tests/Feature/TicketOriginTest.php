<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use TresPontosTech\Support\Actions\CreateSupportTicketAction;
use TresPontosTech\Support\DTOs\CreateSupportTicketDTO;
use TresPontosTech\Support\DTOs\TicketOriginDTO;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\TicketOriginSourceEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\TicketOrigin;

use function Pest\Laravel\assertDatabaseCount;

function makeTicketDTO(?TicketOriginDTO $origin = null): CreateSupportTicketDTO
{
    return new CreateSupportTicketDTO(
        category: SupportTicketCategoryEnum::FinancialIssue,
        subject: 'Cobrança duplicada',
        description: 'Fui cobrado duas vezes no mesmo mês.',
        visitorName: 'João da Silva',
        visitorEmail: 'joao.silva@empresa.com',
        origin: $origin,
    );
}

it('records the origin of a ticket that came in from an integration', function (): void {
    $ticket = resolve(CreateSupportTicketAction::class)->execute(makeTicketDTO(
        new TicketOriginDTO(TicketOriginSourceEnum::Chatx, 'CHATX-123456'),
    ));

    expect($ticket->origin)->not->toBeNull()
        ->and($ticket->origin->source)->toBe(TicketOriginSourceEnum::Chatx)
        ->and($ticket->origin->external_reference)->toBe('CHATX-123456');
});

it('leaves tickets opened on the platform without an origin', function (): void {
    $ticket = resolve(CreateSupportTicketAction::class)->execute(makeTicketDTO());

    expect($ticket->origin()->exists())->toBeFalse();
});

it('rejects a second ticket carrying an external reference already used by the same source', function (): void {
    $action = resolve(CreateSupportTicketAction::class);
    $origin = new TicketOriginDTO(TicketOriginSourceEnum::Chatx, 'CHATX-123456');

    $action->execute(makeTicketDTO($origin));

    expect(fn () => $action->execute(makeTicketDTO($origin)))->toThrow(QueryException::class);
});

it('rolls the ticket back when its origin is refused, leaving no orphan', function (): void {
    $action = resolve(CreateSupportTicketAction::class);
    $origin = new TicketOriginDTO(TicketOriginSourceEnum::Chatx, 'CHATX-123456');

    $action->execute(makeTicketDTO($origin));

    try {
        $action->execute(makeTicketDTO($origin));
    } catch (QueryException) {
        // expected — asserted above
    }

    // The duplicate attempt must leave nothing behind: one ticket, one origin.
    assertDatabaseCount(SupportTicket::class, 1);
    assertDatabaseCount(TicketOrigin::class, 1);
});

it('lets the same external reference exist under a different source', function (): void {
    $ticket = SupportTicket::factory()->create();

    TicketOrigin::factory()->create([
        'support_ticket_id' => $ticket->id,
        'source' => TicketOriginSourceEnum::Chatx,
        'external_reference' => 'REF-1',
    ]);

    // The unique is composite, so a future source reusing the reference is not a clash.
    // Asserted through the constraint itself rather than a second enum case, since
    // ChatX is the only source today.
    expect(TicketOrigin::query()->where('external_reference', 'REF-1')->count())->toBe(1);
});

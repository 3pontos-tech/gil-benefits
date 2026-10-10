<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Jobs\DispatchSupportTicketJob;
use TresPontosTech\Support\Mail\SupportTicketConfirmationMail;
use TresPontosTech\Support\Mail\SupportTicketStatusUpdatedMail;
use TresPontosTech\Support\Models\SupportTicket;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    $this->travelTo('2026-10-09 10:00:00');
    Mail::fake();
    Queue::fake();
});

function ticketFor(User $user, array $attributes = []): SupportTicket
{
    return SupportTicket::factory()->create(['user_id' => $user->id, ...$attributes]);
}

it('rejects the ticket routes without a token', function (): void {
    getJson(route('api.v1.tickets.index'))->assertUnauthorized();
});

it('lists the employee tickets newest first, paginated', function (): void {
    $employee = actingAsApiEmployee();
    $older = ticketFor($employee);
    $this->travel(1)->hour();
    $newer = ticketFor($employee, ['status' => SupportTicketStatusEnum::InProgress]);

    getJson(route('api.v1.tickets.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $newer->id)
        ->assertJsonPath('data.0.status', 'in_progress')
        ->assertJsonPath('data.1.id', $older->id)
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.total', 2);
});

it('includes tickets opened outside the employer company, like the help center ones', function (): void {
    $employee = actingAsApiEmployee();
    $fromHelpCenter = ticketFor($employee, ['company_id' => null, 'visitor_email' => $employee->email]);
    $fromAnotherCompany = ticketFor($employee, ['company_id' => Company::factory()]);

    $ids = getJson(route('api.v1.tickets.index'))->assertOk()->json('data.*.id');

    expect($ids)->toEqualCanonicalizing([$fromHelpCenter->id, $fromAnotherCompany->id]);
});

it('leaves out the tickets of other people', function (): void {
    actingAsApiEmployee();
    ticketFor(User::factory()->create());

    getJson(route('api.v1.tickets.index'))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('opens a pending ticket with a protocol and routes it to the support team', function (): void {
    $employee = actingAsApiEmployee();

    $response = postJson(route('api.v1.tickets.store'), [
        'category' => 'scheduling_issue',
        'subject' => 'Trocar o dia da consultoria',
        'description' => 'Vou estar viajando no dia do encontro.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.category', 'scheduling_issue')
        ->assertJsonPath('data.subject', 'Trocar o dia da consultoria')
        ->assertJsonPath('data.status', 'pending');

    expect($response->json('data.protocol'))->toBe('SUP-2026-0001');

    assertDatabaseHas(SupportTicket::class, [
        'id' => $response->json('data.id'),
        'user_id' => $employee->id,
        'company_id' => $employee->employerCompanyId(),
        'browser' => 'app',
        'device' => 'mobile',
        'url' => null,
    ]);

    Queue::assertPushed(DispatchSupportTicketJob::class, fn (DispatchSupportTicketJob $job): bool => $job->ticket->id === $response->json('data.id'));
    Mail::assertQueued(SupportTicketConfirmationMail::class, fn (SupportTicketConfirmationMail $mail): bool => $mail->hasTo($employee->email));
});

it('files the individual subscriber ticket under the default company', function (): void {
    $subscriber = defaultCompanyUser();
    Sanctum::actingAs($subscriber, ['employee']);

    $response = postJson(route('api.v1.tickets.store'), [
        'category' => 'general_question',
        'subject' => 'Dúvida',
        'description' => 'Como funciona o plano?',
    ])->assertCreated();

    expect(SupportTicket::query()->findOrFail($response->json('data.id'))->company_id)
        ->toBe(Company::query()->where('slug', Company::DEFAULT_SLUG)->value('id'));
});

it('validates the new ticket', function (array $payload, array $errors): void {
    actingAsApiEmployee();

    postJson(route('api.v1.tickets.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errors);

    Queue::assertNothingPushed();
})->with([
    'missing fields' => [[], [
        'category' => 'O campo categoria é obrigatório.',
        'subject' => 'O campo assunto é obrigatório.',
        'description' => 'O campo descrição é obrigatório.',
    ]],
    'unknown category' => [
        ['category' => 'refund', 'subject' => 'Reembolso', 'description' => 'Quero meu dinheiro.'],
        ['category'],
    ],
    'subject too long' => [
        ['category' => 'other', 'subject' => str_repeat('a', 256), 'description' => 'Texto.'],
        ['subject'],
    ],
    'description too long' => [
        ['category' => 'other', 'subject' => 'Assunto', 'description' => str_repeat('a', 5001)],
        ['description'],
    ],
]);

it('closes the ticket and lets the requester know', function (SupportTicketStatusEnum $from): void {
    $employee = actingAsApiEmployee();
    $ticket = ticketFor($employee, ['status' => $from]);

    patchJson(route('api.v1.tickets.update', $ticket), ['status' => 'closed'])
        ->assertOk()
        ->assertJsonPath('data.id', $ticket->id)
        ->assertJsonPath('data.status', 'closed');

    expect($ticket->fresh()->status)->toBe(SupportTicketStatusEnum::Closed);
    Mail::assertQueued(SupportTicketStatusUpdatedMail::class, fn (SupportTicketStatusUpdatedMail $mail): bool => $mail->hasTo($employee->email));
})->with([
    SupportTicketStatusEnum::Pending,
    SupportTicketStatusEnum::InProgress,
    SupportTicketStatusEnum::Resolved,
]);

it('refuses to close a ticket twice', function (): void {
    $employee = actingAsApiEmployee();
    $ticket = ticketFor($employee);

    patchJson(route('api.v1.tickets.update', $ticket), ['status' => 'closed'])->assertOk();

    patchJson(route('api.v1.tickets.update', $ticket), ['status' => 'closed'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status' => 'Este chamado já foi encerrado.']);

    Mail::assertQueuedCount(1);
});

it('only accepts closed from the employee', function (string $status): void {
    $employee = actingAsApiEmployee();
    $ticket = ticketFor($employee, ['status' => SupportTicketStatusEnum::InProgress]);

    patchJson(route('api.v1.tickets.update', $ticket), ['status' => $status])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    expect($ticket->fresh()->status)->toBe(SupportTicketStatusEnum::InProgress);
})->with(['resolved', 'pending', 'in_progress', 'archived']);

it('does not find a ticket of another person', function (): void {
    actingAsApiEmployee();
    $ticket = ticketFor(User::factory()->create());

    patchJson(route('api.v1.tickets.update', $ticket), ['status' => 'closed'])->assertNotFound();

    expect($ticket->fresh()->status)->toBe(SupportTicketStatusEnum::Pending);
});

it('answers 404 for an id that is not a uuid', function (): void {
    actingAsApiEmployee();

    patchJson('/api/v1/tickets/123', ['status' => 'closed'])->assertNotFound();
});

it('keeps every category the panel offers', function (SupportTicketCategoryEnum $category): void {
    actingAsApiEmployee();

    postJson(route('api.v1.tickets.store'), [
        'category' => $category->value,
        'subject' => 'Assunto',
        'description' => 'Texto.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.category', $category->value);
})->with(SupportTicketCategoryEnum::cases());

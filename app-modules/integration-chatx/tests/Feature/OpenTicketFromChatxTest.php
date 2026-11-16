<?php

declare(strict_types=1);

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\TicketOriginSourceEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\TicketOrigin;

use function Pest\Laravel\assertDatabaseCount;

const INTAKE_SECRET = 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2';

beforeEach(function (): void {
    config()->set('chatx.webhook_secret', INTAKE_SECRET);
    config()->set('chatx.allowed_ips', []);
    Mail::fake();
});

/**
 * The payload ChatX actually sends, per the example they shared.
 *
 * @param  array<string, mixed>  $overrides
 */
function intakePayload(array $overrides = []): array
{
    return array_replace_recursive([
        'event' => 'ticket.created',
        'timestamp' => now()->toIso8601ZuluString(),
        'external_reference' => 'wpp-conv-a1b2c3d4',
        'visitor' => [
            'name' => 'João da Silva',
            'email' => 'joao.silva@empresa.com',
            'document' => '39053344705',
            'phone' => '+5511999999999',
            'company_name' => 'Empresa XYZ Ltda',
        ],
        'ticket' => [
            'category' => 'financeiro',
            'subject' => 'Erro ao gerar boleto',
            'description' => 'Cliente relata que o boleto não foi gerado após a compra.',
        ],
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $payload
 */
function postIntake(array $payload, bool $acceptJson = true): TestResponse
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $timestamp = now()->toIso8601ZuluString();

    return test()->call('POST', '/webhooks/chatx', [], [], [], array_filter([
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => $acceptJson ? 'application/json' : null,
        'HTTP_X_TIMESTAMP' => $timestamp,
        'HTTP_X_SIGNATURE' => hash_hmac('sha256', $timestamp . $body, INTAKE_SECRET),
    ]), $body);
}

function registeredRequester(string $email = 'joao.silva@empresa.com', string $taxId = '39053344705'): User
{
    $user = User::factory()->create(['email' => $email]);

    Detail::factory()->recycle($user)->create(['tax_id' => $taxId]);

    return $user;
}

it('opens a ticket and answers with the protocol so the bot can quote it', function (): void {
    $user = registeredRequester();

    $response = postIntake(intakePayload())->assertStatus(Response::HTTP_CREATED);

    $ticket = SupportTicket::query()->withoutGlobalScopes()->sole();

    expect($ticket->user_id)->toBe($user->id)
        ->and($ticket->subject)->toBe('Erro ao gerar boleto')
        ->and($ticket->category)->toBe(SupportTicketCategoryEnum::FinancialIssue);

    $response->assertJson([
        'ticket_id' => $ticket->id,
        'protocol' => $ticket->protocol,
        'external_reference' => 'wpp-conv-a1b2c3d4',
    ]);
});

it('records where the ticket came in from', function (): void {
    registeredRequester();

    postIntake(intakePayload())->assertCreated();

    $origin = TicketOrigin::query()->sole();

    expect($origin->source)->toBe(TicketOriginSourceEnum::Chatx)
        ->and($origin->external_reference)->toBe('wpp-conv-a1b2c3d4');
});

it('matches the requester to their account by e-mail', function (): void {
    $user = registeredRequester(email: 'maria@empresa.com');

    postIntake(intakePayload(['visitor' => ['email' => 'maria@empresa.com']]))->assertCreated();

    expect(SupportTicket::query()->withoutGlobalScopes()->sole()->user_id)->toBe($user->id);
});

it('refuses an e-mail that belongs to nobody, without saying so', function (): void {
    postIntake(intakePayload())
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        // Never "no customer with that e-mail" — that would let anyone holding the
        // credentials discover who is a customer by trying addresses.
        ->assertExactJson(['error' => 'dados inválidos']);

    assertDatabaseCount(SupportTicket::class, 0);
});

it('accepts a CPF that matches the account', function (): void {
    registeredRequester(taxId: '39053344705');

    postIntake(intakePayload(['visitor' => ['document' => '390.533.447-05']]))->assertCreated();
});

it('refuses a CPF that belongs to somebody else', function (): void {
    registeredRequester(taxId: '39053344705');

    postIntake(intakePayload(['visitor' => ['document' => '11144477735']]))
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertExactJson(['error' => 'dados inválidos']);

    assertDatabaseCount(SupportTicket::class, 0);
});

it('refuses a request without a CPF, since e-mail alone is easy to guess', function (): void {
    registeredRequester();

    $payload = intakePayload();
    unset($payload['visitor']['document']);

    postIntake($payload)
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrorFor('visitor.document');

    assertDatabaseCount(SupportTicket::class, 0);
});

it('returns the ticket that already exists when a reference repeats', function (): void {
    registeredRequester();

    $first = postIntake(intakePayload())->assertCreated();

    // A retried delivery must not open a second ticket — 200, same ticket.
    postIntake(intakePayload())
        ->assertOk()
        ->assertJson(['ticket_id' => $first->json('ticket_id')]);

    assertDatabaseCount(SupportTicket::class, 1);
    assertDatabaseCount(TicketOrigin::class, 1);
});

it('opens a separate ticket for a different reference from the same person', function (): void {
    registeredRequester();

    postIntake(intakePayload())->assertCreated();
    postIntake(intakePayload(['external_reference' => 'wpp-conv-99999999']))->assertCreated();

    assertDatabaseCount(SupportTicket::class, 2);
});

it('maps their category vocabulary onto ours', function (string $sent, SupportTicketCategoryEnum $expected): void {
    registeredRequester();

    postIntake(intakePayload(['ticket' => ['category' => $sent]]))->assertCreated();

    expect(SupportTicket::query()->withoutGlobalScopes()->sole()->category)->toBe($expected);
})->with([
    'their word' => ['financeiro', SupportTicketCategoryEnum::FinancialIssue],
    'accented' => ['Finanças', SupportTicketCategoryEnum::FinancialIssue],
    'our own value' => ['login_access', SupportTicketCategoryEnum::LoginAccess],
    'unknown falls back' => ['bruxaria', SupportTicketCategoryEnum::Other],
]);

it('rejects a payload missing a required field, naming it', function (): void {
    registeredRequester();

    // Schema errors do name the field — that is what lets ChatX debug. Only the
    // identity failure stays vague.
    postIntake(intakePayload(['ticket' => ['subject' => null]]))
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrorFor('ticket.subject');
});

it('answers a schema error with JSON even when ChatX sends no Accept header', function (): void {
    registeredRequester();

    postIntake(intakePayload(['ticket' => ['subject' => null]]), acceptJson: false)
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrorFor('ticket.subject');
});

it('ignores query parameters on a signed body sent without a JSON content type', function (): void {
    registeredRequester();
    registeredRequester('victim@example.com', '11144477735');

    $body = json_encode(intakePayload(), JSON_THROW_ON_ERROR);
    $timestamp = now()->toIso8601ZuluString();
    $query = http_build_query(intakePayload(['visitor' => ['email' => 'victim@example.com', 'document' => '11144477735']]));

    test()->call('POST', '/webhooks/chatx?' . $query, [], [], [], [
        'CONTENT_TYPE' => 'text/plain',
        'HTTP_X_TIMESTAMP' => $timestamp,
        'HTTP_X_SIGNATURE' => hash_hmac('sha256', $timestamp . $body, INTAKE_SECRET),
    ], $body)->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

    assertDatabaseCount(SupportTicket::class, 0);
});

it('rejects an event it does not handle', function (): void {
    registeredRequester();

    postIntake(intakePayload(['event' => 'ticket.updated']))
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrorFor('event');

    assertDatabaseCount(SupportTicket::class, 0);
});

it('logs the payload for the webhook panel', function (): void {
    registeredRequester();

    postIntake(intakePayload())->assertCreated();

    expect(DB::table('inbound_webhooks')->where('source', 'chatx')->count())->toBe(1);
});

it('keeps the CPF and phone out of the stored delivery', function (): void {
    registeredRequester();

    postIntake(intakePayload())->assertCreated();

    $stored = json_decode((string) DB::table('inbound_webhooks')->where('source', 'chatx')->value('payload'), true);

    expect($stored['visitor'])->not->toHaveKeys(['document', 'phone'])
        ->and($stored['visitor']['email'])->toBe('joao.silva@empresa.com');
});

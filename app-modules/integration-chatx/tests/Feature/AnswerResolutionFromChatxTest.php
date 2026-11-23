<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketStatusChangeSourceEnum;
use TresPontosTech\Support\Mail\SupportTicketReopenedMail;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\TicketOrigin;

const ANSWER_SECRET = 'f0e1d2c3b4a5f6e7d8c9b0a1f2e3d4c5b6a7f8e9d0c1b2a3f4e5d6c7b8a9f0e1';

beforeEach(function (): void {
    config()->set('chatx.webhook_secret', ANSWER_SECRET);
    config()->set('chatx.allowed_ips', []);
    config()->set('support.emails.financial', 'financial-team@example.com');
    Mail::fake();
    Bus::fake();
});

function chatxTicket(SupportTicketStatusEnum $status): SupportTicket
{
    $ticket = SupportTicket::factory()->create([
        'user_id' => User::factory(),
        'category' => SupportTicketCategoryEnum::FinancialIssue,
        'status' => $status,
    ]);

    TicketOrigin::factory()->create([
        'support_ticket_id' => $ticket->id,
        'external_reference' => 'wpp-conv-a1b2c3d4',
    ]);

    return $ticket;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function answerPayload(string $event, array $overrides = []): array
{
    return array_replace_recursive([
        'event' => $event,
        'timestamp' => now()->toIso8601ZuluString(),
        'external_reference' => 'wpp-conv-a1b2c3d4',
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $payload
 */
function postAnswer(array $payload): TestResponse
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $timestamp = now()->toIso8601ZuluString();

    return test()->call('POST', '/webhooks/chatx', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_TIMESTAMP' => $timestamp,
        'HTTP_X_SIGNATURE' => hash_hmac('sha256', $timestamp . $body, ANSWER_SECRET),
    ], $body);
}

describe('reopen requested', function (): void {
    it('reopens a resolved ticket, recording the customer message and telling the sector', function (): void {
        $ticket = chatxTicket(SupportTicketStatusEnum::Resolved);

        postAnswer(answerPayload('ticket.reopen_requested', ['message' => 'O boleto continua sem gerar.']))
            ->assertOk()
            ->assertExactJson([
                'ticket_id' => $ticket->id,
                'protocol' => $ticket->protocol,
                'status' => 'in_progress',
            ]);

        expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::InProgress)
            ->and($ticket->statusChanges()->sole())
            ->source->toBe(TicketStatusChangeSourceEnum::Chatx)
            ->actor_user_id->toBe($ticket->user_id)
            ->reason->toBe('O boleto continua sem gerar.');

        Mail::assertQueued(SupportTicketReopenedMail::class, fn (SupportTicketReopenedMail $mail): bool => $mail->hasTo('financial-team@example.com'));
    });

    it('reopens without a message', function (): void {
        $ticket = chatxTicket(SupportTicketStatusEnum::Resolved);

        postAnswer(answerPayload('ticket.reopen_requested'))->assertOk();

        expect($ticket->statusChanges()->sole()->reason)->toBeNull();
    });

    it('changes nothing on a ticket that is already open', function (SupportTicketStatusEnum $status): void {
        $ticket = chatxTicket($status);

        postAnswer(answerPayload('ticket.reopen_requested'))
            ->assertOk()
            ->assertJson(['status' => $status->value]);

        expect($ticket->statusChanges()->count())->toBe(0);
    })->with([
        'pending' => SupportTicketStatusEnum::Pending,
        'in progress' => SupportTicketStatusEnum::InProgress,
    ]);

    it('refuses a closed ticket, so the customer opens another one', function (): void {
        $ticket = chatxTicket(SupportTicketStatusEnum::Closed);

        postAnswer(answerPayload('ticket.reopen_requested'))
            ->assertStatus(Response::HTTP_CONFLICT)
            ->assertJson(['status' => 'closed']);

        expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::Closed);
    });

    it('rejects a message over 2000 characters', function (): void {
        chatxTicket(SupportTicketStatusEnum::Resolved);

        postAnswer(answerPayload('ticket.reopen_requested', ['message' => str_repeat('a', 2001)]))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('message');
    });

    it('answers a repeated delivery without a second history row or e-mail', function (): void {
        $ticket = chatxTicket(SupportTicketStatusEnum::Resolved);

        postAnswer(answerPayload('ticket.reopen_requested'))->assertOk();
        postAnswer(answerPayload('ticket.reopen_requested'))->assertOk()->assertJson(['status' => 'in_progress']);

        expect($ticket->statusChanges()->count())->toBe(1);
        Mail::assertQueued(SupportTicketReopenedMail::class, 1);
    });
});

describe('resolution confirmed', function (): void {
    it('closes a resolved ticket', function (): void {
        $ticket = chatxTicket(SupportTicketStatusEnum::Resolved);

        postAnswer(answerPayload('ticket.resolution_confirmed', ['ticket' => ['id' => $ticket->id, 'protocol' => $ticket->protocol]]))
            ->assertOk()
            ->assertJson(['ticket_id' => $ticket->id, 'status' => 'closed']);

        expect($ticket->refresh()->status)->toBe(SupportTicketStatusEnum::Closed)
            ->and($ticket->statusChanges()->sole())
            ->source->toBe(TicketStatusChangeSourceEnum::Chatx)
            ->reason->toBeNull();
    });

    it('changes nothing on a ticket that is already closed', function (): void {
        $ticket = chatxTicket(SupportTicketStatusEnum::Closed);

        postAnswer(answerPayload('ticket.resolution_confirmed'))
            ->assertOk()
            ->assertJson(['status' => 'closed']);

        expect($ticket->statusChanges()->count())->toBe(0);
    });

    it('refuses a ticket that is not resolved', function (SupportTicketStatusEnum $status): void {
        $ticket = chatxTicket($status);

        postAnswer(answerPayload('ticket.resolution_confirmed'))
            ->assertStatus(Response::HTTP_CONFLICT)
            ->assertJson(['status' => $status->value]);

        expect($ticket->refresh()->status)->toBe($status);
    })->with([
        'pending' => SupportTicketStatusEnum::Pending,
        'in progress' => SupportTicketStatusEnum::InProgress,
    ]);
});

it('answers 404 for a reference no ticket came from', function (string $event): void {
    chatxTicket(SupportTicketStatusEnum::Resolved);

    postAnswer(answerPayload($event, ['external_reference' => 'wpp-conv-unknown']))
        ->assertNotFound();
})->with(['ticket.reopen_requested', 'ticket.resolution_confirmed']);

it('rejects an answer without the reference', function (): void {
    $payload = answerPayload('ticket.reopen_requested');
    unset($payload['external_reference']);

    postAnswer($payload)
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrorFor('external_reference');
});

it('masks the customer message in the stored delivery', function (): void {
    chatxTicket(SupportTicketStatusEnum::Resolved);

    postAnswer(answerPayload('ticket.reopen_requested', ['message' => 'Meu telefone mudou.']))->assertOk();

    $stored = json_decode((string) DB::table('inbound_webhooks')->where('source', 'chatx')->value('payload'), true);

    expect($stored['message'])->toBe(str_repeat('*', mb_strlen('Meu telefone mudou.')));
});

it('answers 409 when the ticket was moved by someone else before the answer applied', function (string $event, SupportTicketStatusEnum $movedTo): void {
    $ticket = chatxTicket(SupportTicketStatusEnum::Resolved);

    SupportTicket::retrieved(function (SupportTicket $retrieved) use ($ticket, $movedTo): void {
        if ($retrieved->is($ticket) && $retrieved->status === SupportTicketStatusEnum::Resolved) {
            SupportTicket::query()->withoutGlobalScopes()->whereKey($ticket->id)->update(['status' => $movedTo]);
        }
    });

    postAnswer(answerPayload($event))
        ->assertStatus(Response::HTTP_CONFLICT)
        ->assertJson(['status' => $movedTo->value]);

    expect($ticket->statusChanges()->count())->toBe(0);
})->with([
    'reopen after an agent closed it' => ['ticket.reopen_requested', SupportTicketStatusEnum::Closed],
    'confirm after an agent reopened it' => ['ticket.resolution_confirmed', SupportTicketStatusEnum::InProgress],
]);

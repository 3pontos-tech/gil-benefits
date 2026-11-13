<?php

declare(strict_types=1);

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use TresPontosTech\IntegrationChatx\Senders\ChatxNotificationChannel;
use TresPontosTech\Support\Actions\CreateSupportTicketAction;
use TresPontosTech\Support\DTOs\CreateSupportTicketDTO;
use TresPontosTech\Support\DTOs\TicketOriginDTO;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketNotificationChannelEnum;
use TresPontosTech\Support\Enums\TicketNotificationEventEnum;
use TresPontosTech\Support\Enums\TicketNotificationStatusEnum;
use TresPontosTech\Support\Enums\TicketOriginSourceEnum;
use TresPontosTech\Support\Jobs\SendTicketNotificationJob;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\SupportTicketNotification;
use TresPontosTech\Support\Models\TicketOrigin;
use TresPontosTech\Support\TicketNotificationManager;

use function Pest\Laravel\freezeTime;

const CHATX_STATUS_URL = 'https://chatx.test/status';

beforeEach(function (): void {
    config()->set('chatx.status_webhook.url', CHATX_STATUS_URL);
    config()->set('chatx.status_webhook.token', 'test-token');
});

function chatxChannel(): ChatxNotificationChannel
{
    return resolve(ChatxNotificationChannel::class);
}

function ticketFromChatx(string $requesterName = 'Mariana Souza', string $subject = 'Boleto veio em dobro'): SupportTicket
{
    $user = User::factory()->create(['name' => $requesterName, 'email' => 'mariana@example.com']);
    Detail::factory()->recycle($user)->create(['phone_number' => '11999999999']);

    $ticket = SupportTicket::factory()->for($user)->create([
        'protocol' => 'SUP-2026-0008',
        'subject' => $subject,
        'status' => SupportTicketStatusEnum::Pending,
    ]);
    TicketOrigin::factory()->for($ticket, 'ticket')->create(['external_reference' => '3031']);

    return $ticket;
}

it('is the WhatsApp channel of the support notifications', function (): void {
    expect(resolve(TicketNotificationManager::class)->channel(TicketNotificationChannelEnum::Whatsapp))
        ->toBeInstanceOf(ChatxNotificationChannel::class);
});

it('queues the creation message when a ticket comes in through ChatX', function (): void {
    Mail::fake();
    Queue::fake([SendTicketNotificationJob::class]);

    $ticket = resolve(CreateSupportTicketAction::class)->execute(new CreateSupportTicketDTO(
        category: SupportTicketCategoryEnum::FinancialIssue,
        subject: 'Boleto veio em dobro',
        description: 'Cobrança duplicada.',
        userId: User::factory()->create(['name' => 'Mariana Souza'])->id,
        environment: 'testing',
        origin: new TicketOriginDTO(TicketOriginSourceEnum::Chatx, '3031'),
    ));

    expect(SupportTicketNotification::query()->sole()->message)
        ->toBe("Olá, Mariana! Seu chamado foi criado com sucesso. Número do chamado: {$ticket->protocol}.");
});

describe('message', function (): void {
    it('has no message for a ticket that did not come from ChatX', function (): void {
        $ticket = SupportTicket::factory()->create();

        expect(chatxChannel()->message($ticket, null, SupportTicketStatusEnum::Pending))->toBeNull();
    });

    it('greets the requester by first name on creation', function (): void {
        expect(chatxChannel()->message(ticketFromChatx(), null, SupportTicketStatusEnum::Pending))
            ->toBe('Olá, Mariana! Seu chamado foi criado com sucesso. Número do chamado: SUP-2026-0008.');
    });

    it('says what happened on each status transition', function (SupportTicketStatusEnum $from, SupportTicketStatusEnum $to, string $message): void {
        expect(chatxChannel()->message(ticketFromChatx(), $from, $to))->toBe($message);
    })->with([
        'taken into progress' => [
            SupportTicketStatusEnum::Pending,
            SupportTicketStatusEnum::InProgress,
            'Olá, Mariana! Seu chamado SUP-2026-0008 — Boleto veio em dobro — foi recebido e já está em andamento com nosso time de suporte. Avisamos por aqui assim que houver novidade.',
        ],
        'resolved' => [
            SupportTicketStatusEnum::InProgress,
            SupportTicketStatusEnum::Resolved,
            'Olá, Mariana! Seu chamado SUP-2026-0008 — Boleto veio em dobro — foi resolvido pelo nosso time. Se ainda estiver com dificuldade, é só responder esta mensagem que reabrimos o atendimento.',
        ],
        'reopened' => [
            SupportTicketStatusEnum::Resolved,
            SupportTicketStatusEnum::InProgress,
            'Olá, Mariana! Seu chamado SUP-2026-0008 — Boleto veio em dobro — foi reaberto e voltou para a fila do nosso time. Assim que houver uma nova resposta, você recebe por aqui.',
        ],
        'closed from progress' => [
            SupportTicketStatusEnum::InProgress,
            SupportTicketStatusEnum::Closed,
            'Olá, Mariana! Seu chamado SUP-2026-0008 — Boleto veio em dobro — foi encerrado. Se precisar de algo mais, é só abrir um novo atendimento por aqui.',
        ],
        'closed during triage' => [
            SupportTicketStatusEnum::Pending,
            SupportTicketStatusEnum::Closed,
            'Olá, Mariana! Seu chamado SUP-2026-0008 — Boleto veio em dobro — foi encerrado. Se precisar de algo mais, é só abrir um novo atendimento por aqui.',
        ],
    ]);

    it('collapses whitespace in the quoted subject', function (): void {
        $ticket = ticketFromChatx(subject: "Boleto  da mensalidade\nveio em dobro");

        expect(chatxChannel()->message($ticket, SupportTicketStatusEnum::Pending, SupportTicketStatusEnum::InProgress))
            ->toContain('— Boleto da mensalidade veio em dobro —');
    });

    it('greets without a name when the requester has none', function (): void {
        expect(chatxChannel()->message(ticketFromChatx(requesterName: '  '), null, SupportTicketStatusEnum::Pending))
            ->toStartWith('Olá! Seu chamado foi criado');
    });
});

describe('send', function (): void {
    beforeEach(function (): void {
        Http::preventStrayRequests();
    });

    function chatxNotification(TicketNotificationEventEnum $event = TicketNotificationEventEnum::StatusChanged): SupportTicketNotification
    {
        $created = $event === TicketNotificationEventEnum::Created;

        return SupportTicketNotification::factory()->for(ticketFromChatx(), 'ticket')->create([
            'event' => $event,
            'from_status' => $created ? null : SupportTicketStatusEnum::InProgress,
            'to_status' => $created ? SupportTicketStatusEnum::Pending : SupportTicketStatusEnum::Resolved,
            'message' => 'Olá, Mariana!',
        ]);
    }

    it('posts the agreed payload with the bearer token', function (): void {
        freezeTime();
        Http::fake([CHATX_STATUS_URL => Http::response(['ok' => true])]);

        $notification = chatxNotification();
        $ticket = $notification->ticket;

        $result = chatxChannel()->send($notification);

        expect($result->status)->toBe(TicketNotificationStatusEnum::Sent)
            ->and($result->responseCode)->toBe(200);

        Http::assertSent(fn (Request $request): bool => $request->url() === CHATX_STATUS_URL
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request->data() === [
                'event' => 'ticket.status_updated',
                'timestamp' => now()->toIso8601ZuluString(),
                'external_reference' => '3031',
                'ticket' => [
                    'id' => $ticket->id,
                    'protocol' => 'SUP-2026-0008',
                    'status' => 'resolved',
                    'previous_status' => 'in_progress',
                    'subject' => 'Boleto veio em dobro',
                ],
                'customer' => [
                    'client_id' => $ticket->user_id,
                    'name' => 'Mariana Souza',
                    'phone' => '11999999999',
                    'email' => 'mariana@example.com',
                ],
                'message' => 'Olá, Mariana!',
            ]);
    });

    it('sends the creation as ticket.created with no previous status', function (): void {
        Http::fake([CHATX_STATUS_URL => Http::response()]);

        chatxChannel()->send(chatxNotification(TicketNotificationEventEnum::Created));

        Http::assertSent(fn (Request $request): bool => $request['event'] === 'ticket.created'
            && $request['ticket']['status'] === 'pending'
            && $request['ticket']['previous_status'] === null);
    });

    it('reports a rejected call with its status and body', function (): void {
        Http::fake([CHATX_STATUS_URL => Http::response(['message' => 'Unauthenticated.'], 401)]);

        $result = chatxChannel()->send(chatxNotification());

        expect($result->status)->toBe(TicketNotificationStatusEnum::Failed)
            ->and($result->responseCode)->toBe(401)
            ->and($result->error)->toBe('{"message":"Unauthenticated."}');
    });

    it('reports a failed connection without a status', function (): void {
        Http::fake([CHATX_STATUS_URL => Http::failedConnection()]);

        $result = chatxChannel()->send(chatxNotification());

        expect($result->status)->toBe(TicketNotificationStatusEnum::Failed)
            ->and($result->responseCode)->toBeNull();
    });

    it('skips without calling ChatX when the webhook is not configured', function (string $key): void {
        Http::fake([CHATX_STATUS_URL => Http::response()]);
        config()->set($key);

        $result = chatxChannel()->send(chatxNotification());

        expect($result->status)->toBe(TicketNotificationStatusEnum::Skipped)
            ->and($result->error)->toContain('CHATX_STATUS_WEBHOOK');
        Http::assertNothingSent();
    })->with([
        'no url' => 'chatx.status_webhook.url',
        'no token' => 'chatx.status_webhook.token',
    ]);
});

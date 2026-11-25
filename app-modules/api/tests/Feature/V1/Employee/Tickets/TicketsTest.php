<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use TresPontosTech\Api\Enums\TokenAbility;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketChannelEnum;
use TresPontosTech\Support\Enums\TicketStatusChangeSourceEnum;
use TresPontosTech\Support\Jobs\DispatchSupportTicketJob;
use TresPontosTech\Support\Mail\SupportTicketConfirmationMail;
use TresPontosTech\Support\Mail\SupportTicketStatusUpdatedMail;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\SupportTicketStatusChange;

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
        'channel' => TicketChannelEnum::Api->value,
    ]);

    Queue::assertPushed(DispatchSupportTicketJob::class, fn (DispatchSupportTicketJob $job): bool => $job->ticket->id === $response->json('data.id'));
    Mail::assertQueued(SupportTicketConfirmationMail::class, fn (SupportTicketConfirmationMail $mail): bool => $mail->hasTo($employee->email));
});

it('files the individual subscriber ticket under the default company', function (): void {
    $subscriber = standaloneSubscriber();
    Sanctum::actingAs($subscriber, [TokenAbility::Employee->value]);

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
    assertDatabaseHas(SupportTicketStatusChange::class, [
        'support_ticket_id' => $ticket->id,
        'from_status' => $from->value,
        'to_status' => SupportTicketStatusEnum::Closed->value,
        'source' => TicketStatusChangeSourceEnum::Requester->value,
        'actor_user_id' => $employee->id,
    ]);
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

/** A real PDF header, so the media library takes it as `application/pdf`. */
function ticketPdf(string $name = 'extrato.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF\n");
}

/**
 * @param  list<UploadedFile>  $files
 */
function ticketWithAttachments(User $user, array $files): SupportTicket
{
    $ticket = ticketFor($user);

    foreach ($files as $file) {
        $ticket->addMedia($file)->toMediaCollection('attachments');
    }

    return $ticket->refresh();
}

it('keeps the photos and the PDF sent with a new ticket as its attachments', function (): void {
    Storage::fake('r2');
    actingAsApiEmployee();

    $response = postJson(route('api.v1.tickets.store'), [
        'category' => 'platform_error',
        'subject' => 'Erro ao enviar extrato',
        'description' => 'Apareceu "Sem conexão" com a internet funcionando.',
        'attachments' => [
            UploadedFile::fake()->image('foto-do-erro.jpg', 1080, 1920),
            UploadedFile::fake()->image('tela.png', 390, 844),
            ticketPdf('relatório de novembro.pdf'),
        ],
    ])
        ->assertCreated()
        ->assertJsonCount(3, 'data.attachments')
        ->assertJsonPath('data.attachments.0.name', 'foto-do-erro.jpg')
        ->assertJsonPath('data.attachments.0.mime_type', 'image/jpeg')
        ->assertJsonPath('data.attachments.1.mime_type', 'image/png')
        ->assertJsonPath('data.attachments.2.name', 'relatório de novembro.pdf')
        ->assertJsonPath('data.attachments.2.mime_type', 'application/pdf');

    $ticket = SupportTicket::query()->findOrFail($response->json('data.id'));

    expect($ticket->getMedia('attachments'))->toHaveCount(3)
        ->and($response->json('data.attachments.0.id'))->toBe($ticket->getFirstMedia('attachments')?->uuid);
    Queue::assertPushed(DispatchSupportTicketJob::class);
});

it('opens a ticket without attachments as before', function (): void {
    actingAsApiEmployee();

    postJson(route('api.v1.tickets.store'), [
        'category' => 'general_question',
        'subject' => 'Dúvida',
        'description' => 'Como funciona o plano?',
    ])
        ->assertCreated()
        ->assertJsonPath('data.attachments', []);
});

it('refuses attachments the panel would refuse, and opens nothing', function (Closure $attachments, string $field): void {
    Storage::fake('r2');
    actingAsApiEmployee();

    postJson(route('api.v1.tickets.store'), [
        'category' => 'other',
        'subject' => 'Assunto',
        'description' => 'Texto do chamado.',
        'attachments' => $attachments(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);

    expect(SupportTicket::query()->count())->toBe(0);
    Queue::assertNothingPushed();
})->with([
    'not a photo or a PDF' => [fn (): array => [UploadedFile::fake()->create('nota.txt', 4, 'text/plain')], 'attachments.0'],
    'over 5 MB' => [fn (): array => [ticketPdf(), UploadedFile::fake()->create('grande.pdf', 5121, 'application/pdf')], 'attachments.1'],
    'more than five' => [fn (): array => array_map(fn (int $index): UploadedFile => UploadedFile::fake()->image("foto-{$index}.jpg"), range(1, 6)), 'attachments'],
    'not a list' => [fn (): string => 'foto.jpg', 'attachments'],
]);

it('lists each ticket with its attachments, without links', function (): void {
    Storage::fake('r2');
    $employee = actingAsApiEmployee();
    $ticket = ticketWithAttachments($employee, [UploadedFile::fake()->image('foto.jpg'), ticketPdf()]);
    ticketFor($employee);

    $response = getJson(route('api.v1.tickets.index'))->assertOk();

    expect($response->json('data.1.attachments'))->toBe([
        [
            'id' => $ticket->getMedia('attachments')[0]->uuid,
            'name' => 'foto.jpg',
            'mime_type' => 'image/jpeg',
            'size' => $ticket->getMedia('attachments')[0]->size,
        ],
        [
            'id' => $ticket->getMedia('attachments')[1]->uuid,
            'name' => 'extrato.pdf',
            'mime_type' => 'application/pdf',
            'size' => $ticket->getMedia('attachments')[1]->size,
        ],
    ])->and($response->json('data.0.attachments'))->toBe([]);
});

it('hands an attachment through a two-minute link that downloads it with its name', function (): void {
    Storage::fake('r2');
    $captured = [];
    Storage::disk('r2')->buildTemporaryUrlsUsing(function (string $path, DateTimeInterface $expiration, array $options) use (&$captured): string {
        $captured = ['path' => $path, 'expiration' => $expiration, 'options' => $options];

        return 'https://files.test/' . $path . '?signature=abc';
    });
    $employee = actingAsApiEmployee();
    $ticket = ticketWithAttachments($employee, [ticketPdf('relatório de novembro.pdf')]);
    $media = $ticket->getFirstMedia('attachments');

    postJson(route('api.v1.tickets.attachments.download', [$ticket->id, $media?->uuid]))
        ->assertOk()
        ->assertJsonPath('data.url', 'https://files.test/' . $captured['path'] . '?signature=abc')
        ->assertJsonPath('data.filename', 'relatório de novembro.pdf')
        ->assertJsonPath('data.mime_type', 'application/pdf')
        ->assertJsonPath('data.expires_at', '2026-10-09T10:02:00-03:00');

    expect($captured['expiration']->getTimestamp())->toBe(now()->addMinutes(2)->getTimestamp())
        ->and($captured['options']['ResponseContentType'])->toBe('application/pdf')
        ->and($captured['options']['ResponseContentDisposition'])
        ->toBe("attachment; filename=\"relatorio de novembro.pdf\"; filename*=utf-8''relat%C3%B3rio%20de%20novembro.pdf");
});

it('hands no attachment of another person, nor of another ticket', function (): void {
    Storage::fake('r2');
    $employee = actingAsApiEmployee();
    $own = ticketWithAttachments($employee, [ticketPdf()]);
    $foreign = ticketWithAttachments(User::factory()->create(), [ticketPdf()]);
    $foreignMedia = $foreign->getFirstMedia('attachments');

    postJson(route('api.v1.tickets.attachments.download', [$foreign->id, $foreignMedia?->uuid]))->assertNotFound();
    postJson(route('api.v1.tickets.attachments.download', [$own->id, $foreignMedia?->uuid]))->assertNotFound();
});

<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use TresPontosTech\Support\Actions\CreateSupportTicketAction;
use TresPontosTech\Support\DTOs\CreateSupportTicketDTO;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Models\SupportTicket;

beforeEach(function (): void {
    Date::setTestNow('2026-06-11 12:00:00');
    Mail::fake();
});

afterEach(function (): void {
    Date::setTestNow();
    SupportTicket::clearBootedModels();
});

function dto(string $subject = 'subject'): CreateSupportTicketDTO
{
    return new CreateSupportTicketDTO(
        category: SupportTicketCategoryEnum::Bug,
        subject: $subject,
        description: 'description',
        visitorName: 'Visitor',
        visitorEmail: 'visitor@example.com',
        environment: 'testing',
    );
}

it('creates tickets with sequential global protocols', function (): void {
    $action = resolve(CreateSupportTicketAction::class);

    $first = $action->execute(dto('first'));
    $second = $action->execute(dto('second'));

    expect($first->protocol)->toBe('SUP-2026-0001')
        ->and($second->protocol)->toBe('SUP-2026-0002');
});

it('narrows a multiple FileUpload state into a list of uploads', function (): void {
    $dto = CreateSupportTicketDTO::fromFormState([
        'category' => SupportTicketCategoryEnum::Bug,
        'subject' => 'subject',
        'description' => 'description',
        'attachments' => [
            UploadedFile::fake()->image('a.png'),
            UploadedFile::fake()->image('b.png'),
        ],
    ]);

    expect($dto->attachments)->toHaveCount(2);
});

it('stores every uploaded attachment in the media collection', function (): void {
    Storage::fake('r2');

    $ticket = resolve(CreateSupportTicketAction::class)->execute(new CreateSupportTicketDTO(
        category: SupportTicketCategoryEnum::Bug,
        subject: 'subject',
        description: 'description',
        visitorName: 'Visitor',
        visitorEmail: 'visitor@example.com',
        environment: 'testing',
        attachments: [
            UploadedFile::fake()->image('first.png'),
            UploadedFile::fake()->image('second.png'),
        ],
    ));

    expect($ticket->getMedia('attachments'))->toHaveCount(2);
});

it('attaches a guest ticket to the registered user whose email matches the visitor email', function (): void {
    $user = User::factory()->create(['email' => 'visitor@example.com']);

    $ticket = resolve(CreateSupportTicketAction::class)->execute(dto());

    expect($ticket->user_id)->toBe($user->id);
});

it('leaves the ticket without a user when no registered email matches', function (): void {
    $ticket = resolve(CreateSupportTicketAction::class)->execute(dto());

    expect($ticket->user_id)->toBeNull();
});

it('keeps an explicit user id over the visitor email lookup', function (): void {
    $explicit = User::factory()->create();
    User::factory()->create(['email' => 'visitor@example.com']);

    $ticket = resolve(CreateSupportTicketAction::class)->execute(new CreateSupportTicketDTO(
        category: SupportTicketCategoryEnum::Bug,
        subject: 'subject',
        description: 'description',
        userId: $explicit->id,
        visitorEmail: 'visitor@example.com',
        environment: 'testing',
    ));

    expect($ticket->user_id)->toBe($explicit->id);
});

it('does not collide with an existing protocol hidden by the tenant scope (regression)', function (): void {
    $action = resolve(CreateSupportTicketAction::class);

    // First ticket exists globally (e.g. opened by a guest).
    $action->execute(dto('guest'));

    // Tenant scope now hides every row from this context.
    SupportTicket::addGlobalScope('company_tenancy', fn ($query) => $query->whereRaw('1 = 0'));

    // Must still take the next global number instead of colliding on SUP-2026-0001.
    $next = $action->execute(dto('company'));

    expect($next->protocol)->toBe('SUP-2026-0002');
});

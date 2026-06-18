<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\App\Filament\Resources\SupportTickets\Pages\CreateSupportTicket;
use TresPontosTech\App\Filament\Resources\SupportTickets\SupportTicketResource;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Mail\SupportTicketConfirmationMail;
use TresPontosTech\Support\Mail\SupportTicketMail;
use TresPontosTech\Support\Models\SupportTicket;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Mail::fake();
});

it('opens a ticket through the app panel and notifies area + requester', function (): void {
    $user = actingAsEmployee();

    livewire(CreateSupportTicket::class)
        ->fillForm([
            'category' => SupportTicketCategoryEnum::FinancialIssue->value,
            'subject' => 'Cobrança indevida',
            'description' => 'Fui cobrado em duplicidade.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(SupportTicket::class, [
        'subject' => 'Cobrança indevida',
        'user_id' => $user->getKey(),
        'category' => SupportTicketCategoryEnum::FinancialIssue->value,
        'status' => SupportTicketStatusEnum::Pending->value,
    ]);

    Mail::assertSent(
        SupportTicketMail::class,
        fn (SupportTicketMail $mail): bool => $mail->hasTo(config('support.emails.financial')),
    );
    Mail::assertQueued(
        SupportTicketConfirmationMail::class,
        fn (SupportTicketConfirmationMail $mail): bool => $mail->hasTo($user->email),
    );
});

it('requires category, subject and description', function (): void {
    actingAsEmployee();

    livewire(CreateSupportTicket::class)
        ->fillForm([
            'category' => null,
            'subject' => null,
            'description' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'category' => 'required',
            'subject' => 'required',
            'description' => 'required',
        ]);
});

it('does not allow editing or deleting tickets', function (): void {
    actingAsEmployee();

    expect(SupportTicketResource::canEdit(new SupportTicket))->toBeFalse()
        ->and(SupportTicketResource::canDelete(new SupportTicket))->toBeFalse()
        ->and(SupportTicketResource::canDeleteAny())->toBeFalse();
});

it('only lists tickets owned by the current user', function (): void {
    $user = actingAsEmployee();
    $company = filament()->getTenant();

    $mine = SupportTicket::query()->create([
        'protocol' => 'SUP-2026-0001',
        'user_id' => $user->getKey(),
        'company_id' => $company?->getKey(),
        'category' => SupportTicketCategoryEnum::Bug,
        'subject' => 'meu',
        'description' => 'd',
        'status' => SupportTicketStatusEnum::Pending,
        'environment' => 'testing',
    ]);

    $other = User::factory()->employee()->create();
    SupportTicket::query()->create([
        'protocol' => 'SUP-2026-0002',
        'user_id' => $other->getKey(),
        'company_id' => $company?->getKey(),
        'category' => SupportTicketCategoryEnum::Bug,
        'subject' => 'de outro',
        'description' => 'd',
        'status' => SupportTicketStatusEnum::Pending,
        'environment' => 'testing',
    ]);

    $visible = SupportTicketResource::getEloquentQuery()->pluck('id');

    expect($visible)->toHaveCount(1)
        ->and($visible->first())->toBe($mine->getKey());
});

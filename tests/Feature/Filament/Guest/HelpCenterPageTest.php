<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Filament\Guest\Pages\HelpCenterPage;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Mail\SupportTicketConfirmationMail;
use TresPontosTech\Support\Mail\SupportTicketMail;
use TresPontosTech\Support\Models\SupportTicket;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Mail::fake();
    filament()->setCurrentPanel(FilamentPanel::Guest->value);
});

it('lets a visitor open a ticket and notifies area + visitor', function (): void {
    livewire(HelpCenterPage::class)
        ->fillForm([
            'visitor_name' => 'Maria Visitante',
            'visitor_email' => 'maria@example.com',
            'visitor_company_name' => 'ACME',
            'category' => SupportTicketCategoryEnum::FinancialIssue->value,
            'subject' => 'Dúvida de cobrança',
            'description' => 'Quero entender a fatura.',
        ])
        ->call('submit')
        ->assertHasNoFormErrors();

    assertDatabaseHas(SupportTicket::class, [
        'user_id' => null,
        'visitor_name' => 'Maria Visitante',
        'visitor_email' => 'maria@example.com',
        'visitor_company_name' => 'ACME',
        'category' => SupportTicketCategoryEnum::FinancialIssue->value,
        'status' => SupportTicketStatusEnum::Dispatched->value,
    ]);

    Mail::assertSent(
        SupportTicketMail::class,
        fn (SupportTicketMail $mail): bool => $mail->hasTo(config('support.emails.financial')),
    );
    Mail::assertQueued(
        SupportTicketConfirmationMail::class,
        fn (SupportTicketConfirmationMail $mail): bool => $mail->hasTo('maria@example.com'),
    );
});

it('throttles ticket submissions per IP', function (): void {
    $payload = [
        'visitor_name' => 'Spammer',
        'visitor_email' => 'spam@example.com',
        'category' => SupportTicketCategoryEnum::Bug->value,
        'subject' => 'subject',
        'description' => 'description',
    ];

    $component = livewire(HelpCenterPage::class);

    // The limiter allows 5 submissions per IP before blocking.
    for ($i = 0; $i < 5; ++$i) {
        $component->fillForm($payload)->call('submit')->assertHasNoFormErrors();
    }

    expect(SupportTicket::query()->count())->toBe(5);

    // The 6th submission from the same IP is rejected without creating a ticket.
    $component->fillForm($payload)->call('submit');

    expect(SupportTicket::query()->count())->toBe(5);
});

it('requires visitor name and email', function (): void {
    livewire(HelpCenterPage::class)
        ->fillForm([
            'visitor_name' => null,
            'visitor_email' => null,
            'category' => SupportTicketCategoryEnum::Bug->value,
            'subject' => 'x',
            'description' => 'y',
        ])
        ->call('submit')
        ->assertHasFormErrors([
            'visitor_name' => 'required',
            'visitor_email' => 'required',
        ]);
});

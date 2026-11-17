<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Support\Actions\TransitionSupportTicketStatusAction;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketStatusChangeSourceEnum;
use TresPontosTech\Support\Mail\SupportTicketReopenedMail;
use TresPontosTech\Support\Models\SupportTicket;

beforeEach(function (): void {
    Mail::fake();
    Bus::fake();
    config()->set('support.emails.financial', 'financial-team@example.com');
});

function resolvedFinancialTicket(): SupportTicket
{
    return SupportTicket::factory()->create([
        'protocol' => 'SUP-2026-0101',
        'category' => SupportTicketCategoryEnum::FinancialIssue,
        'status' => SupportTicketStatusEnum::Resolved,
    ]);
}

it('e-mails the sector of the category when an admin reopens the ticket', function (): void {
    $admin = User::factory()->create(['name' => 'Support Admin']);
    $ticket = resolvedFinancialTicket();

    resolve(TransitionSupportTicketStatusAction::class)->execute(
        $ticket,
        SupportTicketStatusEnum::InProgress,
        TicketStatusChangeSourceEnum::Admin,
        $admin,
        'The charge showed up again this month.',
    );

    Mail::assertQueued(SupportTicketReopenedMail::class, fn (SupportTicketReopenedMail $mail): bool => $mail->hasTo('financial-team@example.com')
        && $mail->ticket->is($ticket)
        && $mail->reason === 'The charge showed up again this month.'
        && $mail->reopenedBy === 'Support Admin');
    Mail::assertQueued(SupportTicketReopenedMail::class, 1);
});

it('does not e-mail the sector on other transitions', function (SupportTicketStatusEnum $from, SupportTicketStatusEnum $to): void {
    $ticket = SupportTicket::factory()->create([
        'category' => SupportTicketCategoryEnum::FinancialIssue,
        'status' => $from,
    ]);

    resolve(TransitionSupportTicketStatusAction::class)->execute($ticket, $to, TicketStatusChangeSourceEnum::Admin);

    Mail::assertNotQueued(SupportTicketReopenedMail::class);
})->with([
    'pending to in progress' => [SupportTicketStatusEnum::Pending, SupportTicketStatusEnum::InProgress],
    'in progress to resolved' => [SupportTicketStatusEnum::InProgress, SupportTicketStatusEnum::Resolved],
    'resolved to closed' => [SupportTicketStatusEnum::Resolved, SupportTicketStatusEnum::Closed],
]);

it('does not e-mail the sector when the external destination reopened the ticket itself', function (): void {
    resolve(TransitionSupportTicketStatusAction::class)->execute(
        resolvedFinancialTicket(),
        SupportTicketStatusEnum::InProgress,
        TicketStatusChangeSourceEnum::ExternalDestination,
    );

    Mail::assertNotQueued(SupportTicketReopenedMail::class);
});

it('shows the protocol, the reason and who reopened it', function (): void {
    $html = (new SupportTicketReopenedMail(resolvedFinancialTicket(), 'The charge showed up again this month.', 'Support Admin'))->render();

    expect($html)->toContain('SUP-2026-0101')
        ->toContain('The charge showed up again this month.')
        ->toContain('Support Admin');
});

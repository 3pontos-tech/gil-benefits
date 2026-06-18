<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationStatusEnum;
use TresPontosTech\Support\Mail\SupportTicketMail;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Services\TicketRouterService;

beforeEach(function (): void {
    Mail::fake();
});

function ticketOfCategory(SupportTicketCategoryEnum $category): SupportTicket
{
    return SupportTicket::query()->create([
        'protocol' => 'SUP-2026-0001',
        'category' => $category,
        'subject' => 'subject',
        'description' => 'description',
        'status' => SupportTicketStatusEnum::Pending,
        'environment' => 'testing',
    ]);
}

/**
 * Expected recipient config key per category. Hardcoded on purpose so an
 * accidental remapping in the enum is caught by the test.
 */
dataset('category_routing', [
    'login_access -> ti' => [SupportTicketCategoryEnum::LoginAccess, 'support.emails.support_ti'],
    'platform_error -> ti' => [SupportTicketCategoryEnum::PlatformError, 'support.emails.support_ti'],
    'bug -> ti' => [SupportTicketCategoryEnum::Bug, 'support.emails.support_ti'],
    'integration -> ti' => [SupportTicketCategoryEnum::Integration, 'support.emails.support_ti'],
    'performance -> ti' => [SupportTicketCategoryEnum::Performance, 'support.emails.support_ti'],
    'scheduling_issue -> ti' => [SupportTicketCategoryEnum::SchedulingIssue, 'support.emails.support_ti'],
    'financial_issue -> financial' => [SupportTicketCategoryEnum::FinancialIssue, 'support.emails.financial'],
    'contract_plan -> commercial' => [SupportTicketCategoryEnum::ContractPlan, 'support.emails.commercial'],
    'cancellation_complaint -> cs' => [SupportTicketCategoryEnum::CancellationComplaint, 'support.emails.cs'],
    'suggestion_feedback -> product' => [SupportTicketCategoryEnum::SuggestionFeedback, 'support.emails.product'],
    'general_question -> cs' => [SupportTicketCategoryEnum::GeneralQuestion, 'support.emails.cs'],
    'other -> cs' => [SupportTicketCategoryEnum::Other, 'support.emails.cs'],
]);

it('dispatches each category to its channel recipient and marks it sent', function (
    SupportTicketCategoryEnum $category,
    string $recipientConfigKey,
): void {
    $ticket = ticketOfCategory($category);

    resolve(TicketRouterService::class)->dispatch($ticket);

    /** @var string $recipient */
    $recipient = config($recipientConfigKey);

    Mail::assertSent(
        SupportTicketMail::class,
        fn (SupportTicketMail $mail): bool => $mail->hasTo($recipient) && $mail->ticket->is($ticket),
    );
    Mail::assertSent(SupportTicketMail::class, 1);

    $ticket->refresh();
    $destination = $ticket->destinations()->sole();

    // Routing notifies the sector but does not advance the lifecycle — the ticket
    // stays Pending until an agent moves it into progress.
    expect($destination->channel)->toBe($category->getDestinationChannel())
        ->and($destination->status)->toBe(TicketDestinationStatusEnum::Sent)
        ->and($ticket->status)->toBe(SupportTicketStatusEnum::Pending);

})->with('category_routing');

<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;

it('exposes self-service hints only for login and scheduling categories', function (): void {
    expect(SupportTicketCategoryEnum::LoginAccess->getHint())->toHaveCount(2)
        ->and(SupportTicketCategoryEnum::SchedulingIssue->getHint())->toHaveCount(1);

    $withoutHint = array_filter(
        SupportTicketCategoryEnum::cases(),
        static fn (SupportTicketCategoryEnum $category): bool => ! in_array($category, [
            SupportTicketCategoryEnum::LoginAccess,
            SupportTicketCategoryEnum::SchedulingIssue,
        ], true),
    );

    foreach ($withoutHint as $category) {
        expect($category->getHint())->toBe([]);
    }
});

it('splits the login hint into password and plan tips', function (): void {
    [$password, $plan] = SupportTicketCategoryEnum::LoginAccess->getHint();

    expect($password)->toContain('Esqueci minha senha')
        ->and($plan)->toContain('plano');
});

it('spells out the cancellation deadline in the scheduling hint', function (): void {
    expect(SupportTicketCategoryEnum::SchedulingIssue->getHint()[0])
        ->toContain((string) Appointment::CANCELLATION_WINDOW_HOURS)
        ->toContain('crédito');
});

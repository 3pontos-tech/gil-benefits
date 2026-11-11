<?php

declare(strict_types=1);

use App\Models\Users\User;
use App\Notifications\ContentNotification;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Notification;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Actions\AllocateCreditToEmployee;
use TresPontosTech\Credits\Actions\MigrateCompanyCreditsOwner;
use TresPontosTech\Credits\Actions\RevokeCreditsFromEmployees;
use TresPontosTech\Credits\Actions\TransferCreditBetweenEmployees;
use TresPontosTech\Credits\Jobs\WarnExpiringCreditsJob;
use TresPontosTech\Credits\Models\UserCredit;
use TresPontosTech\Credits\Notifications\CreditsExpiringNotification;

beforeEach(function (): void {
    Notification::fake();
    $this->travelTo('2026-11-08 10:07:00');
    $this->holder = User::factory()->create();
});

function freeCreditFor(User $holder, ?string $expiresAt): UserCredit
{
    return UserCredit::factory()->available()->create(['holder_id' => $holder->getKey(), 'expires_at' => $expiresAt]);
}

/**
 * @return list<string>
 */
function expiryBodiesSentTo(User $holder): array
{
    return Notification::sent($holder, CreditsExpiringNotification::class)
        ->map(fn (CreditsExpiringNotification $notification): ?string => $notification->content()->push?->body)
        ->values()
        ->all();
}

it('warns a week before and the day before, on the phone, with the last valid day', function (string $expiresAt, string $lastDay): void {
    freeCreditFor($this->holder, $expiresAt);
    freeCreditFor($this->holder, $expiresAt);

    (new WarnExpiringCreditsJob)->handle();

    expect(expiryBodiesSentTo($this->holder))->toBe([sprintf('Agende um encontro até %s para usar seu crédito antes do vencimento.', $lastDay)]);
    Notification::assertSentTo($this->holder, CreditsExpiringNotification::class, fn (CreditsExpiringNotification $notification): bool => in_array(ContentNotification::PUSH_CHANNEL, $notification->via($this->holder), true));
})->with([
    'a week ahead, at the end of the day' => ['2026-11-15 23:59:59', '15/11'],
    'the day before, expiring at midnight' => ['2026-11-09 00:00:00', '08/11'],
]);

it('warns the day before the last valid day of a credit that expires at midnight', function (): void {
    $credit = freeCreditFor($this->holder, '2026-11-10 00:00:00');

    (new WarnExpiringCreditsJob)->handle();

    expect(expiryBodiesSentTo($this->holder))->toBe(['Agende um encontro até 09/11 para usar seu crédito antes do vencimento.'])
        ->and($credit->refresh()->expiry_warned_day_before_at)->not->toBeNull();
});

it('does not repeat a warning, and warns again the day before', function (): void {
    $credit = freeCreditFor($this->holder, '2026-11-15 23:59:59');

    (new WarnExpiringCreditsJob)->handle();
    (new WarnExpiringCreditsJob)->handle();
    expect(expiryBodiesSentTo($this->holder))->toHaveCount(1);

    $this->travelTo('2026-11-14 10:07:00');
    (new WarnExpiringCreditsJob)->handle();

    expect(expiryBodiesSentTo($this->holder))->toHaveCount(2)
        ->and($credit->refresh()->expiry_warned_day_before_at)->not->toBeNull();
});

it('catches up on a run that was missed', function (): void {
    freeCreditFor($this->holder, '2026-11-14 23:59:59');

    (new WarnExpiringCreditsJob)->handle();

    expect(expiryBodiesSentTo($this->holder))->toBe(['Agende um encontro até 14/11 para usar seu crédito antes do vencimento.']);
});

it('sends one push per person, the most urgent, when both warnings are due', function (): void {
    $tomorrow = freeCreditFor($this->holder, '2026-11-09 23:59:59');
    $nextWeek = freeCreditFor($this->holder, '2026-11-13 23:59:59');

    (new WarnExpiringCreditsJob)->handle();

    expect(expiryBodiesSentTo($this->holder))->toBe(['Agende um encontro até 09/11 para usar seu crédito antes do vencimento.'])
        ->and($tomorrow->refresh()->expiry_warned_day_before_at)->not->toBeNull()
        ->and($nextWeek->refresh()->expiry_warned_week_before_at)->not->toBeNull();
});

it('leaves alone credits that are not free or not expiring soon', function (callable $make): void {
    $make($this->holder);

    (new WarnExpiringCreditsJob)->handle();

    Notification::assertNothingSent();
})->with([
    'reserved for an appointment' => [fn (User $holder): UserCredit => UserCredit::factory()->inUse()->create(['holder_id' => $holder->getKey(), 'expires_at' => '2026-11-15 23:59:59'])],
    'already expired' => [fn (User $holder): UserCredit => UserCredit::factory()->expired()->create(['holder_id' => $holder->getKey(), 'expires_at' => '2026-11-15 23:59:59'])],
    'without an expiry date' => [fn (User $holder): UserCredit => freeCreditFor($holder, null)],
    'expiring in 10 days' => [fn (User $holder): UserCredit => freeCreditFor($holder, '2026-11-18 23:59:59')],
]);

it('keeps the credit eligible when the warning fails', function (): void {
    $credit = freeCreditFor($this->holder, '2026-11-15 23:59:59');
    Notification::shouldReceive('send')->andThrow(new RuntimeException('notifications table unavailable'));

    expect(fn () => (new WarnExpiringCreditsJob)->handle())->toThrow(RuntimeException::class);

    expect($credit->refresh()->expiry_warned_week_before_at)->toBeNull();
});

it('warns whoever receives a credit after the previous holder was warned', function (bool $heldByTheOwner, callable $handOver): void {
    $owner = User::factory()->create();
    $employee = User::factory()->create();
    $company = Company::factory()->create(['user_id' => $owner->getKey()]);
    $credit = UserCredit::factory()->available()->create([
        'owner_id' => $owner->getKey(),
        'holder_id' => $heldByTheOwner ? $owner->getKey() : $employee->getKey(),
        'company_id' => $company->getKey(),
        'expires_at' => '2026-11-15 23:59:59',
    ]);
    (new WarnExpiringCreditsJob)->handle();

    $newHolder = $handOver($credit, $company, $owner, $employee);
    (new WarnExpiringCreditsJob)->handle();

    expect(expiryBodiesSentTo($newHolder))->toBe(['Agende um encontro até 15/11 para usar seu crédito antes do vencimento.']);
})->with([
    'transferred to a colleague' => [false, function (UserCredit $credit): User {
        $colleague = User::factory()->create();
        resolve(TransferCreditBetweenEmployees::class)->handle($credit->refresh(), $colleague);

        return $colleague;
    }],
    'allocated from the company pool' => [true, function (UserCredit $credit, Company $company, User $owner, User $employee): User {
        resolve(AllocateCreditToEmployee::class)->handle($company, $employee, 1);

        return $employee;
    }],
    'revoked back to the company' => [false, function (UserCredit $credit, Company $company, User $owner): User {
        resolve(RevokeCreditsFromEmployees::class)->handle($company);

        return $owner;
    }],
    'moved to the new company owner' => [true, function (UserCredit $credit, Company $company, User $owner): User {
        $newOwner = User::factory()->create();
        $company->updateQuietly(['user_id' => $newOwner->getKey()]);
        resolve(MigrateCompanyCreditsOwner::class)->handle($company, (string) $owner->getKey());

        return $newOwner;
    }],
]);

it('runs one warning job at a time', function (): void {
    expect(new WarnExpiringCreditsJob)->toBeInstanceOf(ShouldBeUnique::class);
});

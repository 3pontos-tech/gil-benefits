<?php

declare(strict_types=1);

use App\Models\Users\User;
use App\Notifications\ContentNotification;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Notification;
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

it('runs one warning job at a time', function (): void {
    expect(new WarnExpiringCreditsJob)->toBeInstanceOf(ShouldBeUnique::class);
});

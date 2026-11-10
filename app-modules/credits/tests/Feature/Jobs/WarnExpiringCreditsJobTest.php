<?php

declare(strict_types=1);

use App\Models\Users\User;
use App\Notifications\ContentNotification;
use Illuminate\Support\Facades\Notification;
use TresPontosTech\Credits\Jobs\WarnExpiringCreditsJob;
use TresPontosTech\Credits\Models\UserCredit;
use TresPontosTech\Credits\Notifications\CreditsExpiringNotification;

beforeEach(function (): void {
    Notification::fake();
    $this->travelTo('2026-11-08 10:00:00');
    $this->holder = User::factory()->create();
});

it('warns 7 days and 1 day before, once per person, with the first date', function (string $expiresAt, string $lastDay): void {
    UserCredit::factory()->available()->create(['holder_id' => $this->holder->getKey(), 'expires_at' => $expiresAt]);
    UserCredit::factory()->available()->create(['holder_id' => $this->holder->getKey(), 'expires_at' => $expiresAt]);

    (new WarnExpiringCreditsJob)->handle();

    Notification::assertSentToTimes($this->holder, CreditsExpiringNotification::class, 1);
    Notification::assertSentTo($this->holder, CreditsExpiringNotification::class, fn (CreditsExpiringNotification $notification): bool => $notification->content()->push?->body === sprintf('Agende um encontro até %s para usar seu crédito antes do vencimento.', $lastDay)
        && in_array(ContentNotification::PUSH_CHANNEL, $notification->via($this->holder), true));
})->with([
    'a week ahead, at the end of the day' => ['2026-11-15 23:59:59', '15/11'],
    'a day ahead, at midnight' => ['2026-11-09 00:00:00', '08/11'],
]);

it('leaves alone credits that are not free or not expiring soon', function (callable $make): void {
    $make($this->holder);

    (new WarnExpiringCreditsJob)->handle();

    Notification::assertNothingSent();
})->with([
    'reserved for an appointment' => [fn (User $holder): UserCredit => UserCredit::factory()->inUse()->create(['holder_id' => $holder->getKey(), 'expires_at' => '2026-11-15 23:59:59'])],
    'already expired' => [fn (User $holder): UserCredit => UserCredit::factory()->expired()->create(['holder_id' => $holder->getKey(), 'expires_at' => '2026-11-15 23:59:59'])],
    'without an expiry date' => [fn (User $holder): UserCredit => UserCredit::factory()->available()->create(['holder_id' => $holder->getKey(), 'expires_at' => null])],
    'expiring in 3 days' => [fn (User $holder): UserCredit => UserCredit::factory()->available()->create(['holder_id' => $holder->getKey(), 'expires_at' => '2026-11-11 23:59:59'])],
]);

it('does not repeat the warning when it runs twice the same day', function (): void {
    UserCredit::factory()->available()->create(['holder_id' => $this->holder->getKey(), 'expires_at' => '2026-11-15 23:59:59']);

    (new WarnExpiringCreditsJob)->handle();
    (new WarnExpiringCreditsJob)->handle();

    Notification::assertSentToTimes($this->holder, CreditsExpiringNotification::class, 1);
});

<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use TresPontosTech\Api\Exceptions\PushConfigurationException;
use TresPontosTech\Api\Models\PushTicket;
use TresPontosTech\Api\Models\PushToken;

use function Pest\Laravel\artisan;

/**
 * @param  array<string, array<string, mixed>>  $receipts
 */
function expoReceipts(array $receipts): void
{
    Http::fake(['exp.host/--/api/v2/push/getReceipts' => Http::response(['data' => $receipts])]);
}

function pushTicketFor(PushToken $token, string $id, int $minutesAgo = 20): PushTicket
{
    return PushTicket::query()->create(['id' => $id, 'push_token_id' => $token->getKey(), 'created_at' => now()->subMinutes($minutesAgo)]);
}

/**
 * O aparelho se registrou antes de qualquer envio dos testes.
 */
beforeEach(function (): void {
    $this->phone = PushToken::factory()->create(['user_id' => standaloneSubscriber()->getKey(), 'created_at' => now()->subHour(), 'updated_at' => now()->subHour()]);
});

it('clears the tickets that were delivered', function (): void {
    pushTicketFor($this->phone, 'delivered');
    expoReceipts(['delivered' => ['status' => 'ok']]);

    artisan('push:check-receipts')->assertSuccessful();

    expect(PushTicket::query()->count())->toBe(0)
        ->and(PushToken::query()->count())->toBe(1);
});

it('forgets a phone whose receipt says it no longer exists', function (): void {
    pushTicketFor($this->phone, 'gone');
    expoReceipts(['gone' => ['status' => 'error', 'details' => ['error' => 'DeviceNotRegistered']]]);

    artisan('push:check-receipts')->assertSuccessful();

    expect(PushToken::query()->count())->toBe(0)
        ->and(PushTicket::query()->count())->toBe(0);
});

it('reports a configuration error by its code alone', function (): void {
    Exceptions::fake();
    pushTicketFor($this->phone, 'bad-key');
    expoReceipts(['bad-key' => ['status' => 'error', 'message' => 'credentials for ' . $this->phone->token, 'details' => ['error' => 'InvalidCredentials']]]);

    artisan('push:check-receipts')->assertSuccessful();

    Exceptions::assertReported(fn (PushConfigurationException $exception): bool => str_contains($exception->getMessage(), 'InvalidCredentials')
        && ! str_contains($exception->getMessage(), $this->phone->token));
    expect(PushTicket::query()->count())->toBe(0)
        ->and(PushToken::query()->count())->toBe(1);
});

it('keeps a phone the app registered again after the push was sent', function (): void {
    pushTicketFor($this->phone, 'reinstalled', minutesAgo: 20);
    $this->phone->touch();
    expoReceipts(['reinstalled' => ['status' => 'error', 'details' => ['error' => 'DeviceNotRegistered']]]);

    artisan('push:check-receipts')->assertSuccessful();

    expect(PushToken::query()->count())->toBe(1)
        ->and(PushTicket::query()->count())->toBe(0);
});

it('keeps a phone registered again in the same second the push was sent', function (): void {
    $this->freezeTime();
    pushTicketFor($this->phone, 'same-second', minutesAgo: 20);
    PushToken::query()->whereKey($this->phone->getKey())->update(['updated_at' => now()->subMinutes(20)]);
    expoReceipts(['same-second' => ['status' => 'error', 'details' => ['error' => 'DeviceNotRegistered']]]);

    artisan('push:check-receipts')->assertSuccessful();

    expect(PushToken::query()->count())->toBe(1);
});

it('waits for receipts that are not ready yet', function (): void {
    pushTicketFor($this->phone, 'fresh', minutesAgo: 5);
    pushTicketFor($this->phone, 'pending');
    expoReceipts([]);

    artisan('push:check-receipts')->assertSuccessful();

    Http::assertSent(fn (Request $request): bool => $request->data() === ['ids' => ['pending']]);
    expect(PushTicket::query()->pluck('id')->sort()->values()->all())->toBe(['fresh', 'pending']);
});

it('drops tickets the Expo has already forgotten', function (): void {
    pushTicketFor($this->phone, 'old', minutesAgo: 60 * 25);
    Http::fake();

    artisan('push:check-receipts')->assertSuccessful();

    Http::assertNothingSent();
    expect(PushTicket::query()->count())->toBe(0);
});

<?php

declare(strict_types=1);

use App\Models\Users\Detail;
use App\Models\Users\User;
use App\Support\PostHog\CapturePostHogEvent;
use App\Support\PostHog\PostHog;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function (): void {
    config(['services.posthog.key' => 'phc_test', 'services.posthog.host' => 'https://us.i.posthog.com']);
    Http::fake(['us.i.posthog.com/*' => Http::response(['status' => 'Ok'])]);
});

it('sends the event to the PostHog capture endpoint marked as coming from the Flamma', function (): void {
    $this->travelTo(now()->setDateTime(2026, 11, 12, 10, 0));

    resolve(PostHog::class)->capture('user-uuid', 'cadastro_concluido', ['utm_source' => 'flare']);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://us.i.posthog.com/i/v0/e/'
        && $request['api_key'] === 'phc_test'
        && $request['event'] === 'cadastro_concluido'
        && $request['distinct_id'] === 'user-uuid'
        && $request['properties'] === ['utm_source' => 'flare', 'source' => 'flamma', '$geoip_disable' => true]
        && $request['timestamp'] === now()->toIso8601String()
        && Str::isUuid($request['uuid']));
});

it('sends nothing without a key', function (?string $key): void {
    config(['services.posthog.key' => $key]);
    Queue::fake();

    resolve(PostHog::class)->capture('user-uuid', 'cadastro_concluido');

    Queue::assertNothingPushed();
    Http::assertNothingSent();
})->with(['null' => null, 'empty' => '']);

it('keeps the moment and the uuid of the capture when the job runs later or again', function (): void {
    Queue::fake();
    $this->travelTo(now()->setDateTime(2026, 11, 12, 10, 0));
    $capturedAt = now()->toIso8601String();

    resolve(PostHog::class)->capture('user-uuid', 'cadastro_concluido');

    $job = Queue::pushed(CapturePostHogEvent::class)->sole();
    $this->travel(2)->hours();
    $job->handle();
    $job->handle();

    $sent = Http::recorded()->map(fn (array $pair): array => [$pair[0]['timestamp'], $pair[0]['uuid']])->all();
    expect($sent)->toBe([[$capturedAt, $job->uuid], [$capturedAt, $job->uuid]]);
});

it('fails the job on an HTTP error so the queue retries it', function (): void {
    config(['services.posthog.host' => 'https://eu.i.posthog.com']);
    Http::fake(['eu.i.posthog.com/*' => Http::response('unavailable', 503)]);
    $job = new CapturePostHogEvent('user-uuid', 'cadastro_concluido', ['source' => 'flamma'], now()->toIso8601String(), (string) Str::uuid());

    expect(fn () => $job->handle())->toThrow(RequestException::class);
});

it('drops a queued event when the key was removed before it ran', function (): void {
    $job = new CapturePostHogEvent('user-uuid', 'cadastro_concluido', ['source' => 'flamma'], now()->toIso8601String(), (string) Str::uuid());
    config(['services.posthog.key' => null]);

    $job->handle();

    Http::assertNothingSent();
});

it('merges an anonymous id into the user with the alias event', function (): void {
    resolve(PostHog::class)->alias('anonymous-id', 'user-uuid');

    Http::assertSent(fn (Request $request): bool => $request['event'] === '$create_alias'
        && $request['distinct_id'] === 'user-uuid'
        && $request['properties'] === ['alias' => 'anonymous-id', 'source' => 'flamma', '$geoip_disable' => true]);
});

it('sends person properties nested in $set', function (): void {
    resolve(PostHog::class)->capture('user-uuid', 'cadastro_concluido', ['$set' => ['email' => 'ana.teste@example.com', 'name' => 'Ana Teste']]);

    Http::assertSent(fn (Request $request): bool => $request['properties'] === [
        '$set' => ['email' => 'ana.teste@example.com', 'name' => 'Ana Teste'],
        'source' => 'flamma',
        '$geoip_disable' => true,
    ]);
});

it('builds the person properties with the phone in E.164', function (?string $phone, ?string $expected): void {
    $user = User::factory()->create(['name' => 'Ana Teste', 'email' => 'ana.teste@example.com']);
    Detail::factory()->for($user)->create(['phone_number' => $phone]);

    expect(PostHog::personProperties($user))->toBe(array_filter(['email' => 'ana.teste@example.com', 'name' => 'Ana Teste', 'phone' => $expected]));
})->with([
    'E.164' => ['+5511900000001', '+5511900000001'],
    'digits only' => ['11900000001', '+5511900000001'],
    'masked' => ['(11) 90000-0001', '+5511900000001'],
    'invalid' => ['12345', null],
    'empty' => [null, null],
]);

it('builds the person properties of a user without details', function (): void {
    $user = User::factory()->create(['name' => 'Ana Teste', 'email' => 'ana.teste@example.com']);

    expect(PostHog::personProperties($user))->toBe(['email' => 'ana.teste@example.com', 'name' => 'Ana Teste']);
});

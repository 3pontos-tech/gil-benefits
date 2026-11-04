<?php

declare(strict_types=1);

use TresPontosTech\Api\Models\PasswordResetCode;

use function Pest\Laravel\postJson;

it('exposes exactly the keys the app reads from the code check', function (): void {
    config()->set('api.password_reset.timebox_ms', 0);
    $user = standaloneSubscriber();
    PasswordResetCode::factory()->withCode('123456')->for($user)->create();

    $response = postJson(route('api.v1.auth.password.verify'), ['email' => $user->email, 'code' => '123456'])
        ->assertOk();

    expect(array_keys($response->json()))->toBe(['data'])
        ->and(array_keys($response->json('data')))->toBe(['reset_token', 'expires_at'])
        ->and($response->json('data.reset_token'))->toBeString()->toHaveLength(64)
        ->and($response->json('data.expires_at'))->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
});

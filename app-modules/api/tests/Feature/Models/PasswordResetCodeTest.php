<?php

declare(strict_types=1);

use TresPontosTech\Api\Models\PasswordResetCode;

use function Pest\Laravel\artisan;

it('prunes only the rows that no longer hold a code, a token or account errors', function (): void {
    $empty = PasswordResetCode::factory()->create();
    $expiredCode = PasswordResetCode::factory()->create(['code_hash' => str_repeat('a', 64), 'code_expires_at' => now()->subMinute()]);
    $expiredToken = PasswordResetCode::factory()->create(['reset_token_hash' => str_repeat('b', 64), 'reset_token_expires_at' => now()->subMinute()]);
    $activeCode = PasswordResetCode::factory()->withCode()->create();
    $activeToken = PasswordResetCode::factory()->withResetToken(str_repeat('c', 64))->create();
    $withErrors = PasswordResetCode::factory()->create(['failed_attempts' => 3]);

    artisan('model:prune', ['--model' => [PasswordResetCode::class]])->assertSuccessful();

    expect(PasswordResetCode::query()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$activeCode->id, $activeToken->id, $withErrors->id])->sort()->values()->all())
        ->and(PasswordResetCode::query()->whereKey([$empty->id, $expiredCode->id, $expiredToken->id])->exists())->toBeFalse();
});

<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Api\Models\PasswordResetCode;
use TresPontosTech\Api\Support\PasswordResetSecret;

/**
 * @extends Factory<PasswordResetCode>
 */
class PasswordResetCodeFactory extends Factory
{
    protected $model = PasswordResetCode::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'code_hash' => null,
            'code_attempts' => 0,
            'failed_attempts' => 0,
            'code_sent_at' => null,
            'code_expires_at' => null,
            'reset_token_hash' => null,
            'reset_token_expires_at' => null,
        ];
    }

    /**
     * Código enviado agora, dentro da validade.
     */
    public function withCode(string $code = '123456'): self
    {
        return $this->state(fn (): array => [
            'code_hash' => PasswordResetSecret::hash($code),
            'code_sent_at' => now(),
            'code_expires_at' => now()->addMinutes((int) config('api.password_reset.code_ttl_minutes')),
        ]);
    }

    /**
     * Código já conferido, trocado por um token dentro da validade.
     */
    public function withResetToken(string $token): self
    {
        return $this->state(fn (): array => [
            'reset_token_hash' => PasswordResetSecret::hash($token),
            'reset_token_expires_at' => now()->addMinutes((int) config('api.password_reset.reset_token_ttl_minutes')),
        ]);
    }

    public function lockedOut(): self
    {
        return $this->state(fn (): array => [
            'failed_attempts' => (int) config('api.password_reset.max_failed_attempts_per_account'),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Api\Enums\PushPlatform;
use TresPontosTech\Api\Enums\TokenAbility;
use TresPontosTech\Api\Models\PushToken;

/**
 * @extends Factory<PushToken>
 */
class PushTokenFactory extends Factory
{
    protected $model = PushToken::class;

    /**
     * Um aparelho com sessão própria no app: cada token de push nasce de um token de acesso.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'personal_access_token_id' => fn (array $attributes): int => User::query()
                ->whereKey($attributes['user_id'])
                ->firstOrFail()
                ->createToken('Android', [TokenAbility::Employee->value])
                ->accessToken
                ->getKey(),
            'token' => 'ExponentPushToken[' . fake()->unique()->bothify('????????????????????##') . ']',
            'platform' => PushPlatform::Android,
        ];
    }
}

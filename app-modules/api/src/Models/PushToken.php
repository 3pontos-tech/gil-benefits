<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;
use TresPontosTech\Api\Database\Factories\PushTokenFactory;
use TresPontosTech\Api\Enums\PushPlatform;

/**
 * O endereço de push de uma sessão do app (`ExponentPushToken[...]`). Vive enquanto o token de
 * acesso da sessão viver: a FK leva os dois juntos. Também sai quando a Expo avisa que o
 * aparelho não existe mais (`DeviceNotRegistered`).
 *
 * @property string $id
 * @property string $user_id
 * @property int $personal_access_token_id
 * @property string $token
 * @property PushPlatform $platform
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read PersonalAccessToken $accessToken
 */
#[UseFactory(PushTokenFactory::class)]
final class PushToken extends Model
{
    /** @use HasFactory<PushTokenFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'push_tokens';

    protected $fillable = [
        'user_id',
        'personal_access_token_id',
        'token',
        'platform',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<PersonalAccessToken, $this>
     */
    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class, 'personal_access_token_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform' => PushPlatform::class,
        ];
    }
}

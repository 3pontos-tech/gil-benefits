<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\Employee\Me;

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use TresPontosTech\Api\Enums\PushPlatform;
use TresPontosTech\Api\Models\PushToken;

final readonly class RegisterPushTokenAction
{
    /**
     * Prende o token de push à sessão que o enviou. Se ele pertencia a outra pessoa ou a outra
     * sessão (o aparelho trocou de conta), muda de dono; e a sessão fica com um token só, então
     * um token antigo do mesmo aparelho sai.
     */
    public function handle(User $user, PersonalAccessToken $session, string $token, PushPlatform $platform): void
    {
        DB::transaction(function () use ($user, $session, $token, $platform): void {
            PushToken::query()
                ->where('personal_access_token_id', $session->getKey())
                ->where('token', '!=', $token)
                ->delete();

            PushToken::query()->updateOrCreate(['token' => $token], [
                'user_id' => $user->getKey(),
                'personal_access_token_id' => $session->getKey(),
                'platform' => $platform,
            ]);
        });
    }
}

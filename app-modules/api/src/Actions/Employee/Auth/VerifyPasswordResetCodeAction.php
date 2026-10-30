<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\Employee\Auth;

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Timebox;
use SensitiveParameter;
use TresPontosTech\Api\DTOs\Employee\Auth\PasswordResetGrant;
use TresPontosTech\Api\Exceptions\Employee\Auth\PasswordResetCodeException;
use TresPontosTech\Api\Models\PasswordResetCode;
use TresPontosTech\Api\Support\PasswordResetSecret;

final readonly class VerifyPasswordResetCodeAction
{
    public function __construct(private FindUserByEmailAction $findUserByEmail) {}

    /**
     * Toda falha dá o mesmo erro e leva o mesmo tempo mínimo; só o acerto responde antes, como no
     * login.
     *
     * @throws PasswordResetCodeException
     */
    public function handle(string $email, #[SensitiveParameter] string $code): PasswordResetGrant
    {
        return resolve(Timebox::class)->call(function (Timebox $timebox) use ($email, $code): PasswordResetGrant {
            $user = $this->findUserByEmail->handle($email);
            $grant = $user instanceof User ? $this->exchange($user, $code) : null;

            if (! $grant instanceof PasswordResetGrant) {
                throw PasswordResetCodeException::invalid();
            }

            $timebox->returnEarly();

            return $grant;
        }, (int) config('api.password_reset.timebox_ms') * 1000);
    }

    /**
     * A linha fica travada: sem isso, palpites em paralelo passam do limite, e dois acertos ao
     * mesmo tempo emitiriam dois tokens. O erro conta no código e na conta, e o último erro
     * permitido descarta o código. O acerto troca o código pelo token, mas não zera os erros da
     * conta: só um reset zera.
     *
     * Devolve null em vez de lançar a exceção, para o erro contado não ser desfeito no rollback.
     */
    private function exchange(User $user, #[SensitiveParameter] string $code): ?PasswordResetGrant
    {
        return DB::transaction(function () use ($user, $code): ?PasswordResetGrant {
            $row = PasswordResetCode::query()->where('user_id', $user->getKey())->lockForUpdate()->first();
            $now = now();

            if (! $row instanceof PasswordResetCode || $row->isLockedOut() || ! $row->hasActiveCode($now)) {
                return null;
            }

            if (! PasswordResetSecret::matches($row->code_hash, $code)) {
                $attempts = $row->code_attempts + 1;
                $exhausted = $attempts >= (int) config('api.password_reset.max_attempts_per_code');

                $row->update([
                    'code_attempts' => $attempts,
                    'failed_attempts' => $row->failed_attempts + 1,
                    'code_hash' => $exhausted ? null : $row->code_hash,
                    'code_expires_at' => $exhausted ? null : $row->code_expires_at,
                ]);

                return null;
            }

            $token = PasswordResetSecret::token();
            $expiresAt = $now->copy()->addMinutes((int) config('api.password_reset.reset_token_ttl_minutes'));

            $row->update([
                'code_hash' => null,
                'code_attempts' => 0,
                'code_expires_at' => null,
                'reset_token_hash' => PasswordResetSecret::hash($token),
                'reset_token_expires_at' => $expiresAt,
            ]);

            return new PasswordResetGrant($token, $expiresAt);
        });
    }
}

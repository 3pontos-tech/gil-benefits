<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\Employee\Auth;

use App\Models\Users\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Timebox;
use SensitiveParameter;
use TresPontosTech\Api\Exceptions\Employee\Auth\PasswordResetTokenException;
use TresPontosTech\Api\Models\PasswordResetCode;
use TresPontosTech\Api\Support\PasswordResetSecret;
use TresPontosTech\User\Actions\ChangeUserPasswordAction;

final readonly class ResetPasswordWithCodeAction
{
    public function __construct(
        private FindUserByEmailAction $findUserByEmail,
        private ChangeUserPasswordAction $changeUserPassword,
    ) {}

    /**
     * Troca a senha com o token do `verify` e desconecta tudo. Os tokens do app saem pela
     * ChangeUserPasswordAction, sem aparelho a manter. O `remember_token` novo, gravado junto com a
     * senha, derruba o "lembrar de mim" do painel, como no reset do Laravel, e apagar o token do broker invalida um link do site
     * enviado antes. O `PasswordReset` dispara os mesmos listeners do reset pelo site: sessões web,
     * código pendente e o e-mail de senha alterada.
     *
     * Toda falha dá o mesmo erro no mesmo tempo mínimo, como o `verify`.
     *
     * @throws PasswordResetTokenException
     */
    public function handle(string $email, #[SensitiveParameter] string $resetToken, #[SensitiveParameter] string $password): void
    {
        resolve(Timebox::class)->call(function (Timebox $timebox) use ($email, $resetToken, $password): void {
            $user = $this->findUserByEmail->handle($email);

            if (! $user instanceof User) {
                throw PasswordResetTokenException::expired();
            }

            DB::transaction(function () use ($user, $resetToken, $password): void {
                $row = PasswordResetCode::query()->where('user_id', $user->getKey())->lockForUpdate()->first();

                if (
                    ! $row instanceof PasswordResetCode
                    || ! $row->hasActiveResetToken(now())
                    || ! PasswordResetSecret::matches($row->reset_token_hash, $resetToken)
                ) {
                    throw PasswordResetTokenException::expired();
                }

                $user->setRememberToken(Str::random(60));
                $this->changeUserPassword->handle($user, $password);

                $row->delete();
                Password::broker()->deleteToken($user);

                event(new PasswordReset($user));
            });

            $timebox->returnEarly();
        }, (int) config('api.password_reset.timebox_ms') * 1000);
    }
}

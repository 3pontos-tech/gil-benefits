<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\Employee\Auth;

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Timebox;
use TresPontosTech\Api\Mail\PasswordResetCodeMail;
use TresPontosTech\Api\Models\PasswordResetCode;
use TresPontosTech\Api\Support\PasswordResetSecret;

final readonly class RequestPasswordResetCodeAction
{
    public function __construct(
        private FindUserByEmailAction $findUserByEmail,
        private ResolveAppAccessAction $resolveAppAccess,
    ) {}

    /**
     * Só quem tem acesso ao app recebe um código; consultor e quem não tem o benefício seguem pelo
     * link do site. Conta bloqueada por erros e pedido antes da espera do reenvio também não recebem
     * nada. Quem chama responde igual em todos os casos.
     *
     * Por isso tudo roda num tempo fixo, sem retorno antecipado: só uma conta real consulta o
     * acesso, grava e calcula hash, e responderia mais devagar. O e-mail vai para a fila e sai
     * depois da resposta.
     */
    public function handle(string $email): void
    {
        resolve(Timebox::class)->call(function () use ($email): void {
            $user = $this->findUserByEmail->handle($email);

            if (! $user instanceof User || ! $this->resolveAppAccess->handle($user)->isGranted()) {
                return;
            }

            $code = $this->issueCode($user);

            if ($code !== null) {
                Mail::to($user)->queue(new PasswordResetCodeMail($code));
            }
        }, (int) config('api.password_reset.timebox_ms') * 1000);
    }

    /**
     * Um código ativo por pessoa: o novo substitui o anterior e descarta um token ainda não usado.
     * As tentativas do código zeram; os erros da conta, não. A linha fica travada para dois pedidos
     * ao mesmo tempo não emitirem dois códigos nem passarem da espera do reenvio.
     */
    private function issueCode(User $user): ?string
    {
        PasswordResetCode::query()->createOrFirst(['user_id' => $user->getKey()]);

        return DB::transaction(function () use ($user): ?string {
            $row = PasswordResetCode::query()->where('user_id', $user->getKey())->lockForUpdate()->firstOrFail();
            $now = now();

            if ($row->isLockedOut() || ! $row->canSendCode($now)) {
                return null;
            }

            $code = PasswordResetSecret::code();

            $row->update([
                'code_hash' => PasswordResetSecret::hash($code),
                'code_attempts' => 0,
                'code_sent_at' => $now,
                'code_expires_at' => $now->copy()->addMinutes((int) config('api.password_reset.code_ttl_minutes')),
                'reset_token_hash' => null,
                'reset_token_expires_at' => null,
            ]);

            return $code;
        });
    }
}

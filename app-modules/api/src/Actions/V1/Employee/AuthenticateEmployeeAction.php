<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\V1\Employee;

use App\Models\Users\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Timebox;
use TresPontosTech\Api\DTOs\EmployeeSession;
use TresPontosTech\Api\Exceptions\EmployeeLoginException;
use TresPontosTech\User\Support\EmailAddress;

final readonly class AuthenticateEmployeeAction
{
    public function __construct(private ResolveEmployeeCompanyAction $resolveCompany) {}

    /**
     * Troca e-mail e senha por um token do aparelho.
     *
     * Entra quem é público do app (User::canUseApp()) e tem o benefício pago em alguma empresa,
     * pela mesma régua do painel; a empresa escolhida vale para o perfil que o login devolve.
     *
     * Cada aparelho guarda um token só: entrar de novo com o mesmo `device_name` substitui
     * o anterior. O token vale `api.token_ttl_days` e carrega a ability `employee`. O evento
     * `Login` alimenta o `RecordLastLogin`, como no login do painel.
     *
     * @throws EmployeeLoginException
     */
    public function handle(string $email, string $password, string $deviceName): EmployeeSession
    {
        $user = $this->verifyCredentials($email, $password);

        if (! $user->canUseApp()) {
            throw EmployeeLoginException::noAccess();
        }

        $access = $this->resolveCompany->handle($user);

        if (! $access->isGranted()) {
            throw EmployeeLoginException::withoutBenefit($access);
        }

        $user->tokens()->where('name', $deviceName)->delete();

        $token = $user->createToken(
            $deviceName,
            ['employee'],
            now()->addDays((int) config('api.token_ttl_days')),
        );

        event(new Login('sanctum', $user, false));

        return new EmployeeSession($user->refresh(), $token->plainTextToken);
    }

    /**
     * Confere e-mail e senha num tempo mínimo fixo, o mesmo do login do Filament: e-mail
     * inexistente não roda o hash e responderia mais rápido, entregando quais contas existem. Só o
     * acerto devolve antes do tempo. São 200 ms; `auth.timebox_duration` não está definida no
     * projeto e só muda isso se for criada.
     *
     * @throws EmployeeLoginException
     */
    private function verifyCredentials(string $email, string $password): User
    {
        return resolve(Timebox::class)->call(function (Timebox $timebox) use ($email, $password): User {
            $user = $this->findByEmail($email);

            if (! $user instanceof User || ! Hash::check($password, $user->password)) {
                throw EmployeeLoginException::invalidCredentials();
            }

            $timebox->returnEarly();

            return $user;
        }, (int) config('auth.timebox_duration', 200_000));
    }

    /**
     * O e-mail é gravado em minúsculas (#291), então a busca normaliza a entrada e compara
     * direto, usando o índice único da coluna.
     */
    private function findByEmail(string $email): ?User
    {
        return User::query()->where('email', EmailAddress::normalize($email))->first();
    }
}

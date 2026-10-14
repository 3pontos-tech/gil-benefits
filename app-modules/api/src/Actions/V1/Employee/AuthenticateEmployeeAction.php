<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\V1\Employee;

use App\Models\Users\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Timebox;
use TresPontosTech\Api\DTOs\EmployeeSession;
use TresPontosTech\Api\Exceptions\EmployeeLoginException;
use TresPontosTech\User\Support\EmailAddress;

final readonly class AuthenticateEmployeeAction
{
    public function __construct(private ResolveAppAccessAction $resolveAppAccess) {}

    /**
     * Troca e-mail e senha por um token do aparelho.
     *
     * Quem entra é decidido pela ResolveAppAccessAction; o login fixa a empresa liberada como o
     * EnsureAppAccess faz nas outras rotas, porque o perfil que ele devolve já é dessa empresa.
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

        $access = $this->resolveAppAccess->handle($user);

        if (! $access->isGranted()) {
            throw EmployeeLoginException::denied($access);
        }

        Filament::setTenant($access->company, isQuiet: true);

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

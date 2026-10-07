<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\V1\Employee;

use App\Models\Users\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Hash;
use TresPontosTech\Api\DTOs\EmployeeSession;
use TresPontosTech\Api\Exceptions\EmployeeLoginException;

final readonly class AuthenticateEmployeeAction
{
    /**
     * Troca e-mail e senha por um token do aparelho.
     *
     * Cada aparelho guarda um token só: entrar de novo com o mesmo `device_name` substitui
     * o anterior. O token vale `api.token_ttl_days` e carrega a ability `employee`. O evento
     * `Login` alimenta o `RecordLastLogin`, como no login do painel.
     *
     * @throws EmployeeLoginException
     */
    public function handle(string $email, string $password, string $deviceName): EmployeeSession
    {
        $user = $this->findByEmail($email);

        if (! $user instanceof User || ! Hash::check($password, $user->password)) {
            throw EmployeeLoginException::invalidCredentials();
        }

        if (! $user->canUseApp()) {
            throw EmployeeLoginException::noAccess();
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
     * Busca sem diferenciar maiúsculas: o cadastro do painel e a API de tenant gravam o
     * e-mail como foi digitado, mas o app envia sempre em minúsculas.
     */
    private function findByEmail(string $email): ?User
    {
        return User::query()
            ->whereRaw('lower(email) = ?', [mb_strtolower($email)])
            ->first();
    }
}

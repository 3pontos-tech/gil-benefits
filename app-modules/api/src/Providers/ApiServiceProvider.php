<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Providers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/api.php', 'api');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'api');

        $this->registerRateLimiters();
        $this->registerErrorResponses();
    }

    /**
     * Limites por público (A10). O colaborador conta por usuário: a autenticação roda antes do
     * throttle, então toda requisição que chega aqui já tem dono.
     *
     * O login tem dois tetos. Por e-mail + IP, para uma conta não travar um escritório inteiro
     * atrás do mesmo NAT; e por IP, mais alto, para um IP não testar senhas em e-mails diferentes
     * sem limite (o painel limita por IP).
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('api-employee', fn (Request $request): Limit => Limit::perMinute((int) config('api.rate_limits.employee_per_minute'))
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip()));

        RateLimiter::for('api-login', function (Request $request): array {
            $email = $request->input('email');

            return [
                Limit::perMinute((int) config('api.rate_limits.login_per_minute'))
                    ->by('email:' . (is_string($email) ? Str::lower($email) : '') . '|' . $request->ip()),
                Limit::perMinute((int) config('api.rate_limits.login_per_minute_per_ip'))
                    ->by('ip:' . $request->ip()),
            ];
        });
    }

    /**
     * Erros fora do 422 da API do colaborador, em pt_BR e sem detalhe interno: o 404 do Laravel
     * traz o nome da classe do model, e 403, 405 e 429 saem em inglês. Um 403 com motivo próprio
     * (EnsureAppAccess, remoção de material) mantém a mensagem. A API da empresa segue como está.
     */
    private function registerErrorResponses(): void
    {
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! $handler instanceof Handler) {
                return;
            }

            $handler->renderable(fn (HttpException $exception, Request $request): ?JsonResponse => $this->replacesEmployeeError($exception, $request)
                ? response()->json(['message' => $this->employeeErrorMessage($exception)], $exception->getStatusCode(), $exception->getHeaders())
                : null);
        });
    }

    private function replacesEmployeeError(HttpException $exception, Request $request): bool
    {
        return $request->is('api/v1/*')
            && ! $request->is('api/v1/company/*')
            && in_array($exception->getStatusCode(), [
                Response::HTTP_FORBIDDEN,
                Response::HTTP_NOT_FOUND,
                Response::HTTP_METHOD_NOT_ALLOWED,
                Response::HTTP_TOO_MANY_REQUESTS,
            ], true);
    }

    private function employeeErrorMessage(HttpException $exception): string
    {
        $previous = $exception->getPrevious();

        return match ($exception->getStatusCode()) {
            Response::HTTP_NOT_FOUND => __('api::errors.not_found'),
            Response::HTTP_METHOD_NOT_ALLOWED => __('api::errors.method_not_allowed'),
            Response::HTTP_TOO_MANY_REQUESTS => __('api::errors.too_many_requests'),
            default => match (true) {
                $previous instanceof MissingAbilityException => __('api::auth.no_access'),
                $previous instanceof AuthorizationException, blank($exception->getMessage()) => __('api::errors.forbidden'),
                default => $exception->getMessage(),
            },
        };
    }
}

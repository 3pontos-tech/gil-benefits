<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Support;

use Illuminate\Support\Facades\Cache;
use Prism\Prism\Enums\Provider;

/**
 * Circuit breaker simples por (provider, model) para as chamadas de IA.
 *
 * O estado é persistido via Cache, usando uma chave padronizada
 * `{keyPrefix}:{provider}:{model}`. Quando uma tentativa de geração falha de forma
 * transitória (rate limit, provider sobrecarregado), a Action responsável abre o
 * breaker chamando `open(...)`. Próximas tentativas para o mesmo par ficam pulando
 * esse candidato até o TTL expirar (`$cooldownMinutes`).
 */
final readonly class AiCircuitBreaker
{
    public function __construct(
        private int $cooldownMinutes,
        private string $keyPrefix = 'appointments:ai:cb',
    ) {}

    public function isOpen(Provider $provider, string $model): bool
    {
        return Cache::has($this->keyFor($provider, $model));
    }

    /**
     * Abre o breaker para o par e retorna a chave de cache utilizada, para que o
     * chamador possa logar o identificador do breaker aberto.
     */
    public function open(Provider $provider, string $model): string
    {
        $key = $this->keyFor($provider, $model);
        Cache::put($key, true, now()->addMinutes($this->cooldownMinutes));

        return $key;
    }

    public function keyFor(Provider $provider, string $model): string
    {
        return sprintf('%s:%s:%s', $this->keyPrefix, $provider->value, $model);
    }

    public function cooldownMinutes(): int
    {
        return $this->cooldownMinutes;
    }
}

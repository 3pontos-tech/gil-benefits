<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Financial\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;

/**
 * Chave e TTL de cache das Actions financeiras (FLM-41, D-14).
 *
 * Mesmo formato do `BuildsEngagementCacheKey`, com um acréscimo: o método de
 * invalidação por bloco, que existe porque o cockpit expõe um botão de
 * recalcular (D-12). Sem ele, o Fernando lança algo e fica cinco minutos
 * achando que o painel travou.
 */
trait BuildsFinancialCacheKey
{
    private const int CACHE_TTL_MINUTES = 5;

    private function financialCacheKey(string $bucket, FinancialFilters $filters): string
    {
        return implode('.', [
            'panel_admin.financial',
            $bucket,
            $filters->fingerprint(),
        ]);
    }

    private function financialCacheTtl(): CarbonInterface
    {
        return now()->addMinutes(self::CACHE_TTL_MINUTES);
    }

    /**
     * Descarta o bloco pedido para o recálculo sob demanda.
     *
     * Apaga só a chave daqueles filtros: recalcular o mês corrente não deve
     * jogar fora o que já foi computado para os outros meses.
     */
    private function forgetFinancialCache(string $bucket, FinancialFilters $filters): void
    {
        Cache::forget($this->financialCacheKey($bucket, $filters));
    }
}

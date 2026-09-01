<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use TresPontosTech\PanelAdmin\Actions\Financial\Concerns\BuildsFinancialCacheKey;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;

/**
 * Os métodos do trait são privados por design — as Actions são as únicas
 * consumidoras. Este host expõe o mínimo para travar o comportamento.
 */
function cacheKeyHost(): object
{
    return new class
    {
        use BuildsFinancialCacheKey;

        public function key(string $bucket, FinancialFilters $filters): string
        {
            return $this->financialCacheKey($bucket, $filters);
        }

        public function forget(string $bucket, FinancialFilters $filters): void
        {
            $this->forgetFinancialCache($bucket, $filters);
        }

        public function ttlInMinutes(): int
        {
            return (int) round(now()->diffInMinutes($this->financialCacheTtl()));
        }
    };
}

beforeEach(function (): void {
    Cache::flush();
    $this->host = cacheKeyHost();
    $this->filters = FinancialFilters::fromPageFilters(['month' => '2026-08']);
});

it('usa o namespace do cockpit na chave', function (): void {
    expect($this->host->key('revenue_kpis', $this->filters))->toStartWith('panel_admin.financial.revenue_kpis.');
});

it('separa blocos diferentes dos mesmos filtros', function (): void {
    expect($this->host->key('revenue_kpis', $this->filters))
        ->not->toBe($this->host->key('payment_totals', $this->filters));
});

it('separa o mesmo bloco em meses diferentes', function (): void {
    $julho = FinancialFilters::fromPageFilters(['month' => '2026-07']);

    expect($this->host->key('revenue_kpis', $this->filters))
        ->not->toBe($this->host->key('revenue_kpis', $julho));
});

it('expira em 5 minutos, como o resto dos relatórios', function (): void {
    expect($this->host->ttlInMinutes())->toBe(5);
});

it('descarta só o bloco pedido ao recalcular', function (): void {
    $kpis = $this->host->key('revenue_kpis', $this->filters);
    $payments = $this->host->key('payment_totals', $this->filters);

    Cache::put($kpis, 'antigo', 300);
    Cache::put($payments, 'intacto', 300);

    $this->host->forget('revenue_kpis', $this->filters);

    expect(Cache::has($kpis))->toBeFalse()
        ->and(Cache::get($payments))->toBe('intacto');
});

it('não descarta o mesmo bloco de outro mês', function (): void {
    $julho = FinancialFilters::fromPageFilters(['month' => '2026-07']);
    $chaveJulho = $this->host->key('revenue_kpis', $julho);

    Cache::put($chaveJulho, 'intacto', 300);
    $this->host->forget('revenue_kpis', $this->filters);

    expect(Cache::get($chaveJulho))->toBe('intacto');
});

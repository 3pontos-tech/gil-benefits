<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

/**
 * Um alerta de cobrança (STORY-237).
 *
 * O terceiro alerta da story — "duas cobranças recusadas consecutivas" — virou
 * alerta de inadimplência: a Virtu não reporta recusa, então uma renovação que
 * falha aparece como assinatura inadimplente e não como cobrança negada (D-04).
 */
final readonly class BillingAlert
{
    /**
     * @param  Collection<int, ContractRow>  $companies
     */
    public function __construct(
        public string $key,
        public string $severity,
        public Heroicon $icon,
        public Collection $companies,
        public int $totalCents,
        public bool $isEstimatedDate = false,
    ) {}

    public function count(): int
    {
        return $this->companies->count();
    }

    public function isEmpty(): bool
    {
        return $this->companies->isEmpty();
    }
}

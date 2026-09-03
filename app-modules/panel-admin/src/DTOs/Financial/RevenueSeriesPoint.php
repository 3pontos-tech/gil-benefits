<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

/**
 * Um ponto do gráfico de evolução de receita (STORY-231).
 */
final readonly class RevenueSeriesPoint
{
    public function __construct(
        public string $label,
        public int $totalCents,
        public int $b2bCents,
        public int $standaloneCents,
        public ?float $variation,
        public bool $isReconstructed,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'total_cents' => $this->totalCents,
            'b2b_cents' => $this->b2bCents,
            'standalone_cents' => $this->standaloneCents,
            'variation' => $this->variation,
            'is_reconstructed' => $this->isReconstructed,
        ];
    }
}

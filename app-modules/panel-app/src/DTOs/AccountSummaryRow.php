<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\DTOs;

/**
 * Uma linha do card "Sua conta": ícone, rótulo e o estado atual do dado.
 */
final readonly class AccountSummaryRow
{
    /**
     * @param  bool|null  $isPositive  `null` marca a linha como informativa (sem
     *                                 ícone de estado à direita).
     */
    public function __construct(
        public string $icon,
        public string $label,
        public string $status,
        public ?bool $isPositive = null,
    ) {}
}

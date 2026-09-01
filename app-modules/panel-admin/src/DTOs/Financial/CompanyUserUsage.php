<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

/**
 * Um colaborador no detalhe de utilização da empresa (STORY-241, cenário 3).
 */
final readonly class CompanyUserUsage
{
    public function __construct(
        public string $name,
        public string $email,
        public string $statusLabel,
        public string $statusColor,
    ) {}
}

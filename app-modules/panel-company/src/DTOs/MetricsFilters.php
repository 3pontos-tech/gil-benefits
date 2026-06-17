<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

/**
 * Optional scope applied to a metrics query: a single employee or a whole
 * department. Carries a stable cache key fragment.
 */
final readonly class MetricsFilters
{
    public function __construct(
        public ?string $userId = null,
        public ?string $departmentId = null,
    ) {}

    public static function none(): self
    {
        return new self;
    }

    public function isScoped(): bool
    {
        return filled($this->userId) || filled($this->departmentId);
    }

    public function cacheKey(): string
    {
        return match (true) {
            filled($this->userId) => 'u:' . $this->userId,
            filled($this->departmentId) => 'd:' . $this->departmentId,
            default => 'all',
        };
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs;

use Carbon\CarbonImmutable;

/**
 * Normalised filter state of the engagement report page: the analysed period
 * and, optionally, the companies the report is narrowed down to.
 */
final readonly class EngagementFilters
{
    /**
     * @param  array<int, string>  $companyIds  Empty means "every company".
     */
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
        public array $companyIds = [],
    ) {}

    /**
     * @param  array<string, mixed>|null  $pageFilters
     */
    public static function fromPageFilters(?array $pageFilters): self
    {
        $startDate = data_get($pageFilters, 'startDate');
        $endDate = data_get($pageFilters, 'endDate');

        $start = filled($startDate)
            ? CarbonImmutable::parse($startDate)->startOfDay()
            : CarbonImmutable::now()->subDays(30)->startOfDay();

        $end = filled($endDate)
            ? CarbonImmutable::parse($endDate)->endOfDay()
            : CarbonImmutable::now()->endOfDay();

        /** @var array<int, string> $companyIds */
        $companyIds = array_values(array_filter(
            (array) data_get($pageFilters, 'companies', []),
            fn (mixed $id): bool => filled($id),
        ));

        return new self(
            start: $start,
            end: $end->lessThan($start) ? $start->endOfDay() : $end,
            companyIds: $companyIds,
        );
    }

    /**
     * Stable fingerprint of the filters, used as a cache key segment.
     */
    public function fingerprint(): string
    {
        $companyIds = $this->companyIds;
        sort($companyIds);

        return md5(implode('|', [
            $this->start->toDateTimeString(),
            $this->end->toDateTimeString(),
            ...$companyIds,
        ]));
    }
}

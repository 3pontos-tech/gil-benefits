<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs;

use Carbon\CarbonImmutable;
use TresPontosTech\PanelAdmin\Support\EngagementNumber;

/**
 * Weekly bucket comparing booked consultancies against the ones actually held.
 */
final readonly class EngagementWeek
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
        public int $scheduled,
        public int $completed,
    ) {}

    public function completionRate(): ?float
    {
        return EngagementNumber::rate($this->completed, $this->scheduled);
    }

    /**
     * Human label of the bucket, e.g. "28/07 – 03/08".
     */
    public function label(): string
    {
        return sprintf('%s – %s', $this->start->format('d/m'), $this->end->format('d/m'));
    }

    /**
     * Row shape consumed by the weekly table widget.
     *
     * @return array{week: string, starts_at: string, scheduled: int, completed: int, completion_rate: float|null}
     */
    public function toArray(): array
    {
        return [
            'week' => $this->label(),
            'starts_at' => $this->start->toDateString(),
            'scheduled' => $this->scheduled,
            'completed' => $this->completed,
            'completion_rate' => $this->completionRate(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\App\DTOs;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Support\Htmlable;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\User\Enums\LifeMoment;

final readonly class UserJourney
{
    /**
     * @param  list<LifeMoment>  $stages
     * @param  list<AppointmentCategoryEnum>  $topicsCovered
     */
    public function __construct(
        public ?LifeMoment $stage,
        public ?int $stageIndex,
        public array $stages,
        public int $completedConsultations,
        public array $topicsCovered,
        public int $topicsTotal,
        public int $ratingsGiven,
        public int $pendingRatings,
        public ?CarbonInterface $lastConsultationAt,
    ) {}

    public function isOnboarded(): bool
    {
        return $this->stage instanceof LifeMoment;
    }

    public function stageLabel(): string|Htmlable|null
    {
        return $this->stage?->getLabel();
    }

    public function topicsCoveredCount(): int
    {
        return count($this->topicsCovered);
    }

    public function hasCovered(AppointmentCategoryEnum $category): bool
    {
        return in_array($category, $this->topicsCovered, true);
    }
}

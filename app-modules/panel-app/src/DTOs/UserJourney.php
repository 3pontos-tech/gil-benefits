<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\DTOs;

use Carbon\CarbonInterface;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\User\Enums\LifeMoment;

final readonly class UserJourney
{
    public const HEALTH_SCORE_MAX = 100;

    /**
     * @param  list<LifeMoment>  $stages
     * @param  list<AppointmentCategoryEnum>  $topicsCovered
     * @param  int  $healthScorePreviousMonth  Score recalculado com os dados que existiam
     *                                         na virada do mês, usado só para o delta.
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
        public int $completedThisMonth = 0,
        public int $topicsCoveredThisMonth = 0,
        public int $ratingsThisMonth = 0,
        public int $healthScore = 0,
        public int $healthScorePreviousMonth = 0,
    ) {}

    public function isOnboarded(): bool
    {
        return $this->stage instanceof LifeMoment;
    }

    public function healthScoreDelta(): int
    {
        return $this->healthScore - $this->healthScorePreviousMonth;
    }

    public function stageLabel(): ?string
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

<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Resources\V1\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use TresPontosTech\Api\DTOs\EmployeeJourney;
use TresPontosTech\Api\DTOs\FocusItem;
use TresPontosTech\Api\DTOs\HealthPoint;
use TresPontosTech\Api\Support\ApiDates;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\User\Enums\LifeMoment;

/**
 * @property EmployeeJourney $resource
 */
class JourneyResource extends JsonResource
{
    public function __construct(EmployeeJourney $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $journey = $this->resource->journey;
        $quarter = $this->resource->quarter;

        return [
            'stage' => $journey->stage?->value,
            'stage_index' => $journey->stageIndex,
            'stages' => array_map(fn (LifeMoment $stage): string => $stage->value, $journey->stages),
            'completed_consultations' => $journey->completedConsultations,
            'topics_covered' => array_map(fn (AppointmentCategoryEnum $topic): string => $topic->value, $journey->topicsCovered),
            'topics_total' => $journey->topicsTotal,
            'ratings_given' => $journey->ratingsGiven,
            'pending_ratings' => $journey->pendingRatings,
            'last_consultation_at' => ApiDates::dateTime($journey->lastConsultationAt),
            'completed_this_month' => $journey->completedThisMonth,
            'health_score' => $journey->healthScore,
            'health_score_previous_month' => $journey->healthScorePreviousMonth,
            'quarter' => [
                'completed' => $quarter->completed,
                'goal' => $quarter->goal,
                'starts_at' => ApiDates::date($quarter->startsAt),
                'ends_at' => ApiDates::date($quarter->endsAt),
            ],
            'health_history' => array_map(fn (HealthPoint $point): array => [
                'month' => $point->month,
                'score' => $point->score,
            ], $this->resource->healthHistory),
            'focus' => array_map(fn (FocusItem $item): array => [
                'label' => $item->label,
                'status_label' => $item->statusLabel,
                'tone' => $item->tone->value,
            ], $this->resource->focus),
        ];
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\V1\Employee;

use App\Models\Users\User;
use TresPontosTech\Api\DTOs\EmployeeJourney;
use TresPontosTech\Api\DTOs\FocusItem;
use TresPontosTech\Api\DTOs\HealthPoint;
use TresPontosTech\Api\DTOs\QuarterProgress;
use TresPontosTech\Api\Enums\FocusTone;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\PanelApp\Actions\BuildUserJourneyAction;
use TresPontosTech\PanelApp\DTOs\UserJourney;
use TresPontosTech\User\Enums\LifeMoment;

final readonly class BuildEmployeeJourneyAction
{
    private const int HISTORY_MONTHS = 6;

    public function __construct(private BuildUserJourneyAction $buildJourney) {}

    public function handle(User $user): EmployeeJourney
    {
        $journey = ($this->buildJourney)($user);

        return new EmployeeJourney(
            journey: $journey,
            quarter: $this->quarter($user),
            healthHistory: $this->healthHistory($user),
            focus: $this->focus($journey),
        );
    }

    /**
     * Encontros concluídos no trimestre civil corrente contra a meta do config.
     */
    private function quarter(User $user): QuarterProgress
    {
        $startsAt = now()->startOfQuarter();
        $endsAt = now()->endOfQuarter();

        return new QuarterProgress(
            completed: $user->appointments()
                ->where('status', AppointmentStatus::Completed->value)
                ->whereBetween('appointment_at', [$startsAt, $endsAt])
                ->count(),
            goal: (int) config('api.quarter_goal'),
            startsAt: $startsAt,
            endsAt: $endsAt,
        );
    }

    /**
     * @return list<HealthPoint>
     */
    private function healthHistory(User $user): array
    {
        $points = [];

        foreach ($this->buildJourney->monthlyHealthScores($user, self::HISTORY_MONTHS) as $month => $score) {
            $points[] = new HealthPoint((string) $month, $score);
        }

        return $points;
    }

    /**
     * "O que mais pesa agora": um item por componente do score, na mesma ordem de peso
     * (momento de vida, cobertura de assuntos, avaliações).
     *
     * @return list<FocusItem>
     */
    private function focus(UserJourney $journey): array
    {
        return [
            $this->lifeMomentFocus($journey->stage),
            $this->topicsFocus($journey->topicsCoveredCount(), $journey->topicsTotal),
            $this->ratingsFocus($journey->pendingRatings),
        ];
    }

    private function lifeMomentFocus(?LifeMoment $stage): FocusItem
    {
        return new FocusItem(
            label: __('api::journey.focus.life_moment.label'),
            statusLabel: $stage?->getLabel() ?? __('api::journey.focus.life_moment.unknown'),
            tone: match ($stage) {
                LifeMoment::Endebted, LifeMoment::Messy => FocusTone::Warning,
                LifeMoment::Saver, LifeMoment::Investor => FocusTone::Success,
                LifeMoment::Payer, null => FocusTone::Info,
            },
        );
    }

    /**
     * Até um terço dos assuntos pede atenção; a partir de dois terços está bem coberto.
     */
    private function topicsFocus(int $covered, int $total): FocusItem
    {
        $ratio = $total > 0 ? $covered / $total : 0;

        return new FocusItem(
            label: __('api::journey.focus.topics.label'),
            statusLabel: __('api::journey.focus.topics.status', ['covered' => $covered, 'total' => $total]),
            tone: match (true) {
                $ratio >= 2 / 3 => FocusTone::Success,
                $ratio > 1 / 3 => FocusTone::Info,
                default => FocusTone::Warning,
            },
        );
    }

    private function ratingsFocus(int $pending): FocusItem
    {
        return new FocusItem(
            label: __('api::journey.focus.ratings.label'),
            statusLabel: $pending > 0
                ? trans_choice('api::journey.focus.ratings.pending', $pending)
                : __('api::journey.focus.ratings.up_to_date'),
            tone: $pending > 0 ? FocusTone::Warning : FocusTone::Success,
        );
    }
}

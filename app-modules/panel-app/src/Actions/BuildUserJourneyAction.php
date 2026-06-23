<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Actions;

use App\Models\Users\User;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\PanelApp\DTOs\UserJourney;
use TresPontosTech\User\Enums\LifeMoment;

class BuildUserJourneyAction
{
    /**
     * Ordem canônica da escada de maturidade financeira.
     *
     * @var list<LifeMoment>
     */
    public const STAGES = [
        LifeMoment::Endebted,
        LifeMoment::Messy,
        LifeMoment::Payer,
        LifeMoment::Saver,
        LifeMoment::Investor,
    ];

    /**
     * Cache por usuário, válido enquanto a instância viver (scoped = um request).
     *
     * @var array<int|string, UserJourney>
     */
    private array $cache = [];

    public function __invoke(User $user): UserJourney
    {
        return $this->cache[$user->getKey()] ??= $this->build($user);
    }

    private function build(User $user): UserJourney
    {
        $user->loadMissing('anamnese');

        $stage = $user->anamnese?->life_moment;
        $stageIndex = $stage === null ? null : array_search($stage, self::STAGES, true);

        $completed = $user->appointments()
            ->where('status', AppointmentStatus::Completed->value)
            ->get(['id', 'category_type', 'appointment_at']);

        /** @var list<AppointmentCategoryEnum> $topicsCovered */
        $topicsCovered = $completed
            ->pluck('category_type')
            ->unique(fn (AppointmentCategoryEnum $category): string => $category->value)
            ->values()
            ->all();

        $ratingsGiven = AppointmentFeedback::query()
            ->where('user_id', $user->getKey())
            ->count();

        $pendingRatings = $user->appointments()
            ->where('status', AppointmentStatus::Completed->value)
            ->whereDoesntHave('feedback')
            ->count();

        return new UserJourney(
            stage: $stage,
            stageIndex: $stageIndex === false ? null : $stageIndex,
            stages: self::STAGES,
            completedConsultations: $completed->count(),
            topicsCovered: $topicsCovered,
            topicsTotal: count(AppointmentCategoryEnum::cases()),
            ratingsGiven: $ratingsGiven,
            pendingRatings: $pendingRatings,
            lastConsultationAt: $completed->max('appointment_at'),
        );
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Actions;

use App\Models\Users\User;
use Illuminate\Support\Collection;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
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
        $stageIndex = $stageIndex === false ? null : $stageIndex;

        $completed = $user->appointments()
            ->where('status', AppointmentStatus::Completed->value)
            ->get(['id', 'category_type', 'appointment_at']);

        /** @var list<AppointmentCategoryEnum> $topicsCovered */
        $topicsCovered = $this->distinctTopics($completed);

        $ratingsGiven = AppointmentFeedback::query()
            ->where('user_id', $user->getKey())
            ->count();

        $pendingRatings = $user->appointments()
            ->where('status', AppointmentStatus::Completed->value)
            ->whereDoesntHave('feedback')
            ->count();

        $topicsTotal = count(AppointmentCategoryEnum::cases());

        // Recorte do mês corrente, usado nos indicadores de tendência dos cards.
        $monthStart = now()->startOfMonth();
        $before = $completed->filter(fn ($appointment): bool => $appointment->appointment_at < $monthStart);
        $ratingsBefore = AppointmentFeedback::query()
            ->where('user_id', $user->getKey())
            ->where('created_at', '<', $monthStart)
            ->count();

        return new UserJourney(
            stage: $stage,
            stageIndex: $stageIndex,
            stages: self::STAGES,
            completedConsultations: $completed->count(),
            topicsCovered: $topicsCovered,
            topicsTotal: $topicsTotal,
            ratingsGiven: $ratingsGiven,
            pendingRatings: $pendingRatings,
            lastConsultationAt: $completed->max('appointment_at'),
            completedThisMonth: $completed->count() - $before->count(),
            topicsCoveredThisMonth: count($topicsCovered) - count($this->distinctTopics($before)),
            ratingsThisMonth: $ratingsGiven - $ratingsBefore,
            healthScore: $this->healthScore(
                $stageIndex,
                count($topicsCovered),
                $topicsTotal,
                $ratingsGiven,
                $completed->count(),
            ),
            // O momento de vida não tem histórico, então entra igual nos dois
            // lados da conta: o delta reflete apenas consultorias e avaliações.
            healthScorePreviousMonth: $this->healthScore(
                $stageIndex,
                count($this->distinctTopics($before)),
                $topicsTotal,
                $ratingsBefore,
                $before->count(),
            ),
        );
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     * @return list<AppointmentCategoryEnum>
     */
    private function distinctTopics(Collection $appointments): array
    {
        /** @var list<AppointmentCategoryEnum> $topics */
        $topics = $appointments
            ->pluck('category_type')
            ->unique(fn (AppointmentCategoryEnum $category): string => $category->value)
            ->values()
            ->all();

        return $topics;
    }

    /**
     * Índice de saúde financeira, de 0 a 100.
     *
     * A régua é uma primeira proposta, não uma definição de produto: momento de
     * vida pesa 60, cobertura de temas 20 e engajamento (avaliar o que fez) 20.
     * Está isolada aqui de propósito, para ser ajustada sem tocar na tela.
     */
    private function healthScore(?int $stageIndex, int $topicsCovered, int $topicsTotal, int $ratings, int $completed): int
    {
        $stagePoints = $stageIndex === null
            ? 0
            : (int) round((($stageIndex + 1) / count(self::STAGES)) * 60);

        $topicPoints = $topicsTotal > 0
            ? (int) round(($topicsCovered / $topicsTotal) * 20)
            : 0;

        $engagementPoints = $completed > 0
            ? (int) round((min($ratings, $completed) / $completed) * 20)
            : 0;

        return min(UserJourney::HEALTH_SCORE_MAX, $stagePoints + $topicPoints + $engagementPoints);
    }
}

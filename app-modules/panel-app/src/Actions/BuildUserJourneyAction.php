<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Actions;

use App\Models\Users\User;
use Carbon\CarbonInterface;
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

    /**
     * Score no fim de cada um dos últimos `$months` meses, do mais antigo ao atual.
     *
     * Cada mês usa as consultorias e avaliações que existiam até a virada seguinte; o mês
     * corrente vai até agora e coincide com `healthScore`. O momento de vida não tem
     * histórico, então entra igual em todos os meses, como no `healthScorePreviousMonth`.
     *
     * @return array<string, int> 'Y-m' => score
     */
    public function monthlyHealthScores(User $user, int $months): array
    {
        $stageIndex = $this->stageIndexOf($user);
        $completed = $this->completedAppointments($user);
        $ratedAt = $this->ratingDates($user);

        $scores = [];

        for ($offset = $months - 1; $offset >= 0; --$offset) {
            $month = now()->startOfMonth()->subMonthsNoOverflow($offset);

            $scores[$month->format('Y-m')] = $this->healthScoreBefore(
                $month->copy()->addMonthNoOverflow(),
                $stageIndex,
                $completed,
                $ratedAt,
            );
        }

        return $scores;
    }

    private function build(User $user): UserJourney
    {
        $stageIndex = $this->stageIndexOf($user);
        $stage = $stageIndex === null ? null : self::STAGES[$stageIndex];

        $completed = $this->completedAppointments($user);
        $ratedAt = $this->ratingDates($user);

        /** @var list<AppointmentCategoryEnum> $topicsCovered */
        $topicsCovered = $this->distinctTopics($completed);

        $ratingsGiven = $ratedAt->count();

        $pendingRatings = $user->appointments()
            ->where('status', AppointmentStatus::Completed->value)
            ->whereDoesntHave('feedback')
            ->count();

        $topicsTotal = count(AppointmentCategoryEnum::cases());

        // Recorte do mês corrente, usado nos indicadores de tendência dos cards.
        $monthStart = now()->startOfMonth();
        $before = $completed->filter(fn ($appointment): bool => $appointment->appointment_at < $monthStart);
        $ratingsBefore = $ratedAt->filter(fn (CarbonInterface $createdAt): bool => $createdAt < $monthStart)->count();

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
            healthScorePreviousMonth: $this->healthScoreBefore($monthStart, $stageIndex, $completed, $ratedAt),
        );
    }

    private function stageIndexOf(User $user): ?int
    {
        $user->loadMissing('anamnese');

        $stage = $user->anamnese?->life_moment;

        if ($stage === null) {
            return null;
        }

        $stageIndex = array_search($stage, self::STAGES, true);

        return $stageIndex === false ? null : $stageIndex;
    }

    /**
     * @return Collection<int, Appointment>
     */
    private function completedAppointments(User $user): Collection
    {
        return $user->appointments()
            ->where('status', AppointmentStatus::Completed->value)
            ->get(['id', 'category_type', 'appointment_at']);
    }

    /**
     * @return Collection<int, CarbonInterface>
     */
    private function ratingDates(User $user): Collection
    {
        /** @var Collection<int, CarbonInterface> $dates */
        $dates = AppointmentFeedback::query()
            ->where('user_id', $user->getKey())
            ->get(['created_at'])
            ->pluck('created_at');

        return $dates;
    }

    /**
     * Score recalculado só com o que existia antes de `$cutoff`.
     *
     * @param  Collection<int, Appointment>  $completed
     * @param  Collection<int, CarbonInterface>  $ratedAt
     */
    private function healthScoreBefore(CarbonInterface $cutoff, ?int $stageIndex, Collection $completed, Collection $ratedAt): int
    {
        $before = $completed->filter(fn (Appointment $appointment): bool => $appointment->appointment_at < $cutoff);

        return $this->healthScore(
            $stageIndex,
            count($this->distinctTopics($before)),
            count(AppointmentCategoryEnum::cases()),
            $ratedAt->filter(fn (CarbonInterface $createdAt): bool => $createdAt < $cutoff)->count(),
            $before->count(),
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

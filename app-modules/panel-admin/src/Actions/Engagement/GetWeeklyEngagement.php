<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Engagement;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelAdmin\Actions\Engagement\Concerns\BuildsEngagementCacheKey;
use TresPontosTech\PanelAdmin\DTOs\EngagementFilters;
use TresPontosTech\PanelAdmin\DTOs\EngagementWeek;

/**
 * Weekly series of booked versus held consultancies inside the filtered period.
 * Weeks are bucketed in PHP so the grouping stays consistent across database
 * drivers, and always run Monday to Sunday regardless of the active locale.
 */
final class GetWeeklyEngagement
{
    use BuildsEngagementCacheKey;

    private const int FIRST_DAY_OF_WEEK = CarbonInterface::MONDAY;

    private const int LAST_DAY_OF_WEEK = CarbonInterface::SUNDAY;

    /**
     * @return Collection<int, EngagementWeek>
     */
    public function handle(EngagementFilters $filters): Collection
    {
        return Cache::remember(
            $this->engagementCacheKey('weekly', $filters),
            $this->engagementCacheTtl(),
            fn (): Collection => $this->build($filters),
        );
    }

    /**
     * @return Collection<int, EngagementWeek>
     */
    private function build(EngagementFilters $filters): Collection
    {
        $boundaries = $this->weekBoundaries($filters);

        if ($boundaries->isEmpty()) {
            return collect();
        }

        $counts = $this->countPerWeek($boundaries, $filters);

        return $boundaries->map(fn (array $week, int $index): EngagementWeek => new EngagementWeek(
            start: $week['start'],
            end: $week['end'],
            scheduled: $counts[$index]['scheduled'],
            completed: $counts[$index]['completed'],
        ));
    }

    /**
     * Every Monday-to-Sunday window touched by the period, including the ones
     * without any appointment, so the series has no gaps.
     *
     * @return Collection<int, array{start: CarbonImmutable, end: CarbonImmutable}>
     */
    private function weekBoundaries(EngagementFilters $filters): Collection
    {
        $boundaries = collect();
        $cursor = $filters->start->startOfWeek(self::FIRST_DAY_OF_WEEK);
        $lastWeek = $filters->end->startOfWeek(self::FIRST_DAY_OF_WEEK);

        while ($cursor->lessThanOrEqualTo($lastWeek)) {
            $boundaries->push([
                'start' => $cursor,
                'end' => $cursor->endOfWeek(self::LAST_DAY_OF_WEEK),
            ]);

            $cursor = $cursor->addWeek();
        }

        return $boundaries;
    }

    /**
     * Counts booked and held appointments for every week in a single query,
     * with one conditional aggregate per week — no appointment row is loaded
     * into memory. The positional column aliases never leave this method.
     *
     * @param  Collection<int, array{start: CarbonImmutable, end: CarbonImmutable}>  $boundaries
     * @return array<int, array{scheduled: int, completed: int}>
     */
    private function countPerWeek(Collection $boundaries, EngagementFilters $filters): array
    {
        $query = Appointment::query()
            ->whereBetween('appointment_at', [$filters->start, $filters->end])
            ->when(
                $filters->companyIds !== [],
                fn (Builder $query) => $query->whereIn('company_id', $filters->companyIds),
            );

        foreach ($boundaries as $index => $week) {
            $query
                ->selectRaw(
                    'SUM(CASE WHEN appointment_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as scheduled_' . $index,
                    [$week['start'], $week['end']],
                )
                ->selectRaw(
                    'SUM(CASE WHEN appointment_at BETWEEN ? AND ? AND status = ? THEN 1 ELSE 0 END) as completed_' . $index,
                    [$week['start'], $week['end'], AppointmentStatus::Completed->value],
                );
        }

        /** @var array<string, mixed> $row */
        $row = (array) $query->toBase()->first();

        return $boundaries
            ->keys()
            ->mapWithKeys(fn (int $index): array => [
                $index => [
                    'scheduled' => (int) ($row['scheduled_' . $index] ?? 0),
                    'completed' => (int) ($row['completed_' . $index] ?? 0),
                ],
            ])
            ->all();
    }
}

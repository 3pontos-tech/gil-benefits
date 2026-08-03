<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Widgets\Engagement;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Collection;
use TresPontosTech\PanelAdmin\Actions\Engagement\GetWeeklyEngagement;
use TresPontosTech\PanelAdmin\DTOs\EngagementWeek;
use TresPontosTech\PanelAdmin\Filament\Widgets\Engagement\Concerns\HasEngagementFilters;
use TresPontosTech\PanelAdmin\Support\EngagementThresholds;

class EngagementWeeklyChartWidget extends ChartWidget
{
    use HasEngagementFilters;
    use InteractsWithPageFilters;

    /** Minimum number of weeks that makes a trend line meaningful. */
    private const int MINIMUM_WEEKS = 2;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '320px';

    /** @var Collection<int, EngagementWeek>|null */
    private ?Collection $weeks = null;

    public function getHeading(): ?string
    {
        return __('panel-admin::widgets.engagement.weekly_chart.heading');
    }

    public function getDescription(): ?string
    {
        return $this->hasEnoughHistory()
            ? __('panel-admin::widgets.engagement.weekly_chart.description')
            : __('panel-admin::widgets.engagement.weekly_chart.insufficient_history');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        if (! $this->hasEnoughHistory()) {
            return ['datasets' => [], 'labels' => []];
        }

        $weeks = $this->weeks();

        return [
            'datasets' => [
                [
                    'label' => __('panel-admin::widgets.engagement.weekly_chart.dataset_scheduled'),
                    'data' => $weeks->map(fn (EngagementWeek $week): int => $week->scheduled)->all(),
                    'tension' => 0.3,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => __('panel-admin::widgets.engagement.weekly_chart.dataset_completed'),
                    'data' => $weeks->map(fn (EngagementWeek $week): int => $week->completed)->all(),
                    'tension' => 0.3,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'pointBackgroundColor' => $weeks
                        ->map(fn (EngagementWeek $week): string => $this->isCritical($week)
                            ? 'rgb(239, 68, 68)'
                            : 'rgb(34, 197, 94)')
                        ->all(),
                    'pointRadius' => $weeks
                        ->map(fn (EngagementWeek $week): int => $this->isCritical($week) ? 6 : 3)
                        ->all(),
                ],
            ],
            'labels' => $weeks->map(fn (EngagementWeek $week): string => $week->label())->all(),
        ];
    }

    private function hasEnoughHistory(): bool
    {
        return $this->weeks()->count() >= self::MINIMUM_WEEKS;
    }

    /**
     * @return Collection<int, EngagementWeek>
     */
    private function weeks(): Collection
    {
        return $this->weeks ??= resolve(GetWeeklyEngagement::class)->handle($this->engagementFilters());
    }

    private function isCritical(EngagementWeek $week): bool
    {
        $rate = $week->completionRate();

        return $rate !== null && $rate < EngagementThresholds::WEEKLY_COMPLETION_RATE;
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Widgets\Engagement;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\PanelAdmin\Actions\Engagement\GetEngagementTotals;
use TresPontosTech\PanelAdmin\Filament\Widgets\Engagement\Concerns\HasEngagementFilters;
use TresPontosTech\PanelAdmin\Support\EngagementNumber;
use TresPontosTech\PanelAdmin\Support\EngagementThresholds;

class EngagementTotalsWidget extends StatsOverviewWidget
{
    use HasEngagementFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    protected function getHeading(): ?string
    {
        return __('panel-admin::widgets.engagement.totals.heading');
    }

    protected function getDescription(): ?string
    {
        return __('panel-admin::widgets.engagement.totals.description');
    }

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $totals = resolve(GetEngagementTotals::class)->handle($this->engagementFilters());

        return [
            Stat::make(
                __('panel-admin::widgets.engagement.totals.seats'),
                EngagementNumber::integer($totals->seats),
            )
                ->description(__('panel-admin::widgets.engagement.totals.seats_description'))
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('gray'),

            Stat::make(
                __('panel-admin::widgets.engagement.totals.registered'),
                EngagementNumber::integer($totals->registered),
            )
                ->description(__('panel-admin::widgets.engagement.totals.registered_description'))
                ->descriptionIcon('heroicon-o-users')
                ->color('gray'),

            Stat::make(
                __('panel-admin::widgets.engagement.totals.registration_rate'),
                EngagementNumber::percent($totals->registrationRate()),
            )
                ->description(__('panel-admin::widgets.engagement.totals.registration_rate_description'))
                ->descriptionIcon('heroicon-o-user-plus')
                ->color('gray'),

            Stat::make(
                __('panel-admin::widgets.engagement.totals.scheduling_rate'),
                EngagementNumber::percent($totals->schedulingRate()),
            )
                ->description($this->countDescription(
                    'panel-admin::widgets.engagement.totals.scheduling_rate_description',
                    $totals->withAppointment,
                ))
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('gray'),

            Stat::make(
                __('panel-admin::widgets.engagement.totals.completion_rate'),
                EngagementNumber::percent($totals->completionRate()),
            )
                ->description($this->countDescription(
                    'panel-admin::widgets.engagement.totals.completion_rate_description',
                    $totals->withCompletedAppointment,
                ))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($this->completionRateColor($totals->completionRate())),

            Stat::make(
                __('panel-admin::widgets.engagement.totals.recurrence_rate'),
                EngagementNumber::percent($totals->recurrenceRate()),
            )
                ->description($this->countDescription(
                    'panel-admin::widgets.engagement.totals.recurrence_rate_description',
                    $totals->withRecurrence,
                ))
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('gray'),
        ];
    }

    /**
     * Pluralised caption of a stat, with the count formatted for the locale.
     */
    private function countDescription(string $key, int $count): string
    {
        return trans_choice($key, $count, ['total' => EngagementNumber::integer($count)]);
    }

    /**
     * The completion rate is the only consolidated indicator with an agreed
     * critical line (50%, from the funnel acceptance criteria). Registration,
     * booking and recurrence rates stay neutral until the business defines
     * theirs — colouring them would invent an alert nobody asked for.
     */
    private function completionRateColor(?float $rate): string
    {
        return match (true) {
            $rate === null => 'gray',
            $rate < EngagementThresholds::COMPANY_COMPLETION_RATE => 'danger',
            default => 'success',
        };
    }
}

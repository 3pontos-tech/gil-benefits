<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Widgets\Financial;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\PanelAdmin\Actions\Financial\GetConsultingVolume;
use TresPontosTech\PanelAdmin\DTOs\Financial\ConsultingVolume;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\Concerns\HasFinancialFilters;
use TresPontosTech\PanelAdmin\Support\EngagementNumber;

/**
 * Volume de consultorias do mês (STORY-238).
 */
class ConsultingVolumeWidget extends StatsOverviewWidget
{
    use HasFinancialFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    protected function getHeading(): ?string
    {
        return __('panel-admin::widgets.financial.consulting.heading');
    }

    protected function getDescription(): ?string
    {
        return __('panel-admin::widgets.financial.consulting.description');
    }

    protected function getColumns(): int
    {
        return 5;
    }

    protected function getStats(): array
    {
        $volume = resolve(GetConsultingVolume::class)->handle($this->financialFilters());

        return [
            Stat::make(
                __('panel-admin::widgets.financial.consulting.scheduled'),
                EngagementNumber::integer($volume->scheduled),
            )
                ->description(__('panel-admin::widgets.financial.consulting.scheduled_description'))
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('gray'),

            Stat::make(
                __('panel-admin::widgets.financial.consulting.completed'),
                EngagementNumber::integer($volume->completed),
            )
                ->description(__('panel-admin::widgets.financial.consulting.completed_description'))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make(
                __('panel-admin::widgets.financial.consulting.cancelled'),
                EngagementNumber::integer($volume->cancelled),
            )
                ->description($this->cancelledDescription($volume))
                ->descriptionIcon('heroicon-o-x-circle')
                ->color($volume->cancelledLate > 0 ? 'warning' : 'gray'),

            Stat::make(
                __('panel-admin::widgets.financial.consulting.no_show'),
                EngagementNumber::integer($volume->noShow),
            )
                ->description(__('panel-admin::widgets.financial.consulting.no_show_description'))
                ->descriptionIcon('heroicon-o-user-minus')
                ->color($volume->noShow > 0 ? 'danger' : 'gray'),

            Stat::make(
                __('panel-admin::widgets.financial.consulting.completion_rate'),
                EngagementNumber::percent($volume->completionRate()),
            )
                ->description(__('panel-admin::widgets.financial.consulting.completion_rate_description'))
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('gray'),
        ];
    }

    /**
     * Cancelamento tardio soma em "Canceladas", como a story pede, mas aparece
     * na descrição: ele consome crédito, e some do radar do financeiro se ficar
     * apenas embutido no total.
     */
    private function cancelledDescription(ConsultingVolume $volume): string
    {
        if ($volume->cancelledLate < 1) {
            return __('panel-admin::widgets.financial.consulting.cancelled_description');
        }

        return trans_choice(
            'panel-admin::widgets.financial.consulting.cancelled_late',
            $volume->cancelledLate,
            ['total' => EngagementNumber::integer($volume->cancelledLate)],
        );
    }
}

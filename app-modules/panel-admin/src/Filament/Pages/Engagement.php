<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Engagement\ExportEngagementCsv;
use TresPontosTech\PanelAdmin\Actions\Engagement\GetEngagementFunnel;
use TresPontosTech\PanelAdmin\DTOs\EngagementFilters;
use TresPontosTech\PanelAdmin\DTOs\EngagementFunnelRow;
use TresPontosTech\PanelAdmin\Filament\Widgets\Engagement\EngagementFunnelTableWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Engagement\EngagementTotalsWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Engagement\EngagementWeeklyChartWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Engagement\EngagementWeeklyTableWidget;
use UnitEnum;

class Engagement extends BaseDashboard
{
    use HasFiltersForm;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Funnel;

    protected static string|null|UnitEnum $navigationGroup = null;

    protected static ?int $navigationSort = 11;

    protected static string $routePath = 'engagement';

    protected static ?string $title = null;

    /** Default window of the report, shared by the filters form and the reset action. */
    private const int DEFAULT_PERIOD_DAYS = 30;

    /** Above this, the companies badge shows a count instead of the names. */
    private const int MAX_COMPANY_NAMES_IN_BADGE = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::resources.navigation_group.reports');
    }

    public function getTitle(): string|Htmlable
    {
        return __('panel-admin::resources.pages.engagement.title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('panel-admin::resources.pages.engagement.subheading');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.pages.engagement.navigation_label');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)
                ->columnSpanFull()
                ->schema([
                    DatePicker::make('startDate')
                        ->label(__('panel-admin::resources.pages.engagement.filter_start_date'))
                        ->default(now()->subDays(self::DEFAULT_PERIOD_DAYS)),
                    DatePicker::make('endDate')
                        ->label(__('panel-admin::resources.pages.engagement.filter_end_date'))
                        ->default(now()),
                    Select::make('companies')
                        ->label(__('panel-admin::resources.pages.engagement.filter_companies'))
                        ->placeholder(__('panel-admin::resources.pages.engagement.filter_companies_placeholder'))
                        ->options(fn (): array => Company::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->native(false),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label(__('panel-admin::resources.pages.engagement.export_csv'))
                ->icon(Heroicon::ArrowDownTray)
                ->color('gray')
                ->disabled(fn (): bool => $this->funnelRows()->isEmpty())
                ->tooltip(function (): ?string {
                    if ($this->funnelRows()->isNotEmpty()) {
                        return null;
                    }

                    return __('panel-admin::resources.pages.engagement.export_csv_empty');
                })
                ->action(fn (): StreamedResponse => resolve(ExportEngagementCsv::class)->handle(
                    $this->engagementFilters(),
                    $this->funnelRows(),
                )),

            Action::make('clearFilters')
                ->label(__('panel-admin::resources.pages.engagement.clear_filters'))
                ->icon(Heroicon::XMark)
                ->color('gray')
                ->link()
                ->visible(fn (): bool => $this->hasActiveFilters())
                ->action(function (): void {
                    $this->getFiltersForm()->fill();
                    $this->updatedFilters();
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFiltersFormContentComponent(),
            $this->activeFiltersIndicator(),
            Grid::make(1)
                ->schema($this->getWidgetsSchemaComponents([
                    EngagementTotalsWidget::class,
                    EngagementFunnelTableWidget::class,
                    EngagementWeeklyChartWidget::class,
                    EngagementWeeklyTableWidget::class,
                ])),
        ]);
    }

    /**
     * Badges summarising the filters in effect, so the numbers on screen are
     * never read out of context.
     */
    private function activeFiltersIndicator(): Flex
    {
        return Flex::make([
            Text::make(__('panel-admin::resources.pages.engagement.active_filters'))
                ->size(TextSize::Small)
                ->color('gray')
                ->grow(false),

            Text::make(fn (): string => $this->periodBadge())
                ->badge()
                ->color('primary')
                ->icon(Heroicon::CalendarDays)
                ->grow(false),

            Text::make(fn (): string => $this->companiesBadge())
                ->badge()
                ->color('primary')
                ->icon(Heroicon::BuildingOffice2)
                ->grow(false)
                ->visible(fn (): bool => $this->engagementFilters()->companyIds !== []),
        ])->verticallyAlignCenter();
    }

    private function periodBadge(): string
    {
        $filters = $this->engagementFilters();

        return sprintf('%s – %s', $filters->start->format('d/m/Y'), $filters->end->format('d/m/Y'));
    }

    private function companiesBadge(): string
    {
        $names = Company::query()
            ->whereKey($this->engagementFilters()->companyIds)
            ->orderBy('name')
            ->pluck('name');

        if ($names->count() > self::MAX_COMPANY_NAMES_IN_BADGE) {
            return __('panel-admin::resources.pages.engagement.filter_badge_companies', [
                'count' => $names->count(),
            ]);
        }

        return $names->implode(', ');
    }

    /**
     * Whether the report is showing anything other than its default view.
     */
    private function hasActiveFilters(): bool
    {
        $filters = $this->engagementFilters();
        if ($filters->companyIds !== []) {
            return true;
        }

        if (! $filters->start->isSameDay(now()->subDays(self::DEFAULT_PERIOD_DAYS))) {
            return true;
        }

        return ! $filters->end->isSameDay(now());
    }

    private function engagementFilters(): EngagementFilters
    {
        return EngagementFilters::fromPageFilters($this->filters);
    }

    /**
     * @return Collection<int, EngagementFunnelRow>
     */
    private function funnelRows(): Collection
    {
        return resolve(GetEngagementFunnel::class)->handle($this->engagementFilters());
    }
}

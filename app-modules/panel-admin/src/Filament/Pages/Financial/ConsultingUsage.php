<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Pages\Financial;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Filament\Clusters\Financial\FinancialCluster;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\ConsultingValueWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\ConsultingVolumeWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\ExtraCreditsTableWidget;

/**
 * Módulo "Consultorias e Consumo" do cockpit financeiro.
 *
 * Entrega as stories 238, 240 e 239. Esta última pedia margem operacional, e
 * margem não é calculável: a plataforma não sabe por quanto uma consultoria é
 * vendida — a do plano está embutida na mensalidade e não é alocável. Entrega o
 * lado que dá para saber: o volume que consumiu crédito, multiplicado por um
 * valor único que a Flamma configura, lado a lado com a mensalidade de cada
 * empresa. Quem lê faz a comparação que a margem faria.
 */
class ConsultingUsage extends BaseDashboard
{
    use HasFiltersForm;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $cluster = FinancialCluster::class;

    protected static string $routePath = 'consulting-usage';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = null;

    public static function canAccess(): bool
    {
        return FinancialCluster::canAccess();
    }

    public function getTitle(): string|Htmlable
    {
        return __('panel-admin::resources.pages.financial_consulting.title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('panel-admin::resources.pages.financial_consulting.subheading');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.pages.financial_consulting.navigation_label');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('month')
                        ->label(__('panel-admin::resources.pages.financial_consulting.filter_month'))
                        ->options($this->monthOptions())
                        ->default(now()->format('Y-m'))
                        ->native(false),
                    Select::make('companies')
                        ->label(__('panel-admin::resources.pages.financial_consulting.filter_companies'))
                        ->placeholder(__('panel-admin::resources.pages.financial_consulting.filter_companies_placeholder'))
                        ->options(fn (): array => Company::query()
                            ->withoutDefault()
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

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFiltersFormContentComponent(),
            Grid::make(1)->schema($this->getWidgetsSchemaComponents([
                ConsultingVolumeWidget::class,
                ExtraCreditsTableWidget::class,
                ConsultingValueWidget::class,
            ])),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function monthOptions(): array
    {
        $options = [];

        foreach (range(0, 11) as $offset) {
            $month = now()->toImmutable()->subMonthsNoOverflow($offset);
            $options[$month->format('Y-m')] = ucfirst($month->translatedFormat('F/Y'));
        }

        return $options;
    }
}

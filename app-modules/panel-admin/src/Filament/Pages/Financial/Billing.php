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
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\BillingAlertsWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\PaymentTotalsWidget;

/**
 * Módulo "Cobranças" do cockpit financeiro (STORY-236 e 237).
 *
 * Escopo reduzido e rotulado por D-04: a tela mostra os status que o gateway de
 * fato reporta. Recusado e expirado não existem na origem, e estorno chega mas
 * ainda não é tratado.
 */
class Billing extends BaseDashboard
{
    use HasFiltersForm;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $cluster = FinancialCluster::class;

    protected static string $routePath = 'billing';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = null;

    public static function canAccess(): bool
    {
        return FinancialCluster::canAccess();
    }

    public function getTitle(): string|Htmlable
    {
        return __('panel-admin::resources.pages.financial_billing.title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('panel-admin::resources.pages.financial_billing.subheading');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.pages.financial_billing.navigation_label');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('month')
                        ->label(__('panel-admin::resources.pages.financial_billing.filter_month'))
                        ->options($this->monthOptions())
                        ->default(now()->format('Y-m'))
                        ->native(false),
                    Select::make('companies')
                        ->label(__('panel-admin::resources.pages.financial_billing.filter_companies'))
                        ->placeholder(__('panel-admin::resources.pages.financial_billing.filter_companies_placeholder'))
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
                BillingAlertsWidget::class,
                PaymentTotalsWidget::class,
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

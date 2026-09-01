<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Widgets\Financial;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use TresPontosTech\PanelAdmin\Actions\Financial\GetBillingAlerts;
use TresPontosTech\PanelAdmin\DTOs\Financial\BillingAlert;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\Concerns\HasFinancialFilters;

/**
 * Banners de alerta de cobrança (STORY-237).
 *
 * Dispensa por sessão, como a story pede: o alerta some até o próximo login, e
 * não para sempre — a situação que o gerou continua valendo amanhã.
 */
class BillingAlertsWidget extends Widget
{
    use HasFinancialFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    protected string $view = 'panel-admin::filament.financial.billing-alerts';

    /** Chave de sessão dos alertas já dispensados nesta sessão. */
    private const string SESSION_KEY = 'panel_admin.financial.dismissed_alerts';

    public function dismiss(string $key): void
    {
        $dismissed = $this->dismissedKeys();
        $dismissed[] = $key;

        session()->put(self::SESSION_KEY, array_values(array_unique($dismissed)));
    }

    /**
     * @return Collection<int, BillingAlert>
     */
    public function alerts(): Collection
    {
        $dismissed = $this->dismissedKeys();

        return resolve(GetBillingAlerts::class)
            ->handle($this->financialFilters())
            ->reject(fn (BillingAlert $alert): bool => in_array($alert->key, $dismissed, strict: true))
            ->values();
    }

    /**
     * @return array<int, string>
     */
    private function dismissedKeys(): array
    {
        /** @var array<int, string> $dismissed */
        $dismissed = session()->get(self::SESSION_KEY, []);

        return $dismissed;
    }
}

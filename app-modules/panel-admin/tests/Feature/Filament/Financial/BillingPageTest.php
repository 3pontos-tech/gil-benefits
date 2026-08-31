<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\CompanyFinancialStatusEnum;
use TresPontosTech\Billing\Core\Enums\CreditOrderStatusEnum;
use TresPontosTech\Billing\Core\Models\CreditOrder;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Financial\GetBillingAlerts;
use TresPontosTech\PanelAdmin\Actions\Financial\GetPaymentTotals;
use TresPontosTech\PanelAdmin\DTOs\Financial\BillingAlert;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;
use TresPontosTech\PanelAdmin\DTOs\Financial\PaymentStatusRow;
use TresPontosTech\PanelAdmin\Filament\Pages\Financial\Billing;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\BillingAlertsWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\PaymentTotalsWidget;

use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    Cache::flush();
    travelTo(CarbonImmutable::create(2026, 8, 27, 12, 0, 0));
    $this->filters = FinancialFilters::fromPageFilters(null);
    actingAsFinancial();
});

function billingCompany(string $name, string $status, ?CarbonImmutable $createdAt = null, ?CarbonImmutable $endsAt = null): Company
{
    $company = Company::factory()->create(['name' => $name]);

    $subscription = $company->subscriptions()->create([
        'type' => 'company',
        'stripe_id' => 'sub_' . fake()->unique()->uuid(),
        'stripe_status' => $status,
        'quantity' => 10,
        'ends_at' => $endsAt,
    ]);

    $subscription->forceFill(['created_at' => $createdAt ?? now()->subMonths(3)])->save();

    return $company;
}

function paymentRow(string $status): PaymentStatusRow
{
    return resolve(GetPaymentTotals::class)
        ->handle(FinancialFilters::fromPageFilters(null))
        ->firstWhere('status', $status);
}

describe('pagamentos por situação', function (): void {
    it('entrega as três situações que o gateway reporta', function (): void {
        $statuses = resolve(GetPaymentTotals::class)->handle($this->filters)->pluck('status')->all();

        expect($statuses)->toBe([
            CompanyFinancialStatusEnum::Active->value,
            CompanyFinancialStatusEnum::Trial->value,
            CompanyFinancialStatusEnum::Delinquent->value,
        ]);
    });

    it('soma assinaturas e compras de crédito na linha de aprovados', function (): void {
        $company = billingCompany('Alpha SA', 'active');

        CreditOrder::factory()->create([
            'company_id' => $company->getKey(),
            'provider' => BillingProviderEnum::Virtu,
            'status' => CreditOrderStatusEnum::Paid,
            'amount_cents' => 15000,
            'paid_at' => now()->subDays(3),
        ]);

        $row = paymentRow(CompanyFinancialStatusEnum::Active->value);

        expect($row->subscriptions)->toBe(1)
            ->and($row->creditOrders)->toBe(1)
            ->and($row->quantity())->toBe(2)
            ->and($row->totalCents)->toBe(44900 + 15000);
    });

    it('conta pedido pendente na linha de pendentes', function (): void {
        $company = billingCompany('Alpha SA', 'active');

        CreditOrder::factory()->create([
            'company_id' => $company->getKey(),
            'provider' => BillingProviderEnum::Virtu,
            'status' => CreditOrderStatusEnum::Pending,
            'amount_cents' => 15000,
            'paid_at' => null,
        ]);

        expect(paymentRow(CompanyFinancialStatusEnum::Trial->value)->creditOrders)->toBe(1);
    });

    it('conta a empresa inadimplente com o valor em risco', function (): void {
        billingCompany('Atrasada', 'defaulter');

        $row = paymentRow(CompanyFinancialStatusEnum::Delinquent->value);

        expect($row->subscriptions)->toBe(1)
            ->and($row->totalCents)->toBe(44900);
    });

    it('ignora pagamento de outro mês', function (): void {
        $company = billingCompany('Alpha SA', 'active');

        CreditOrder::factory()->create([
            'company_id' => $company->getKey(),
            'provider' => BillingProviderEnum::Virtu,
            'status' => CreditOrderStatusEnum::Paid,
            'amount_cents' => 15000,
            'paid_at' => now()->subMonths(2),
        ]);

        expect(paymentRow(CompanyFinancialStatusEnum::Active->value)->creditOrders)->toBe(0);
    });

    it('avisa na tela o que o gateway não reporta', function (): void {
        billingCompany('Alpha SA', 'active');

        Livewire::test(PaymentTotalsWidget::class)
            ->assertOk()
            ->assertSeeText('Recusado e expirado não são reportados pelo gateway');
    });
});

describe('alertas de cobrança', function (): void {
    it('alerta cobrança vencendo em até 7 dias, marcada como estimada', function (): void {
        billingCompany('Vence logo', 'active', CarbonImmutable::create(2026, 3, 30));

        $alert = resolve(GetBillingAlerts::class)->handle($this->filters)->firstWhere('key', 'due_soon');

        expect($alert)->toBeInstanceOf(BillingAlert::class)
            ->and($alert->count())->toBe(1)
            ->and($alert->isEstimatedDate)->toBeTrue();
    });

    it('alerta assinatura cancelada nas últimas 24 horas com o valor perdido', function (): void {
        billingCompany('Saiu ontem', 'inactive', endsAt: now()->toImmutable()->subHours(3));

        $alert = resolve(GetBillingAlerts::class)->handle($this->filters)->firstWhere('key', 'recently_cancelled');

        // O valor tem de vir da assinatura cancelada: a empresa já não vale nada
        // para o `RevenueResolver`, e somar pela linha dela zeraria o alerta.
        expect($alert->count())->toBe(1)
            ->and($alert->totalCents)->toBe(44900);
    });

    it('não alerta cancelamento antigo', function (): void {
        billingCompany('Saiu semana passada', 'inactive', endsAt: now()->toImmutable()->subDays(5));

        expect(resolve(GetBillingAlerts::class)->handle($this->filters)->firstWhere('key', 'recently_cancelled'))->toBeNull();
    });

    it('alerta inadimplência no lugar da recusa recorrente', function (): void {
        billingCompany('Atrasada', 'defaulter');

        $alert = resolve(GetBillingAlerts::class)->handle($this->filters)->firstWhere('key', 'delinquent');

        expect($alert->count())->toBe(1)
            ->and($alert->totalCents)->toBe(44900);
    });

    it('não devolve alerta vazio', function (): void {
        billingCompany('Tudo certo', 'active', CarbonImmutable::create(2026, 8, 26));

        $keys = resolve(GetBillingAlerts::class)->handle($this->filters)->pluck('key')->all();

        expect($keys)->not->toContain('delinquent')
            ->and($keys)->not->toContain('recently_cancelled');
    });
});

describe('dispensa por sessão', function (): void {
    it('esconde o alerta dispensado e mantém os demais', function (): void {
        billingCompany('Atrasada', 'defaulter', CarbonImmutable::create(2026, 3, 30));

        $widget = Livewire::test(BillingAlertsWidget::class);

        expect($widget->instance()->alerts()->pluck('key')->all())->toContain('delinquent', 'due_soon');

        $widget->call('dismiss', 'delinquent');

        $remaining = $widget->instance()->alerts()->pluck('key')->all();

        expect($remaining)->not->toContain('delinquent')
            ->and($remaining)->toContain('due_soon');
    });
});

describe('tela', function (): void {
    it('monta os dois widgets na página', function (): void {
        Livewire::test(Billing::class)
            ->assertOk()
            ->assertSeeLivewire(BillingAlertsWidget::class)
            ->assertSeeLivewire(PaymentTotalsWidget::class);
    });

    it('fecha para Admin comum', function (): void {
        actingAsAdmin();

        expect(Billing::canAccess())->toBeFalse();
    });
});

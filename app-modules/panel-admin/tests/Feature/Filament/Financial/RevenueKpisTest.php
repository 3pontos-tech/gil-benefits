<?php

declare(strict_types=1);

use App\Models\Users\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Enums\CreditOrderStatusEnum;
use TresPontosTech\Credits\Models\CreditOrder;
use TresPontosTech\PanelAdmin\Actions\Financial\GetRevenueKpis;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;
use TresPontosTech\PanelAdmin\Filament\Pages\Financial\RevenueDashboard;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\RevenueKpisWidget;

use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    Cache::flush();
    travelTo(CarbonImmutable::create(2026, 8, 27, 12, 0, 0));
    $this->filters = FinancialFilters::fromPageFilters(null);
    actingAsFinancial();
});

/**
 * Assinatura de empresa vigente desde `$createdAt`, encerrada em `$endsAt`.
 */
function b2bSubscription(int $seats, ?CarbonImmutable $createdAt = null, ?CarbonImmutable $endsAt = null): Company
{
    $company = Company::factory()->create();

    $subscription = $company->subscriptions()->create([
        'type' => 'company',
        'stripe_id' => 'sub_' . fake()->unique()->uuid(),
        'stripe_status' => 'active',
        'quantity' => $seats,
        'ends_at' => $endsAt,
    ]);

    $subscription->forceFill(['created_at' => $createdAt ?? now()->subMonths(6)])->save();

    return $company;
}

function standaloneSubscription(int $unitAmount): User
{
    $price = Price::factory()->create(['unit_amount_decimal' => $unitAmount]);
    $user = User::factory()->create();

    $subscription = $user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_' . fake()->unique()->uuid(),
        'stripe_status' => 'active',
        'stripe_price' => $price->provider_price_id,
        'quantity' => 1,
    ]);

    $subscription->forceFill(['created_at' => now()->subMonths(6)])->save();

    return $user;
}

describe('MRR', function (): void {
    it('separa B2B de avulso e soma no total', function (): void {
        b2bSubscription(10);   // R$ 449,00
        standaloneSubscription(9990); // R$ 99,90

        $kpis = resolve(GetRevenueKpis::class)->handle($this->filters);

        expect($kpis->current->b2bCents)->toBe(44900)
            ->and($kpis->current->standaloneCents)->toBe(9990)
            ->and($kpis->current->totalCents())->toBe(54890);
    });

    it('conta pagantes de cada tipo', function (): void {
        b2bSubscription(10);
        b2bSubscription(20);
        standaloneSubscription(9990);

        $kpis = resolve(GetRevenueKpis::class)->handle($this->filters);

        expect($kpis->current->payingCompanies)->toBe(2)
            ->and($kpis->current->payingUsers)->toBe(1);
    });

    it('mantém no MRR a assinatura que só termina depois do mês', function (): void {
        b2bSubscription(10, now()->toImmutable()->subMonths(3), now()->toImmutable()->addMonth());

        expect(resolve(GetRevenueKpis::class)->handle($this->filters)->current->b2bCents)->toBe(44900);
    });

    it('tira do mês a assinatura encerrada antes dele', function (): void {
        b2bSubscription(10, now()->toImmutable()->subMonths(6), now()->toImmutable()->subMonths(3));

        expect(resolve(GetRevenueKpis::class)->handle($this->filters)->current->b2bCents)->toBe(0);
    });

    it('tira do mês a assinatura criada depois dele', function (): void {
        b2bSubscription(10, now()->toImmutable()->addMonth());

        expect(resolve(GetRevenueKpis::class)->handle($this->filters)->current->b2bCents)->toBe(0);
    });
});

describe('receita total', function (): void {
    it('soma os créditos extras pagos no mês', function (): void {
        $company = b2bSubscription(10);

        CreditOrder::factory()->create([
            'company_id' => $company->getKey(),
            'provider' => BillingProviderEnum::Virtu,
            'status' => CreditOrderStatusEnum::Paid,
            'amount_cents' => 15000,
            'paid_at' => now()->subDays(3),
        ]);

        $kpis = resolve(GetRevenueKpis::class)->handle($this->filters);

        expect($kpis->extraCreditsCents)->toBe(15000)
            ->and($kpis->totalRevenueCents())->toBe(59900);
    });

    it('ignora crédito pendente, que é intenção e não receita', function (): void {
        $company = b2bSubscription(10);

        CreditOrder::factory()->create([
            'company_id' => $company->getKey(),
            'provider' => BillingProviderEnum::Virtu,
            'status' => CreditOrderStatusEnum::Pending,
            'amount_cents' => 15000,
            'paid_at' => null,
        ]);

        expect(resolve(GetRevenueKpis::class)->handle($this->filters)->extraCreditsCents)->toBe(0);
    });

    it('ignora crédito pago em outro mês', function (): void {
        $company = b2bSubscription(10);

        CreditOrder::factory()->create([
            'company_id' => $company->getKey(),
            'provider' => BillingProviderEnum::Virtu,
            'status' => CreditOrderStatusEnum::Paid,
            'amount_cents' => 15000,
            'paid_at' => now()->subMonths(2),
        ]);

        expect(resolve(GetRevenueKpis::class)->handle($this->filters)->extraCreditsCents)->toBe(0);
    });
});

describe('ticket médio', function (): void {
    it('usa o mesmo universo no numerador e no denominador', function (): void {
        b2bSubscription(10); // 44900
        b2bSubscription(20); // 20 x 34,90 = 69800

        $kpis = resolve(GetRevenueKpis::class)->handle($this->filters);

        expect($kpis->current->averageTicketCents())->toBe(intdiv(44900 + 69800, 2));
    });

    it('devolve nulo quando nenhuma empresa tem valor conhecido', function (): void {
        standaloneSubscription(9990);

        expect(resolve(GetRevenueKpis::class)->handle($this->filters)->current->averageTicketCents())->toBeNull();
    });
});

describe('variação contra o mês anterior', function (): void {
    it('calcula a variação quando o mês anterior teve receita', function (): void {
        b2bSubscription(10, now()->toImmutable()->subMonths(6));
        b2bSubscription(10, now()->toImmutable()->startOfMonth()->addDay());

        $kpis = resolve(GetRevenueKpis::class)->handle($this->filters);

        expect($kpis->hasComparison())->toBeTrue()
            ->and($kpis->variation('total'))->toBe(100.0);
    });

    it('não compara quando o mês anterior não teve receita', function (): void {
        b2bSubscription(10, now()->toImmutable()->startOfMonth()->addDay());

        $kpis = resolve(GetRevenueKpis::class)->handle($this->filters);

        expect($kpis->hasComparison())->toBeFalse()
            ->and($kpis->variation('total'))->toBeNull();
    });
});

describe('empresa-balde', function (): void {
    it('não conta assinatura da empresa-balde como B2B', function (): void {
        $bucket = Company::factory()->create(['slug' => Company::DEFAULT_SLUG]);
        $subscription = $bucket->subscriptions()->create([
            'type' => 'company',
            'stripe_id' => 'sub_' . fake()->unique()->uuid(),
            'stripe_status' => 'active',
            'quantity' => 100,
        ]);
        $subscription->forceFill(['created_at' => now()->subMonths(6)])->save();

        $kpis = resolve(GetRevenueKpis::class)->handle($this->filters);

        expect($kpis->current->b2bCents)->toBe(0)
            ->and($kpis->current->payingCompanies)->toBe(0);
    });
});

describe('tela', function (): void {
    it('abre para o perfil financeiro e mostra os cards', function (): void {
        b2bSubscription(10);

        Livewire::test(RevenueKpisWidget::class)
            ->assertOk()
            ->assertSeeText('MRR')
            ->assertSeeText('Ticket médio')
            ->assertSeeText('449,00');
    });

    it('não mostra card de receita projetada', function (): void {
        b2bSubscription(10);

        Livewire::test(RevenueKpisWidget::class)
            ->assertOk()
            ->assertDontSeeText('Projetada')
            ->assertDontSeeText('Projeção');
    });

    it('exibe a hora do cálculo', function (): void {
        b2bSubscription(10);

        Livewire::test(RevenueKpisWidget::class)
            ->assertOk()
            ->assertSeeText('Calculado às 12:00');
    });

    it('monta o widget dentro da página', function (): void {
        Livewire::test(RevenueDashboard::class)
            ->assertOk()
            ->assertSeeLivewire(RevenueKpisWidget::class);
    });

    it('fecha para Admin comum', function (): void {
        actingAsAdmin();

        expect(RevenueDashboard::canAccess())->toBeFalse();
    });
});

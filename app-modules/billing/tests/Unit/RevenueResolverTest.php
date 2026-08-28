<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Enums\MonthlyValueSourceEnum;
use TresPontosTech\Billing\Core\Enums\SeatPricingTierEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Support\MoneyCents;
use TresPontosTech\Billing\Core\Support\RevenueResolver;
use TresPontosTech\Company\Models\Company;

beforeEach(function (): void {
    $this->resolver = new RevenueResolver;
});

function companyWithSubscription(int $quantity, string $status = 'active'): Company
{
    $company = Company::factory()->create();
    $company->subscriptions()->create([
        'type' => 'company',
        'stripe_id' => 'sub_' . fake()->unique()->uuid(),
        'stripe_status' => $status,
        'quantity' => $quantity,
    ]);

    return $company->fresh();
}

describe('assinatura de empresa (metered)', function (): void {
    it('espelha a conta do checkout em cada faixa de assento', function (int $quantity): void {
        $company = companyWithSubscription($quantity);

        $expected = MoneyCents::fromReais(
            SeatPricingTierEnum::fromQuantity($quantity)->pricePerSeat() * $quantity
        )->cents;

        expect($this->resolver->monthlyValueForCompany($company)->cents)->toBe($expected);
    })->with([
        'faixa 5-15' => 10,
        'limite da faixa 1' => 15,
        'faixa 16-30' => 20,
        'limite da faixa 2' => 30,
        'faixa 31-70' => 50,
        'acima de 70' => 120,
    ]);

    it('calcula 10 assentos como 10 x R$ 44,90', function (): void {
        $value = $this->resolver->monthlyValueForCompany(companyWithSubscription(10));

        expect($value->cents)->toBe(44900)
            ->and($value->source)->toBe(MonthlyValueSourceEnum::SubscriptionSeatTier);
    });

    it('conta assinatura inadimplente como receita contratada', function (string $status): void {
        expect($this->resolver->monthlyValueForCompany(companyWithSubscription(10, $status))->cents)->toBe(44900);
    })->with([
        'ativa' => 'active',
        'em trial' => 'trialing',
        'atrasada' => 'past_due',
        'inadimplente' => 'defaulter',
    ]);

    it('ignora assinatura cancelada ou pendente', function (string $status): void {
        $company = companyWithSubscription(10, $status);

        expect($this->resolver->monthlyValueForCompany($company)->isKnown())->toBeFalse();
    })->with([
        'cancelada' => 'inactive',
        'pendente' => 'pending',
    ]);
});

describe('contrato B2B sem assinatura', function (): void {
    it('não estima valor pela régua de assento', function (): void {
        $company = Company::factory()->create();
        CompanyPlan::factory()->create([
            'company_id' => $company->getKey(),
            'seats' => 20,
            'status' => CompanyPlanStatusEnum::Active,
        ]);

        $value = $this->resolver->monthlyValueForCompany($company->fresh());

        expect($value->isKnown())->toBeFalse()
            ->and($value->cents)->toBeNull()
            ->and($value->source)->toBe(MonthlyValueSourceEnum::Unknown);
    });

    it('usa a assinatura quando ela existe, ignorando o contrato', function (): void {
        $company = companyWithSubscription(10);
        CompanyPlan::factory()->create([
            'company_id' => $company->getKey(),
            'seats' => 50,
            'status' => CompanyPlanStatusEnum::Active,
        ]);

        expect($this->resolver->monthlyValueForCompany($company->fresh())->cents)->toBe(44900);
    });
});

describe('empresa sem forma de precificação', function (): void {
    it('devolve desconhecido, nunca zero', function (): void {
        $value = $this->resolver->monthlyValueForCompany(Company::factory()->create());

        expect($value->isKnown())->toBeFalse()
            ->and($value->cents)->toBeNull()
            ->and($value->source)->toBe(MonthlyValueSourceEnum::Unknown);
    });
});

describe('assinante avulso', function (): void {
    it('usa o preço cadastrado do plano, em centavos', function (): void {
        $price = Price::factory()->create(['unit_amount_decimal' => 9990]);
        $user = User::factory()->create();
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_' . fake()->unique()->uuid(),
            'stripe_status' => 'active',
            'stripe_price' => $price->provider_price_id,
            'quantity' => 1,
        ]);

        $value = $this->resolver->monthlyValueForUser($user->fresh());

        expect($value->cents)->toBe(9990)
            ->and($value->source)->toBe(MonthlyValueSourceEnum::SubscriptionPrice);
    });

    it('devolve desconhecido quando o preço não existe mais', function (): void {
        $user = User::factory()->create();
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_' . fake()->unique()->uuid(),
            'stripe_status' => 'active',
            'stripe_price' => 'price_que_sumiu',
            'quantity' => 1,
        ]);

        expect($this->resolver->monthlyValueForUser($user->fresh())->isKnown())->toBeFalse();
    });
});

describe('MoneyCents', function (): void {
    it('arredonda reais como o checkout arredonda', function (): void {
        expect(MoneyCents::fromReais(44.90 * 10)->cents)->toBe(44900)
            ->and(MoneyCents::fromReais(24.90 * 50)->cents)->toBe(124500)
            ->and(MoneyCents::fromReais(11.90 * 120)->cents)->toBe(142800);
    });

    it('formata em real brasileiro', function (): void {
        expect(MoneyCents::fromCents(44900)->format())->toContain('449,00');
    });
});

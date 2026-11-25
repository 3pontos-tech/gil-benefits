<?php

declare(strict_types=1);

use App\Models\Users\User;
use Tests\Fakes\FakeBillingContract;
use Tests\Fakes\FakeDiscountableBillingContract;
use Tests\Fakes\FakeLimitedDurationDiscountBillingContract;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\Entities\PlanEntity;
use TresPontosTech\Billing\Core\Entities\PriceEntity;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Discounts\Actions\ListAvailableDiscounts;
use TresPontosTech\Discounts\Enums\DiscountCustomerTypeEnum;
use TresPontosTech\Discounts\Enums\DiscountDurationEnum;
use TresPontosTech\Discounts\Enums\DiscountRedemptionStatusEnum;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Models\DiscountRedemption;

beforeEach(function (): void {
    $this->plano = new PlanEntity(
        name: 'Plano Mensal',
        slug: 'plano-mensal',
        productId: 'prod_teste',
        prices: collect([new PriceEntity(
            type: 'user',
            priceId: 'price_mensal',
            priceInCents: 10000,
            audience: PriceAudienceEnum::Standalone,
        )]),
        trialDays: false,
        hasGenericTrial: false,
        allowPromotionCodes: false,
        collectTaxIds: false,
        isMeteredPrice: false,
        provider: BillingProviderEnum::Virtu,
    );

    $this->listar = fn (User $user, ?BillingContract $gateway = null, ?PlanEntity $plano = null) => resolve(ListAvailableDiscounts::class)->handle(
        $user,
        $plano ?? $this->plano,
        PriceAudienceEnum::Standalone,
        $gateway ?? new FakeDiscountableBillingContract,
    );
});

describe('elegibilidade por regra', function (): void {
    it('oferece o cupom só quando a regra é cumprida', function (Closure $preparar, bool $elegivel): void {
        $user = User::factory()->create();
        $preparar($user);

        expect(($this->listar)($user))->toHaveCount($elegivel ? 1 : 0);
    })->with([
        'cupom automático sem restrições' => [
            fn () => Discount::factory()->automatic()->create(),
            true,
        ],
        'cupom inativo' => [
            fn () => Discount::factory()->automatic()->inactive()->create(),
            false,
        ],
        'antes da janela de início' => [
            fn () => Discount::factory()->automatic()->create(['starts_at' => now()->addDay()]),
            false,
        ],
        'depois da janela de fim' => [
            fn () => Discount::factory()->automatic()->create(['ends_at' => now()->subDay()]),
            false,
        ],
        'dentro da janela' => [
            fn () => Discount::factory()->automatic()->create(['starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]),
            true,
        ],
        'audiência diferente' => [
            fn () => Discount::factory()->automatic()->create(['audience' => PriceAudienceEnum::Subsidized]),
            false,
        ],
        'sem audiência definida' => [
            fn () => Discount::factory()->automatic()->create(['audience' => null]),
            true,
        ],
        'plano fora da lista' => [
            fn () => Discount::factory()->automatic()->create(['eligible_plan_slugs' => ['outro-plano']]),
            false,
        ],
        'plano dentro da lista' => [
            fn () => Discount::factory()->automatic()->create(['eligible_plan_slugs' => ['outro-plano', 'plano-mensal']]),
            true,
        ],
        'cupom para empresa' => [
            fn () => Discount::factory()->automatic()->create(['customer_type' => DiscountCustomerTypeEnum::Company]),
            false,
        ],
        'primeira assinatura já paga' => [
            function (User $user): void {
                Discount::factory()->automatic()->create(['first_subscription_only' => true]);
                Subscription::factory()->create([
                    'subscriptionable_type' => $user->getMorphClass(),
                    'subscriptionable_id' => $user->getKey(),
                    'stripe_status' => 'active',
                ]);
            },
            false,
        ],
        'primeira assinatura com checkout pendente' => [
            function (User $user): void {
                Discount::factory()->automatic()->create(['first_subscription_only' => true]);
                Subscription::factory()->create([
                    'subscriptionable_type' => $user->getMorphClass(),
                    'subscriptionable_id' => $user->getKey(),
                    'stripe_status' => 'pending',
                ]);
            },
            true,
        ],
        'limite por pessoa atingido' => [
            function (User $user): void {
                $discount = Discount::factory()->automatic()->create(['max_redemptions_per_customer' => 1]);
                DiscountRedemption::factory()->for($discount)->forBillable($user)->active()->create();
            },
            false,
        ],
        'limite por pessoa com resgate cancelado' => [
            function (User $user): void {
                $discount = Discount::factory()->automatic()->create(['max_redemptions_per_customer' => 1]);
                DiscountRedemption::factory()->for($discount)->forBillable($user)->canceled()->create();
            },
            true,
        ],
        'limite por pessoa sem limite definido' => [
            function (User $user): void {
                $discount = Discount::factory()->automatic()->create(['max_redemptions_per_customer' => null]);
                DiscountRedemption::factory()->for($discount)->forBillable($user)->completed()->create();
            },
            true,
        ],
        'limite total atingido por outra pessoa' => [
            function (User $user): void {
                $discount = Discount::factory()->automatic()->create(['max_redemptions' => 1, 'max_redemptions_per_customer' => null]);
                DiscountRedemption::factory()->for($discount)->forBillable(User::factory()->create())->completed()->create();
            },
            false,
        ],
        'reserva vencida não conta no limite total' => [
            function (User $user): void {
                $discount = Discount::factory()->automatic()->create(['max_redemptions' => 1, 'max_redemptions_per_customer' => null]);
                DiscountRedemption::factory()->for($discount)->forBillable(User::factory()->create())->create([
                    'status' => DiscountRedemptionStatusEnum::Reserved,
                    'reserved_until' => now()->subMinute(),
                ]);
            },
            true,
        ],
        'grant vigente do usuário' => [
            fn (User $user) => DiscountGrant::factory()->for(Discount::factory())->claimedBy($user)->create(),
            true,
        ],
        'grant ainda não resgatado' => [
            fn () => DiscountGrant::factory()->for(Discount::factory())->create(),
            false,
        ],
        'grant de outra pessoa' => [
            fn () => DiscountGrant::factory()->for(Discount::factory())->claimedBy(User::factory()->create())->create(),
            false,
        ],
        'grant revogado' => [
            fn (User $user) => DiscountGrant::factory()->for(Discount::factory())->claimedBy($user)->revoked()->create(),
            false,
        ],
        'grant vencido' => [
            fn (User $user) => DiscountGrant::factory()->for(Discount::factory())->claimedBy($user)->expired()->create(),
            false,
        ],
        'grant de cupom inativo' => [
            fn (User $user) => DiscountGrant::factory()->for(Discount::factory()->inactive())->claimedBy($user)->create(),
            false,
        ],
        'grant travado em outro plano' => [
            fn (User $user) => DiscountGrant::factory()->for(Discount::factory())->claimedBy($user)->create(['plan_slug' => 'outro-plano']),
            false,
        ],
        'grant travado no mesmo plano' => [
            fn (User $user) => DiscountGrant::factory()->for(Discount::factory())->claimedBy($user)->create(['plan_slug' => 'plano-mensal']),
            true,
        ],
    ]);
});

describe('capacidade do gateway', function (): void {
    it('só oferece duração limitada quando o gateway cobra ciclos limitados', function (Closure $cupom, bool $gatewayLimitado, bool $elegivel): void {
        $user = User::factory()->create();
        $cupom();

        $gateway = $gatewayLimitado
            ? new FakeLimitedDurationDiscountBillingContract
            : new FakeDiscountableBillingContract;

        expect(($this->listar)($user, $gateway))->toHaveCount($elegivel ? 1 : 0);
    })->with([
        'todas as mensalidades sem suporte' => [fn () => Discount::factory()->automatic()->create(), false, true],
        'só a primeira cobrança sem suporte' => [fn () => Discount::factory()->automatic()->create(['duration' => DiscountDurationEnum::Once]), false, false],
        'só a primeira cobrança com suporte' => [fn () => Discount::factory()->automatic()->create(['duration' => DiscountDurationEnum::Once]), true, true],
        'repetido sem suporte' => [fn () => Discount::factory()->automatic()->repeating(3)->create(), false, false],
        'repetido com suporte' => [fn () => Discount::factory()->automatic()->repeating(3)->create(), true, true],
    ]);

    it('não oferece nada quando o gateway não aplica desconto no checkout', function (): void {
        $user = User::factory()->create();
        Discount::factory()->automatic()->create();

        expect(($this->listar)($user, new FakeBillingContract))->toBeEmpty();
    });
});

describe('ordenação e pontuação', function (): void {
    it('ordena pelo maior desconto em 12 ciclos e pré-seleciona o primeiro', function (): void {
        $user = User::factory()->create();

        $percentual = Discount::factory()->automatic()->create(['name' => 'Percentual', 'percent_off' => 30]);
        $valorFixo = Discount::factory()->automatic()->fixedAmount(2000)->create(['name' => 'Valor fixo']);
        $tresCiclos = Discount::factory()->automatic()->repeating(3)->create(['name' => 'Três ciclos', 'percent_off' => 20]);

        $ofertas = ($this->listar)($user, new FakeLimitedDurationDiscountBillingContract);

        expect($ofertas->pluck('label')->all())->toBe(['Percentual', 'Valor fixo', 'Três ciclos'])
            ->and($ofertas->first()->score)->toBe(3000 * 12)
            ->and($ofertas->get(1)->score)->toBe(2000 * 12)
            ->and($ofertas->last()->score)->toBe(2000 * 3)
            ->and($ofertas->first()->discountedAmountCents)->toBe(7000)
            ->and($ofertas->first()->listAmountCents)->toBe(10000)
            ->and($ofertas->first()->savingPerCycleCents)->toBe(3000)
            ->and($ofertas->first()->key)->toBe('discount:' . $percentual->getKey());
    });

    it('desempata pelo cupom que expira primeiro', function (): void {
        $user = User::factory()->create();

        Discount::factory()->automatic()->create(['name' => 'Expira depois', 'ends_at' => now()->addDays(30)]);
        Discount::factory()->automatic()->create(['name' => 'Expira antes', 'ends_at' => now()->addDays(10)]);

        expect(($this->listar)($user)->pluck('label')->all())->toBe(['Expira antes', 'Expira depois']);
    });

    it('desempata pela maior economia por ciclo quando o total é igual', function (): void {
        $user = User::factory()->create();

        Discount::factory()->automatic()->create(['name' => 'Dez por cento vitalício', 'percent_off' => 10]);
        Discount::factory()->automatic()->repeating(6)->create(['name' => 'Vinte por cento por seis ciclos', 'percent_off' => 20]);

        $ofertas = ($this->listar)($user, new FakeLimitedDurationDiscountBillingContract);

        expect($ofertas->pluck('score')->unique()->all())->toBe([12000])
            ->and($ofertas->pluck('label')->all())->toBe(['Vinte por cento por seis ciclos', 'Dez por cento vitalício']);
    });

    it('usa a janela mais curta entre o grant e o cupom como validade da oferta', function (): void {
        $user = User::factory()->create();
        $fimDoCupom = now()->addDays(20)->startOfSecond();
        $discount = Discount::factory()->create(['ends_at' => $fimDoCupom]);
        DiscountGrant::factory()->for($discount)->claimedBy($user)->create(['expires_at' => now()->addDays(90)]);

        $oferta = ($this->listar)($user)->first();

        expect($oferta->expiresAt?->equalTo($fimDoCupom))->toBeTrue()
            ->and($oferta->key)->toStartWith('grant:');
    });
});

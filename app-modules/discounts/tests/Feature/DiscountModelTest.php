<?php

declare(strict_types=1);

use TresPontosTech\Discounts\Enums\DiscountCustomerTypeEnum;
use TresPontosTech\Discounts\Enums\DiscountDistributionEnum;
use TresPontosTech\Discounts\Enums\DiscountDurationEnum;
use TresPontosTech\Discounts\Enums\DiscountRedemptionStatusEnum;
use TresPontosTech\Discounts\Enums\DiscountValueTypeEnum;
use TresPontosTech\Discounts\Models\Discount;

it('cria por padrão um desconto percentual, para todas as mensalidades, de uso único por pessoa', function (): void {
    $discount = Discount::factory()->create();

    expect($discount->value_type)->toBe(DiscountValueTypeEnum::Percent)
        ->and($discount->percent_off)->toBe(30)
        ->and($discount->duration)->toBe(DiscountDurationEnum::Forever)
        ->and($discount->distribution)->toBe(DiscountDistributionEnum::Grant)
        ->and($discount->customer_type)->toBe(DiscountCustomerTypeEnum::Person)
        ->and($discount->max_redemptions_per_customer)->toBe(1)
        ->and($discount->max_redemptions)->toBeNull()
        ->and($discount->eligible_plan_slugs)->toBeNull()
        ->and($discount->locks_plan)->toBeFalse()
        ->and($discount->first_subscription_only)->toBeFalse();
});

it('mantém os padrões do banco no objeto antes de salvar', function (): void {
    $discount = new Discount(['percent_off' => 20]);

    expect($discount->value_type)->toBe(DiscountValueTypeEnum::Percent)
        ->and($discount->duration)->toBe(DiscountDurationEnum::Forever)
        ->and($discount->distribution)->toBe(DiscountDistributionEnum::Grant)
        ->and($discount->customer_type)->toBe(DiscountCustomerTypeEnum::Person);
});

it('grava o estado de cupom por código, com código normalizado', function (): void {
    $discount = Discount::factory()->code(' clau 10 ')->create();

    expect($discount->distribution)->toBe(DiscountDistributionEnum::Code)
        ->and($discount->code)->toBe('CLAU10');
});

it('cria um desconto automático sem código', function (): void {
    $discount = Discount::factory()->automatic()->create();

    expect($discount->distribution)->toBe(DiscountDistributionEnum::Automatic)
        ->and($discount->code)->toBeNull();
});

it('cria um desconto em valor fixo sem percentual', function (): void {
    $discount = Discount::factory()->fixedAmount(4990)->create();

    expect($discount->value_type)->toBe(DiscountValueTypeEnum::FixedAmount)
        ->and($discount->amount_off_cents)->toBe(4990)
        ->and($discount->percent_off)->toBeNull();
});

it('cria um desconto repetido com a quantidade de ciclos', function (): void {
    $discount = Discount::factory()->repeating(2)->create();

    expect($discount->duration)->toBe(DiscountDurationEnum::Repeating)
        ->and($discount->duration_cycles)->toBe(2);
});

it('recusa combinações incoerentes de valor, duração e distribuição', function (array $atributos): void {
    expect(fn () => Discount::factory()->create($atributos))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'percentual sem percentual' => [['percent_off' => null]],
    'percentual acima de 100' => [['percent_off' => 101]],
    'percentual com valor fixo' => [['amount_off_cents' => 5000]],
    'valor fixo sem valor' => [['value_type' => DiscountValueTypeEnum::FixedAmount, 'percent_off' => null]],
    'valor fixo com percentual' => [['value_type' => DiscountValueTypeEnum::FixedAmount, 'amount_off_cents' => 5000]],
    'repetido sem ciclos' => [['duration' => DiscountDurationEnum::Repeating]],
    'ciclos sem repetido' => [['duration_cycles' => 3]],
    'código sem código' => [['distribution' => DiscountDistributionEnum::Code]],
    'código só com espaços' => [['distribution' => DiscountDistributionEnum::Code, 'code' => '   ']],
    'código em desconto automático' => [['distribution' => DiscountDistributionEnum::Automatic, 'code' => 'CLAU10']],
    'código sem janela nem data final' => [['distribution' => DiscountDistributionEnum::Code, 'code' => 'CLAU10', 'claim_window_days' => null]],
    'concessão sem prazo para assinar' => [['distribution' => DiscountDistributionEnum::Grant, 'claim_window_days' => null]],
]);

it('rotula os enums de desconto em português', function (): void {
    $enums = [
        ...DiscountValueTypeEnum::cases(),
        ...DiscountDurationEnum::cases(),
        ...DiscountDistributionEnum::cases(),
        ...DiscountCustomerTypeEnum::cases(),
        ...DiscountRedemptionStatusEnum::cases(),
    ];

    foreach ($enums as $enum) {
        expect($enum->getLabel())->toBeString()->not->toBeEmpty();
    }
});

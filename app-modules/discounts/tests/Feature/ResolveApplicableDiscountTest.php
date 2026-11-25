<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Discounts\Actions\ResolveApplicableDiscount;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;

it('resolve só o desconto resgatado, vigente, ativo e compatível com o plano', function (
    string $cenario,
    ?string $planoTravado,
    string $planoPedido,
    PriceAudienceEnum $audiencia,
    bool $aplica,
): void {
    $user = User::factory()->create();
    $discount = Discount::factory()->create([
        'active' => $cenario !== 'desconto inativo',
        'audience' => PriceAudienceEnum::Standalone,
    ]);

    $grant = DiscountGrant::factory()->for($discount)->claimedBy($user)->create([
        'plan_slug' => $planoTravado,
        'revoked_at' => $cenario === 'revogado' ? now() : null,
        'expires_at' => $cenario === 'vencido' ? now()->subDay() : now()->addDays(180),
    ]);

    if ($cenario === 'não resgatado') {
        $grant->update(['user_id' => null]);
    }

    $resolvido = resolve(ResolveApplicableDiscount::class)->handle($user, $planoPedido, $audiencia);

    expect($resolvido?->getKey())->toBe($aplica ? $grant->getKey() : null);
})->with([
    'não resgatado' => ['não resgatado', null, 'mensal', PriceAudienceEnum::Standalone, false],
    'revogado' => ['revogado', null, 'mensal', PriceAudienceEnum::Standalone, false],
    'vencido' => ['vencido', null, 'mensal', PriceAudienceEnum::Standalone, false],
    'audiência subsidiada' => ['subsidiado', null, 'mensal', PriceAudienceEnum::Subsidized, false],
    'plano travado em outro plano' => ['plano travado', 'anual', 'mensal', PriceAudienceEnum::Standalone, false],
    'desconto inativo' => ['desconto inativo', null, 'mensal', PriceAudienceEnum::Standalone, false],
    'plano nulo' => ['plano nulo', null, 'mensal', PriceAudienceEnum::Standalone, true],
    'plano igual' => ['plano igual', 'mensal', 'mensal', PriceAudienceEnum::Standalone, true],
]);

<?php

declare(strict_types=1);

use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Support\DiscountInviteUrl;

it('gera o link assinado do convite com o host do app.url', function (): void {
    config(['app.url' => 'https://flamma.test']);
    $grant = DiscountGrant::factory()->create();

    $url = DiscountInviteUrl::for($grant);

    expect($url)->toStartWith('https://flamma.test/convite-clau/' . $grant->getKey() . '?expires=')
        ->and($url)->toContain('signature=');
});

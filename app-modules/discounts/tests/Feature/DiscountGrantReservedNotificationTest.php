<?php

declare(strict_types=1);

use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Notifications\DiscountGrantReservedNotification;
use TresPontosTech\Discounts\Support\DiscountInviteUrl;

beforeEach(function (): void {
    config(['app.url' => 'https://flamma.test']);
});

it('monta o e-mail de ativação com desconto, saudação, botão e validade', function (): void {
    $discount = Discount::factory()->active()->create(['percent_off' => 30]);
    $grant = DiscountGrant::factory()->for($discount)->create([
        'email' => 'ana@example.com',
        'name' => 'Ana Souza',
    ]);

    $mail = (new DiscountGrantReservedNotification($grant))->toMail(new stdClass);

    expect($mail->subject)->toBe('Seu desconto de 30% na Flamma está reservado')
        ->and($mail->greeting)->toBe('Olá, Ana!')
        ->and($mail->actionUrl)->toBe(DiscountInviteUrl::for($grant))
        ->and(implode(' ', $mail->introLines))->toContain($grant->expires_at->format('d/m/Y'));
});

it('usa saudação genérica quando não há nome', function (): void {
    $grant = DiscountGrant::factory()->for(Discount::factory()->active()->create())->create(['name' => null]);

    $mail = (new DiscountGrantReservedNotification($grant))->toMail(new stdClass);

    expect($mail->greeting)->toBe('Olá!');
});

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

    expect($mail->subject)->toBe('Seu desconto na Flamma está reservado')
        ->and($mail->view)->toBe('emails.discounts.grant-reserved')
        ->and($mail->viewData)->toMatchArray([
            'summary' => '30% de desconto em todas as mensalidades',
            'greeting' => 'Olá, Ana!',
            'expiresAt' => $grant->expires_at->format('d/m/Y'),
            'activationUrl' => DiscountInviteUrl::for($grant),
        ]);
});

it('usa saudação genérica quando não há nome', function (): void {
    $grant = DiscountGrant::factory()->for(Discount::factory()->active()->create())->create(['name' => null]);

    $mail = (new DiscountGrantReservedNotification($grant))->toMail(new stdClass);

    expect($mail->viewData['greeting'])->toBe('Olá!');
});

it('renderiza o e-mail com o visual da Flamma e textos em português', function (): void {
    config(['app.name' => 'Flamma']);
    $grant = DiscountGrant::factory()->for(Discount::factory()->active()->create(['percent_off' => 30]))->create([
        'name' => 'Ana Souza',
    ]);

    $mail = (new DiscountGrantReservedNotification($grant))->toMail(new stdClass);
    $html = view($mail->view, $mail->viewData)->render();

    expect($html)
        ->toContain('Olá, Ana!')
        ->toContain('Ativar minha conta')
        ->toContain('Atenciosamente,')
        ->toContain('Equipe Flamma')
        ->toContain(e(DiscountInviteUrl::for($grant)))
        ->not->toContain('Regards')
        ->not->toContain('Laravel');
});

it('descreve um desconto em valor fixo sem cair no texto de percentual', function (): void {
    config(['app.name' => 'Flamma']);
    $grant = DiscountGrant::factory()->for(Discount::factory()->active()->fixedAmount()->create(['amount_off_cents' => 1000]))->create([
        'name' => 'Ana Souza',
    ]);

    $mail = (new DiscountGrantReservedNotification($grant))->toMail(new stdClass);
    $html = view($mail->view, $mail->viewData)->render();

    expect(str_replace("\u{a0}", ' ', $mail->viewData['summary']))->toBe('R$ 10,00 de desconto em todas as mensalidades')
        ->and($html)->toContain('de desconto em todas as mensalidades')
        ->not->toContain('% de desconto');
});

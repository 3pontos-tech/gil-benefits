<?php

declare(strict_types=1);

it('publishes the privacy policy with the analytics tool and the transfer abroad', function (): void {
    $this->get(route('site.privacy'))
        ->assertOk()
        ->assertSee('Política de Privacidade')
        ->assertSee('PostHog')
        ->assertSee('Estados Unidos')
        ->assertSee('30 dias')
        ->assertSee(config('services.contact.email'));
});

it('tells that PostHog receives the name, e-mail and phone to identify the account', function (): void {
    $this->get(route('site.privacy'))
        ->assertOk()
        ->assertSee('a sua conta nas análises, o seu nome, e-mail e telefone (quando você informa um)');
});

it('names the controller with the legal name and CNPJ when they are configured', function (): void {
    config(['services.contact.legal_name' => 'Empresa Exemplo Ltda.', 'services.contact.cnpj' => '00.000.000/0001-00']);

    $this->get(route('site.privacy'))
        ->assertOk()
        ->assertSee('Empresa Exemplo Ltda., CNPJ 00.000.000/0001-00');
});

it('falls back to a generic controller name without the legal data', function (): void {
    config(['services.contact.legal_name' => null, 'services.contact.cnpj' => null]);

    $this->get(route('site.privacy'))
        ->assertOk()
        ->assertSee('empresa responsável pelo Flamma');
});

it('links the privacy policy from the site footer', function (): void {
    $this->get(route('site.home'))
        ->assertOk()
        ->assertSee('href="' . route('site.privacy') . '"', false);
});

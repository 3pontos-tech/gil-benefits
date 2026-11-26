<?php

declare(strict_types=1);

it('informa que a Clau envia nome, e-mail, telefone e CPF do titular', function (): void {
    $this->get(route('site.privacy'))
        ->assertOk()
        ->assertSee('seu telefone e seu CPF')
        ->assertSee('Clau');
});

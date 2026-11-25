<?php

declare(strict_types=1);

it('tells that the clau sends the data of the discount holder', function (): void {
    $this->get(route('site.privacy'))
        ->assertOk()
        ->assertSee('código gerado a partir do seu CPF')
        ->assertSee('Clau');
});

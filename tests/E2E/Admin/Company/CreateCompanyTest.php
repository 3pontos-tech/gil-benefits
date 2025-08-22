<?php

use App\Models\Companies\Company;
use App\Models\Users\User;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

it('should be possible create a company', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $page = visit('/admin');

    $page->assertSee('Painel de Controle');
    $page->click('Criar Empresa');
    $page->assertSee('Criar Company');

    $page->select('form.user_id', $admin->id);
    $page->type('form.name', 'nome foda pra carai');
    $page->type('form.tax_id', '11111111111111');

    $page->assertValue('form.slug', 'nome-foda-pra-carai');

    $page->click('key-bindings-1'); // representa o botão 'Criar'

    $page->navigate('/admin/companies');
    $page->assertSee('nome foda pra carai');
    $page->assertSee($admin->name);

    assertDatabaseHas(Company::class, [
        'user_id' => $admin->id,
        'name' => 'nome foda pra carai',
        'slug' => 'nome-foda-pra-carai',
        'tax_id' => '11.111.111/1111-11',
    ]);

    assertDatabaseCount(Company::class, 1);
});

<?php

namespace Tests\E2E;

use App\Models\Companies\Company;
use App\Models\Users\Detail;
use App\Models\Users\User;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

it('should be possible create a user', function (): void {

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Company::factory()
        ->recycle($admin)
        ->create();

    filament()->setCurrentPanel('admin');

    $page = visit('/admin');

    $page->assertSee('Painel de Controle');
    $page->click('Criar Usuário');
    $page->assertSee('Criar User');

    $page->type('form.name', 'usuario foda');
    $page->type('form.email', 'fodase@fodase.com');
    $page->type('form.password', '12345678');

    $page->type('form.detail.tax_id', '11111111111');
    $page->type('form.detail.document_id', '111111111');
    $page->select('form.detail.company_id', '1');

    $page->click('key-bindings-1');

    $page->assertNoJavaScriptErrors();

    $page->assertSee('Criado');

    $page->navigate('/admin/users');
    $page->assertSee('usuario foda');

    assertDatabaseCount(User::class, 2);

    assertDatabaseHas(User::class, [
        'name' => 'usuario foda',
        'email' => 'fodase@fodase.com',
    ]);

    assertDatabaseHas(Detail::class, [
        'tax_id' => '111.111.111-11',
        'document_id' => '11.111.111-1',
        'company_id' => 1,
    ]);
});

it('should be possible create a user and see the page of edition', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $company = Company::factory()
        ->recycle($admin)
        ->create();

    filament()->setCurrentPanel('admin');

    $page = visit('/admin');

    $page->assertSee('Painel de Controle');
    $page->click('Criar Usuário');
    $page->assertSee('Criar User');

    $page->type('form.name', 'usuario foda');
    $page->type('form.email', 'fodase@fodase.com');
    $page->type('form.password', '12345678');

    $page->type('form.detail.tax_id', '12341111111');
    $page->type('form.detail.document_id', '111114321');
    $page->select('form.detail.company_id', '1');

    $page->click('key-bindings-1');

    $page->assertNoJavaScriptErrors();

    $page->assertSee('Criado');

    $page->assertSee('Editar User');

    $page->assertValue('form.name', 'usuario foda');
    $page->assertValue('form.email', 'fodase@fodase.com');
    $page->assertValue('form.detail.tax_id', '123.411.111-11');
    $page->assertValue('form.detail.document_id', '11.111.432-1');

    $page->assertValue('form.detail.company_id', $company->id);

    $page->assertSee('Excluir');
    $page->assertSee('Salvar alterações');
    $page->assertSee('Cancelar');
});


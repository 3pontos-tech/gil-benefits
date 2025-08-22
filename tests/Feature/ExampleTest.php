<?php

use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Models\Companies\Company;
use App\Models\Users\User;
use Livewire\Livewire;
use function Pest\Laravel\assertDatabaseCount;

it('returns a successful response', function (): void {

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
    $page->type('form.name', 'usuario foda');
    $page->type('form.password', '12345678');
    $page->type('form.name', 'usuario foda');
    $page->type('form.detail.tax_id', '11111111111');
    $page->type('form.detail.document_id', '111111111');
    $page->select('form.detail.company_id', '1');
    $page->click('Criar');

    $page->assertNoJavaScriptErrors();
    $page->wait(10);
    $page->assertSee('Criado');
    assertDatabaseCount(User::class, 3);
});

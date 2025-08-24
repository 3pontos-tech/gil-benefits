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

it('should be possible edit a user data', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    /** @var User $fakeUser */
    $fakeUser = User::factory()->create([
        'name' => 'fulano de tal',
        'email' => 'fulanodetal@email.com',
        'password' => '12345678',
    ]);

    /** @var Company $company */
    $company = Company::factory()
        ->create();

    /** @var Company $newCompany */
    $newCompany = Company::factory()->create();

    /** @var Detail $detail */
    $detail = Detail::factory()
        ->recycle($fakeUser)
        ->recycle($company)
        ->create();

    $this->actingAs($admin);

    $page = visit('/admin/users');

    $page->assertSee('Users');
    $page->assertSee($fakeUser->name);
    $page->assertSee($fakeUser->email);
    $page->assertSee($detail->document_id);
    $page->assertSee($detail->tax_id);

    $page->click($fakeUser->name);

    $page->assertValue('form.name', $fakeUser->name);
    $page->assertValue('form.email', $fakeUser->email);
    $page->assertValue('form.password', '');
    $page->assertValue('form.detail.tax_id', $detail->tax_id);
    $page->assertValue('form.detail.document_id', $detail->document_id);
    $page->assertValue('form.detail.company_id', $company->id);

    $page->type('form.name', 'novo nome');
    $page->type('form.email', 'novoemail@email.com');
    $page->type('form.password', '87654321');
    $page->type('form.detail.tax_id', '22222222222');
    $page->type('form.detail.document_id', '333333333');
    $page->select('form.detail.company_id', $newCompany->id);

    $page->click('key-bindings-2');

    $page->assertNoJavaScriptErrors();

    $page->assertSee('Salvo');

    $this->assertDatabaseHas(User::class, [
        'id' => $fakeUser->id,
        'name' => 'novo nome',
        'email' => 'novoemail@email.com',
    ]);

    $this->assertDatabaseHas(Detail::class, [
        'user_id' => $fakeUser->id,
        'tax_id' => '222.222.222-22',
        'document_id' => '33.333.333-3',
        'company_id' => $newCompany->id,
    ]);

    $page = visit("/admin/users/{$fakeUser->id}/edit");

    $page->assertValue('form.name', 'novo nome');
    $page->assertValue('form.email', 'novoemail@email.com');
    $page->assertValue('form.detail.tax_id', '222.222.222-22');
    $page->assertValue('form.detail.document_id', '33.333.333-3');
    $page->assertValue('form.detail.company_id', $newCompany->id);
});


//WIP
// it('should be possible to delete a user from the list', function () {
//     /** @var User $admin */
//     $admin = User::factory()->admin()->create();

//     /** @var User $user */
//     $user = User::factory()->create();

//     $this->actingAs($admin);

//     $page = visit('/admin/users');

//     $page->assertSee($user->name);

//     $page->check('.fi-ta-record-checkbox.fi-checkbox-input', str($user->id)->toString());

//     $page->click('.fi-btn.fi-size-md.fi-labeled-from-sm.fi-ac-btn-group');
//     $page->click('.fi-dropdown-list-item-label');
//     $page->assertSee('Excluir Users selecionado Você tem certeza que gostaria de fazer isso?');
//     $page->click('filamentFormButton');

//     $page->assertSee('Are you sure you want to delete?');
//     $page->click('confirm-delete');

//     $page->assertNoJavaScriptErrors();

//     $this->assertDatabaseMissing('users', ['id' => $user->id]);
// });

// it('should be possible to delete a user from the edit page', function () {
//     /** @var User $admin */
//     $admin = User::factory()->admin()->create();

//     /** @var User $user */
//     $user = User::factory()->create();

//     $this->actingAs($admin);

//     $page = visit("/admin/users/{$user->id}/edit");

//     $page->click('delete-user');
//     $page->assertSee('Are you sure you want to delete?');
//     $page->click('confirm-delete');

//     $page->assertNoJavaScriptErrors();

//     $this->assertDatabaseMissing('users', ['id' => $user->id]);
// });

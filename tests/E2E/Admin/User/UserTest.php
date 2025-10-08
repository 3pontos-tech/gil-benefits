<?php

namespace Tests\E2E;

use App\Models\Users\Detail;
use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

it('should be possible create a user', function () {

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
})->skipOnCI();

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

    $page = visit("/admin/users/$fakeUser->id/edit");

    $page->assertValue('form.name', 'novo nome');
    $page->assertValue('form.email', 'novoemail@email.com');
    $page->assertValue('form.detail.tax_id', '222.222.222-22');
    $page->assertValue('form.detail.document_id', '33.333.333-3');
    $page->assertValue('form.detail.company_id', $newCompany->id);
})->skipOnCI();

it('should be possible to force delete a user from the header action', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    /** @var User $user */
    $user = User::factory()->create();
    $user->delete();

    $this->actingAs($admin);

    $page = visit('/admin/users');

    $page->click('.fi-dropdown.fi-ta-filters-dropdown');
    $page->assertSee('Filtros  Registros excluídos');

    $page->select('tableFiltersForm.trashed.value', 'Somente registros excluídos');
    $page->click('Aplicar filtros');
    $page->click('.fi-dropdown.fi-ta-filters-dropdown');

    $page->assertSee($user->name);
    $page->assertSee($user->email);

    $page->check('.fi-ta-record-checkbox.fi-checkbox-input', str($user->id)->toString());

    $page->click('Abrir ações');
    $page->click('Forçar exclusão selecionado');
    $page->assertSee('Forçar exclusão de Users selecionado Você tem certeza que gostaria de fazer isso?');

    $page->click('Excluir');
    $page->assertSee('Excluído');

    $this->assertDatabaseMissing(User::class, [
        'id' => $user->id,
    ]);
    $this->assertNull(User::withTrashed()->find($user->id));
})->skipOnCI();

it('should be possible to force delete a user from the table action', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    /** @var User $user */
    $user = User::factory()->create();
    $user->delete();

    $this->actingAs($admin);

    $page = visit('/admin/users');

    $page->click('.fi-dropdown.fi-ta-filters-dropdown');
    $page->assertSee('Filtros  Registros excluídos');

    $page->select('tableFiltersForm.trashed.value', 'Somente registros excluídos');
    $page->click('Aplicar filtros');
    $page->click('.fi-dropdown.fi-ta-filters-dropdown');

    $page->assertSee($user->name);
    $page->assertSee($user->email);

    $page->click('Forçar exclusão');
    $page->assertSee('Forçar exclusão de user Você tem certeza que gostaria de fazer isso?');
    $page->click('Excluir');
    $page->assertSee('Excluído');

    $this->assertDatabaseMissing(User::class, [
        'id' => $user->id,
    ]);
    $this->assertNull(User::withTrashed()->find($user->id));
})->skipOnCI();

it('should be possible to force delete a user from the edit page', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    /** @var User $user */
    $user = User::factory()->create();
    $user->delete();

    $this->actingAs($admin);

    $page = visit("/admin/users/$user->id/edit");

    $page->click('Forçar exclusão');

    $page->assertSee('Forçar exclusão de User Você tem certeza que gostaria de fazer isso?');

    $page->pressAndWaitFor('Excluir', 1);

    $this->assertDatabaseMissing(User::class, [
        'id' => $user->id,
    ]);
    $this->assertNull(User::withTrashed()->find($user->id));
})->skipOnCI();

it('should be possible to soft deleted a user from header action', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    /** @var User $user */
    $user = User::factory()->create();

    $this->actingAs($admin);

    $page = visit('/admin/users');

    $page->assertSee($user->name);

    $page->check('.fi-ta-record-checkbox.fi-checkbox-input', str($user->id)->toString());

    $page->click('Abrir ações');
    $page->click('Excluir selecionado');
    $page->assertSee('Excluir Users selecionado Você tem certeza que gostaria de fazer isso?');

    $page->click('Excluir');
    $page->assertSee('Excluído');
    $page->assertNoJavaScriptErrors();

    $nullUser = User::query()->find($user->id);

    expect($nullUser)->toBeNull();

    $this->assertSoftDeleted(User::class, [
        'id' => $user->id,
    ]);
})->skipOnCI();

it('should be possible to soft deleted a user from the edit page', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    /** @var User $user */
    $user = User::factory()->create();

    $this->actingAs($admin);

    $page = visit("/admin/users/$user->id/edit");

    $page->click('Excluir');
    $page->assertSee('Excluir User Você tem certeza que gostaria de fazer isso?');

    $page->pressAndWaitFor('[x-data="filamentFormButton"].fi-color-danger', 1);

    $this->assertSoftDeleted(User::class, ['id' => $user->id]);
})->skipOnCI();

it('should be possible to restore a soft deleted user from the header action', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    /** @var User $user */
    $user = User::factory()->create();
    $user->delete();

    $this->actingAs($admin);

    $page = visit('/admin/users');

    $page->click('.fi-dropdown.fi-ta-filters-dropdown');
    $page->assertSee('Filtros  Registros excluídos');

    $page->select('tableFiltersForm.trashed.value', 'Somente registros excluídos');
    $page->click('Aplicar filtros');
    $page->click('.fi-dropdown.fi-ta-filters-dropdown');

    $page->assertSee($user->name);
    $page->assertSee($user->email);

    $page->check('.fi-ta-record-checkbox.fi-checkbox-input', str($user->id)->toString());
    $page->click('Abrir ações');
    $page->click('Restaurar selecionado');

    $page->assertSee('Restaurar Users selecionado Você tem certeza que gostaria de fazer isso?');

    $page->click('[x-data="filamentFormButton"]');

    $page->assertSee('Restaurado');

    $restoredUser = User::query()->find($user->id);

    expect($restoredUser)->not->toBeNull();

    $this->assertDatabaseHas(User::class, [
        'id' => $user->id,
        'deleted_at' => null,
    ]);
})->skipOnCI();

it('should be possible to restore a soft deleted user from the list page', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    /** @var User $user */
    $user = User::factory()->create();
    $user->delete();

    $this->actingAs($admin);

    $page = visit('/admin/users');

    $page->click('.fi-dropdown.fi-ta-filters-dropdown');
    $page->assertSee('Filtros  Registros excluídos');

    $page->select('tableFiltersForm.trashed.value', 'Somente registros excluídos');
    $page->click('Aplicar filtros');
    $page->click('.fi-dropdown.fi-ta-filters-dropdown');

    $page->assertSee($user->name);
    $page->assertSee($user->email);

    $page->press('Restaurar');
    $page->assertSee('Restaurar user Você tem certeza que gostaria de fazer isso?');

    $page->click('[x-data="filamentFormButton"]');
    $page->assertSee('Restaurado');
    $page->assertNoJavaScriptErrors();

    $restoredUser = User::query()->find($user->id);
    expect($restoredUser)->not->toBeNull();

    $this->assertDatabaseHas(User::class, [
        'id' => $user->id,
        'deleted_at' => null,
    ]);
})->skipOnCI();

it('should be possible to restore a soft deleted user from the edit page', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    /** @var User $user */
    $user = User::factory()->create();
    $user->delete();
    $this->actingAs($admin);

    $page = visit("/admin/users/$user->id/edit");

    $page->click('Restaurar');
    $page->assertSee('Restaurar User Você tem certeza que gostaria de fazer isso?');

    $page->click('.fi-modal-window [x-data="filamentFormButton"]');

    $page->assertSee('Restaurado');

    $this->assertDatabaseHas(User::class, [
        'id' => $user->id,
        'deleted_at' => null,
    ]);
})->skipOnCI();

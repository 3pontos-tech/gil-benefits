<?php

use App\Models\Users\Detail;
use App\Models\Users\User;
use TresPontosTech\Admin\Filament\Resources\Users\Pages\EditUser;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAsAdmin();
    $this->user = User::factory()->create();
});

it('should render', function (): void {
    livewire(EditUser::class, ['record' => $this->user->getKey()])
        ->assertOk();
});

it('should be able to register an user', function () {
    $company = Company::factory()->createOne();

    livewire(EditUser::class, ['record' => $this->user->getKey()])
        ->assertOk()
        ->fillForm([
            'name' => 'updated user',
            'email' => 'updated@doe.com',
            'password' => $this->user->password,
            'detail.tax_id' => '999.111.111-11',
            'detail.document_id' => '22.333.444-5',
            'detail.company_id' => $company->getKey(),

        ])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas(User::class, [
        'name' => 'updated user',
        'email' => 'updated@doe.com',
    ]);

    $this->user->fresh();

    assertDatabaseHas(Detail::class, [
        'user_id' => $this->user->getKey(),
        'tax_id' => '999.111.111-11',
        'document_id' => '22.333.444-5',
        'company_id' => $company->getKey(),
    ]);
});

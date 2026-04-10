<?php

use App\Models\Users\Detail;
use App\Models\Users\User;
use TresPontosTech\Admin\Filament\Resources\Users\Pages\CreateUser;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAsAdmin();
});

it('should render', function (): void {
    livewire(CreateUser::class)
        ->assertOk();
});

it('should be able to register a user', function () {
    $company = Company::factory()->createOne();

    livewire(CreateUser::class)
        ->assertOk()
        ->fillForm([
            'name' => 'John Doe',
            'email' => 'joe@doe.com',
            'password' => 'password',
            'detail.tax_id' => '97692325057',
            'detail.document_id' => '99.999.999-9',
            'detail.company_id' => $company->getKey(),

        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(User::class, [
        'name' => 'John Doe',
        'email' => 'joe@doe.com',
    ]);
    $user = User::query()->where('users.email', 'joe@doe.com')->first();

    assertDatabaseHas(Detail::class, [
        'user_id' => $user->getKey(),
        'tax_id' => '97692325057',
        'document_id' => '999999999',
        'company_id' => $company->getKey(),
    ]);
});

describe('validation tests', function () {

    test('name field', function ($value, $rule): void {
        livewire(CreateUser::class)
            ->assertOk()
            ->fillForm([
                'name' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => $rule]);
    })->with([
        'required' => ['', 'required'],
    ]);

    test('email field', function ($value, $rule): void {
        livewire(CreateUser::class)
            ->assertOk()
            ->fillForm([
                'email' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => $rule]);
    })->with([
        'required' => ['', 'required'],
        'email' => ['not-a-email', 'email'],
    ]);

    test('password field', function ($value, $rule): void {
        livewire(CreateUser::class)
            ->assertOk()
            ->fillForm([
                'password' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['password' => $rule]);
    })->with([
        'required' => ['', 'required'],
    ]);

    test('tax_id field', function ($value, $rule): void {
        Detail::factory()->state(['tax_id' => '26871748075'])->createOne();
        livewire(CreateUser::class)
            ->assertOk()
            ->set('data.detail.tax_id', $value)
            ->call('create')
            ->assertHasFormErrors(['detail.tax_id' => $rule]);
    })->with([
        'required' => [null, 'required'],
        'unique' => ['26871748075', 'unique'],
    ]);

    test('document_id field', function ($value, $rule): void {
        Detail::factory()->state(['document_id' => '26871748075'])->createOne();
        livewire(CreateUser::class)
            ->assertOk()
            ->set('data.detail.document_id', $value)
            ->call('create')
            ->assertHasFormErrors(['detail.document_id' => $rule]);
    })->with([
        'unique' => ['26871748075', 'unique'],
        'min_length' => ['AB', 'min'],
    ]);
});

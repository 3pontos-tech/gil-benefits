<?php

use App\Models\Users\Detail;
use App\Models\Users\User;
use Filament\Actions\Testing\TestAction;
use TresPontosTech\PanelCompany\Filament\Pages\Tenancy\EditTenantProfile;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

it('should register an employee ', function () {
    actingAsCompanyOwner();

    livewire(EditTenantProfile::class)
        ->assertOk()
        ->callAction(
            TestAction::make('Invite Member')->table(),
            data: [
                'name' => 'Funcionário Teste',
                'email' => 'teste@empresa.com',
                'password' => 'password123',
                'detail' => [
                    'tax_id' => '123.456.789-00',
                    'document_id' => '12.345.678-9',
                    'phone_number' => '+5511999999999',
                ],
            ]
        )
        ->assertHasNoFormErrors();

    assertDatabaseHas(User::class, [
        'name' => 'Funcionário Teste',
        'email' => 'teste@empresa.com',
    ]);
    assertDatabaseHas(Detail::class, [
        'tax_id' => '123.456.789-00',
        'document_id' => '12.345.678-9',
        'phone_number' => '+5511999999999',
    ]);
});

describe('validation tests', function () {
    test('name::field ', function ($value, $rule) {
        actingAsCompanyOwner();

        livewire(EditTenantProfile::class)
            ->assertOk()
            ->callAction(
                TestAction::make('Invite Member')->table(),
                data: [
                    'name' => $value,
                ]
            )
            ->assertHasFormErrors(['name' => $rule]);

        assertDatabaseMissing(User::class, [
            'name' => 'Funcionário Teste',
            'email' => 'teste@empresa.com',
        ]);
        assertDatabaseMissing(Detail::class, [
            'tax_id' => '123.456.789-00',
            'document_id' => '12.345.678-9',
            'phone_number' => '+5511999999999',
        ]);
    })->with([
        'required' => ['', 'required'],
    ]);

    test('email::field ', function ($value, $rule) {
        actingAsCompanyOwner();

        livewire(EditTenantProfile::class)
            ->assertOk()
            ->callAction(
                TestAction::make('Invite Member')->table(),
                data: [
                    'email' => value($value),
                ]
            )
            ->assertHasFormErrors(['email' => $rule]);

    })->with([
        'required' => ['', 'required'],
        'email' => ['notanemail', 'email'],
        'unique' => [fn () => auth()->user()->email, 'unique:users,email'],
    ]);
    test('password::field ', function ($value, $rule) {
        actingAsCompanyOwner();

        livewire(EditTenantProfile::class)
            ->assertOk()
            ->callAction(
                TestAction::make('Invite Member')->table(),
                data: [
                    'password' => value($value),
                ]
            )
            ->assertHasFormErrors(['password' => $rule]);

    })->with([
        'required' => ['', 'required'],
    ]);

    test('tax_id::field ', function ($value, $rule) {
        actingAsCompanyOwner();

        livewire(EditTenantProfile::class)
            ->assertOk()
            ->callAction(
                TestAction::make('Invite Member')->table(),
                data: [
                    'detail' => [
                        'tax_id' => value($value),
                    ]]
            )
            ->assertHasFormErrors(['detail.tax_id' => $rule]);

    })->with([
        'required' => [null, 'required'],
        'unique at company' => [fn () => (string) auth()->user()->detail->tax_id, '(tax_id) already registered at this company'],
    ]);
    test('document_id::field ', function ($value, $rule) {
        actingAsCompanyOwner();

        livewire(EditTenantProfile::class)
            ->assertOk()
            ->callAction(
                TestAction::make('Invite Member')->table(),
                data: [
                    'detail' => [
                        'document_id' => value($value),
                    ]]
            )
            ->assertHasFormErrors(['detail.document_id' => $rule]);

    })->with([
        'required' => [null, 'required'],
        'unique at company' => [fn () => (string) auth()->user()->detail->document_id, '(document_id) already registered at this company'],
    ]);
});

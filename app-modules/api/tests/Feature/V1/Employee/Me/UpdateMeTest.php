<?php

declare(strict_types=1);

use App\Models\Users\Detail;
use App\Models\Users\User;

use function Pest\Laravel\patchJson;

/**
 * Colaborador com cadastro completo (o `detail` com CPF que o cadastro do painel cria).
 */
function employeeWithDetail(string $phoneNumber = '+5511999990000'): User
{
    $employee = actingAsApiEmployee();
    Detail::factory()->for($employee)->create(['phone_number' => $phoneNumber]);

    return $employee;
}

it('updates the name and returns the profile', function (): void {
    $employee = employeeWithDetail();

    patchJson(route('api.v1.me.update'), ['name' => 'Maria Souza'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Maria Souza')
        ->assertJsonPath('data.phone_number', '+5511999990000');

    expect($employee->fresh()->name)->toBe('Maria Souza');
});

it('updates the phone number in E.164', function (): void {
    $employee = employeeWithDetail();

    patchJson(route('api.v1.me.update'), ['phone_number' => '+5521988887777'])
        ->assertOk()
        ->assertJsonPath('data.phone_number', '+5521988887777');

    expect($employee->detail()->value('phone_number'))->toBe('+5521988887777');
});

it('clears the phone number with null', function (): void {
    $employee = employeeWithDetail();

    patchJson(route('api.v1.me.update'), ['phone_number' => null])
        ->assertOk()
        ->assertJsonPath('data.phone_number', null);

    expect($employee->detail()->value('phone_number'))->toBeNull();
});

it('rejects a phone number outside E.164', function (string $phoneNumber): void {
    employeeWithDetail();

    patchJson(route('api.v1.me.update'), ['phone_number' => $phoneNumber])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['phone_number' => 'Informe o telefone com DDI e DDD, ex.: +5511999990000.']);
})->with(['(11) 99999-0000', '11999990000', '+55 11 99999 0000', '+0123456789']);

it('refuses a phone number while the registration has no detail', function (): void {
    actingAsApiEmployee();

    patchJson(route('api.v1.me.update'), ['phone_number' => '+5511999990000'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['phone_number' => 'Complete seu cadastro com o CPF antes de informar o telefone.']);
});

it('requires the current password to change the email', function (): void {
    employeeWithDetail();

    patchJson(route('api.v1.me.update'), ['email' => 'novo@example.com'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);

    patchJson(route('api.v1.me.update'), ['email' => 'novo@example.com', 'current_password' => 'wrong-password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);
});

it('changes the email with the current password', function (): void {
    $employee = employeeWithDetail();

    patchJson(route('api.v1.me.update'), ['email' => 'novo@example.com', 'current_password' => 'password'])
        ->assertOk()
        ->assertJsonPath('data.email', 'novo@example.com');

    expect($employee->fresh()->email)->toBe('novo@example.com');
});

it('refuses an email that belongs to someone else', function (): void {
    employeeWithDetail();
    User::factory()->create(['email' => 'taken@example.com']);

    patchJson(route('api.v1.me.update'), ['email' => 'taken@example.com', 'current_password' => 'password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('refuses an empty update', function (): void {
    employeeWithDetail();

    patchJson(route('api.v1.me.update'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name' => 'Informe o campo que deseja atualizar.']);
});

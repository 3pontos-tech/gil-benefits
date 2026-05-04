<?php

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Actions\ImportUsersFromFileAction;
use TresPontosTech\User\Mail\WelcomeUserMail;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses(RefreshDatabase::class);

function makeCsvFile(array $rows, array $headers = ['name', 'email', 'document_id', 'tax_id', 'phone_number']): string
{
    $path = tempnam(sys_get_temp_dir(), 'import_test_') . '.csv';
    $handle = fopen($path, 'w');
    fputcsv($handle, $headers);
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return $path;
}

// --- Happy path ---

it('imports all users and creates details and roles', function (): void {
    $company = Company::factory()->create();

    $csv = makeCsvFile([
        ['João Silva', 'joao@empresa.com', '12345678', '123.456.789-00', '11999999999'],
        ['Maria Costa', 'maria@empresa.com', '87654321', '987.654.321-00', '21988887777'],
    ]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result->imported)->toBe(2);
    expect($result->errors)->toBeEmpty();

    assertDatabaseHas(User::class, ['email' => 'joao@empresa.com']);
    assertDatabaseHas(User::class, ['email' => 'maria@empresa.com']);

    assertDatabaseHas(Detail::class, ['document_id' => '12345678', 'tax_id' => '12345678900', 'phone_number' => '11999999999']);
    assertDatabaseHas(Detail::class, ['document_id' => '87654321', 'tax_id' => '98765432100', 'phone_number' => '21988887777']);

    $user = User::query()->where('email', 'joao@empresa.com')->first();
    expect($company->employees()->where('user_id', $user->getKey())->exists())->toBeTrue();
    expect($user->hasRole(Roles::Employee))->toBeTrue();
});

it('imports users without document_id', function (): void {
    $company = Company::factory()->create();

    $csv = makeCsvFile([
        ['Pedro Souza', 'pedro@empresa.com', '', '111.222.333-44', '11999999999'],
    ]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result->imported)->toBe(1);
    expect($result->errors)->toBeEmpty();
});

it('sends welcome email with temporary password to each imported user', function (): void {
    Mail::fake();

    $company = Company::factory()->create();

    $csv = makeCsvFile([
        ['Joao Silva', 'joao@empresa.com', '12345678', '123.456.789-00', '11999999999'],
        ['Maria Costa', 'maria@empresa.com', '87654321', '987.654.321-00', '21988887777'],
    ]);

    resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    Mail::assertQueued(
        WelcomeUserMail::class,
        fn (WelcomeUserMail $mail): bool => $mail->hasTo('joao@empresa.com') && filled($mail->password),
    );

    Mail::assertQueued(
        WelcomeUserMail::class,
        fn (WelcomeUserMail $mail): bool => $mail->hasTo('maria@empresa.com') && filled($mail->password),
    );

    Mail::assertQueuedCount(2);
});

it('each imported user receives a unique temporary password', function (): void {
    Mail::fake();

    $company = Company::factory()->create();

    $csv = makeCsvFile([
        ['Joao Silva', 'joao@empresa.com', '12345678', '123.456.789-00', '11999999999'],
        ['Maria Costa', 'maria@empresa.com', '87654321', '987.654.321-00', '21988887777'],
    ]);

    resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    $passwords = Mail::queued(WelcomeUserMail::class)
        ->map(fn (WelcomeUserMail $mail): ?string => $mail->password)
        ->unique();

    expect($passwords)->toHaveCount(2);
});

it('returns zero imported and empty errors for an empty file', function (): void {
    $company = Company::factory()->create();

    $result = resolve(ImportUsersFromFileAction::class)->execute(makeCsvFile([]), 'csv', $company);

    expect($result->imported)->toBe(0);
    expect($result->errors)->toBeEmpty();
});

// --- File structure validation ---

it('fails when required columns are missing from the file', function (): void {
    $company = Company::factory()->create();

    $csv = makeCsvFile([['João', 'joao@empresa.com']], ['name', 'email']);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result->imported)->toBe(0);
    expect($result->errors)->toHaveCount(1);
    expect($result->errors[0]->message)->toContain('Estrutura inválida');
});

it('ignores empty rows and still imports valid ones', function (): void {
    $company = Company::factory()->create();

    $csv = makeCsvFile([
        ['João Silva', 'joao@empresa.com', '12345678', '123.456.789-00', '11999999999'],
        ['', '', '', '', ''],
        ['Maria Costa', 'maria@empresa.com', '87654321', '987.654.321-00', '21988887777'],
    ]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result->imported)->toBe(2);
    expect($result->errors)->toBeEmpty();
});

// --- Validation failures abort the entire import ---

it('fails entire import when any row is missing phone_number', function (): void {
    $company = Company::factory()->create();

    $csv = makeCsvFile([
        ['Válido', 'valido@empresa.com', '12345678', '123.456.789-00', '11999999999'],
        ['Sem Telefone', 'semtelefone@empresa.com', '87654321', '987.654.321-00', ''],
    ]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result->imported)->toBe(0);
    expect($result->errors)->not->toBeEmpty();

    assertDatabaseMissing(User::class, ['email' => 'valido@empresa.com']);
});

it('fails entire import when any row has an invalid email', function (): void {
    $company = Company::factory()->create();

    $csv = makeCsvFile([
        ['Válido', 'valido@empresa.com', '12345678', '123.456.789-00', '11999999999'],
        ['Inválido', 'nao-é-um-email', '87654321', '987.654.321-00', '21988887777'],
    ]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result->imported)->toBe(0);
    expect($result->errors)->toHaveCount(1);
    expect($result->errors[0]->message)->toContain('inválido');

    assertDatabaseMissing(User::class, ['email' => 'valido@empresa.com']);
});

it('fails entire import when duplicate emails exist within the file', function (): void {
    $company = Company::factory()->create();

    $csv = makeCsvFile([
        ['João', 'joao@empresa.com', '12345678', '123.456.789-00', '11999999999'],
        ['João Dup', 'joao@empresa.com', '87654321', '987.654.321-00', '21988887777'],
    ]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result->imported)->toBe(0);
    expect($result->errors)->toHaveCount(1);
    expect($result->errors[0]->message)->toContain('duplicado');
});

it('fails entire import when any email is already registered in the system', function (): void {
    $company = Company::factory()->create();
    User::factory()->create(['email' => 'existing@empresa.com']);

    $csv = makeCsvFile([
        ['Existing', 'existing@empresa.com', '12345678', '123.456.789-00', '11999999999'],
        ['Novo', 'novo@empresa.com', '87654321', '987.654.321-00', '21988887777'],
    ]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result->imported)->toBe(0);
    expect($result->errors)->toHaveCount(1);
    expect($result->errors[0]->email)->toBe('existing@empresa.com');

    assertDatabaseMissing(User::class, ['email' => 'novo@empresa.com']);
});

it('fails entire import when duplicate tax_ids exist within the file', function (): void {
    $company = Company::factory()->create();

    $csv = makeCsvFile([
        ['João', 'joao@empresa.com', '12345678', '123.456.789-00', '11999999999'],
        ['João Dup', 'joaodup@empresa.com', '87654321', '123.456.789-00', '21988887777'],
    ]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result->imported)->toBe(0);
    expect($result->errors)->toHaveCount(1);
    expect($result->errors[0]->message)->toContain('duplicado');
});

it('fails entire import when any tax_id is already registered in the system', function (): void {
    $company = Company::factory()->create();
    $existingUser = User::factory()->create();
    Detail::factory()->create(['user_id' => $existingUser->getKey(), 'tax_id' => '11122233344']);

    $csv = makeCsvFile([
        ['Novo', 'novo@empresa.com', '12345678', '111.222.333-44', '11999999999'],
    ]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result->imported)->toBe(0);
    expect($result->errors)->toHaveCount(1);
    expect($result->errors[0]->message)->toContain('já cadastrado');

    assertDatabaseMissing(User::class, ['email' => 'novo@empresa.com']);
});

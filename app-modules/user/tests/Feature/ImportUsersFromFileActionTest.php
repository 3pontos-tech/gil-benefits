<?php

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Actions\ImportUsersFromFileAction;
use TresPontosTech\User\DTOs\ImportErrorDTO;
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
    expect(
        $company->employees()
            ->wherePivot('user_id', $user->getKey())
            ->wherePivot('role', Roles::Employee->value)
            ->exists()
    )->toBeTrue();
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

    Mail::assertQueued(WelcomeUserMail::class, 2);
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

it('imports a row with a blank phone_number', function (): void {
    $company = Company::factory()->create();

    $csv = makeCsvFile([
        ['Válido', 'valido@empresa.com', '12345678', '123.456.789-00', '11999999999'],
        ['Sem Telefone', 'semtelefone@empresa.com', '87654321', '987.654.321-00', ''],
    ]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result->imported)->toBe(2);
    expect($result->errors)->toBeEmpty();

    assertDatabaseHas(Detail::class, ['tax_id' => '98765432100', 'phone_number' => null]);
});

it('imports a file without the phone_number column', function (): void {
    $company = Company::factory()->create();

    $csv = makeCsvFile(
        [['Sem Telefone', 'semtelefone@empresa.com', '87654321', '987.654.321-00']],
        ['name', 'email', 'document_id', 'tax_id'],
    );

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result->imported)->toBe(1);
    expect($result->errors)->toBeEmpty();

    assertDatabaseHas(Detail::class, ['tax_id' => '98765432100', 'phone_number' => null]);
});

// --- Validation failures abort the entire import ---

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

it('imports a mixed spreadsheet in one go, reporting each row that was not simply created', function (): void {
    Mail::fake();

    $company = Company::factory()->create();
    $member = User::factory()->create(['email' => 'member@empresa.com']);
    Detail::factory()->for($member)->create(['tax_id' => '11111111111']);
    $company->employees()->attach($member->getKey(), ['role' => Roles::Employee->value]);
    $selfSignup = User::factory()->create(['email' => 'signup@empresa.com']);
    Detail::factory()->for($selfSignup)->create(['tax_id' => '22222222222']);
    $elsewhere = User::factory()->create(['email' => 'elsewhere@empresa.com']);
    Detail::factory()->for($elsewhere)->create(['tax_id' => '33333333333']);
    Company::factory()->create()->employees()->attach($elsewhere->getKey(), ['role' => Roles::Employee->value]);

    $csv = makeCsvFile([
        ['Nova', 'nova@empresa.com', '12345678', '444.444.444-44', '11999999999'],
        ['Nova de novo', 'nova@empresa.com', '87654321', '555.555.555-55', '11999999999'],
        ['Membro', 'member@empresa.com', '', '111.111.111-11', ''],
        ['Cadastrada', 'signup@empresa.com', '', '222.222.222-22', ''],
        ['Outra empresa', 'elsewhere@empresa.com', '', '333.333.333-33', ''],
    ]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result)
        ->imported->toBe(1)
        ->linked->toBe(1)
        ->skipped->toBe(2)
        ->needsSupport->toBe(1)
        ->errors->toBeEmpty();
    expect(array_map(fn (ImportErrorDTO $report): array => [$report->row, $report->email, $report->message], $result->rowReports))->toBe([
        [3, 'nova@empresa.com', 'Ignorado: e-mail ou CPF repetido de uma linha anterior da planilha.'],
        [4, 'member@empresa.com', 'Ignorado: já faz parte da empresa.'],
        [5, 'signup@empresa.com', 'Já tinha cadastro no ' . config('app.name') . ' e foi vinculado à empresa.'],
        [6, 'elsewhere@empresa.com', 'Não importado: a pessoa está vinculada a outra empresa. Contate o suporte.'],
    ]);
    expect(User::query()->where('email', 'nova@empresa.com')->count())->toBe(1);
    expect($company->employees()->whereKey($selfSignup->getKey())->exists())->toBeTrue();
    expect($company->employees()->whereKey($elsewhere->getKey())->exists())->toBeFalse();
});

it('fails entire import when the people joining exceed the free seats, ignoring skipped rows', function (): void {
    $company = Company::factory()->create();
    CompanyPlan::factory()->create([
        'company_id' => $company->getKey(),
        'status' => CompanyPlanStatusEnum::Active->value,
        'starts_at' => now()->subDay(),
        'seats' => 1,
    ]);
    $selfSignup = User::factory()->create(['email' => 'signup@empresa.com']);
    Detail::factory()->for($selfSignup)->create(['tax_id' => '22222222222']);

    $csv = makeCsvFile([
        ['Nova', 'nova@empresa.com', '12345678', '444.444.444-44', '11999999999'],
        ['Nova de novo', 'nova@empresa.com', '87654321', '555.555.555-55', '21988887777'],
        ['Cadastrada', 'signup@empresa.com', '', '222.222.222-22', ''],
    ]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result->errors)->toHaveCount(1)
        ->and($result->errors[0]->message)->toBe('Limite de assentos excedido. Disponíveis: 1, solicitados: 2.');
    assertDatabaseMissing(User::class, ['email' => 'nova@empresa.com']);
    expect($company->employees()->whereKey($selfSignup->getKey())->exists())->toBeFalse();
});

it('fails entire import on a format error even when other rows are already registered', function (): void {
    $company = Company::factory()->create();
    User::factory()->create(['email' => 'existing@empresa.com']);

    $csv = makeCsvFile([
        ['Existing', 'existing@empresa.com', '12345678', '123.456.789-00', '11999999999'],
        ['Inválido', 'nao-é-um-email', '87654321', '987.654.321-00', '21988887777'],
    ]);

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result->errors)->toHaveCount(1)
        ->and($result->errors[0]->message)->toBe('Email inválido.');
});

it('links the imported employee to the department named in the row', function (): void {
    $company = Company::factory()->create();
    $sales = Department::factory()->for($company)->create(['name' => 'Vendas']);

    $csv = makeCsvFile(
        [['Ana Lima', 'ana@empresa.com', '12345678', '123.456.789-00', '11999999999', 'vendas']],
        ['name', 'email', 'document_id', 'tax_id', 'phone_number', 'department'],
    );

    resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    $user = User::query()->where('email', 'ana@empresa.com')->firstOrFail();

    expect($company->employees()->find($user->getKey())->pivot->department_id)->toBe($sales->id);
});

it('returns a structure error when two columns have the same header', function (): void {
    $company = Company::factory()->create();

    $csv = makeCsvFile(
        [['Ana Lima', 'ana@empresa.com', 'outra@empresa.com', '123.456.789-00', '11999999999']],
        ['name', 'email', 'Email', 'tax_id', 'phone_number'],
    );

    $result = resolve(ImportUsersFromFileAction::class)->execute($csv, 'csv', $company);

    expect($result->imported)->toBe(0)
        ->and($result->errors)->toHaveCount(1)
        ->and($result->errors[0]->row)->toBe(1)
        ->and($result->errors[0]->message)->toContain('Colunas repetidas: email');

    assertDatabaseMissing(User::class, ['email' => 'ana@empresa.com']);
});

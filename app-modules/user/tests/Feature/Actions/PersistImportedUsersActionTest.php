<?php

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Actions\ClassifyImportRowsAction;
use TresPontosTech\User\Actions\PersistImportedUsersAction;
use TresPontosTech\User\DTOs\ClassifiedImportRowDTO;
use TresPontosTech\User\DTOs\ImportErrorDTO;
use TresPontosTech\User\Enums\ImportRowOutcome;
use TresPontosTech\User\Events\ExistingUserImported;
use TresPontosTech\User\Events\UserImported;
use TresPontosTech\User\Mail\WelcomeUserMail;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

function makeUserRows(int $count, int $startIndex = 1): Collection
{
    return collect(range($startIndex, $startIndex + $count - 1))->map(fn (int $i): array => [
        'name' => 'User ' . $i,
        'email' => sprintf('persistuser%d@example.com', $i),
        'tax_id' => str_pad((string) $i, 11, '0', STR_PAD_LEFT),
        'phone_number' => '11' . str_pad((string) $i, 9, '0', STR_PAD_LEFT),
        'document_id' => 'DOC' . $i,
    ]);
}

it('persists users, details, company pivot, and roles in batch', function (): void {
    Mail::fake();

    $company = Company::factory()->create();
    $rows = makeUserRows(3);

    $result = resolve(PersistImportedUsersAction::class)->execute($rows, $company);

    expect($result->imported)->toBe(3)
        ->and($result->errors)->toBeEmpty();

    assertDatabaseCount(User::class, 1 + 3); // 1 company owner + 3 imported
    assertDatabaseCount(Detail::class, 3);

    foreach ($rows as $row) {
        assertDatabaseHas(User::class, ['email' => $row['email']]);

        $user = User::query()->where('email', $row['email'])->first();

        expect($company->employees()->wherePivot('user_id', $user->id)->exists())->toBeTrue();
        expect(
            $company->employees()
                ->wherePivot('user_id', $user->id)
                ->wherePivot('role', Roles::Employee->value)
                ->exists()
        )->toBeTrue();
    }
});

it('queues a welcome email for each created user', function (): void {
    Mail::fake();

    $company = Company::factory()->create();
    $rows = makeUserRows(2);

    resolve(PersistImportedUsersAction::class)->execute($rows, $company);

    Mail::assertQueued(WelcomeUserMail::class, 2);
});

it('hashes the temporary password with a lower bcrypt cost than configured, matching the emailed password', function (): void {
    Mail::fake();
    config(['hashing.bcrypt.rounds' => 10]);
    Hash::forgetDrivers();

    $company = Company::factory()->create();

    resolve(PersistImportedUsersAction::class)->execute(makeUserRows(1), $company);

    $user = User::query()->where('email', 'persistuser1@example.com')->firstOrFail();

    Mail::assertQueued(WelcomeUserMail::class, fn (WelcomeUserMail $mail): bool => Hash::check((string) $mail->password, $user->password));
    expect(password_get_info($user->password)['options']['cost'])->toBe(4);
});

it('announces each created user, so the onboarding reaches imported employees too', function (): void {
    Mail::fake();
    Event::fake([UserImported::class]);

    $company = Company::factory()->create();
    $rows = makeUserRows(2);

    resolve(PersistImportedUsersAction::class)->execute($rows, $company);

    Event::assertDispatchedTimes(UserImported::class, 2);
    Event::assertDispatched(UserImported::class, fn (UserImported $event): bool => $event->user->email === $rows->first()['email']);
});

it('returns an ImportUsersResultDTO with the correct imported count', function (): void {
    Mail::fake();

    $company = Company::factory()->create();
    $rows = makeUserRows(5);

    $result = resolve(PersistImportedUsersAction::class)->execute($rows, $company);

    expect($result->imported)->toBe(5)
        ->and($result->hasErrors())->toBeFalse();
});

it('links a person registered after the validation instead of failing the chunk', function (): void {
    Mail::fake();

    $company = Company::factory()->create();
    $rows = makeUserRows(100, startIndex: 1000)->push([
        'name' => 'Late Signup',
        'email' => 'late@example.com',
        'tax_id' => '99999999999',
        'phone_number' => '11999999999',
        'document_id' => 'DOCLATE',
    ]);
    $lateSignup = User::factory()->create(['email' => 'late@example.com']);

    $result = resolve(PersistImportedUsersAction::class)->execute($rows, $company);

    expect($result->imported)->toBe(100)
        ->and($result->linked)->toBe(1);
    expect(User::query()->where('email', 'late@example.com')->count())->toBe(1);
    expect($company->employees()->whereKey($lateSignup->getKey())->exists())->toBeTrue();
});

it('links each imported employee to the department id resolved before the job', function (): void {
    Mail::fake();

    $company = Company::factory()->create();
    $engineering = Department::factory()->for($company)->create(['name' => 'Engenharia']);

    $rows = makeUserRows(2, startIndex: 2000)->values()->map(fn (array $row, int $index): array => [
        ...$row,
        'department' => 'Engenharia',
        'department_id' => [$engineering->id, null][$index],
    ]);

    $engineering->update(['name' => 'Engenharia Renomeada']);

    resolve(PersistImportedUsersAction::class)->execute($rows, $company);

    $departmentByEmail = $company->employees()->get()
        ->mapWithKeys(fn (User $user): array => [$user->email => $user->pivot->department_id]);

    expect($departmentByEmail->get('persistuser2000@example.com'))->toBe($engineering->id)
        ->and($departmentByEmail->get('persistuser2001@example.com'))->toBeNull();
});

it('links an existing person without a new account, keeping their password and details', function (): void {
    Mail::fake();
    Event::fake([UserImported::class, ExistingUserImported::class]);

    $company = Company::factory()->create();
    $engineering = Department::factory()->for($company)->create();
    $person = User::factory()->create(['email' => 'existing@example.com', 'name' => 'Original Name']);
    Detail::factory()->for($person)->create(['tax_id' => '12312312312', 'phone_number' => '11911112222']);
    $passwordBefore = $person->password;

    $result = resolve(PersistImportedUsersAction::class)->execute(collect([[
        'name' => 'Spreadsheet Name',
        'email' => 'existing@example.com',
        'tax_id' => '12312312312',
        'phone_number' => '11933334444',
        'department_id' => $engineering->id,
        '__row_number' => 2,
    ]]), $company);

    expect($result->linked)->toBe(1)
        ->and($result->imported)->toBe(0);
    expect(User::query()->where('email', 'existing@example.com')->count())->toBe(1);
    assertDatabaseHas(User::class, ['id' => $person->id, 'name' => 'Original Name', 'password' => $passwordBefore]);
    assertDatabaseHas(Detail::class, ['user_id' => $person->id, 'phone_number' => '11911112222']);
    assertDatabaseHas('company_employees', [
        'company_id' => $company->id,
        'user_id' => $person->id,
        'role' => Roles::Employee->value,
        'active' => true,
        'department_id' => $engineering->id,
    ]);
    Mail::assertNotQueued(WelcomeUserMail::class);
    Event::assertNotDispatched(UserImported::class);
    Event::assertDispatched(ExistingUserImported::class, fn (ExistingUserImported $event): bool => $event->user->is($person));
});

it('reactivates an inactive employee, taking the department of the row', function (): void {
    Mail::fake();
    Event::fake([ExistingUserImported::class]);

    $company = Company::factory()->create();
    $sales = Department::factory()->for($company)->create();
    $support = Department::factory()->for($company)->create();
    $former = User::factory()->create(['email' => 'former@example.com']);
    Detail::factory()->for($former)->create(['tax_id' => '45645645645']);
    $company->employees()->attach($former->getKey(), ['role' => Roles::Employee->value, 'active' => false, 'department_id' => $sales->id]);

    $result = resolve(PersistImportedUsersAction::class)->execute(collect([[
        'name' => 'Former',
        'email' => 'former@example.com',
        'tax_id' => '45645645645',
        'department_id' => $support->id,
        '__row_number' => 2,
    ]]), $company);

    expect($result->reactivated)->toBe(1)
        ->and(array_map(fn (ImportErrorDTO $report): array => [$report->row, $report->message], $result->rowReports))
        ->toBe([[2, 'Funcionário inativo da empresa, reativado.']]);
    expect($company->employees()->find($former->getKey())->pivot)
        ->active->toBeTrue()
        ->department_id->toBe($support->id);
    Mail::assertNotQueued(WelcomeUserMail::class);
    Event::assertDispatched(ExistingUserImported::class);
});

it('keeps the department of a reactivated employee when the row leaves it blank', function (): void {
    Mail::fake();

    $company = Company::factory()->create();
    $sales = Department::factory()->for($company)->create();
    $former = User::factory()->create(['email' => 'former@example.com']);
    Detail::factory()->for($former)->create(['tax_id' => '45645645645']);
    $company->employees()->attach($former->getKey(), ['role' => Roles::Employee->value, 'active' => false, 'department_id' => $sales->id]);

    resolve(PersistImportedUsersAction::class)->execute(collect([[
        'name' => 'Former',
        'email' => 'former@example.com',
        'tax_id' => '45645645645',
        'department_id' => null,
        '__row_number' => 2,
    ]]), $company);

    expect($company->employees()->find($former->getKey())->pivot->department_id)->toBe($sales->id);
});

it('reports a person hired by another company after the validation and still writes the rest of the chunk', function (): void {
    Mail::fake();

    $company = Company::factory()->create();
    $person = User::factory()->create(['email' => 'hired@example.com']);
    Detail::factory()->for($person)->create(['tax_id' => '78978978978']);
    Company::factory()->create()->employees()->attach($person->getKey(), ['role' => Roles::Employee->value]);

    $rows = makeUserRows(1)->push([
        'name' => 'Hired',
        'email' => 'hired@example.com',
        'tax_id' => '78978978978',
        '__row_number' => 3,
    ]);

    $result = resolve(PersistImportedUsersAction::class)->execute($rows, $company);

    expect($result->imported)->toBe(1)
        ->and($result->needsSupport)->toBe(1);
    expect($company->employees()->whereKey($person->getKey())->exists())->toBeFalse();
    assertDatabaseHas(User::class, ['email' => 'persistuser1@example.com']);
});

it('classifies and writes the chunk again when an insert hits a person created in the meantime', function (): void {
    Mail::fake();

    $company = Company::factory()->create();
    $person = User::factory()->create(['email' => 'race@example.com']);
    Detail::factory()->for($person)->create(['tax_id' => '32132132132']);

    app()->instance(ClassifyImportRowsAction::class, new class extends ClassifyImportRowsAction
    {
        private int $calls = 0;

        public function execute(Collection $rows, Company $company): Collection
        {
            $classified = parent::execute($rows, $company);

            return ++$this->calls === 2
                ? $classified->map(fn (ClassifiedImportRowDTO $row): ClassifiedImportRowDTO => new ClassifiedImportRowDTO($row->row, ImportRowOutcome::Create))
                : $classified;
        }
    });

    $result = resolve(PersistImportedUsersAction::class)->execute(collect([[
        'name' => 'Race',
        'email' => 'race@example.com',
        'tax_id' => '32132132132',
        '__row_number' => 2,
    ]]), $company);

    expect($result->linked)->toBe(1)
        ->and($result->imported)->toBe(0);
    expect(User::query()->where('email', 'race@example.com')->count())->toBe(1);
    expect($company->employees()->whereKey($person->getKey())->exists())->toBeTrue();
});

<?php

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Actions\PersistImportedUsersAction;
use TresPontosTech\User\Mail\WelcomeUserMail;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses(RefreshDatabase::class);

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
        expect($user->hasRole(Roles::Employee))->toBeTrue();
    }
});

it('queues a welcome email for each created user', function (): void {
    Mail::fake();

    $company = Company::factory()->create();
    $rows = makeUserRows(2);

    resolve(PersistImportedUsersAction::class)->execute($rows, $company);

    Mail::assertQueued(WelcomeUserMail::class, 2);
});

it('returns an ImportUsersResultDTO with the correct imported count', function (): void {
    Mail::fake();

    $company = Company::factory()->create();
    $rows = makeUserRows(5);

    $result = resolve(PersistImportedUsersAction::class)->execute($rows, $company);

    expect($result->imported)->toBe(5)
        ->and($result->hasErrors())->toBeFalse();
});

it('a rollback in one chunk does not affect previously persisted chunks', function (): void {
    Mail::fake();

    $company = Company::factory()->create();

    // Create a user that will cause a unique constraint violation in chunk 2
    User::factory()->create(['email' => 'conflict@example.com']);

    // 100 valid rows → chunk 1
    $rows = makeUserRows(100, startIndex: 1000);

    // 1 conflicting row → triggers rollback in chunk 2
    $rows->push([
        'name' => 'Conflict User',
        'email' => 'conflict@example.com',
        'tax_id' => '99999999999',
        'phone_number' => '11999999999',
        'document_id' => 'DOCCONFLICT',
    ]);

    $threwException = false;
    try {
        resolve(PersistImportedUsersAction::class)->execute($rows, $company);
    } catch (Throwable) {
        $threwException = true;
    }

    expect($threwException)->toBeTrue();

    // 1 pre-existing conflict user + 100 users from chunk 1 (chunk 2 rolled back)
    assertDatabaseCount(User::class, 1 + 1 + 100);

    // The conflicting email is not duplicated (still only the original user)
    expect(User::query()->where('email', 'conflict@example.com')->count())->toBe(1);

    // Users from chunk 1 are persisted
    assertDatabaseHas(User::class, ['email' => 'persistuser1000@example.com']);
    assertDatabaseMissing(User::class, ['email' => 'persistuser1101@example.com']);
});

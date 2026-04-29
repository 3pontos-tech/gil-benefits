<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\User\Actions\PersistImportedUsersAction;
use TresPontosTech\User\Jobs\ImportUsersJob;

function makeJobRows(int $count = 2): Collection
{
    return collect(range(1, $count))->map(fn (int $i): array => [
        'name' => 'User ' . $i,
        'email' => 'jobuser' . $i . '@example.com',
        'document_id' => 'DOC' . $i,
        'tax_id' => str_pad((string) ($i * 100000000), 11, '0', STR_PAD_LEFT),
        'phone_number' => '11' . str_pad((string) $i, 9, '9', STR_PAD_LEFT),
        'password' => 'password',
        '__row_number' => $i + 1,
    ]);
}

it('persists users when the job is handled with a valid company', function (): void {
    $company = Company::factory()->create();
    $rows = makeJobRows(2);

    $job = new ImportUsersJob($rows, $company->id, 'any-user-id');
    $job->handle(resolve(PersistImportedUsersAction::class));

    expect(User::query()->whereIn('email', ['jobuser1@example.com', 'jobuser2@example.com'])->count())->toBe(2);
});

it('throws ModelNotFoundException when the company does not exist', function (): void {
    $job = new ImportUsersJob(collect(), 'non-existent-id', 'any-user-id');

    expect(fn () => $job->handle(resolve(PersistImportedUsersAction::class)))
        ->toThrow(ModelNotFoundException::class);
});

it('logs an error when the failed method is called', function (): void {
    Log::spy();

    $companyId = 'non-existent-id';
    $job = new ImportUsersJob(collect(), $companyId, 'any-user-id');
    $exception = new RuntimeException('Something went wrong');

    $job->failed($exception);

    Log::shouldHaveReceived('error')
        ->once()
        ->with('ImportUsersJob falhou', Mockery::subset(['company_id' => $companyId]));
});

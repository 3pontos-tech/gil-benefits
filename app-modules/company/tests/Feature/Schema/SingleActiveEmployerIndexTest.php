<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TresPontosTech\Company\Actions\EnsureSingleEmployerIndex;
use TresPontosTech\Company\Models\Company;

const SINGLE_EMPLOYER_MIGRATION = __DIR__ . '/../../../database/migrations/2026_10_30_000000_add_single_active_employer_index.php';

function singleEmployerIndexExists(): bool
{
    return collect(Schema::getIndexes('company_employees'))
        ->contains(fn (array $index): bool => $index['name'] === EnsureSingleEmployerIndex::INDEX);
}

it('creates the index only when the default bucket exists', function (): void {
    $migration = require SINGLE_EMPLOYER_MIGRATION;

    $migration->up();

    expect(singleEmployerIndexExists())->toBeFalse();

    Company::factory()->create(['slug' => Company::DEFAULT_SLUG]);
    $migration->up();
    expect(singleEmployerIndexExists())->toBeTrue();

    $migration->down();
    expect(singleEmployerIndexExists())->toBeFalse();
});

it('is enforced by the database for a second active employee row', function (): void {
    $bucket = Company::factory()->create(['slug' => Company::DEFAULT_SLUG]);
    $migration = require SINGLE_EMPLOYER_MIGRATION;
    $migration->up();

    $person = defaultCompanyUser();
    $first = Company::factory()->create();
    $second = Company::factory()->create();
    $first->employees()->attach($person->getKey());

    $insert = fn (): int => DB::table('company_employees')->insert([
        'id' => (string) Str::uuid7(),
        'company_id' => $second->getKey(),
        'user_id' => $person->getKey(),
        'role' => 'employee',
        'active' => true,
    ]);

    expect($insert)->toThrow(QueryException::class);

    $migration->down();
});

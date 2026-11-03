<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use TresPontosTech\Company\Actions\EnsureSingleEmployerIndex;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\artisan;

afterEach(fn () => resolve(EnsureSingleEmployerIndex::class)->drop());

function singleEmployerIndexIsThere(): bool
{
    return collect(Schema::getIndexes('company_employees'))
        ->contains(fn (array $index): bool => $index['name'] === EnsureSingleEmployerIndex::INDEX);
}

it('fails without the default company and creates the index once it exists', function (): void {
    artisan('company:single-employer-index')->assertFailed();
    expect(singleEmployerIndexIsThere())->toBeFalse();

    Company::factory()->create(['slug' => Company::DEFAULT_SLUG]);

    artisan('company:single-employer-index')->assertSuccessful();
    artisan('company:single-employer-index')->assertSuccessful();
    expect(singleEmployerIndexIsThere())->toBeTrue();
});
